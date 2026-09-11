<?php
/** CONTROLLER — Objectifs (épargne progressive) */

require_once __DIR__ . '/../models/Goal.php';

class GoalController
{
    public function index(): void
    {
        require_login();
        $goals = Goal::allForUser(current_user_id());
        render('goals', ['title' => 'Objectifs', 'goals' => $goals]);
    }

    public function store(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $title  = clean_str($_POST['title'] ?? '');
        $target = clean_float($_POST['target_amount'] ?? 0);
        $date   = clean_str($_POST['target_date'] ?? '') ?: null;

        if ($title === '' || $target <= 0) {
            json_response(['success' => false, 'error' => 'Titre et montant cible requis.'], 422);
        }
        $id = Goal::create($userId, $title, $target, $date);
        json_response(['success' => true, 'id' => $id]);
    }

    public function update(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id     = (int)($_POST['id'] ?? 0);
        $title  = clean_str($_POST['title'] ?? '');
        $target = clean_float($_POST['target_amount'] ?? 0);
        $date   = clean_str($_POST['target_date'] ?? '') ?: null;

        if (!$id || !Goal::find($id, $userId)) {
            json_response(['success' => false, 'error' => 'Objectif introuvable.'], 404);
        }
        if ($title === '' || $target <= 0) {
            json_response(['success' => false, 'error' => 'Titre et montant cible requis.'], 422);
        }
        Goal::update($id, $userId, $title, $target, $date);
        json_response(['success' => true]);
    }

    public function destroy(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id || !Goal::find($id, $userId)) {
            json_response(['success' => false, 'error' => 'Objectif introuvable.'], 404);
        }
        Goal::delete($id, $userId);
        json_response(['success' => true]);
    }

    public function deposit(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $goalId = (int)($_POST['goal_id'] ?? 0);
        $amount = clean_float($_POST['amount'] ?? 0);
        $date   = clean_str($_POST['deposited_on'] ?? date('Y-m-d'));

        if (!$goalId || $amount <= 0) {
            json_response(['success' => false, 'error' => 'Montant de dépôt invalide.'], 422);
        }
        $ok = Goal::addDeposit($goalId, $userId, $amount, $date);
        if (!$ok) {
            json_response(['success' => false, 'error' => 'Objectif introuvable.'], 404);
        }
        json_response(['success' => true, 'goal' => Goal::find($goalId, $userId)]);
    }
}
