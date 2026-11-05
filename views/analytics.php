<?php
$cur = currency_symbol($_SESSION['user_currency'] ?? 'EUR');

$incomeVar  = $previous['income']  > 0 ? round((($current['income']  - $previous['income'])  / $previous['income'])  * 100) : null;
$expenseVar = $previous['expense'] > 0 ? round((($current['expense'] - $previous['expense']) / $previous['expense']) * 100) : null;
$savingsVar = $savingsRatePrevious > 0 ? ($savingsRateCurrent - $savingsRatePrevious) : null;

function variationBadge(?float $v, bool $invertGood = false): string {
    if ($v === null) return '';
    $positive = $v >= 0;
    $good  = $invertGood ? !$positive : $positive;
    $cls   = $good ? 'up' : 'down';
    $arrow = $positive ? '↑' : '↓';
    return "<span class='kpi-trend $cls'>$arrow " . abs((int)round($v)) . "%</span>";
}
?>

<form method="get" action="index.php" class="panel panel-static period-filter-panel" style="margin-bottom:16px;">
    <input type="hidden" name="page" value="analytics">
    <div style="display:flex;align-items:end;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin:0;">
            <label for="period-from"><?= th('finance.from') ?></label>
            <input class="form-control" type="date" id="period-from" name="from" value="<?= htmlspecialchars($periodFrom) ?>" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label for="period-to"><?= th('finance.to') ?></label>
            <input class="form-control" type="date" id="period-to" name="to" value="<?= htmlspecialchars($periodTo) ?>" required>
        </div>
        <span class="text-xs text-faint"><?= htmlspecialchars($periodFrom) ?> – <?= htmlspecialchars($periodTo) ?></span>
    </div>
</form>

<div class="grid grid-3">
    <div class="panel">
        <div class="flex-between">
            <h3><?= th('analytics.savings_rate') ?></h3>
            <div class="panel-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2a9 9 0 0 1 9 9c0 3.5-1.5 6-4 8l-5 3-5-3c-2.5-2-4-4.5-4-8a9 9 0 0 1 9-9z"/></svg>
            </div>
        </div>
        <div class="kpi-value savings"><?= $savingsRateCurrent ?>%</div>
        <?= variationBadge(is_numeric($savingsVar) ? (float)$savingsVar : null) ?>
        <div class="kpi-sub"><?= th('analytics.previous_period') ?> <?= htmlspecialchars($previousFrom) ?> – <?= htmlspecialchars($previousTo) ?>: <?= $savingsRatePrevious ?>%</div>
    </div>
    <div class="panel">
        <div class="flex-between">
            <h3><?= th('analytics.income') ?></h3>
            <div class="panel-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
        </div>
        <div class="kpi-value income"><?= number_format($current['income'],0,',',' ') ?> <?= $cur ?></div>
        <?= variationBadge($incomeVar) ?>
        <div class="kpi-sub"><?= th('analytics.previous_period') ?> <?= htmlspecialchars($previousFrom) ?> – <?= htmlspecialchars($previousTo) ?>: <?= number_format($previous['income'],0,',',' ') ?> <?= $cur ?></div>
    </div>
    <div class="panel">
        <div class="flex-between">
            <h3><?= th('analytics.expense') ?></h3>
            <div class="panel-icon red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
            </div>
        </div>
        <div class="kpi-value expense"><?= number_format($current['expense'],0,',',' ') ?> <?= $cur ?></div>
        <?= variationBadge($expenseVar, true) ?>
        <div class="kpi-sub"><?= th('analytics.previous_period') ?> <?= htmlspecialchars($previousFrom) ?> – <?= htmlspecialchars($previousTo) ?>: <?= number_format($previous['expense'],0,',',' ') ?> <?= $cur ?></div>
    </div>
</div>

