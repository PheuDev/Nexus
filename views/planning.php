<div class="panel panel-static" style="margin-bottom:16px;">
    <div class="panel-title-row">
        <div class="section-title"><?= th('planning.title') ?></div>
        <button class="btn btn-primary btn-sm" onclick="openTaskForm()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?= th('planning.new_btn') ?>
        </button>
    </div>
    <form id="filter-form" class="form-row" style="align-items:flex-end;flex-wrap:wrap;gap:12px;">
        <div class="form-group mb-0" style="min-width:140px;flex:1;">
            <label><?= th('planning.badge_done') ?></label>
            <select name="status" class="form-control">
                <option value=""><?= th('planning.all_statuses') ?></option>
                <option value="pending" <?= $filters['status']==='pending'?'selected':'' ?>><?= th('planning.pending') ?></option>
                <option value="done"    <?= $filters['status']==='done'   ?'selected':'' ?>><?= th('planning.done') ?></option>
            </select>
        </div>
        <div class="form-group mb-0" style="min-width:140px;flex:1;">
            <label><?= th('planning.priority') ?></label>
            <select name="priority" class="form-control">
                <option value=""><?= th('planning.all_priorities') ?></option>
                <option value="high"   <?= $filters['priority']==='high'  ?'selected':'' ?>><?= th('planning.high') ?></option>
                <option value="medium" <?= $filters['priority']==='medium'?'selected':'' ?>><?= th('planning.medium') ?></option>
                <option value="low"    <?= $filters['priority']==='low'   ?'selected':'' ?>><?= th('planning.low') ?></option>
            </select>
        </div>
        <div class="form-group mb-0" style="flex:0;">
            <label style="visibility:hidden;">Go</label>
            <button type="submit" class="btn btn-sm"><?= th('planning.filter_btn') ?></button>
        </div>
    </form>
</div>

<div class="panel panel-static">
    <div class="nexus-list" id="task-list">
        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">✅</div>
                <?= th('planning.no_tasks') ?><br>
                <button class="btn btn-primary btn-sm" onclick="openTaskForm()" style="margin-top:12px;"><?= th('planning.create_first') ?></button>
            </div>
        <?php endif; ?>
        <?php foreach ($tasks as $t):
            $isDone = $t['status'] === 'done';
            $badge  = $isDone ? th('planning.badge_done') : match($t['priority']) {
                'high'   => th('planning.badge_high'),
                'medium' => th('planning.badge_medium'),
                'low'    => th('planning.badge_low'),
                default  => $t['priority'],
            };
        ?>
        <div class="list-row task-row" data-id="<?= $t['id'] ?>">
            <label style="display:flex;align-items:center;gap:12px;cursor:pointer;flex:1;min-width:0;">
                <input type="checkbox" onchange="toggleTask(<?= $t['id'] ?>)" <?= $isDone?'checked':'' ?>
                       aria-label="<?= htmlspecialchars($t['title']) ?>">
                <div style="min-width:0;">
                    <div style="font-size:13px;font-weight:500;<?= $isDone?'text-decoration:line-through;color:var(--text-faint);':'color:var(--text-bright);' ?>white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars($t['title']) ?>
                    </div>
                    <?php if ($t['due_date']): ?>
                        <div style="font-size:11px;color:var(--text-faint);margin-top:2px;font-family:var(--font-mono);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="10" height="10" style="vertical-align:middle;margin-right:3px;"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
                            <?= date('d/m/Y', strtotime($t['due_date'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </label>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <span class="badge badge-<?= $isDone?'done':$t['priority'] ?>"><?= $badge ?></span>
                <button class="btn btn-icon btn-sm" onclick='openTaskForm(<?= json_encode($t) ?>)' title="<?= th('planning.modal_edit') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="btn btn-icon btn-sm btn-danger" onclick="deleteTask(<?= $t['id'] ?>)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="task-modal" class="nexus-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="task-modal-title">
    <div class="nexus-modal" style="max-width:420px;">
        <h3 id="task-modal-title"><?= th('planning.modal_new') ?></h3>
        <form id="task-form">
            <input type="hidden" name="id" id="task-id">
            <div class="form-group">
                <label><?= th('planning.title_label') ?></label>
                <input type="text" name="title" id="task-title" class="form-control" placeholder="<?= th('planning.title_ph') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= th('planning.deadline') ?></label>
                    <input type="date" name="due_date" id="task-date" class="form-control">
                </div>
                <div class="form-group">
                    <label><?= th('planning.priority') ?></label>
                    <select name="priority" id="task-priority" class="form-control">
                        <option value="low"><?= th('planning.low') ?></option>
                        <option value="medium" selected><?= th('planning.medium') ?></option>
                        <option value="high"><?= th('planning.high') ?></option>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><?= th('planning.save') ?></button>
                <button type="button" class="btn" onclick="document.getElementById('task-modal').classList.remove('open')"><?= th('planning.cancel') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openTaskForm(t) {
    const today=new Date().toISOString().slice(0,10);
    document.getElementById('task-modal').classList.add('open');
    document.getElementById('task-modal-title').textContent=t?'<?= th('planning.modal_edit') ?>':'<?= th('planning.modal_new') ?>';
    document.getElementById('task-id').value      =t?t.id:'';
    document.getElementById('task-title').value   =t?t.title:'';
    document.getElementById('task-date').value    =t?(t.due_date||''):today;
    document.getElementById('task-priority').value=t?t.priority:'medium';
}
document.getElementById('task-modal').addEventListener('click',(e)=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('open');});

document.getElementById('task-form').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const data=Object.fromEntries(new FormData(e.target));
    const url=data.id?'index.php?page=planning&action=update':'index.php?page=planning&action=store';
    const res=await NEXUS.post(url,data);
    if(res.success){ NEXUS.toast('<?= th('planning.saved') ?>','success'); setTimeout(()=>location.reload(),500); }
    else NEXUS.toast(res.error||'<?= th('common.error') ?>','error');
});

async function toggleTask(id) {
    const res=await NEXUS.post('index.php?page=planning&action=toggle',{id});
    if(res.success) setTimeout(()=>location.reload(),300);
    else NEXUS.toast(res.error||'<?= th('common.error') ?>','error');
}

async function deleteTask(id) {
    const ok=await NEXUS.confirm('<?= th('planning.confirm_delete') ?>','','🗑',true);
    if(!ok) return;
    const res=await NEXUS.post('index.php?page=planning&action=destroy',{id});
    if(res.success){ document.querySelector(`[data-id="${id}"]`)?.remove(); NEXUS.toast('<?= th('planning.deleted') ?>','success'); }
    else NEXUS.toast(res.error||'<?= th('common.error') ?>','error');
}

document.getElementById('filter-form').addEventListener('submit',(e)=>{
    e.preventDefault();
    const params=new URLSearchParams(new FormData(e.target));
    window.location.href='index.php?page=planning&'+params.toString();
});
</script>
