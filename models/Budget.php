<?php
/** MODEL — Budget
 * Budgets mensuels par catégorie.*/
class Budget
{
    /** Tous les budgets du mois donné (défaut : mois courant). */
    public static function forMonth(int $userId, string $month = ''): array
    {
        if ($month === '') $month = date('Y-m');

        $stmt = db()->prepare("
            SELECT b.*, c.name AS category_name, c.color AS category_color, c.type AS category_type,
                   COALESCE((
                       SELECT SUM(t.amount) FROM transactions t
                       WHERE t.user_id = b.user_id
                         AND t.category_id = b.category_id
                         AND DATE_FORMAT(t.occurred_on, '%Y-%m') = b.month
                   ), 0) AS spent
            FROM budgets b
            JOIN categories c ON c.id = b.category_id
            WHERE b.user_id = ? AND b.month = ?
            ORDER BY c.type, c.name
        ");
        $stmt->execute([$userId, $month]);
        return $stmt->fetchAll();
    }

    public static function find(int $userId, int $categoryId, string $month): ?array
    {
        $stmt = db()->prepare('SELECT * FROM budgets WHERE user_id = ? AND category_id = ? AND month = ?');
        $stmt->execute([$userId, $categoryId, $month]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function upsert(int $userId, int $categoryId, string $month, float $amount): void
    {
        $stmt = db()->prepare("
            INSERT INTO budgets (user_id, category_id, month, amount)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount)
        ");
        $stmt->execute([$userId, $categoryId, $month, $amount]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $stmt = db()->prepare('DELETE FROM budgets WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    /** Retourne le total dépensé par catégorie pour un mois donné, comparé au budget défini. */
    public static function summaryForMonth(int $userId, string $month = ''): array
    {
        if ($month === '') $month = date('Y-m');
        $rows = self::forMonth($userId, $month);
        $result = [];
        foreach ($rows as $r) {
            $pct = $r['amount'] > 0 ? min(200, round(($r['spent'] / $r['amount']) * 100)) : 0;
            $result[] = [
                'category_id'    => $r['category_id'],
                'category_name'  => $r['category_name'],
                'category_color' => $r['category_color'],
                'category_type'  => $r['category_type'],
                'budget'         => (float)$r['amount'],
                'spent'          => (float)$r['spent'],
                'pct'            => $pct,
                'over'           => $r['spent'] > $r['amount'],
            ];
        }
        return $result;
    }
}