<div class="grid grid-2 mt-16">
    <div class="panel panel-static">
        <div class="panel-title-row"><div class="section-title"><?= th('analytics.evolution') ?></div><span class="text-xs text-faint"><?= htmlspecialchars($periodFrom) ?> – <?= htmlspecialchars($periodTo) ?></span></div>
        <div class="chart-wrap chart-wrap-lg"><canvas id="chart-evo"></canvas></div>
    </div>
    <div class="panel panel-static">
        <div class="panel-title-row">
            <div class="section-title"><?= th('analytics.breakdown') ?></div>
            <span class="text-xs text-faint"><?= htmlspecialchars($periodFrom) ?> – <?= htmlspecialchars($periodTo) ?></span>
        </div>
        <?php if (empty($expenseByCat)): ?>
            <div class="empty-state"><div class="empty-state-icon">📊</div><?= th('analytics.no_expense_cat') ?></div>
        <?php else: ?>
            <div class="chart-wrap chart-wrap-lg"><canvas id="chart-cat"></canvas></div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const periodForm = document.querySelector('form[action="index.php"] input[name="page"][value="analytics"]')?.form;
    const periodFrom = periodForm?.querySelector('input[name="from"]');
    const periodTo = periodForm?.querySelector('input[name="to"]');
    if (periodForm && periodFrom && periodTo) {
        const syncDateBounds = () => {
            periodFrom.max = periodTo.value;
            periodTo.min = periodFrom.value;
        };
        syncDateBounds();
        [periodFrom, periodTo].forEach(input => input.addEventListener('change', () => {
            syncDateBounds();
            if (periodForm.reportValidity()) periodForm.requestSubmit();
        }));
    }

    const MONTH_NAMES = <?= json_encode(t('months')) ?>;
    const raw    = <?= json_encode($series) ?>;
    const months = [...new Set(raw.map(r=>r.ym))].sort();
    const income  = months.map(m=>{ const r=raw.find(x=>x.ym===m&&x.type==='income');  return r?parseFloat(r.total):0; });
    const expense = months.map(m=>{ const r=raw.find(x=>x.ym===m&&x.type==='expense'); return r?parseFloat(r.total):0; });
    const labels  = months.map(m=>{ const[year,mo]=m.split('-'); return `${MONTH_NAMES[parseInt(mo,10)-1]??m} ${year}`; });

    const isDark  = document.documentElement.getAttribute('data-theme')!=='light';
    const tc      = isDark?'#aab9b1':'#708276';
    const gc      = isDark?'rgba(220,235,226,0.07)':'rgba(48,77,64,0.1)';
    const tbg     = isDark?'#202d28':'#ffffff';
    const tooltip = isDark?'rgba(167,131,255,0.35)':'rgba(104,69,207,0.3)';
    const def     = { plugins:{ legend:{labels:{color:tc,font:{family:'Inter',size:12},boxWidth:12,padding:16}}, tooltip:{backgroundColor:tbg,borderColor:tooltip,borderWidth:1,titleColor:isDark?'#f3f7f4':'#15251d',bodyColor:tc,padding:12} }, scales:{ x:{ticks:{color:tc,font:{size:11}},grid:{color:gc}}, y:{ticks:{color:tc,font:{size:11}},grid:{color:gc}} } };

    new Chart(document.getElementById('chart-evo'),{
        type:'bar',
        data:{ labels, datasets:[
            {label:'<?= th('common.income') ?>',  data:income,  backgroundColor:'rgba(115,201,161,0.55)',borderColor:'rgba(115,201,161,0.8)',borderWidth:1,borderRadius:4},
            {label:'<?= th('common.expense') ?>', data:expense, backgroundColor:'rgba(223,137,129,0.55)',borderColor:'rgba(223,137,129,0.8)',borderWidth:1,borderRadius:4},
        ]},
        options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},...def},
    });

    const cats=<?= json_encode($expenseByCat) ?>;
    if(cats.length>0){
        new Chart(document.getElementById('chart-cat'),{
            type:'doughnut',
            data:{labels:cats.map(c=>c.name),datasets:[{data:cats.map(c=>parseFloat(c.total)),backgroundColor:cats.map(c=>c.color||'#a783ff'),borderWidth:2,borderColor:isDark?'#111627':'#ffffff',hoverOffset:8}]},
            options:{responsive:true,maintainAspectRatio:false,cutout:'68%',
                plugins:{
                    legend:{position:'bottom',labels:{color:tc,font:{family:'Inter',size:11},padding:14,boxWidth:10}},
                    tooltip:{backgroundColor:tbg,borderColor:tooltip,borderWidth:1,titleColor:isDark?'#f3f7f4':'#15251d',bodyColor:tc,padding:12,
                        callbacks:{label:ctx=>' '+ctx.label+' : '+ctx.parsed.toLocaleString('<?= nexus_locale() ?>')+' <?= $cur ?>'}},
                }
            },
        });
    }
})();
</script>
