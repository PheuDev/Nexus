<?php
/**
 * MODEL — ActivityLog
 * Journal d'activité utilisateur (créations, modifications, suppressions).
 */
class ActivityLog
{
    /** Enregistre une entrée de log. */
    public static function write(int $userId, string $action, string $entity, ?int $entityId, string $summary): void
    {
        $stmt = db()->prepare(
            'INSERT INTO activity_logs (user_id, action, entity, entity_id, summary) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $action, $entity, $entityId, mb_substr($summary, 0, 255)]);
    }

    /** Retourne les N dernières entrées de log pour un utilisateur. */
    public static function recent(int $userId, int $limit = 50): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int)$limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Supprime les logs plus vieux que N jours (nettoyage optionnel). */
    public static function purgeOlderThan(int $userId, int $days = 90): void
    {
        $stmt = db()->prepare(
            "DELETE FROM activity_logs WHERE user_id = ? AND created_at < CURRENT_TIMESTAMP - (? * INTERVAL '1 day')"
        );
        $stmt->execute([$userId, $days]);
    }
}
