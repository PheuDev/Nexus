<?php
$cur = currency_symbol($_SESSION['user_currency'] ?? 'EUR');
?>
<div class="panel panel-static" style="margin-bottom:16px;">
    <div class="panel-title-row">
        <div class="section-title"><?= th('goals.title') ?></div>
        <button class="btn btn-primary btn-sm" onclick="openGoalForm()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?= th('goals.new_btn') ?>
        </button>
    </div>
</div>

<div class="grid grid-3" id="goals-grid">
    <?php if (empty($goals)): ?>
        <div class="panel" style="grid-column:1/-1;">
            <div class="empty-state">
                <div class="empty-state-icon">🎯</div>
                <?= th('goals.no_goals') ?><br>
                <button class="btn btn-primary btn-sm" onclick="openGoalForm()" style="margin-top:14px;"><?= th('goals.create_first') ?></button>
            </div>
        </div>
    <?php endif; ?>
    <?php foreach ($goals as $g):
        $pct       = $g['target_amount'] > 0 ? min(100, round(($g['current_amount']/$g['target_amount'])*100)) : 0;
        $remaining = max(0, $g['target_amount'] - $g['current_amount']);
        $achieved  = $g['status'] === 'achieved';
    ?>
    <div class="panel goal-card <?= $achieved ? 'goal-achieved' : '' ?>" data-id="<?= $g['id'] ?>">
        <div class="flex-between" style="margin-bottom:14px;">
            <h3 style="font-size:13px;color:var(--text-main);text-transform:none;letter-spacing:0;font-weight:600;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= htmlspecialchars($g['title']) ?>
            </h3>
            <?php if ($achieved): ?>
                <span class="badge badge-low" style="flex-shrink:0;margin-left:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="10" height="10"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= th('goals.achieved') ?>
                </span>
            <?php else: ?>
                <span class="badge badge-active" style="flex-shrink:0;margin-left:8px;"><?= th('goals.active') ?></span>
            <?php endif; ?>
        </div>
        <div class="kpi-value balance" style="font-size:24px;">
            <?= number_format($g['current_amount'],0,',',' ') ?>
            <span style="font-size:13px;color:var(--text-faint);font-weight:400;">/ <?= number_format($g['target_amount'],0,',',' ') ?> <?= $cur ?></span>
        </div>
        <div class="progress-track lg" style="margin-top:12px;">
            <div class="progress-fill <?= $achieved ? 'done' : '' ?>" data-width="<?= $pct ?>" style="width:0"
                 role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="flex-between mt-8" style="font-size:11px;color:var(--text-faint);font-family:var(--font-mono);">
            <span style="color:<?= $pct>=100?'var(--success)':($pct>=50?'var(--cyan)':'var(--electric)') ?>;font-weight:700;"><?= $pct ?>% <?= th('goals.pct') ?></span>
            <?php if (!$achieved): ?>
                <span><?= th('goals.remaining') ?> <?= number_format($remaining,0,',',' ') ?> <?= $cur ?></span>
            <?php else: ?>
                <span style="color:var(--success);"><?= th('goals.completed') ?></span>
            <?php endif; ?>
        </div>
        <?php if ($g['target_date']): ?>
            <div style="margin-top:8px;font-size:11px;color:var(--text-faint);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11" style="vertical-align:middle;margin-right:4px;color:var(--text-muted);"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
                <?= th('goals.target_date') ?> <?= date('d/m/Y', strtotime($g['target_date'])) ?>
            </div>
        <?php endif; ?>
        <div style="display:flex;gap:8px;margin-top:16px;padding-top:14px;border-top:1px solid var(--border-faint);">
            <?php if (!$achieved): ?>
                <button class="btn btn-sm btn-primary" onclick="openDepositForm(<?= $g['id'] ?>)" style="flex:1;"><?= th('goals.deposit_btn') ?></button>
            <?php endif; ?>
            <button class="btn btn-sm" onclick='openGoalForm(<?= json_encode($g) ?>)' style="flex:1;"><?= th('goals.edit_btn') ?></button>
            <button class="btn btn-sm btn-danger btn-icon" onclick="deleteGoal(<?= $g['id'] ?>)" title="<?= th('goals.delete_btn') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.goal-achieved { border-color:rgba(61,220,151,0.2)!important; background:linear-gradient(145deg,rgba(15,24,20,.95),rgba(10,16,14,.98))!important; }
