<?php
$cur = currency_symbol($_SESSION['user_currency'] ?? 'EUR');
$balancePositive = $balance >= 0;
$savingsGood     = $savingsRate >= 20;
$scoreColor = $score >= 70 ? 'var(--success)' : ($score >= 40 ? 'var(--warning)' : 'var(--danger)');
$scoreLabel = $score >= 70 ? t('dashboard.score_great') : ($score >= 40 ? t('dashboard.score_ok') : t('dashboard.score_bad'));
?>

<!-- Score + prévisions -->
<div class="panel panel-static" style="margin-bottom:16px; padding:14px 20px;">
    <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="font-size:10px; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-faint); font-family:var(--font-mono);"><?= th('dashboard.score_label') ?></div>
            <div style="font-size:28px; font-weight:700; color:<?= $scoreColor ?>; font-family:var(--font-mono);"><?= $score ?><span style="font-size:14px; font-weight:400;">/100</span></div>
            <span style="font-size:12px; color:var(--text-dim);"><?= htmlspecialchars($scoreLabel) ?></span>
        </div>
        <div style="flex:1; min-width:200px;">
            <div class="progress-track" style="height:8px;">
                <div class="progress-fill" data-width="<?= $score ?>" style="width:0; background:<?= $scoreColor ?>;"></div>
            </div>
        </div>
        <?php if (!empty($forecast['income']) || !empty($forecast['expense'])): ?>
        <div style="display:flex; gap:16px; font-size:12px; color:var(--text-faint); border-left:1px solid var(--border-faint); padding-left:16px;">
            <span><?= th('dashboard.forecast_label') ?> —</span>
            <span style="color:var(--success);">↑ <?= number_format($forecast['income'], 0, ',', ' ') ?> <?= $cur ?></span>
            <span style="color:var(--danger);">↓ <?= number_format($forecast['expense'], 0, ',', ' ') ?> <?= $cur ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- KPIs -->
<div class="grid grid-4">

    <div class="panel kpi-panel">
        <div class="flex-between">
            <h3><?= th('dashboard.income_month') ?></h3>
            <div class="panel-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
        </div>
        <div class="kpi-value income"><?= number_format($income, 0, ',', ' ') ?> <?= $cur ?></div>
        <div class="kpi-sub"><?= th('dashboard.since_first') ?></div>
        <div class="kpi-sparkline income" aria-hidden="true"></div>
    </div>

    <div class="panel kpi-panel">
        <div class="flex-between">
            <h3><?= th('dashboard.expense_month') ?></h3>
            <div class="panel-icon red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
            </div>
        </div>
        <div class="kpi-value expense"><?= number_format($expense, 0, ',', ' ') ?> <?= $cur ?></div>
        <div class="kpi-sub"><?= th('dashboard.since_first') ?></div>
        <div class="kpi-sparkline expense" aria-hidden="true"></div>
    </div>

    <div class="panel kpi-panel">
        <div class="flex-between">
            <h3><?= th('dashboard.balance') ?></h3>
            <div class="panel-icon <?= $balancePositive ? 'cyan' : 'red' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 12a2 2 0 0 0 0 4h4v-4z"/><path d="M2 7l2-4h14l2 4"/></svg>
            </div>
        </div>
        <div class="kpi-value balance"><?= ($balance >= 0 ? '+' : '') . number_format($balance, 0, ',', ' ') ?> <?= $cur ?></div>
        <div class="kpi-sub"><?= th('dashboard.income_expense') ?></div>
        <?php if ($balancePositive): ?>
            <span class="kpi-trend up"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg><?= th('dashboard.positive') ?></span>
        <?php else: ?>
            <span class="kpi-trend down"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg><?= th('dashboard.deficit') ?></span>
        <?php endif; ?>
    </div>

    <div class="panel kpi-panel">
        <div class="flex-between">
            <h3><?= th('dashboard.savings_rate') ?></h3>
            <div class="panel-icon <?= $savingsGood ? 'blue' : 'violet' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2a9 9 0 0 1 9 9c0 3.5-1.5 6-4 8l-5 3-5-3c-2.5-2-4-4.5-4-8a9 9 0 0 1 9-9z"/><path d="M12 7v5l3 3"/></svg>
            </div>
        </div>
        <div class="kpi-value savings"><?= $savingsRate ?>%</div>
        <div class="kpi-sub"><?= th('dashboard.goal_20') ?></div>
        <?php if ($savingsGood): ?>
            <span class="kpi-trend up"><?= th('dashboard.goal_reached') ?></span>
        <?php else: ?>
            <span class="kpi-trend flat"><?= th('dashboard.below_20') ?></span>
        <?php endif; ?>
    </div>

