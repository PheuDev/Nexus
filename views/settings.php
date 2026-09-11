<?php
/**
PARAMÈTRES — Préférences utilisateur + suppression de compte
 */
?>

<div class="grid grid-2">

    <!--PRÉFÉRENCES-->
    <div class="panel panel-static">
        <div class="panel-title-row" style="margin-bottom:20px;">
            <div class="section-title"><?= th('settings.preferences') ?></div>
            <div class="panel-icon cyan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/>
                </svg>
            </div>
        </div>

        <form id="settings-form">

            <div class="form-group">
                <label><?= th('settings.currency') ?></label>
                <select name="currency" class="form-control">
                    <?php foreach (['EUR' => '€ Euro', 'USD' => '$ Dollar US', 'GBP' => '£ Livre Sterling', 'XOF' => 'F CFA (UEMOA)'] as $code => $label): ?>
                        <option value="<?= $code ?>" <?= $user['currency'] === $code ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><?= th('settings.theme') ?></label>
                <select name="theme" class="form-control">
                    <option value="dark"  <?= $user['theme'] === 'dark'  ? 'selected' : '' ?>><?= th('settings.theme_dark') ?></option>
                    <option value="light" <?= $user['theme'] === 'light' ? 'selected' : '' ?>><?= th('settings.theme_light') ?></option>
                </select>
            </div>

            <!-- Sélecteur de langue suivant la configuration système -->
            <div class="form-group">
                <label><?= th('settings.language') ?></label>
                <select name="locale" class="form-control" id="locale-select">
                    <?php
                    $currentLocale = nexus_locale();
                    $langs = t('languages');
                    foreach ($langs as $code => $label):
                    ?>
                    <option value="<?= $code ?>" <?= $currentLocale === $code ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Toggle notifications -->
            <div class="form-group">
                <label><?= th('settings.notifications') ?></label>
                <label class="nexus-toggle" style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                    <input type="checkbox"
                           name="notifications_enabled"
                           id="notifs"
                           <?= $user['notifications_enabled'] ? 'checked' : '' ?>>
                    <span style="font-size:13px; color:var(--text-dim); text-transform:none; letter-spacing:0; font-weight:400;">
                        <?= th('settings.notif_label') ?>
                    </span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
                <?= th('settings.save_btn') ?>
            </button>

        </form>
    </div>

    <!--ZONE DE DANGER-->
    <div class="panel panel-static danger-zone">
        <div class="panel-title-row" style="margin-bottom:20px;">
            <div class="section-title" style="color:var(--danger);">
                <?= th('settings.danger_zone') ?>
            </div>
            <div class="panel-icon red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>

        <div style="padding:14px; border-radius:var(--radius-sm); background:rgba(255,95,109,0.05); border:1px solid rgba(255,95,109,0.15); margin-bottom:20px;">
            <p style="margin:0; font-size:12.5px; color:var(--text-dim); line-height:1.7;">
                <?= th('settings.danger_text') ?>
            </p>
        </div>

        <form id="delete-form">
            <div class="form-group">
                <label style="color:var(--danger);">
                    <?= th('settings.confirm_label') ?>
                    <code style="background:rgba(255,95,109,0.12); padding:1px 6px; border-radius:4px; font-family:var(--font-mono); letter-spacing:1px;">
                        <?= th('settings.confirm_word') ?>
                    </code>
                </label>
                <input type="text" name="confirm" class="form-control"
                       placeholder="<?= th('settings.confirm_word') ?>"
                       autocomplete="off"
                       style="border-color:rgba(255,95,109,0.25);">
            </div>
            <button type="submit" class="btn btn-danger" style="width:100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="14" height="14">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    <path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
                </svg>
                <?= th('settings.delete_btn') ?>
            </button>
        </form>

    </div>

</div>

<script>
document.getElementById('settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    if (!fd.has('notifications_enabled')) fd.set('notifications_enabled', '');
    const data = Object.fromEntries(fd);
    const prevLocale = document.documentElement.lang || 'fr';
    const res  = await NEXUS.post('index.php?page=settings&action=update', data);
    if (res.success) {
        document.documentElement.setAttribute('data-theme', data.theme);
        if (res.conversion) {
            const r = res.conversion;
            const rateDisplay = r.rate >= 1
                ? `1 ${r.from} = ${r.rate.toLocaleString(undefined, {maximumFractionDigits: 4})} ${r.to}`
                : `1 ${r.to} = ${(1/r.rate).toLocaleString(undefined, {maximumFractionDigits: 4})} ${r.from}`;
            NEXUS.toast(`${rateDisplay}`, 'success');
            setTimeout(() => window.location.reload(), 1800);
        } else if (res.locale && res.locale !== prevLocale) {
            // Langue changée — rechargement nécessaire pour appliquer les traductions PHP
            window.location.reload();
        } else {
            NEXUS.toast('✓', 'success');
        }
    } else {
        NEXUS.toast(res.error || 'Error.', 'error');
    }
});

document.getElementById('delete-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const ok = await NEXUS.confirm(
        document.querySelector('[name="confirm"]').placeholder + ' ?',
        '⚠',
        '⚠',
        true
    );
    if (!ok) return;
    const data = Object.fromEntries(new FormData(e.target));
    const res  = await NEXUS.post('index.php?page=settings&action=delete_account', data);
    if (res.success) {
        window.location.href = res.redirect;
    } else {
        NEXUS.toast(res.error || 'Error.', 'error');
    }
});
</script>
