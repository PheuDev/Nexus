<?php
/** MODEL — Task
 * Tâches / planning quotidien.*/

class Task
{
    public static function allForUser(int $userId, array $filters = []): array
    {
        $sql = 'SELECT * FROM tasks WHERE user_id = ?';
        $params = [$userId];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $sql .= ' AND priority = ?';
            $params[] = $filters['priority'];
        }

        $sql .= ' ORDER BY status ASC, due_date IS NULL, due_date ASC, FIELD(priority,"high","medium","low")';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function todayForUser(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM tasks WHERE user_id = ? AND (due_date = CURDATE() OR due_date < CURDATE()) AND status = "pending"
                                ORDER BY due_date ASC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Nombre total de tâches en attente (toutes dates confondues). */
    public static function countPending(int $userId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = "pending"');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function find(int $id, int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $title, ?string $dueDate, string $priority): int
    {
        $stmt = db()->prepare('INSERT INTO tasks (user_id, title, due_date, priority) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $title, $dueDate ?: null, $priority]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, int $userId, string $title, ?string $dueDate, string $priority): bool
    {
        $stmt = db()->prepare('UPDATE tasks SET title = ?, due_date = ?, priority = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([$title, $dueDate ?: null, $priority, $id, $userId]);
    }

    public static function toggleStatus(int $id, int $userId): bool
    {
        $task = self::find($id, $userId);
        if (!$task) {
            return false;
        }
        $newStatus = $task['status'] === 'pending' ? 'done' : 'pending';
        $stmt = db()->prepare('UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([$newStatus, $id, $userId]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $stmt = db()->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }
}
