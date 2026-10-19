<?php
/** MODEL — Goal
 * Objectifs financiers + historique des dépôts progressifs. */

class Goal
{
    public static function allForUser(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM goals WHERE user_id = ? ORDER BY status ASC, target_date IS NULL, target_date ASC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id, int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM goals WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $title, float $target, ?string $targetDate): int
    {
        $stmt = db()->prepare('INSERT INTO goals (user_id, title, target_amount, current_amount, target_date)
                                VALUES (?, ?, ?, 0, ?) RETURNING id');
        $stmt->execute([$userId, $title, $target, $targetDate ?: null]);
        return (int)$stmt->fetchColumn();
    }

    public static function update(int $id, int $userId, string $title, float $target, ?string $targetDate): bool
    {
        $stmt = db()->prepare('UPDATE goals SET title = ?, target_amount = ?, target_date = ? WHERE id = ? AND user_id = ?');
        $ok = $stmt->execute([$title, $target, $targetDate ?: null, $id, $userId]);
        self::refreshStatus($id, $userId);
        return $ok;
    }

    public static function delete(int $id, int $userId): bool
    {
        $stmt = db()->prepare('DELETE FROM goals WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    public static function addDeposit(int $goalId, int $userId, float $amount, string $date): bool
    {
        $goal = self::find($goalId, $userId);
        if (!$goal) {
            return false;
        }
        $stmt = db()->prepare('INSERT INTO goal_deposits (goal_id, amount, deposited_on) VALUES (?, ?, ?)');
        $stmt->execute([$goalId, $amount, $date]);

        $stmt = db()->prepare('UPDATE goals SET current_amount = current_amount + ? WHERE id = ?');
        $stmt->execute([$amount, $goalId]);

        self::refreshStatus($goalId, $userId);
        return true;
    }

    public static function deposits(int $goalId, int $userId): array
    {
        if (!self::find($goalId, $userId)) {
            return [];
        }
        $stmt = db()->prepare('SELECT * FROM goal_deposits WHERE goal_id = ? ORDER BY deposited_on DESC');
        $stmt->execute([$goalId]);
        return $stmt->fetchAll();
    }

    /** Passe automatiquement le statut à "achieved" si la cible est atteinte. */
    private static function refreshStatus(int $goalId, int $userId): void
    {
        $goal = self::find($goalId, $userId);
        if (!$goal) {
            return;
        }
        $status = ((float)$goal['current_amount'] >= (float)$goal['target_amount']) ? 'achieved' : 'active';
        $stmt = db()->prepare('UPDATE goals SET status = ? WHERE id = ?');
        $stmt->execute([$status, $goalId]);
    }
}
