<?php
$cur = currency_symbol($_SESSION['user_currency'] ?? 'EUR');
?>
<div class="panel panel-static" style="margin-bottom:16px;">
    <div class="panel-title-row">
        <div class="section-title"><?= th('budgets.title') ?></div>
        <div style="display:flex;gap:8px;align-items:center;">
            <form id="month-form" style="display:flex;gap:8px;align-items:center;">
                <div class="form-group mb-0">
                    <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>" style="width:150px;padding:7px 12px;">
                </div>
                <button type="submit" class="btn btn-sm"><?= th('budgets.show_btn') ?></button>
            </form>
            <button class="btn btn-primary btn-sm" onclick="openBudgetForm()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <?= th('budgets.new_btn') ?>
            </button>
        </div>
    </div>
</div>

<?php if (empty($budgets)): ?>
<div class="panel panel-static">
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <?= th('budgets.no_budgets', ['{month}' => htmlspecialchars($month)]) ?><br>
        <button class="btn btn-primary btn-sm" onclick="openBudgetForm()" style="margin-top:12px;"><?= th('budgets.create_first') ?></button>
    </div>
</div>
<?php else: ?>
<div class="grid grid-3">
    <?php foreach ($budgets as $b):
        $barColor = $b['over'] ? 'background:linear-gradient(90deg,var(--danger),#ff8f97)' : '';
    ?>
    <div class="panel">
        <div class="flex-between" style="margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="tag-dot" style="background:<?= htmlspecialchars($b['category_color']) ?>;width:10px;height:10px;"></span>
                <h3 style="text-transform:none;font-size:14px;color:var(--text-bright);letter-spacing:0;font-weight:600;"><?= htmlspecialchars($b['category_name']) ?></h3>
            </div>
            <span class="badge <?= $b['over']?'badge-high':($b['pct']>=80?'badge-medium':'badge-low') ?>"><?= $b['pct'] ?>%</span>
        </div>
        <div class="flex-between" style="font-size:13px;margin-bottom:8px;">
            <span style="color:var(--text-dim);"><?= th('budgets.spent') ?></span>
            <span style="font-weight:700;color:<?= $b['over']?'var(--danger)':'var(--text-main)' ?>;font-family:var(--font-mono);"><?= number_format($b['spent'],0,',',' ') ?> <?= $cur ?></span>
        </div>
        <div class="flex-between" style="font-size:12px;margin-bottom:10px;">
            <span style="color:var(--text-faint);"><?= th('budgets.budget') ?></span>
            <span style="color:var(--text-faint);font-family:var(--font-mono);"><?= number_format($b['budget'],0,',',' ') ?> <?= $cur ?></span>
        </div>
        <div class="progress-track" style="height:8px;">
            <div class="progress-fill" data-width="<?= min(100,$b['pct']) ?>" style="width:0;<?= $barColor ?>"></div>
        </div>
        <?php if ($b['over']): ?>
            <div style="font-size:11px;color:var(--danger);margin-top:8px;font-family:var(--font-mono);"><?= th('budgets.overspent') ?> +<?= number_format($b['spent']-$b['budget'],0,',',' ') ?> <?= $cur ?></div>
        <?php else: ?>
            <div style="font-size:11px;color:var(--text-faint);margin-top:8px;font-family:var(--font-mono);"><?= th('budgets.remaining') ?> <?= number_format($b['budget']-$b['spent'],0,',',' ') ?> <?= $cur ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div id="budget-modal" class="nexus-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="budget-modal-title">
    <div class="nexus-modal" style="max-width:400px;">
        <h3 id="budget-modal-title"><?= th('budgets.modal_title') ?></h3>
        <form id="budget-form">
            <div class="form-group">
                <label><?= th('budgets.cat_label') ?></label>
                <select name="category_id" id="budget-cat" class="form-control" required>
                    <option value=""><?= th('budgets.cat_ph') ?></option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['type']==='expense'?th('common.expense'):th('common.income') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= th('budgets.month_label') ?></label>
                    <input type="month" name="month" id="budget-month" class="form-control" value="<?= htmlspecialchars($month) ?>" required>
                </div>
                <div class="form-group">
                    <label><?= th('budgets.amount_label') ?> (<?= $cur ?>)</label>
                    <input type="number" name="amount" id="budget-amount" class="form-control" step="0.01" min="1" placeholder="0.00" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><?= th('budgets.save') ?></button>
                <button type="button" class="btn" onclick="document.getElementById('budget-modal').classList.remove('open')"><?= th('budgets.cancel') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openBudgetForm(){ document.getElementById('budget-modal').classList.add('open'); }
document.getElementById('budget-modal').addEventListener('click',(e)=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('open');});
document.getElementById('budget-form').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const data=Object.fromEntries(new FormData(e.target));
    const res=await NEXUS.post('index.php?page=budgets&action=upsert',data);
    if(res.success){ NEXUS.toast('<?= th('budgets.saved') ?>','success'); setTimeout(()=>location.reload(),500); }
    else NEXUS.toast(res.error||'<?= th('common.error') ?>','error');
});
document.getElementById('month-form').addEventListener('submit',(e)=>{
    e.preventDefault();
    const params=new URLSearchParams(new FormData(e.target));
    window.location.href='index.php?page=budgets&'+params.toString();
});
</script>
