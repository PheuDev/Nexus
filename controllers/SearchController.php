<?php
/** CONTROLLER — Search
 * Recherche globale multi-tables (transactions, objectifs, tâches).
 */
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../models/Task.php';

class SearchController
{
    public function search(): void
    {
        require_login();
        $userId = current_user_id();
        $q      = clean_str($_GET['q'] ?? '');

        if (strlen($q) < 2) {
            json_response(['success' => true, 'results' => []]);
        }

        $results = [];

        // Transactions
        $stmt = db()->prepare("SELECT id, label AS title, type, amount, occurred_on AS date
            FROM transactions
            WHERE user_id = ? AND label LIKE ?
            ORDER BY occurred_on DESC LIMIT 5");
        $stmt->execute([$userId, '%' . $q . '%']);
        foreach ($stmt->fetchAll() as $r) {
            $results[] = [
                'type'  => 'transaction',
                'id'    => $r['id'],
                'title' => $r['title'],
                'sub'   => $r['type'] === 'income' ? '+' . $r['amount'] : '-' . $r['amount'],
                'date'  => $r['date'],
                'url'   => 'index.php?page=finances',
                'icon'  => 'wallet',
            ];
        }

        // Objectifs
        $stmt = db()->prepare("SELECT id, title, current_amount, target_amount
            FROM goals WHERE user_id = ? AND title LIKE ? LIMIT 5");
        $stmt->execute([$userId, '%' . $q . '%']);
        foreach ($stmt->fetchAll() as $r) {
            $pct = $r['target_amount'] > 0 ? round(($r['current_amount'] / $r['target_amount']) * 100) : 0;
            $results[] = [
                'type'  => 'goal',
                'id'    => $r['id'],
                'title' => $r['title'],
                'sub'   => $pct . '% atteint',
                'date'  => '',
                'url'   => 'index.php?page=goals',
                'icon'  => 'target',
            ];
        }

        // Tâches
        $stmt = db()->prepare("SELECT id, title, priority, status, due_date
            FROM tasks WHERE user_id = ? AND title LIKE ? LIMIT 5");
        $stmt->execute([$userId, '%' . $q . '%']);
        foreach ($stmt->fetchAll() as $r) {
            $results[] = [
                'type'  => 'task',
                'id'    => $r['id'],
                'title' => $r['title'],
                'sub'   => $r['status'] === 'done' ? 'Terminée' : $r['priority'],
                'date'  => $r['due_date'] ?? '',
                'url'   => 'index.php?page=planning',
                'icon'  => 'calendar',
            ];
        }

        json_response(['success' => true, 'results' => $results, 'query' => $q]);
    }
}
