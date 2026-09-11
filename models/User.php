<?php
/**
 * MODEL — User
 * Accès aux données utilisateur : création, authentification,
 * mise à jour des préférences (thème, devise, notifications).
 */

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);
        return (int)db()->lastInsertId();
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    public static function updatePreferences(int $userId, string $currency, string $theme, bool $notifications, string $locale = 'fr'): void
    {
        $stmt = db()->prepare('UPDATE users SET currency = ?, theme = ?, notifications_enabled = ?, locale = ? WHERE id = ?');
        $stmt->execute([$currency, $theme, $notifications ? 1 : 0, $locale, $userId]);
    }

    /** Suppression définitive et complète des données de l'utilisateur.Les FOREIGN KEY ... ON DELETE CASCADE nettoient le reste. */
    public static function deleteAccount(int $userId): void
    {
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
    }
}
