<?php
/**LAYOUT PRINCIPAL — NEXUS*/

$currentPage = $_GET['page'] ?? 'dashboard';

$navPages = ['dashboard','finances','budgets','goals','planning','analytics','history','settings'];

$icons = [
    'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    'wallet'   => '<path d="M20 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 14a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/><path d="M2 7l2-4h14l2 4"/>',
    'budget'   => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><path d="M7 11l3-3 2 2 3-3"/>',
    'target'   => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/>',
    'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/>',
    'chart'    => '<path d="M3 20V10l4-4 4 4 4-7 4 3"/><path d="M3 20h18" stroke-width="1" opacity=".4"/>',
    'history'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/><path d="M7 12H3M7 12a5 5 0 0 1 5-5" opacity=".5"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14" opacity=".3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
    'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
];
$navIcons = [
    'dashboard' => 'grid',    'finances'  => 'wallet',
    'budgets'   => 'budget',  'goals'     => 'target',
    'planning'  => 'calendar','analytics' => 'chart',
    'history'   => 'history', 'settings'  => 'settings',
];

$userName     = $_SESSION['user_name']  ?? '';
$userEmail    = $_SESSION['user_email'] ?? '';
$userInitials = strtoupper(mb_substr($userName, 0, 2));

$pendingTasksCount = pending_tasks_count();

$pageLabel = t('nav.' . $currentPage) ?: ucfirst($currentPage);
$pageSub   = t('nav.sub.' . $currentPage) ?: t('layout.personal_os');

$activeTheme = $_SESSION['user_theme'] ?? 'dark';
$locale      = nexus_locale();

?><!DOCTYPE html>
<html lang="<?= $locale ?>" data-theme="<?= htmlspecialchars($activeTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token"    content="<?= htmlspecialchars(csrf_token()) ?>">
    <meta name="user-currency" content="<?= htmlspecialchars($_SESSION['user_currency'] ?? 'EUR') ?>">
    <title>NEXUS — <?= htmlspecialchars($title ?? $pageLabel) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#00d9ff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="NEXUS">

    <script src="assets/js/chart.umd.min.js"></script>
</head>
<body>
<div class="nexus-shell">

    <div class="nexus-overlay" id="nexus-overlay" aria-hidden="true"></div>

    <!-- ================================ SIDEBAR ================================ -->
    <aside class="nexus-sidebar" id="nexus-sidebar" role="navigation" aria-label="<?= th('nav.section') ?>">

        <div class="nexus-brand">
            <div class="logo-mark"><div class="logo-ring"><span class="dot"></span></div></div>
            <div class="nexus-brand-text">
                <h1>NEXUS</h1>
                <div class="nexus-brand-sub"><?= th('layout.personal_os') ?></div>
            </div>
        </div>

        <nav class="nexus-nav" aria-label="<?= th('nav.section') ?>">
            <div class="nav-section-label"><?= th('nav.section') ?></div>
            <?php foreach ($navPages as $key):
                $isActive = ($currentPage === $key);
            ?>
            <a href="index.php?page=<?= $key ?>"
               class="<?= $isActive ? 'active' : '' ?>"
               aria-current="<?= $isActive ? 'page' : 'false' ?>"
               title="<?= th('nav.sub.' . $key) ?>">
                <svg viewBox="0 0 24 24"
                     fill="<?= $key === 'dashboard' ? 'currentColor' : 'none' ?>"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <?= $icons[$navIcons[$key]] ?>
                </svg>
                <span><?= th('nav.' . $key) ?></span>
                <?php if ($key === 'planning' && $pendingTasksCount > 0): ?>
                    <span class="nav-badge" aria-label="<?= $pendingTasksCount ?> pending">
                        <?= $pendingTasksCount > 99 ? '99+' : $pendingTasksCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="nexus-user-card">
            <div class="nexus-user-avatar" aria-hidden="true"><?= htmlspecialchars($userInitials) ?></div>
            <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            <span class="user-email"><?= htmlspecialchars($userEmail) ?></span>
            <div class="user-meta">
                <span class="nexus-status-dot"><?= th('layout.online') ?></span>
                <a href="index.php?page=logout" class="nexus-logout" aria-label="<?= th('layout.logout') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round"
                         width="13" height="13" aria-hidden="true" style="vertical-align:middle;margin-right:4px;">
                        <?= $icons['logout'] ?>
                    </svg><?= th('layout.logout') ?>
                </a>
            </div>
        </div>

    </aside>

    <!-- ================================ MAIN ================================ -->
    <main class="nexus-main" id="nexus-main" role="main">

        <header class="nexus-topbar">
            <div class="nexus-topbar-left">
                <button class="nexus-hamburger" id="nexus-hamburger"
                        aria-label="<?= th('layout.open_menu') ?>"
                        aria-expanded="false" aria-controls="nexus-sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="topbar-page-info">
                    <h2><?= htmlspecialchars($title ?? $pageLabel) ?></h2>
                    <div class="subtitle"><?= htmlspecialchars($pageSub) ?></div>
                </div>
            </div>

            <div class="nexus-topbar-right">
                <!-- Recherche globale -->
                <div class="nexus-search" id="nexus-search-wrap">
                    <div class="nexus-search-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" width="14" height="14"
                             style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-faint);pointer-events:none;">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" id="nexus-search-input" class="form-control"
                               placeholder="<?= th('layout.search_placeholder') ?>"
                               autocomplete="off"
                               style="padding-left:32px;height:34px;width:200px;font-size:12px;border-radius:var(--radius-pill);"
                               aria-label="<?= th('layout.search_placeholder') ?>"
                               aria-expanded="false" aria-controls="nexus-search-results">
                    </div>
                    <div id="nexus-search-results" class="nexus-search-dropdown" style="display:none;" role="listbox"></div>
                </div>

                <!-- Toggle thème -->
                <button id="nexus-theme-toggle" class="btn btn-icon btn-sm"
                        aria-label="<?= th('layout.change_theme') ?>"
                        title="<?= th('layout.change_theme') ?>"
                        style="flex-shrink:0;"></button>

                <div class="nexus-page-tag" aria-hidden="true">
                    <?= strtoupper(htmlspecialchars($currentPage)) ?>
                </div>
                <div class="nexus-clock" id="nexus-clock" aria-label="<?= th('layout.clock_label') ?>">--:--:--</div>
            </div>
        </header>

        <?php $content(); ?>

    </main>
</div>

<script src="assets/js/app.js"></script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(() => {});
}
</script>
</body>
</html>