.goal-achieved::before { background:linear-gradient(90deg,transparent,rgba(61,220,151,0.15),transparent)!important; }
</style>

<!-- Modal objectif -->
<div id="goal-modal" class="nexus-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="goal-modal-title">
    <div class="nexus-modal" style="max-width:420px;">
        <h3 id="goal-modal-title"><?= th('goals.modal_new') ?></h3>
        <form id="goal-form">
            <input type="hidden" name="id" id="goal-id">
            <div class="form-group">
                <label><?= th('goals.title_label') ?></label>
                <input type="text" name="title" id="goal-title" class="form-control" placeholder="<?= th('goals.title_ph') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= th('goals.target_label') ?> (<?= $cur ?>)</label>
                    <input type="number" step="0.01" min="1" name="target_amount" id="goal-target" class="form-control" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label><?= th('goals.date_label') ?></label>
                    <input type="date" name="target_date" id="goal-date" class="form-control">
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><?= th('goals.save') ?></button>
                <button type="button" class="btn" onclick="closeGoalForm()"><?= th('goals.cancel') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal dépôt -->
<div id="deposit-modal" class="nexus-modal-backdrop" role="dialog" aria-modal="true">
    <div class="nexus-modal" style="max-width:360px;">
        <h3><?= th('goals.deposit_title') ?></h3>
        <form id="deposit-form">
            <input type="hidden" name="goal_id" id="deposit-goal-id">
            <div class="form-group">
                <label><?= th('goals.deposit_amount') ?> (<?= $cur ?>)</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="0.00" required autofocus>
            </div>
            <div class="form-group">
                <label><?= th('goals.deposit_date') ?></label>
                <input type="date" name="deposited_on" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><?= th('goals.deposit_btn2') ?></button>
                <button type="button" class="btn" onclick="document.getElementById('deposit-modal').classList.remove('open')"><?= th('goals.cancel') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openGoalForm(g) {
    document.getElementById('goal-modal').classList.add('open');
    document.getElementById('goal-modal-title').textContent = g ? '<?= th('goals.modal_edit') ?>' : '<?= th('goals.modal_new') ?>';
    document.getElementById('goal-id').value     = g ? g.id : '';
    document.getElementById('goal-title').value  = g ? g.title : '';
    document.getElementById('goal-target').value = g ? g.target_amount : '';
    document.getElementById('goal-date').value   = g ? (g.target_date||'') : '';
}
function closeGoalForm() { document.getElementById('goal-modal').classList.remove('open'); }
document.getElementById('goal-modal').addEventListener('click',(e)=>{if(e.target===e.currentTarget)closeGoalForm();});
document.getElementById('deposit-modal').addEventListener('click',(e)=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('open');});

document.getElementById('goal-form').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const data=Object.fromEntries(new FormData(e.target));
    const url=data.id?'index.php?page=goals&action=update':'index.php?page=goals&action=store';
    const res=await NEXUS.post(url,data);
    if(res.success){ NEXUS.toast('<?= th('goals.saved') ?>','success'); setTimeout(()=>location.reload(),500); }
    else NEXUS.toast(res.error||'<?= th('common.error') ?>','error');
});

function openDepositForm(goalId) {
    document.getElementById('deposit-goal-id').value=goalId;
    document.getElementById('deposit-modal').classList.add('open');
}
document.getElementById('deposit-form').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const data=Object.fromEntries(new FormData(e.target));
    const res=await NEXUS.post('index.php?page=goals&action=deposit',data);
    if(res.success){ NEXUS.toast('<?= th('goals.deposited') ?>','success'); setTimeout(()=>location.reload(),500); }
    else NEXUS.toast(res.error||'<?= th('common.error') ?>','error');
});

async function deleteGoal(id) {
    const ok=await NEXUS.confirm('<?= th('goals.confirm_delete') ?>','','🗑',true);
    if(!ok) return;
    const res=await NEXUS.post('index.php?page=goals&action=destroy',{id});
    if(res.success){ document.querySelector(`[data-id="${id}"]`)?.remove(); NEXUS.toast('<?= th('goals.deleted') ?>','success'); }
    else NEXUS.toast(res.error||'<?= th('common.error') ?>','error');
}
</script>
