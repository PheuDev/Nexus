<!DOCTYPE html>
<html lang="<?= nexus_locale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>NEXUS — <?= th('auth.forgot_title') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="assets/icons/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-card{animation:authCardIn .6s cubic-bezier(.34,1.2,.64,1)}
        @keyframes authCardIn{from{opacity:0;transform:translateY(30px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
        #forgot-submit{width:100%;margin-top:6px}
        .reset-success{display:none;padding:16px;border-radius:var(--radius-sm);background:rgba(61,220,151,.08);border:1px solid rgba(61,220,151,.3);font-size:13px;color:var(--text-dim);line-height:1.7}
        .dev-link-box{margin-top:12px;padding:12px;background:rgba(91,140,255,.06);border:1px solid var(--border-soft);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:11px;word-break:break-all;color:var(--cyan)}
        .dev-link-box .label{display:block;font-size:9px;letter-spacing:1px;text-transform:uppercase;color:var(--text-faint);margin-bottom:6px}
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" role="main">
        <div class="auth-logo">
            <div class="auth-logo-ring" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="rgba(0,217,255,0.9)" stroke-width="1.5" stroke-linecap="round" width="22" height="22">
                    <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/>
                    <path d="M12 3v2M12 19v2M3 12h2M19 12h2" opacity=".5"/>
                </svg>
            </div>
        </div>
        <h1>NEXUS</h1>
        <p class="subtitle"><?= th('auth.subtitle_forgot') ?></p>

        <div class="auth-error" id="auth-error" role="alert" aria-live="polite"></div>

        <div id="form-section">
            <p style="font-size:13px;color:var(--text-dim);margin-bottom:20px;line-height:1.6;">
                <?= th('auth.forgot_text') ?>
            </p>
            <form id="forgot-form" novalidate>
                <div class="form-group">
                    <label for="forgot-email"><?= th('auth.email') ?></label>
                    <input type="email" id="forgot-email" name="email" class="form-control"
                           placeholder="vous@gmail.com" autocomplete="email" required autofocus>
                </div>
                <button type="submit" id="forgot-submit" class="btn btn-primary btn-lg">
                    <?= th('auth.forgot_btn') ?>
                </button>
            </form>
        </div>

        <div class="reset-success" id="success-box" role="status">
            <strong style="color:var(--success);">✓ <?= th('auth.reset_sent') ?></strong><br>
            <?= th('auth.reset_sent_text') ?>
            <br><small style="color:var(--text-faint);"><?= th('auth.check_spam') ?></small>
            <div class="dev-link-box" id="dev-link-box" style="display:none;">
                <span class="label"><?= th('auth.dev_mode_link') ?></span>
                <a id="dev-link-url" href="#" style="color:var(--cyan);">–</a>
            </div>
        </div>

        <div class="auth-switch" style="margin-top:20px;">
            <a href="index.php?page=login"><?= th('auth.back_login') ?></a>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
document.getElementById('forgot-form').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const btn=document.getElementById('forgot-submit'),errorBox=document.getElementById('auth-error');
    errorBox.style.display='none';
    btn.disabled=true; btn.textContent='<?= th('auth.sending') ?>';
    const data=Object.fromEntries(new FormData(e.target));
    const res=await NEXUS.post('index.php?page=forgot&action=send',data);
    if(res.success){
        document.getElementById('form-section').style.display='none';
        document.getElementById('success-box').style.display='block';
        if(res.dev_link){
            document.getElementById('dev-link-box').style.display='block';
            const url=document.getElementById('dev-link-url');
            url.href=res.dev_link; url.textContent=res.dev_link;
        }
    } else {
        errorBox.textContent=res.error||'Error';
        errorBox.style.display='block';
        btn.disabled=false; btn.textContent='<?= th('auth.forgot_btn') ?>';
    }
});
</script>
</body>
</html>
