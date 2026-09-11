<?php
/**
 * MODEL — Transaction & Category
 * Regroupe les deux entités "Finances" (fortement liées) dans un seul fichier, conformément à la philosophie NEXUS. */

class Category
{
    public static function allForUser(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM categories WHERE user_id = ? ORDER BY type, name');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id, int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM categories WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $name, string $type, string $color): int
    {
        $stmt = db()->prepare('INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $name, $type, $color]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, int $userId, string $name, string $type, string $color): bool
    {
        $stmt = db()->prepare('UPDATE categories SET name = ?, type = ?, color = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([$name, $type, $color, $id, $userId]);
    }

    public static function delete(int $id, int $userId): bool
    {
        // Les transactions liées passent category_id à NULL (ON DELETE SET NULL en DB)
        $stmt = db()->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    public static function belongsToUser(int $categoryId, int $userId): bool
    {
        $stmt = db()->prepare('SELECT id FROM categories WHERE id = ? AND user_id = ?');
        $stmt->execute([$categoryId, $userId]);
        return (bool)$stmt->fetch();
    }
}

class Transaction
{
    /** Nombre total de transactions (pour la pagination). */
    public static function countForUser(int $userId, array $filters = []): int
    {
        $sql    = 'SELECT COUNT(*) FROM transactions t WHERE t.user_id = ?';
        $params = [$userId];

        if (!empty($filters['type'])) {
            $sql .= ' AND t.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= ' AND t.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND t.label LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND t.occurred_on >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND t.occurred_on <= ?';
            $params[] = $filters['to'];
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function allForUser(int $userId, array $filters = []): array
    {
        $sql = 'SELECT t.*, c.name AS category_name, c.color AS category_color
                FROM transactions t
                LEFT JOIN categories c ON c.id = t.category_id
                WHERE t.user_id = ?';
        $params = [$userId];

        if (!empty($filters['type'])) {
            $sql .= ' AND t.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= ' AND t.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND t.label LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND t.occurred_on >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND t.occurred_on <= ?';
            $params[] = $filters['to'];
        }

        $sql .= ' ORDER BY t.occurred_on DESC, t.id DESC';
        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= ' OFFSET ' . (int)$filters['offset'];
            }
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id, int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $stmt = db()->prepare('INSERT INTO transactions (user_id, category_id, type, amount, label, occurred_on, recurrence, recurrence_end, note)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $data['category_id'] ?: null,
            $data['type'],
            $data['amount'],
            $data['label'],
            $data['occurred_on'],
            $data['recurrence']     ?? 'none',
            $data['recurrence_end'] ?: null,
            $data['note']           ?? null,
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $stmt = db()->prepare('UPDATE transactions SET category_id = ?, type = ?, amount = ?, label = ?, occurred_on = ?, recurrence = ?, recurrence_end = ?, note = ?
                                WHERE id = ? AND user_id = ?');
        return $stmt->execute([
            $data['category_id'] ?: null,
            $data['type'],
            $data['amount'],
            $data['label'],
            $data['occurred_on'],
            $data['recurrence']     ?? 'none',
            $data['recurrence_end'] ?: null,
            $data['note']           ?? null,
            $id,
            $userId,
        ]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $stmt = db()->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    // ---- Agrégats pour dashboard / analyse 
    public static function totalsForPeriod(int $userId, string $from, string $to): array
    {
        $stmt = db()->prepare("SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions
                                WHERE user_id = ? AND occurred_on BETWEEN ? AND ? GROUP BY type");
        $stmt->execute([$userId, $from, $to]);
        $totals = ['income' => 0.0, 'expense' => 0.0];
        foreach ($stmt->fetchAll() as $row) {
            $totals[$row['type']] = (float)$row['total'];
        }
        return $totals;
    }

    public static function monthlySeries(int $userId, int $months = 6): array
    {
        $stmt = db()->prepare("SELECT DATE_FORMAT(occurred_on, '%Y-%m') AS ym, type, SUM(amount) AS total
                                FROM transactions
                                WHERE user_id = ? AND occurred_on >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                                GROUP BY ym, type ORDER BY ym ASC");
        $stmt->execute([$userId, $months]);
        return $stmt->fetchAll();
    }

    public static function byCategory(int $userId, string $type, string $from, string $to): array
    {
        $stmt = db()->prepare("SELECT c.name, c.color, SUM(t.amount) AS total
                                FROM transactions t
                                JOIN categories c ON c.id = t.category_id
                                WHERE t.user_id = ? AND t.type = ? AND t.occurred_on BETWEEN ? AND ?
                                GROUP BY c.id ORDER BY total DESC");
        $stmt->execute([$userId, $type, $from, $to]);
        return $stmt->fetchAll();
    }

    /**
     * Prévisions du mois prochain basées sur la moyenne des N derniers mois.
     * Retourne ['income' => float, 'expense' => float]
     */
    public static function forecast(int $userId, int $months = 3): array
    {
        $stmt = db()->prepare("SELECT type, COALESCE(AVG(monthly_total), 0) AS avg_total
            FROM (
                SELECT DATE_FORMAT(occurred_on, '%Y-%m') AS ym, type, SUM(amount) AS monthly_total
                FROM transactions
                WHERE user_id = ? AND occurred_on >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL ? MONTH)
                  AND occurred_on < DATE_FORMAT(CURDATE(), '%Y-%m-01')
                GROUP BY ym, type
            ) sub
            GROUP BY type");
        $stmt->execute([$userId, $months]);
        $result = ['income' => 0.0, 'expense' => 0.0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['type']] = round((float)$row['avg_total'], 2);
        }
        return $result;
    }

    /** Génère les transactions récurrentes dues depuis la dernière exécution. Elle est appelée au login de l'utilisateur. */
    public static function generateRecurring(int $userId): int
    {
        $today = date('Y-m-d');
        $count = 0;

        // Toutes les transactions récurrentes actives
        $stmt = db()->prepare("SELECT * FROM transactions
            WHERE user_id = ? AND recurrence != 'none'
            AND (recurrence_end IS NULL OR recurrence_end >= ?)
            ORDER BY occurred_on ASC");
        $stmt->execute([$userId, $today]);
        $recurring = $stmt->fetchAll();

        foreach ($recurring as $tx) {
            $nextDate = self::nextRecurrenceDate($tx['occurred_on'], $tx['recurrence']);

            // Génère toutes les occurrences manquées jusqu'à aujourd'hui
            while ($nextDate <= $today) {
                if (!empty($tx['recurrence_end']) && $nextDate > $tx['recurrence_end']) break;

                // Vérifie qu'on n'a pas déjà créé cette occurrence
                $check = db()->prepare("SELECT id FROM transactions
                    WHERE user_id = ? AND label = ? AND amount = ? AND occurred_on = ? AND recurrence = 'none'");
                $check->execute([$userId, $tx['label'], $tx['amount'], $nextDate]);

                if (!$check->fetch()) {
                    $ins = db()->prepare("INSERT INTO transactions (user_id, category_id, type, amount, label, occurred_on, recurrence, note)
                        VALUES (?, ?, ?, ?, ?, ?, 'none', ?)");
                    $ins->execute([
                        $userId, $tx['category_id'], $tx['type'],
                        $tx['amount'], $tx['label'], $nextDate, $tx['note'],
                    ]);
                    $count++;
                }

                // Avance la date source pour la prochaine itération
                $nextDate = self::nextRecurrenceDate($nextDate, $tx['recurrence']);
            }

            // Met à jour la date de base pour éviter de re-générer
            if ($nextDate !== self::nextRecurrenceDate($tx['occurred_on'], $tx['recurrence'])) {
                db()->prepare("UPDATE transactions SET occurred_on = ? WHERE id = ?")
                   ->execute([date('Y-m-d', strtotime($nextDate . ' -1 ' . self::recurrenceInterval($tx['recurrence']))), $tx['id']]);
            }
        }

        return $count;
    }

    private static function nextRecurrenceDate(string $from, string $recurrence): string
    {
        return match($recurrence) {
            'daily'   => date('Y-m-d', strtotime($from . ' +1 day')),
            'weekly'  => date('Y-m-d', strtotime($from . ' +1 week')),
            'monthly' => date('Y-m-d', strtotime($from . ' +1 month')),
            'yearly'  => date('Y-m-d', strtotime($from . ' +1 year')),
            default   => $from,
        };
    }

    private static function recurrenceInterval(string $recurrence): string
    {
        return match($recurrence) {
            'daily'   => 'day',
            'weekly'  => 'week',
            'monthly' => 'month',
            'yearly'  => 'year',
            default   => 'month',
        };
    }
}
