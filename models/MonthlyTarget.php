<?php
/** Objectif mensuel d'épargne propre à chaque utilisateur. */
class MonthlyTarget
{
    public static function forMonth(int $userId, string $month): float
    {
        $stmt = db()->prepare('SELECT target_amount FROM monthly_targets WHERE user_id = ? AND month = ?');
        $stmt->execute([$userId, $month]);
        return (float)($stmt->fetchColumn() ?: 0);
    }

    public static function save(int $userId, string $month, float $amount): void
    {
        $stmt = db()->prepare('INSERT INTO monthly_targets (user_id, month, target_amount) VALUES (?, ?, ?) ON CONFLICT (user_id, month) DO UPDATE SET target_amount = EXCLUDED.target_amount');
        $stmt->execute([$userId, $month, $amount]);
    }
}