</div>

<!-- Objectif mensuel, classement et accès directs -->
<div class="grid grid-3 mt-16 dashboard-focus-grid">
    <section class="panel panel-static monthly-goal-panel">
        <div class="panel-title-row"><div class="section-title"><?= th('dashboard.monthly_goal') ?></div></div>
        <p class="dashboard-muted"><?= th('dashboard.monthly_goal_hint') ?></p>
        <form id="monthly-target-form" class="monthly-target-form">
            <label for="monthly-target"><?= th('dashboard.monthly_target') ?> (<?= $cur ?>)</label>
            <div class="monthly-target-controls">
                <input class="form-control" type="number" id="monthly-target" name="target_amount" min="0" step="0.01" value="<?= htmlspecialchars((string)$monthlyTarget) ?>" required>
                <button class="btn btn-primary" type="submit"><?= th('dashboard.save_target') ?></button>
            </div>
        </form>
        <?php if ($monthlyTarget > 0): ?>
        <div class="monthly-goal-stats">
            <div><span><?= th('dashboard.monthly_actual') ?></span><strong><?= number_format($balance, 0, ',', ' ') ?> <?= $cur ?></strong></div>
            <div><span><?= th('dashboard.remaining') ?></span><strong><?= number_format(max(0, $monthlyTarget - max(0, $balance)), 0, ',', ' ') ?> <?= $cur ?></strong></div>
        </div>
        <div class="progress-track monthly-goal-track"><div class="progress-fill" data-width="<?= $monthlyProgress ?>" style="width:0;background:var(--cyan);"></div></div>
        <div class="monthly-goal-percent"><?= $monthlyProgress ?>% <?= $monthlyProgress >= 100 ? '• ' . th('dashboard.goal_reached') : '' ?></div>
        <?php endif; ?>
    </section>

    <section class="panel panel-static expense-ranking-panel">
        <div class="panel-title-row"><div class="section-title"><?= th('dashboard.expense_ranking') ?></div><span class="dashboard-month-chip"><?= date('m/Y') ?></span></div>
        <?php if (empty($expenseRanking)): ?>
            <div class="empty-state"><?= th('dashboard.no_ranking') ?></div>
        <?php else: $rankMax = max(array_map(fn($row) => (float)$row['total'], $expenseRanking)); ?>
            <div class="expense-ranking-list">
            <?php foreach ($expenseRanking as $rank => $row): $rankPct = $rankMax > 0 ? round(((float)$row['total'] / $rankMax) * 100) : 0; ?>
                <div class="expense-rank-row">
                    <div class="flex-between"><span class="expense-rank-name"><b><?= $rank + 1 ?></b><i style="--rank-color:<?= htmlspecialchars($row['color']) ?>"></i><?= htmlspecialchars($row['name']) ?></span><strong><?= number_format($row['total'], 0, ',', ' ') ?> <?= $cur ?></strong></div>
                    <div class="progress-track"><div class="progress-fill" data-width="<?= $rankPct ?>" style="width:0;background:linear-gradient(90deg,<?= htmlspecialchars($row['color']) ?>,var(--accent-orange));"></div></div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel panel-static quick-actions-panel">
        <div class="panel-title-row"><div class="section-title"><?= th('dashboard.quick_actions') ?></div></div>
        <a class="quick-action income-action" href="index.php?page=finances&quick=income"><span>＋</span><?= th('dashboard.add_income') ?><b>↗</b></a>
        <a class="quick-action expense-action" href="index.php?page=finances&quick=expense"><span>−</span><?= th('dashboard.add_expense') ?><b>↗</b></a>
        <a class="quick-action goal-action" href="index.php?page=goals"><span>◎</span><?= th('dashboard.create_goal') ?><b>↗</b></a>
        <a class="quick-action task-action" href="index.php?page=planning"><span>✓</span><?= th('dashboard.planning') ?><b>↗</b></a>
    </section>
