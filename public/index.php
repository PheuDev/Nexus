<?php
/**NEXUS — FRONT CONTROLLER*/
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/FinanceController.php';
require_once __DIR__ . '/../controllers/GoalController.php';
require_once __DIR__ . '/../controllers/TaskController.php';
require_once __DIR__ . '/../controllers/AnalyticsController.php';
require_once __DIR__ . '/../controllers/SettingsController.php';
require_once __DIR__ . '/../controllers/BudgetController.php';
require_once __DIR__ . '/../controllers/SearchController.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$page   = $_GET['page']   ?? 'dashboard';
$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'login'    => ['GET' => fn() => (new AuthController())->showLogin(),    'POST:login'    => fn() => (new AuthController())->login()],
    'register' => ['GET' => fn() => (new AuthController())->showRegister(), 'POST:register' => fn() => (new AuthController())->register()],
    'forgot'   => ['GET' => fn() => (new AuthController())->showForgot(),   'POST:send'     => fn() => (new AuthController())->sendReset()],
    'reset'    => ['GET' => fn() => (new AuthController())->showReset(),    'POST:process'  => fn() => (new AuthController())->processReset()],
    'logout'   => ['POST:logout' => fn() => (new AuthController())->logout()],
    'privacy'  => ['GET' => fn() => render('privacy', ['title' => 'Confidentialité'], false)],

    'dashboard' => [
        'GET'        => fn() => (new DashboardController())->index(),
        'GET:series' => fn() => (new DashboardController())->series(),
        'POST:target' => fn() => (new DashboardController())->saveMonthlyTarget(),
    ],

    'finances' => [
        'GET'                   => fn() => (new FinanceController())->index(),
        'GET:list'              => fn() => (new FinanceController())->list(),
        'GET:export'            => fn() => (new FinanceController())->exportCsv(),
        'POST:store'            => fn() => (new FinanceController())->store(),
        'POST:update'           => fn() => (new FinanceController())->update(),
        'POST:destroy'          => fn() => (new FinanceController())->destroy(),
        'POST:store_category'   => fn() => (new FinanceController())->storeCategory(),
        'POST:update_category'  => fn() => (new FinanceController())->updateCategory(),
        'POST:destroy_category' => fn() => (new FinanceController())->destroyCategory(),
    ],

    'goals' => [
        'GET'          => fn() => (new GoalController())->index(),
        'POST:store'   => fn() => (new GoalController())->store(),
        'POST:update'  => fn() => (new GoalController())->update(),
        'POST:destroy' => fn() => (new GoalController())->destroy(),
        'POST:deposit' => fn() => (new GoalController())->deposit(),
    ],

    'planning' => [
        'GET'          => fn() => (new TaskController())->index(),
        'POST:store'   => fn() => (new TaskController())->store(),
        'POST:update'  => fn() => (new TaskController())->update(),
        'POST:toggle'  => fn() => (new TaskController())->toggle(),
        'POST:destroy' => fn() => (new TaskController())->destroy(),
    ],

    'analytics' => [
        'GET' => fn() => (new AnalyticsController())->index(),
    ],

    'budgets' => [
        'GET'          => fn() => (new BudgetController())->index(),
        'GET:summary'  => fn() => (new BudgetController())->summary(),
        'POST:upsert'  => fn() => (new BudgetController())->upsert(),
        'POST:destroy' => fn() => (new BudgetController())->destroy(),
    ],

    'search' => [
        'GET' => fn() => (new SearchController())->search(),
    ],

    'settings' => [
        'GET'                 => fn() => (new SettingsController())->index(),
        'POST:update'         => fn() => (new SettingsController())->update(),
        'POST:delete_account' => fn() => (new SettingsController())->deleteAccount(),
    ],

    'history' => [
        'GET' => fn() => nexus_render_history(),
    ],
];

// Vue historique d'activité (inline, sans controller dédié)
function nexus_render_history(): void
{
    require_login();
    require_once __DIR__ . '/../models/ActivityLog.php';
    $logs = ActivityLog::recent(current_user_id(), 100);
    render('history', ['title' => 'Historique', 'logs' => $logs]);
}

// DISPATCH
if (!isset($routes[$page])) {
    http_response_code(404);
    render('404', ['title' => 'Introuvable'], false);
    exit;
}

$key = $method === 'POST' && $action ? "POST:$action" : ($action ? "GET:$action" : $method);

if (!isset($routes[$page][$key])) {
    // Fallback GET sans action
    if (isset($routes[$page]['GET'])) {
        $key = 'GET';
    } else {
        http_response_code(405);
        json_response(['success' => false, 'error' => 'Route/méthode inconnue.'], 405);
    }
}

$publicPages = ['login', 'register', 'forgot', 'reset', 'privacy'];
if (!in_array($page, $publicPages, true)) {
    require_login();
}

$routes[$page][$key]();
