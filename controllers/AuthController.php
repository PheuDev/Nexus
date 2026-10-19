<?php
/**
 * CONTROLLER — Auth
 * Connexion / inscription / déconnexion / reset mot de passe.
*/

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class AuthController
{
    public function showLogin(): void
    {
        if (is_logged_in()) redirect('index.php?page=dashboard');
        render('auth/login', ['title' => 'Connexion'], false);
    }

    public function showRegister(): void
    {
        if (is_logged_in()) redirect('index.php?page=dashboard');
        render('auth/register', ['title' => 'Créer un compte'], false);
    }

    public function login(): void
    {
        csrf_require();
        $email    = strtolower(clean_str($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (!is_valid_email($email) || $password === '') {
            json_response(['success' => false, 'error' => 'Identifiants invalides.'], 422);
        }
        if (!login_attempts_check($email)) {
            json_response(['success' => false, 'error' => 'Trop de tentatives. Réessayez dans ' . LOGIN_LOCKOUT_MINUTES . ' minutes.'], 429);
        }

        $user = User::findByEmail($email);
        if (!$user || !User::verifyPassword($user, $password)) {
            login_attempts_register($email);
            json_response(['success' => false, 'error' => 'Email ou mot de passe incorrect.'], 401);
        }

        login_attempts_reset($email);
        login_user($user);
        ActivityLog::write((int)$user['id'], 'login', 'user', (int)$user['id'], 'Connexion réussie');
        // Génère les transactions récurrentes dues depuis la dernière connexion
        require_once __DIR__ . '/../models/Transaction.php';
        Transaction::generateRecurring((int)$user['id']);
        json_response(['success' => true, 'redirect' => 'index.php?page=dashboard']);
    }

    public function register(): void
    {
        csrf_require();
        $name     = clean_str($_POST['name'] ?? '');
        $email    = strtolower(clean_str($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (strlen($name) < 2 || !is_valid_email($email) || strlen($password) < 8) {
            json_response(['success' => false, 'error' => 'Merci de vérifier les champs (mot de passe : 8 caractères min.).'], 422);
        }
        if (User::findByEmail($email)) {
            json_response(['success' => false, 'error' => 'Cet email est déjà utilisé.'], 409);
        }

        $id   = User::create($name, $email, $password);
        $user = User::findById($id);
        login_user($user);
        json_response(['success' => true, 'redirect' => 'index.php?page=dashboard']);
    }

    public function logout(): void
    {
        csrf_require();
        logout_user();
        redirect('index.php?page=login');
    }

    // MOT DE PASSE OUBLIÉ — Affichage du formulaire
    public function showForgot(): void
    {
        if (is_logged_in()) redirect('index.php?page=dashboard');
        render('auth/forgot', ['title' => 'Mot de passe oublié'], false);
    }

    // MOT DE PASSE OUBLIÉ — Traitement de la demande
    public function sendReset(): void
    {
        csrf_require();
        $email = strtolower(clean_str($_POST['email'] ?? ''));

        if (!is_valid_email($email)) {
            json_response(['success' => false, 'error' => 'Adresse email invalide.'], 422);
        }

        // On ne révèle pas si l'email existe (sécurité anti-énumération)
        $user = User::findByEmail($email);

        if ($user) {
            // Nettoie les anciens tokens non utilisés pour cet email
            $this->cleanOldTokens($email);

            // Génère un token sécurisé
            $rawToken  = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', time() + RESET_TOKEN_EXPIRY_MINUTES * 60);

            $stmt = db()->prepare(
                'INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)'
            );
            $stmt->execute([$email, $tokenHash, $expiresAt]);

            $resetUrl = APP_URL . '/index.php?page=reset&token=' . $rawToken;

            if (APP_ENV === 'dev') {
                // En développement : on renvoie le lien directement pour tester
                json_response([
                    'success'   => true,
                    'dev_link'  => $resetUrl,
                    'dev_note'  => 'Mode dev : aucun email envoyé. Utilisez ce lien pour réinitialiser.',
                ]);
            } else {
                $this->sendResetEmail($email, $user['name'], $resetUrl);
                json_response(['success' => true]);
            }
        } else {
            // Réponse identique que l'email existe ou non
            if (APP_ENV === 'dev') {
                json_response(['success' => true, 'dev_note' => 'Email non trouvé en base (mode dev).']);
            } else {
                json_response(['success' => true]);
            }
        }
    }

    // RÉINITIALISATION — Affichage du formulaire
    public function showReset(): void
    {
        if (is_logged_in()) redirect('index.php?page=dashboard');
        $token = clean_str($_GET['token'] ?? '');
        if ($token === '' || !$this->findValidToken($token)) {
            render('auth/reset', ['title' => 'Lien invalide', 'token' => '', 'invalid' => true], false);
            return;
        }
        render('auth/reset', ['title' => 'Nouveau mot de passe', 'token' => $token, 'invalid' => false], false);
    }

    // RÉINITIALISATION — Traitement du nouveau mot de passe
    public function processReset(): void
    {
        csrf_require();
        $token    = clean_str($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (strlen($password) < 8) {
            json_response(['success' => false, 'error' => 'Le mot de passe doit faire au moins 8 caractères.'], 422);
        }

        $row = $this->findValidToken($token);
        if (!$row) {
            json_response(['success' => false, 'error' => 'Lien invalide ou expiré.'], 422);
        }

        // Met à jour le mot de passe
        $user = User::findByEmail($row['email']);
        if (!$user) {
            json_response(['success' => false, 'error' => 'Compte introuvable.'], 404);
        }

        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);

        // Invalide le token
        $stmt = db()->prepare('UPDATE password_resets SET used = 1 WHERE token_hash = ?');
        $stmt->execute([hash('sha256', $token)]);

        json_response(['success' => true, 'redirect' => 'index.php?page=login']);
    }

    // Helpers privés
    /** Cherche un token valide (non expiré, non utilisé). */
    private function findValidToken(string $rawToken): ?array
    {
        $tokenHash = hash('sha256', $rawToken);
        $stmt = db()->prepare(
            'SELECT * FROM password_resets
             WHERE token_hash = ? AND used = 0 AND expires_at > CURRENT_TIMESTAMP
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Supprime les anciens tokens expirés ou déjà utilisés pour un email. */
    private function cleanOldTokens(string $email): void
    {
        $stmt = db()->prepare('DELETE FROM password_resets WHERE email = ?');
        $stmt->execute([$email]);
    }

    /** Envoie l'email de reset via mail() PHP natif. */
    private function sendResetEmail(string $to, string $name, string $url): void
    {
        $fromName  = MAIL_FROM_NAME;
        $fromEmail = MAIL_FROM;
        $subject   = '=?UTF-8?B?' . base64_encode('Réinitialisation de votre mot de passe NEXUS') . '?=';

        $body = "Bonjour {$name},\r\n\r\n"
              . "Vous avez demandé la réinitialisation de votre mot de passe NEXUS.\r\n\r\n"
              . "Cliquez sur ce lien (valable " . RESET_TOKEN_EXPIRY_MINUTES . " minutes) :\r\n"
              . $url . "\r\n\r\n"
              . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\r\n\r\n"
              . "— L'équipe NEXUS";

        $headers = "From: {$fromName} <{$fromEmail}>\r\n"
                 . "Reply-To: {$fromEmail}\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n";

        mail($to, $subject, base64_encode($body), $headers);
    }
}