</div>

<!-- Graphique + Observations -->
<div class="grid grid-3 mt-16">

    <div class="panel panel-static" style="grid-column:span 2;">
        <div class="panel-title-row">
            <div class="section-title" id="chart-period-label">
                <?= th('dashboard.evolution') ?> — <?= $months ?> <?= $months === 1 ? 'mois' : 'mois' ?>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <div class="period-selector" role="group">
                    <?php foreach ([1=>'1M', 3=>'3M', 6=>'6M', 12=>'12M'] as $m => $label): ?>
                    <button class="period-btn <?= $months === $m ? 'active' : '' ?>"
                            data-months="<?= $m ?>" aria-pressed="<?= $months === $m ? 'true' : 'false' ?>">
                        <?= $label ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <a href="index.php?page=analytics" class="btn btn-sm"><?= th('dashboard.full_analysis') ?></a>
            </div>
        </div>
        <div class="chart-wrap" style="height:220px; position:relative;">
            <canvas id="chart-evolution"></canvas>
            <div id="chart-loading" style="display:none; position:absolute; inset:0; align-items:center; justify-content:center; background:rgba(15,20,32,0.7); border-radius:var(--radius); z-index:2;">
                <span style="color:var(--cyan); font-family:var(--font-mono); font-size:12px; letter-spacing:1px;"><?= th('dashboard.loading') ?></span>
            </div>
        </div>
    </div>

    <div class="panel panel-static">
        <div class="panel-title-row"><div class="section-title"><?= th('dashboard.observations') ?></div></div>
        <div id="insights-list">
            <?php if (empty($insights)): ?>
                <div class="empty-state"><div class="empty-state-icon">🎯</div><?= th('dashboard.no_results') ?></div>
            <?php endif; ?>
            <?php foreach ($insights as $i): ?>
                <div class="insight <?= htmlspecialchars($i['type']) ?>">
                    <span class="insight-icon"><?= match($i['type']) { 'alert' => '⚠', 'success' => '✓', default => '→' } ?></span>
                    <span><?= htmlspecialchars($i['text']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Objectifs / Tâches / Transactions -->
<div class="grid grid-3 mt-16">

    <div class="panel panel-static">
        <div class="panel-title-row">
            <div class="section-title"><?= th('dashboard.active_goals') ?></div>
            <a href="index.php?page=goals" class="btn btn-sm"><?= th('dashboard.see_all') ?></a>
        </div>
        <div class="nexus-list">
            <?php if (empty($activeGoals)): ?>
                <div class="empty-state"><div class="empty-state-icon">🎯</div><?= th('dashboard.no_active_goal') ?><br>
                <a href="index.php?page=goals" style="color:var(--cyan);font-size:12px;"><?= th('dashboard.create_goal') ?></a></div>
            <?php endif; ?>
            <?php foreach ($activeGoals as $g):
                $pct = $g['target_amount'] > 0 ? min(100, round(($g['current_amount']/$g['target_amount'])*100)) : 0;
                $pctColor = $pct >= 80 ? 'var(--success)' : ($pct >= 40 ? 'var(--cyan)' : 'var(--electric)');
            ?>
            <div class="goal-item">
                <div class="flex-between" style="margin-bottom:5px;">
                    <span class="list-row-label" style="font-size:13px;font-weight:500;"><?= htmlspecialchars($g['title']) ?></span>
                    <span style="font-size:12px;font-weight:700;color:<?= $pctColor ?>;font-family:var(--font-mono);"><?= $pct ?>%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill <?= $pct >= 100 ? 'done' : '' ?>" data-width="<?= $pct ?>" style="width:0"></div>
                </div>
                <div style="font-size:11px;color:var(--text-faint);margin-top:4px;font-family:var(--font-mono);">
                    <?= number_format($g['current_amount'],0,',',' ') ?> / <?= number_format($g['target_amount'],0,',',' ') ?> <?= $cur ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel panel-static">
        <div class="panel-title-row">
            <div class="section-title"><?= th('dashboard.today_tasks') ?></div>
            <a href="index.php?page=planning" class="btn btn-sm"><?= th('dashboard.planning') ?></a>
        </div>
        <?php if ($pendingCount > 0): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-radius:var(--radius-sm);background:rgba(0,217,255,0.04);border:1px solid var(--border-faint);margin-bottom:12px;">
            <span style="font-size:11px;color:var(--text-dim);"><?= th('dashboard.pending_total') ?></span>
            <span style="font-family:var(--font-mono);font-size:14px;font-weight:700;color:var(--cyan);background:var(--cyan-glow);padding:2px 10px;border-radius:var(--radius-pill);border:1px solid var(--border-mid);"><?= $pendingCount ?></span>
        </div>
        <?php endif; ?>
        <div class="nexus-list">
            <?php if (empty($todayTasks)): ?>
                <div class="empty-state"><div class="empty-state-icon">🎉</div><?= th('dashboard.nothing_today') ?></div>
            <?php endif; ?>
            <?php foreach ($todayTasks as $t): ?>
            <div class="list-row">
                <div class="list-row-main">
                    <span class="priority-dot priority-<?= $t['priority'] ?>" aria-hidden="true"></span>
                    <span class="list-row-label <?= $t['status']==='done' ? 'task-done' : '' ?>"><?= htmlspecialchars($t['title']) ?></span>
                </div>
                <span class="badge badge-<?= $t['status']==='done' ? 'done' : $t['priority'] ?>"><?= $t['status']==='done' ? '✓' : $t['priority'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel panel-static">
        <div class="panel-title-row">
            <div class="section-title"><?= th('dashboard.recent_tx') ?></div>
            <a href="index.php?page=finances" class="btn btn-sm"><?= th('dashboard.finances') ?></a>
        </div>
        <div class="nexus-list">
            <?php if (empty($recentTx)): ?>
                <div class="empty-state"><div class="empty-state-icon">💳</div><?= th('dashboard.no_tx') ?></div>
            <?php endif; ?>
            <?php foreach ($recentTx as $t): ?>
            <div class="list-row">
                <div class="list-row-main">
                    <span class="tag-dot" style="background:<?= htmlspecialchars($t['category_color'] ?? '#5b8cff') ?>"></span>
                    <span class="list-row-label"><?= htmlspecialchars($t['label']) ?></span>
                </div>
                <span class="amount <?= $t['type'] ?>" style="font-size:13px;white-space:nowrap;">
                    <?= $t['type']==='income' ? '+' : '−' ?><?= number_format($t['amount'],2,',',' ') ?> <?= $cur ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Budgets du mois -->
<?php if (!empty($budgetSummary)): ?>
<div class="grid mt-16" style="grid-template-columns:1fr;">
    <div class="panel panel-static">
        <div class="panel-title-row">
            <div class="section-title"><?= th('dashboard.budgets_month') ?> — <?= t_month((int)date('n'), (int)date('Y')) ?></div>
            <a href="index.php?page=budgets" class="btn btn-sm"><?= th('dashboard.manage_budgets') ?></a>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:16px;">
            <?php foreach ($budgetSummary as $b):
                $barColor = $b['over'] ? 'background:linear-gradient(90deg,var(--danger),#ff8f97)' : '';
            ?>
            <div style="flex:1;min-width:180px;">
                <div class="flex-between" style="margin-bottom:6px;">
                    <span style="font-size:12px;font-weight:600;color:var(--text-main);">
                        <span class="tag-dot" style="background:<?= htmlspecialchars($b['category_color']) ?>"></span>
                        <?= htmlspecialchars($b['category_name']) ?>
                    </span>
                    <span style="font-size:11px;color:<?= $b['over'] ? 'var(--danger)' : 'var(--text-faint)' ?>;font-family:var(--font-mono);">
                        <?= number_format($b['spent'],0,',',' ') ?> / <?= number_format($b['budget'],0,',',' ') ?> <?= $cur ?>
                    </span>
                </div>
                <div class="progress-track" style="height:6px;">
                    <div class="progress-fill" data-width="<?= min(100,$b['pct']) ?>" style="width:0;<?= $barColor ?>"></div>
                </div>
                <div style="font-size:10px;color:<?= $b['over'] ? 'var(--danger)' : 'var(--text-faint)' ?>;margin-top:4px;text-align:right;font-family:var(--font-mono);">
                    <?= $b['pct'] ?>%<?= $b['over'] ? ' — '.th('budgets.exceeded') : '' ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.kpi-panel { transition:transform .3s var(--ease-out),border-color .3s ease; }
.kpi-sparkline { height:2px;border-radius:2px;margin-top:14px;opacity:.5; }
.kpi-sparkline.income  { background:linear-gradient(90deg,transparent,var(--success)); }
.kpi-sparkline.expense { background:linear-gradient(90deg,transparent,var(--danger)); }
.task-done { text-decoration:line-through;color:var(--text-faint) !important; }
.priority-dot { width:6px;height:6px;border-radius:50%;flex-shrink:0; }
.priority-dot.priority-high   { background:var(--danger); }
.priority-dot.priority-medium { background:var(--warning); }
.priority-dot.priority-low    { background:var(--success); }
.goal-item { padding:8px 0;border-bottom:1px solid var(--border-faint); }
.goal-item:last-child { border-bottom:none; }
.period-selector { display:flex;gap:3px;background:rgba(255,255,255,0.04);border:1px solid var(--border-faint);border-radius:var(--radius-sm);padding:3px; }
.period-btn { padding:4px 10px;border-radius:6px;border:none;background:transparent;color:var(--text-faint);font-size:11px;font-weight:600;font-family:var(--font-mono);cursor:pointer;transition:all .18s ease;letter-spacing:.5px; }
.period-btn:hover  { color:var(--text-main);background:rgba(255,255,255,0.06); }
.period-btn.active { background:var(--cyan-glow);color:var(--cyan);border:1px solid var(--border-mid); }
.dashboard-focus-grid { align-items:stretch; }
.monthly-goal-panel,.expense-ranking-panel,.quick-actions-panel { border-top:2px solid var(--compass-silver); }
.dashboard-muted { color:var(--text-dim);font-size:12px;margin:0 0 14px; }
.monthly-target-form label { display:block;color:var(--text-faint);font-size:11px;margin-bottom:6px; }
.monthly-target-controls { display:flex;gap:8px; }
.monthly-target-controls .form-control { min-width:0; }
.monthly-goal-stats { display:flex;justify-content:space-between;gap:12px;margin:16px 0 10px; }
.monthly-goal-stats div { display:grid;gap:3px; }
.monthly-goal-stats span { color:var(--text-faint);font-size:10px;text-transform:uppercase;letter-spacing:.6px; }
.monthly-goal-stats strong { color:var(--compass-polished);font-family:var(--font-mono);font-size:13px; }
.monthly-goal-track { height:9px; }
.monthly-goal-percent { color:var(--accent-orange);font:700 11px var(--font-mono);text-align:right;margin-top:5px; }
.dashboard-month-chip { color:var(--compass-polished);background:var(--compass-glaze);border:1px solid var(--compass-edge);padding:3px 9px;border-radius:var(--radius-pill);font:11px var(--font-mono); }
.expense-ranking-list { display:grid;gap:12px; }
.expense-rank-row .flex-between { margin-bottom:5px;gap:8px; }
.expense-rank-name { display:flex;align-items:center;gap:8px;color:var(--text-main);font-size:12px; }
.expense-rank-name b { color:var(--compass-silver);font:700 11px var(--font-mono); }
.expense-rank-name i { width:8px;height:8px;border-radius:50%;background:var(--rank-color); }
.expense-rank-row strong { color:var(--text-bright);font:600 11px var(--font-mono);white-space:nowrap; }
.quick-actions-panel { display:flex;flex-direction:column; }
.quick-action { display:flex;align-items:center;gap:10px;padding:10px 12px;margin:3px 0;border:1px solid var(--border-faint);border-radius:var(--radius-sm);color:var(--text-main);font-weight:600;transition:transform .2s,border-color .2s,background .2s; }
.quick-action:hover { transform:translateX(3px);border-color:var(--compass-edge);background:var(--compass-glaze); }
.quick-action span { display:grid;place-items:center;width:25px;height:25px;border-radius:8px;font-size:17px;font-weight:800; }
.quick-action b { margin-left:auto;color:var(--compass-silver); }
.income-action span { color:var(--success);background:var(--success-dim); }
.expense-action span { color:var(--danger);background:var(--danger-dim); }
.goal-action span { color:var(--violet);background:var(--violet-dim); }
.task-action span { color:var(--accent-orange);background:var(--accent-orange-dim); }
@media(max-width:900px) { .dashboard-focus-grid { grid-template-columns:1fr; } }
</style>

<script>
(function () {
    document.getElementById('monthly-target-form')?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        const res = await NEXUS.post('index.php?page=dashboard&action=target', data);
        if (res.success) { NEXUS.toast('<?= th('dashboard.save_target') ?>', 'success'); window.location.reload(); }
        else NEXUS.toast(res.error || '<?= th('common.error') ?>', 'error');
    });
    // Noms des mois depuis les traductions PHP
    const MONTH_NAMES = <?= json_encode(t('months')) ?>;

    function buildChartData(raw) {
        const months  = [...new Set(raw.map(r => r.ym))].sort();
        const income  = months.map(m => { const r = raw.find(x => x.ym===m && x.type==='income');  return r ? parseFloat(r.total) : 0; });
        const expense = months.map(m => { const r = raw.find(x => x.ym===m && x.type==='expense'); return r ? parseFloat(r.total) : 0; });
        const labels  = months.map(m => { const [,mo] = m.split('-'); return MONTH_NAMES[parseInt(mo,10)-1] ?? m; });
        return { labels, income, expense };
    }

    const isDark    = document.documentElement.getAttribute('data-theme') !== 'light';
    const gridColor = isDark ? 'rgba(255,255,255,0.025)' : 'rgba(0,0,0,0.06)';
    const tickColor = isDark ? '#4a5268' : '#7a8499';
    const tooltipBg = isDark ? '#0f1420' : '#ffffff';

    const chartOptions = {
        responsive:true, maintainAspectRatio:false,
        interaction:{ mode:'index', intersect:false },
        plugins:{
            legend:{ labels:{ color:tickColor, font:{ family:'Inter', size:12 }, boxWidth:12, padding:16 } },
            tooltip:{ backgroundColor:tooltipBg, borderColor:'rgba(0,180,217,0.3)', borderWidth:1, titleColor:isDark?'#dde4f0':'#1a1f2e', bodyColor:tickColor, padding:12 },
        },
        scales:{
            x:{ ticks:{ color:tickColor, font:{ size:11 } }, grid:{ color:gridColor } },
            y:{ ticks:{ color:tickColor, font:{ size:11 } }, grid:{ color:gridColor } },
        },
    };

    const initial = buildChartData(<?= json_encode($series) ?>);
    const chart   = new Chart(document.getElementById('chart-evolution'), {
        type:'line',
        data:{
            labels: initial.labels,
            datasets:[
                { label:'<?= th('common.income') ?>',  data:initial.income,  borderColor:'#3ddc97', backgroundColor:'rgba(61,220,151,0.07)', tension:.4, fill:true, pointBackgroundColor:'#3ddc97', pointRadius:4, pointHoverRadius:6, borderWidth:2 },
                { label:'<?= th('common.expense') ?>', data:initial.expense, borderColor:'#ff5f6d', backgroundColor:'rgba(255,95,109,0.07)', tension:.4, fill:true, pointBackgroundColor:'#ff5f6d', pointRadius:4, pointHoverRadius:6, borderWidth:2 },
            ],
        },
        options: chartOptions,
    });

    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const mo      = this.dataset.months;
            const loading = document.getElementById('chart-loading');
            document.querySelectorAll('.period-btn').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-pressed','false'); });
            this.classList.add('active'); this.setAttribute('aria-pressed','true');
            document.getElementById('chart-period-label').textContent = '<?= th('dashboard.evolution') ?> — ' + mo + ' mois';
            loading.style.display = 'flex';
            try {
                const res = await NEXUS.get(`index.php?page=dashboard&action=series&months=${mo}`);
                if (res.success) {
                    const d = buildChartData(res.series);
                    chart.data.labels          = d.labels;
                    chart.data.datasets[0].data = d.income;
                    chart.data.datasets[1].data = d.expense;
                    chart.update('active');
                }
            } catch(e) { NEXUS.toast('<?= th('common.error') ?>', 'error'); }
            finally   { loading.style.display = 'none'; }
        });
    });
})();
</script>
