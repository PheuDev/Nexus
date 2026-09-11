<?php
/**
 * CONTROLLER — Budget
 * Gestion des budgets mensuels par catégorie.
 */
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class BudgetController
{
    public function index(): void
    {
        require_login();
        $userId = current_user_id();
        $month  = clean_str($_GET['month'] ?? date('Y-m'));

        // Valide le format YYYY-MM
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

        $budgets    = Budget::summaryForMonth($userId, $month);
        $categories = Category::allForUser($userId);

        render('budgets', [
            'title'      => 'Budgets',
            'budgets'    => $budgets,
            'categories' => $categories,
            'month'      => $month,
        ]);
    }

    public function upsert(): void
    {
        require_login();
        csrf_require();
        $userId     = current_user_id();
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $month      = clean_str($_POST['month'] ?? date('Y-m'));
        $amount     = clean_float($_POST['amount'] ?? 0);

        if (!$categoryId || $amount <= 0 || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            json_response(['success' => false, 'error' => 'Données invalides.'], 422);
        }
        if (!Category::find($categoryId, $userId)) {
            json_response(['success' => false, 'error' => 'Catégorie introuvable.'], 404);
        }

        Budget::upsert($userId, $categoryId, $month, $amount);
        ActivityLog::write($userId, 'create', 'budget', null, "Budget {$month} défini pour catégorie #{$categoryId} : {$amount}");
        json_response(['success' => true]);
    }

    public function destroy(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id     = (int)($_POST['id'] ?? 0);

        if (!$id) {
            json_response(['success' => false, 'error' => 'ID manquant.'], 422);
        }

        Budget::delete($id, $userId);
        ActivityLog::write($userId, 'delete', 'budget', $id, "Budget #{$id} supprimé");
        json_response(['success' => true]);
    }

    /** AJAX — résumé budgets du mois pour affichage inline. */
    public function summary(): void
    {
        require_login();
        $userId = current_user_id();
        $month  = clean_str($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

        json_response([
            'success' => true,
            'budgets' => Budget::summaryForMonth($userId, $month),
        ]);
    }
}
