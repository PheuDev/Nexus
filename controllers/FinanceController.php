<?php
/**
 * CONTROLLER — Finances
 * Gestion des revenus/dépenses : liste paginée, filtres,
 * CRUD transactions, CRUD catégories complet.
 */

require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class FinanceController
{
    // Nombre de transactions par page
    private const PER_PAGE = 20;

    public function index(): void
    {
        require_login();
        $userId = current_user_id();

        $filters = [
            'type'        => clean_str($_GET['type'] ?? ''),
            'category_id' => (int)($_GET['category_id'] ?? 0),
            'search'      => clean_str($_GET['q'] ?? ''),
            'from'        => clean_str($_GET['from'] ?? ''),
            'to'          => clean_str($_GET['to'] ?? ''),
        ];

        // Dates par défaut : 1er du mois → aujourd'hui si non spécifiées
        if ($filters['from'] === '') $filters['from'] = date('Y-m-01');
        if ($filters['to']   === '') $filters['to']   = date('Y-m-d');
        $page     = max(1, (int)($_GET['p'] ?? 1));
        $total    = Transaction::countForUser($userId, $filters);
        $pages    = max(1, (int)ceil($total / self::PER_PAGE));
        $page     = min($page, $pages);
        $offset   = ($page - 1) * self::PER_PAGE;

        $paginatedFilters = array_merge($filters, [
            'limit'  => self::PER_PAGE,
            'offset' => $offset,
        ]);

        $transactions = Transaction::allForUser($userId, $paginatedFilters);
        $categories   = Category::allForUser($userId);

        render('finances', [
            'title'        => 'Finances',
            'transactions' => $transactions,
            'categories'   => $categories,
            'filters'      => $filters,
            'pagination'   => [
                'page'    => $page,
                'pages'   => $pages,
                'total'   => $total,
                'perPage' => self::PER_PAGE,
            ],
        ]);
    }

    public function list(): void
    {
        require_login();
        $userId  = current_user_id();
        $filters = [
            'type'        => clean_str($_GET['type'] ?? ''),
            'category_id' => (int)($_GET['category_id'] ?? 0),
            'search'      => clean_str($_GET['q'] ?? ''),
            'from'        => clean_str($_GET['from'] ?? ''),
            'to'          => clean_str($_GET['to'] ?? ''),
        ];
        json_response(['success' => true, 'data' => Transaction::allForUser($userId, $filters)]);
    }

    public function store(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $data   = $this->extractPayload();

        if (!$this->validate($data)) {
            json_response(['success' => false, 'error' => 'Champs invalides.'], 422);
        }

        $id = Transaction::create($userId, $data);
        ActivityLog::write($userId, 'create', 'transaction', $id, "Ajout : {$data['label']} ({$data['type']}) {$data['amount']}");
        json_response(['success' => true, 'id' => $id]);
    }

    public function update(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id     = (int)($_POST['id'] ?? json_body()['id'] ?? 0);
        $data   = $this->extractPayload();

        if (!$id || !Transaction::find($id, $userId)) {
            json_response(['success' => false, 'error' => 'Transaction introuvable.'], 404);
        }
        if (!$this->validate($data)) {
            json_response(['success' => false, 'error' => 'Champs invalides.'], 422);
        }

        Transaction::update($id, $userId, $data);
        ActivityLog::write($userId, 'update', 'transaction', $id, "Modif : {$data['label']}");
        json_response(['success' => true]);
    }

    public function destroy(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id     = (int)($_POST['id'] ?? json_body()['id'] ?? 0);

        if (!$id || !Transaction::find($id, $userId)) {
            json_response(['success' => false, 'error' => 'Transaction introuvable.'], 404);
        }
        Transaction::delete($id, $userId);
        ActivityLog::write($userId, 'delete', 'transaction', $id, "Suppression transaction #{$id}");
        json_response(['success' => true]);
    }

    // ----------------------------------------------------------
    // EXPORT CSV
    // ----------------------------------------------------------

    public function exportCsv(): void
    {
        require_login();
        $userId = current_user_id();

        $filters = [
            'type'        => clean_str($_GET['type'] ?? ''),
            'category_id' => (int)($_GET['category_id'] ?? 0),
            'search'      => clean_str($_GET['q'] ?? ''),
            'from'        => clean_str($_GET['from'] ?? date('Y-m-01')),
            'to'          => clean_str($_GET['to']   ?? date('Y-m-d')),
        ];

        $rows = Transaction::allForUser($userId, $filters);

        $cur = currency_symbol($_SESSION['user_currency'] ?? 'EUR');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="nexus-transactions-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        // BOM UTF-8 pour Excel
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Date', 'Libellé', 'Type', 'Catégorie', 'Montant (' . $cur . ')', 'Récurrence', 'Note'], ';');

        foreach ($rows as $t) {
            fputcsv($out, [
                $t['occurred_on'],
                $t['label'],
                $t['type'] === 'income' ? 'Revenu' : 'Dépense',
                $t['category_name'] ?? '',
                number_format($t['amount'], 2, ',', ' '),
                $t['recurrence'] ?? 'none',
                $t['note'] ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }

    // CATÉGORIES — CRUD complet

    public function storeCategory(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $name   = clean_str($_POST['name'] ?? '');
        $type   = in_array($_POST['type'] ?? '', ['income', 'expense'], true) ? $_POST['type'] : '';
        $color  = clean_str($_POST['color'] ?? '#00d9ff');

        if ($name === '' || $type === '') {
            json_response(['success' => false, 'error' => 'Nom et type requis.'], 422);
        }
        $id = Category::create($userId, $name, $type, $color);
        json_response(['success' => true, 'id' => $id, 'name' => $name, 'type' => $type, 'color' => $color]);
    }

    public function updateCategory(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id     = (int)($_POST['id'] ?? 0);
        $name   = clean_str($_POST['name'] ?? '');
        $type   = in_array($_POST['type'] ?? '', ['income', 'expense'], true) ? $_POST['type'] : '';
        $color  = clean_str($_POST['color'] ?? '#00d9ff');

        if (!$id || !Category::find($id, $userId)) {
            json_response(['success' => false, 'error' => 'Catégorie introuvable.'], 404);
        }
        if ($name === '' || $type === '') {
            json_response(['success' => false, 'error' => 'Nom et type requis.'], 422);
        }
        Category::update($id, $userId, $name, $type, $color);
        json_response(['success' => true]);
    }

    public function destroyCategory(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id     = (int)($_POST['id'] ?? 0);

        if (!$id || !Category::find($id, $userId)) {
            json_response(['success' => false, 'error' => 'Catégorie introuvable.'], 404);
        }
        Category::delete($id, $userId);
        json_response(['success' => true]);
    }

    // Helpers privés

    private function extractPayload(): array
    {
        $src = !empty($_POST) ? $_POST : json_body();
        return [
            'type'           => in_array($src['type'] ?? '', ['income', 'expense'], true) ? $src['type'] : '',
            'amount'         => clean_float($src['amount'] ?? 0),
            'label'          => clean_str($src['label'] ?? ''),
            'category_id'    => (int)($src['category_id'] ?? 0) ?: null,
            'occurred_on'    => clean_str($src['occurred_on'] ?? date('Y-m-d')),
            'recurrence'     => in_array($src['recurrence'] ?? 'none', ['none','daily','weekly','monthly','yearly'], true) ? $src['recurrence'] : 'none',
            'recurrence_end' => clean_str($src['recurrence_end'] ?? ''),
            'note'           => clean_str($src['note'] ?? ''),
        ];
    }

    private function validate(array $data): bool
    {
        return $data['type'] !== ''
            && $data['amount'] > 0
            && $data['label'] !== ''
            && strtotime($data['occurred_on']) !== false;
    }
}
