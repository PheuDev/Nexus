<?php
$cur = currency_symbol($_SESSION['user_currency'] ?? 'EUR');
$p   = $pagination;
?>
<!-- Filtres -->
<div class="panel panel-static" style="margin-bottom:16px;">
    <div class="panel-title-row">
        <div class="section-title"><?= th('finance.filters') ?></div>
        <div style="display:flex;gap:8px;">
            <a href="index.php?page=finances&action=export&type=<?= urlencode($filters['type']) ?>&from=<?= urlencode($filters['from']) ?>&to=<?= urlencode($filters['to']) ?>"
               class="btn btn-sm export-btn" title="<?= th('finance.csv_btn') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <?= th('finance.csv_btn') ?>
            </a>
            <button class="btn btn-sm" onclick="openCatPanel()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                <?= th('finance.categories_btn') ?>
            </button>
            <button class="btn btn-primary btn-sm" onclick="openTxForm()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <?= th('finance.new_tx') ?>
            </button>
        </div>
    </div>
    <form id="filter-form" class="form-row" style="align-items:flex-end;flex-wrap:wrap;gap:12px;">
        <div class="form-group mb-0" style="min-width:130px;flex:1;">
            <label><?= th('finance.type') ?></label>
            <select name="type" class="form-control">
                <option value=""><?= th('finance.all_types') ?></option>
                <option value="income"  <?= $filters['type']==='income'  ? 'selected':'' ?>><?= th('finance.income') ?></option>
                <option value="expense" <?= $filters['type']==='expense' ? 'selected':'' ?>><?= th('finance.expense') ?></option>
            </select>
        </div>
        <div class="form-group mb-0" style="min-width:160px;flex:2;">
            <label><?= th('finance.search_label') ?></label>
            <input type="text" name="q" class="form-control" placeholder="<?= th('finance.search_ph') ?>" value="<?= htmlspecialchars($filters['search']) ?>">
        </div>
        <div class="form-group mb-0" style="min-width:130px;flex:1;">
            <label><?= th('finance.from') ?></label>
            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filters['from']) ?>">
        </div>
        <div class="form-group mb-0" style="min-width:130px;flex:1;">
            <label><?= th('finance.to') ?></label>
            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filters['to']) ?>">
        </div>
        <div class="form-group mb-0" style="flex:0;">
            <label style="visibility:hidden;">Go</label>
            <button type="submit" class="btn btn-sm"><?= th('finance.filter_btn') ?></button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="panel panel-static">
    <div class="flex-between" style="margin-bottom:14px;font-size:12px;color:var(--text-faint);">
        <span><?= $p['total'] ?> <?= th('finance.tx_count', ['{n}' => '']) ?> — <?= th('finance.page') ?> <span style="color:var(--cyan);font-family:var(--font-mono);"><?= $p['page'] ?></span> / <?= $p['pages'] ?></span>
        <?php if ($p['total'] > 0): ?><span><?= $p['perPage'] ?> <?= th('finance.per_page') ?></span><?php endif; ?>
    </div>
    <?php if (empty($transactions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">💳</div>
            <?= th('finance.no_tx') ?><br>
            <button class="btn btn-sm btn-primary" onclick="openTxForm()" style="margin-top:12px;"><?= th('finance.add_first') ?></button>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="nexus-table">
            <thead><tr>
                <th><?= th('finance.date') ?></th>
                <th><?= th('finance.label') ?></th>
                <th><?= th('finance.category') ?></th>
                <th><?= th('finance.type') ?></th>
                <th style="text-align:right;"><?= th('finance.amount') ?></th>
                <th style="width:80px;"></th>
            </tr></thead>
            <tbody id="tx-tbody">
            <?php foreach ($transactions as $t): ?>
                <tr data-id="<?= $t['id'] ?>">
                    <td><span class="text-mono text-xs" style="color:var(--text-faint);"><?= date('d/m/Y', strtotime($t['occurred_on'])) ?></span></td>
                    <td style="font-weight:500;color:var(--text-bright);"><?= htmlspecialchars($t['label']) ?></td>
                    <td>
                        <?php if ($t['category_name']): ?>
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;">
                                <span class="tag-dot" style="background:<?= htmlspecialchars($t['category_color'] ?? '#5b8cff') ?>"></span>
                                <?= htmlspecialchars($t['category_name']) ?>
                            </span>
                        <?php else: ?><span style="color:var(--text-muted);font-size:12px;">—</span><?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $t['type']==='income' ? 'badge-active' : '' ?>"
                              style="<?= $t['type']!=='income' ? 'color:var(--danger);background:var(--danger-dim);border-color:rgba(255,95,109,0.2);':'' ?>">
                            <?= $t['type']==='income' ? th('finance.income') : th('finance.expense') ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <span class="amount <?= $t['type'] ?>" style="font-size:14px;">
                            <?= $t['type']==='income' ? '+' : '−' ?><?= number_format($t['amount'],2,',',' ') ?> <?= $cur ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;justify-content:flex-end;">
                            <button class="btn btn-icon btn-sm" onclick='openTxForm(<?= json_encode($t) ?>)' title="<?= th('finance.edit') ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button class="btn btn-icon btn-sm btn-danger" onclick="deleteTx(<?= $t['id'] ?>)" title="<?= th('finance.delete') ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($p['pages'] > 1):
        $queryParams = array_filter(['page'=>'finances','type'=>$filters['type'],'q'=>$filters['search'],'from'=>$filters['from'],'to'=>$filters['to']]);
    ?>
    <div class="pagination" style="display:flex;align-items:center;justify-content:center;gap:6px;padding-top:20px;border-top:1px solid var(--border-faint);margin-top:16px;">
        <?php if ($p['page']>1): ?>
            <a href="?<?= http_build_query(array_merge($queryParams,['p'=>$p['page']-1])) ?>" class="btn btn-sm">‹</a>
        <?php else: ?>
            <button class="btn btn-sm" disabled style="opacity:.3;cursor:default;">‹</button>
        <?php endif; ?>
        <?php
        $start=max(1,$p['page']-2); $end=min($p['pages'],$p['page']+2);
        if($start>1){echo '<a href="?'.http_build_query(array_merge($queryParams,['p'=>1])).'" class="btn btn-sm">1</a>';if($start>2)echo '<span style="color:var(--text-faint);padding:0 4px;">…</span>';}
        for($i=$start;$i<=$end;$i++){
            if($i===$p['page']) echo "<span class='btn btn-sm btn-primary' style='min-width:34px;cursor:default;'>$i</span>";
            else echo "<a href='?".http_build_query(array_merge($queryParams,['p'=>$i]))."' class='btn btn-sm' style='min-width:34px;'>$i</a>";
        }
        if($end<$p['pages']){if($end<$p['pages']-1)echo '<span style="color:var(--text-faint);padding:0 4px;">…</span>';echo '<a href="?'.http_build_query(array_merge($queryParams,['p'=>$p['pages']])).'" class="btn btn-sm">'.$p['pages'].'</a>';}
        ?>
        <?php if ($p['page']<$p['pages']): ?>
            <a href="?<?= http_build_query(array_merge($queryParams,['p'=>$p['page']+1])) ?>" class="btn btn-sm">›</a>
        <?php else: ?>
            <button class="btn btn-sm" disabled style="opacity:.3;cursor:default;">›</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal transaction -->
<div id="tx-modal" class="nexus-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="tx-modal-title">
    <div class="nexus-modal" style="max-width:460px;">
        <h3 id="tx-modal-title"><?= th('finance.modal_new') ?></h3>
        <form id="tx-form">
            <input type="hidden" name="id" id="tx-id">
            <div class="form-row">
                <div class="form-group">
                    <label><?= th('finance.type_label') ?></label>
                    <select name="type" id="tx-type" class="form-control" required>
                        <option value="expense"><?= th('finance.expense') ?></option>
                        <option value="income"><?= th('finance.income') ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= th('finance.amount_label') ?> (<?= $cur ?>)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="tx-amount" class="form-control" placeholder="0.00" required>
                </div>
            </div>
            <div class="form-group">
                <label><?= th('finance.label_label') ?></label>
                <input type="text" name="label" id="tx-label" class="form-control" placeholder="<?= th('finance.label_ph') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= th('finance.cat_label') ?></label>
                    <select name="category_id" id="tx-category" class="form-control">
                        <option value=""><?= th('finance.no_cat_opt') ?></option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= th('finance.date_label') ?></label>
                    <input type="date" name="occurred_on" id="tx-date" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= th('finance.recurrence') ?></label>
                    <select name="recurrence" id="tx-recurrence" class="form-control">
                        <option value="none"><?= th('finance.rec_none') ?></option>
                        <option value="daily"><?= th('finance.rec_daily') ?></option>
                        <option value="weekly"><?= th('finance.rec_weekly') ?></option>
                        <option value="monthly"><?= th('finance.rec_monthly') ?></option>
                        <option value="yearly"><?= th('finance.rec_yearly') ?></option>
                    </select>
                </div>
                <div class="form-group" id="tx-recurrence-end-wrap" style="display:none;">
                    <label><?= th('finance.rec_end') ?></label>
                    <input type="date" name="recurrence_end" id="tx-recurrence-end" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label><?= th('finance.note') ?></label>
                <textarea name="note" id="tx-note" class="form-control" rows="2" placeholder="<?= th('finance.note_ph') ?>" style="resize:vertical;min-height:60px;"></textarea>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><?= th('finance.save') ?></button>
                <button type="button" class="btn" onclick="closeTxForm()"><?= th('finance.cancel') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Panneau catégories -->
<div id="cat-panel-backdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:200;align-items:flex-start;justify-content:flex-end;">
    <div id="cat-panel" style="width:380px;height:100vh;overflow-y:auto;background:linear-gradient(180deg,var(--bg-panel),var(--bg-deep));border-left:1px solid var(--border-mid);padding:28px 24px;display:flex;flex-direction:column;gap:20px;animation:slideInRight .3s var(--ease-out);">
        <div class="flex-between">
            <div class="section-title"><?= th('finance.cat_panel_title') ?></div>
            <button class="btn btn-icon btn-sm" onclick="closeCatPanel()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="panel" style="padding:16px;">
            <h3 id="cat-form-title" style="margin-bottom:14px;font-size:12px;color:var(--text-dim);"><?= th('finance.cat_new') ?></h3>
            <form id="cat-form">
                <input type="hidden" name="id" id="cat-id">
                <div class="form-group">
                    <label><?= th('finance.cat_name') ?></label>
                    <input type="text" name="name" id="cat-name" class="form-control" placeholder="<?= th('finance.cat_name_ph') ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?= th('finance.cat_type') ?></label>
                        <select name="type" id="cat-type" class="form-control">
                            <option value="expense"><?= th('finance.expense') ?></option>
                            <option value="income"><?= th('finance.income') ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= th('finance.cat_color') ?></label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input type="color" name="color" id="cat-color" value="#00d9ff"
                                   style="width:40px;height:38px;border:1px solid var(--border-soft);border-radius:var(--radius-sm);background:var(--bg-input);cursor:pointer;padding:2px;">
                            <span id="cat-color-preview" style="font-family:var(--font-mono);font-size:11px;color:var(--text-faint);">#00d9ff</span>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex:1;"><?= th('finance.save') ?></button>
                    <button type="button" class="btn btn-sm" onclick="resetCatForm()" id="cat-cancel-btn" style="display:none;"><?= th('finance.cancel') ?></button>
                </div>
            </form>
        </div>
        <div>
            <div class="section-title" style="margin-bottom:12px;"><?= th('finance.cat_list_title') ?></div>
            <div id="cat-list" style="display:flex;flex-direction:column;gap:6px;">
                <?php if (empty($categories)): ?>
                    <div class="empty-state" style="padding:20px 0;"><?= th('finance.cat_none') ?></div>
                <?php endif; ?>
                <?php foreach ($categories as $c): ?>
                <div class="list-row" data-cat-id="<?= $c['id'] ?>">
                    <div class="list-row-main">
                        <span class="tag-dot" style="background:<?= htmlspecialchars($c['color'] ?? '#5b8cff') ?>;width:10px;height:10px;"></span>
                        <span style="font-size:13px;font-weight:500;"><?= htmlspecialchars($c['name']) ?></span>
                        <span class="badge <?= $c['type']==='income' ? 'badge-active' : '' ?>"
                              style="<?= $c['type']!=='income' ? 'color:var(--danger);background:var(--danger-dim);':'' ?>;font-size:9px;">
                            <?= $c['type']==='income' ? th('finance.income') : th('finance.expense') ?>
                        </span>
                    </div>
                    <div style="display:flex;gap:4px;">
                        <button class="btn btn-icon btn-sm" onclick='editCat(<?= json_encode($c) ?>)' title="<?= th('finance.edit') ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="12" height="12"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="btn btn-icon btn-sm btn-danger" onclick="deleteCat(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')" title="<?= th('finance.delete') ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="12" height="12"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes slideInRight { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
</style>

<script>
const _t = {
    added:      '<?= th('finance.added') ?>',
    updated:    '<?= th('finance.updated') ?>',
    deleted:    '<?= th('finance.deleted') ?>',
    catSaved:   '<?= th('finance.cat_saved') ?>',
    catUpdated: '<?= th('finance.cat_updated') ?>',
    catDeleted: '<?= th('finance.cat_deleted') ?>',
    confirmDel: '<?= th('finance.confirm_delete') ?>',
    error:      '<?= th('finance.error') ?>',
    catNew:     '<?= th('finance.cat_new') ?>',
    catEdit:    '<?= th('finance.cat_edit') ?>',
    cancel:     '<?= th('finance.cancel') ?>',
};

function openTxForm(tx) {
    const today = new Date().toISOString().slice(0,10);
    document.getElementById('tx-modal').classList.add('open');
    document.getElementById('tx-modal-title').textContent = tx ? '<?= th('finance.modal_edit') ?>' : '<?= th('finance.modal_new') ?>';
    document.getElementById('tx-id').value             = tx ? tx.id : '';
    document.getElementById('tx-type').value           = tx ? tx.type : 'expense';
    document.getElementById('tx-amount').value         = tx ? tx.amount : '';
    document.getElementById('tx-label').value          = tx ? tx.label : '';
    document.getElementById('tx-category').value       = tx ? (tx.category_id||'') : '';
    document.getElementById('tx-date').value           = tx ? tx.occurred_on : today;
    document.getElementById('tx-recurrence').value     = tx ? (tx.recurrence||'none') : 'none';
    document.getElementById('tx-recurrence-end').value = tx ? (tx.recurrence_end||'') : '';
    document.getElementById('tx-note').value           = tx ? (tx.note||'') : '';
    toggleRecurrenceEnd();
}
function closeTxForm() { document.getElementById('tx-modal').classList.remove('open'); }
function toggleRecurrenceEnd() {
    const val=document.getElementById('tx-recurrence')?.value;
    const wrap=document.getElementById('tx-recurrence-end-wrap');
    if(wrap) wrap.style.display=(val&&val!=='none')?'block':'none';
}
document.getElementById('tx-recurrence')?.addEventListener('change',toggleRecurrenceEnd);
document.getElementById('tx-modal').addEventListener('click',(e)=>{if(e.target===e.currentTarget)closeTxForm();});

document.getElementById('tx-form').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const data=Object.fromEntries(new FormData(e.target));
    const url=data.id?'index.php?page=finances&action=update':'index.php?page=finances&action=store';
    const res=await NEXUS.post(url,data);
    if(res.success){ NEXUS.toast(data.id?_t.updated:_t.added,'success'); setTimeout(()=>window.location.reload(),600); }
    else NEXUS.toast(res.error||_t.error,'error');
});

async function deleteTx(id) {
    const ok=await NEXUS.confirm(_t.confirmDel,'','🗑',true);
    if(!ok) return;
    const res=await NEXUS.post('index.php?page=finances&action=destroy',{id});
    if(res.success){ document.querySelector(`tr[data-id="${id}"]`)?.remove(); NEXUS.toast(_t.deleted,'success'); }
    else NEXUS.toast(res.error||_t.error,'error');
}

document.getElementById('filter-form').addEventListener('submit',(e)=>{
    e.preventDefault();
    const params=new URLSearchParams(new FormData(e.target));
    window.location.href='index.php?page=finances&'+params.toString();
});

// Catégories
function openCatPanel(){ document.getElementById('cat-panel-backdrop').style.display='flex'; }
function closeCatPanel(){ document.getElementById('cat-panel-backdrop').style.display='none'; }
document.getElementById('cat-panel-backdrop').addEventListener('click',(e)=>{ if(e.target===e.currentTarget)closeCatPanel(); });
document.getElementById('cat-color').addEventListener('input',function(){
    document.getElementById('cat-color-preview').textContent=this.value;
    document.getElementById('cat-color-preview').style.color=this.value;
});
function resetCatForm(){
    document.getElementById('cat-form').reset();
    document.getElementById('cat-id').value='';
    document.getElementById('cat-form-title').textContent=_t.catNew;
    document.getElementById('cat-cancel-btn').style.display='none';
    document.getElementById('cat-color-preview').textContent='#00d9ff';
    document.getElementById('cat-color-preview').style.color='';
}
function editCat(cat){
    document.getElementById('cat-id').value=cat.id;
    document.getElementById('cat-name').value=cat.name;
    document.getElementById('cat-type').value=cat.type;
    document.getElementById('cat-color').value=cat.color||'#00d9ff';
    document.getElementById('cat-color-preview').textContent=cat.color||'#00d9ff';
    document.getElementById('cat-color-preview').style.color=cat.color||'#00d9ff';
    document.getElementById('cat-form-title').textContent=_t.catEdit;
    document.getElementById('cat-cancel-btn').style.display='block';
    document.getElementById('cat-name').focus();
}
document.getElementById('cat-form').addEventListener('submit',async(e)=>{
    e.preventDefault();
    const data=Object.fromEntries(new FormData(e.target));
    const url=data.id?'index.php?page=finances&action=update_category':'index.php?page=finances&action=store_category';
    const res=await NEXUS.post(url,data);
    if(res.success){ NEXUS.toast(data.id?_t.catUpdated:_t.catSaved,'success'); setTimeout(()=>window.location.reload(),500); }
    else NEXUS.toast(res.error||_t.error,'error');
});
async function deleteCat(id,name){
    const msg='<?= th('finance.cat_confirm_del') ?>'.replace('{name}',name);
    const ok=await NEXUS.confirm(msg,'','🗑',true);
    if(!ok) return;
    const res=await NEXUS.post('index.php?page=finances&action=destroy_category',{id});
    if(res.success){ document.querySelector(`[data-cat-id="${id}"]`)?.remove(); NEXUS.toast(_t.catDeleted,'success'); }
    else NEXUS.toast(res.error||_t.error,'error');
}

<?php if (!empty($quickType)): ?>
openTxForm();
document.getElementById('tx-type').value = <?= json_encode($quickType) ?>;
<?php endif; ?>
</script>
