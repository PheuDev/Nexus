<?php
/**
 * NEXUS — CORE
 * Regroupe les fonctions transverses (session, auth, sécurité,
 * réponses JSON/redirections) pour éviter de fragmenter la
 * logique commune dans Service/Helper/Middleware séparés.
 *
 * SOMMAIRE
 *  1. Session
 *  2. CSRF
 *  3. Authentification
 *  4. Sanitisation / validation
 *  5. Réponses (JSON, redirect, vues)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/i18n.php';

// 1. SESSION
function nexus_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => str_starts_with(APP_URL, 'https://'),
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
nexus_start_session();
// Initialise la locale après démarrage de session (priorité : session > navigateur > fr)
nexus_init_locale();

// 2. CSRF
function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($sent) && !empty($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $sent);
}

function csrf_require(): void
{
    if (!csrf_verify()) {
        json_response(['success' => false, 'error' => 'Jeton CSRF invalide.'], 419);
    }
}

// 3. AUTHENTIFICATION
function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'error' => 'Non authentifié.'], 401);
        }
        redirect('index.php?page=login');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_currency'] = $user['currency'] ?? 'EUR';
    $_SESSION['user_theme']    = $user['theme']    ?? 'dark';
    // Locale : priorité à la préférence DB, sinon on garde celle détectée par le navigateur
    if (!empty($user['locale']) && in_array($user['locale'], NEXUS_LOCALES, true)) {
        $_SESSION['locale'] = $user['locale'];
        nexus_set_locale($user['locale']);
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path']);
    }
    session_destroy();
}

/** Limitation des tentatives de connexion (brute-force basique en DB). */
function login_attempts_check(string $email): bool
{
    $stmt = db()->prepare("SELECT COUNT(*) AS n FROM login_attempts WHERE email = ? AND attempted_at > CURRENT_TIMESTAMP - (? * INTERVAL '1 minute')");
    $stmt->execute([$email, LOGIN_LOCKOUT_MINUTES]);
    $row = $stmt->fetch();
    return (int)$row['n'] < MAX_LOGIN_ATTEMPTS;
}

function login_attempts_register(string $email): void
{
    $stmt = db()->prepare('INSERT INTO login_attempts (email, attempted_at) VALUES (?, CURRENT_TIMESTAMP)');
    $stmt->execute([$email]);
}

function login_attempts_reset(string $email): void
{
    $stmt = db()->prepare('DELETE FROM login_attempts WHERE email = ?');
    $stmt->execute([$email]);
}

// 4. SANITISATION / VALIDATION
function clean_str(?string $v): string
{
    return trim(htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'));
}

function clean_float($v): float
{
    return (float)str_replace(',', '.', (string)$v);
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Symbole affiché pour une devise donnée (suffixe, ex: "1 000 F CFA"). */
function currency_symbol(string $code): string
{
    $map = [
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        'XOF' => 'F CFA',
    ];
    return $map[$code] ?? $code;
}

// 5. RÉPONSES
function is_ajax_request(): bool
{
    return (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
    );
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Retourne le nombre de tâches en attente de l'utilisateur connecté.
 * Utilisé dans le layout pour afficher le badge sur "Planning".
 * Retourne 0 si non connecté ou si le modèle n'est pas chargé.
 */
function pending_tasks_count(): int
{
    if (!is_logged_in()) {
        return 0;
    }
    // Le modèle Task n'est pas toujours chargé : on interroge la DB directement
    $stmt = db()->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([current_user_id()]);
    return (int)$stmt->fetchColumn();
}

/**
 * Rend une vue dans le layout principal (ou seule, si layout = false).
 * $view : chemin relatif à /views (ex: 'auth/login', 'dashboard')
 */
function render(string $view, array $data = [], bool $withLayout = true): void
{
    $viewFile = __DIR__ . '/../views/' . $view . '.php';
    if (!file_exists($viewFile)) {
        http_response_code(404);
        echo 'Vue introuvable : ' . htmlspecialchars($view);
        return;
    }
    if (!$withLayout) {
        extract($data, EXTR_SKIP);
        require $viewFile;
        return;
    }
    // Le contenu est une closure qui ré-extrait $data dans son propre scope :
    // nécessaire pour que la vue (require'd depuis le closure) voie les variables.
    $content = function () use ($viewFile, $data) {
        extract($data, EXTR_SKIP);
        require $viewFile;
    };
    extract($data, EXTR_SKIP); // pour que layout.php ait aussi accès à $title, etc.
    require __DIR__ . '/../views/layout.php';
}
