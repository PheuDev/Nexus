<?php
/**
 * CONTROLLER — Finances
 * Gestion des revenus/dépenses : liste paginée, filtres,
 * CRUD transactions, CRUD catégories complet.
 */

require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../core/pdf.php';

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
            'quickType'    => in_array($_GET['quick'] ?? '', ['income', 'expense'], true) ? $_GET['quick'] : '',
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
    // EXPORT PDF
    // ----------------------------------------------------------

    public function exportPdf(): void
    {
        require_login();
        $userId = current_user_id();

        $requestedType = isset($_GET['type']) && is_string($_GET['type']) ? $_GET['type'] : '';
        $requestedFrom = isset($_GET['from']) && is_string($_GET['from']) ? $_GET['from'] : date('Y-m-01');
        $requestedTo = isset($_GET['to']) && is_string($_GET['to']) ? $_GET['to'] : date('Y-m-d');
        [$from, $to] = nexus_date_period($requestedFrom, $requestedTo);
        $filters = [
            'type'        => in_array($requestedType, ['income', 'expense'], true) ? $requestedType : '',
            'category_id' => isset($_GET['category_id']) && is_scalar($_GET['category_id']) ? (int)$_GET['category_id'] : 0,
            'search'      => isset($_GET['q']) && is_string($_GET['q']) ? clean_str($_GET['q']) : '',
            'from'        => $from,
            'to'          => $to,
        ];

        $rows = Transaction::allForUser($userId, $filters);
        $cur = currency_symbol($_SESSION['user_currency'] ?? 'EUR');
        $pdf = nexus_transactions_pdf(
            $rows,
            [
                'title' => t('finance.pdf_title'),
                'generated_by' => t('finance.pdf_generated_by'),
                'income_label' => t('finance.pdf_income'),
                'expense_label' => t('finance.pdf_expense'),
                'net_label' => t('finance.pdf_net'),
                'transactions' => t('finance.pdf_transactions'),
                'date_label' => t('finance.date'),
                'label_label' => t('finance.label'),
                'category_label' => t('finance.category'),
                'type_label' => t('finance.type'),
                'amount_label' => t('finance.amount'),
                'income_type' => t('finance.income'),
                'expense_type' => t('finance.expense'),
                'no_category' => t('finance.no_category'),
                'empty' => t('finance.pdf_empty'),
                'footer' => t('finance.pdf_footer'),
            ],
            $cur,
            $from,
            $to,
            $_SESSION['user_name'] ?? ''
        );

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="nexus-transactions-' . date('Y-m-d') . '.pdf"');
        header('Cache-Control: no-cache');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
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
