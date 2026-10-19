<?php
/** NEXUS — CONFIG
 * Configuration centrale : connexion DB + constantes globales. */

// --- Environnement ---------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '0'); // passer à '1' en dev si besoin de debug

// --- Application --------------------------------------------------------
define('APP_NAME', 'NEXUS');
define('APP_URL', rtrim(getenv('APP_URL') ?: (getenv('RENDER_EXTERNAL_URL') ?: 'http://localhost:8000'), '/'));
define('APP_ENV', getenv('APP_ENV') ?: 'dev');
// --- Sécurité --------------------------------------------------------
define('SESSION_NAME', 'nexus_session');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 10);
define('CSRF_TOKEN_NAME', 'nexus_csrf');


define('MAIL_FROM',         getenv('MAIL_FROM') ?: 'no-reply@nexus.local');
define('MAIL_FROM_NAME',    'NEXUS');
define('RESET_TOKEN_EXPIRY_MINUTES', 60); // durée de validité du lien

// --- Connexion PDO PostgreSQL (singleton) ----------------------------------
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $url = getenv('DATABASE_URL');
            if ($url) {
                $parts = parse_url($url);
                if (!$parts || empty($parts['host']) || empty($parts['path'])) {
                    throw new RuntimeException('DATABASE_URL invalide.');
                }
                $dsn = 'pgsql:host=' . $parts['host']
                    . ';port=' . ($parts['port'] ?? 5432)
                    . ';dbname=' . ltrim($parts['path'], '/')
                    . ';sslmode=require';
                $user = rawurldecode($parts['user'] ?? '');
                $pass = rawurldecode($parts['pass'] ?? '');
            } else {
                $dsn = 'pgsql:host=' . (getenv('PGHOST') ?: '127.0.0.1')
                    . ';port=' . (getenv('PGPORT') ?: '5432')
                    . ';dbname=' . (getenv('PGDATABASE') ?: 'nexus')
                    . ';sslmode=' . (getenv('PGSSLMODE') ?: 'prefer');
                $user = getenv('PGUSER') ?: 'postgres';
                $pass = getenv('PGPASSWORD') ?: '';
            }
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('NEXUS database connection failed: ' . $e->getMessage());
            throw $e;
        }
    }
    return $pdo;
}
