<?php
/**
 * ============================================================
 * NEXUS — CONFIG (exemple)
 * Copiez ce fichier en config.php et adaptez les valeurs.
 * Ne committez JAMAIS config.php — il est dans .gitignore.
 * ============================================================
 */

// --- Environnement ---------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '0'); // '1' en dev pour le debug

// --- Base de données --------------------------------------------------------
define('DB_HOST',    'localhost');
define('DB_NAME',    'nexus');
define('DB_USER',    'root');
define('DB_PASS',    '');          // ← votre mot de passe MySQL
define('DB_CHARSET', 'utf8mb4');

// --- Application --------------------------------------------------------
define('APP_NAME', 'NEXUS');
define('APP_URL',  'http://localhost/nexus/public'); // ← adapter à votre domaine
define('APP_ENV',  'dev'); // 'dev' | 'prod'

// --- Sécurité --------------------------------------------------------
define('SESSION_NAME',         'nexus_session');
define('MAX_LOGIN_ATTEMPTS',   5);
define('LOGIN_LOCKOUT_MINUTES', 10);
define('CSRF_TOKEN_NAME',      'nexus_csrf');

// --- Email / Réinitialisation mot de passe --------------------------------
// En mode 'dev' : le lien de reset s'affiche directement à l'écran.
// En mode 'prod' : l'email est envoyé via mail() PHP natif.
define('MAIL_FROM',                  'no-reply@votre-domaine.com');
define('MAIL_FROM_NAME',             'NEXUS');
define('RESET_TOKEN_EXPIRY_MINUTES', 60);

// --- Connexion PDO (singleton) --------------------------------------------
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Erreur de connexion à la base de données. Vérifiez config/config.php.');
        }
    }
    return $pdo;
}
