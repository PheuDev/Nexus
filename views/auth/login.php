<!DOCTYPE html>
<html lang="<?= nexus_locale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>NEXUS — <?= th('auth.login_title') ?></title>
    <meta name="description" content="Connectez-vous à votre espace NEXUS.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="assets/icons/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        #login-submit{width:100%;margin-top:6px}
        #login-submit.loading{opacity:.75}
        .auth-sep{display:flex;align-items:center;gap:12px;margin:20px 0;color:#2a3040;font-size:11px;letter-spacing:1px;text-transform:uppercase;font-family:'JetBrains Mono',monospace}
        .auth-sep::before,.auth-sep::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,transparent,rgba(100,150,255,.15),transparent)}
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
        <p class="subtitle"><?= th('auth.subtitle_login') ?></p>

        <div class="auth-error" id="auth-error" role="alert" aria-live="polite"></div>

        <form id="login-form" novalidate autocomplete="on">
            <div class="form-group">
                <label for="login-email"><?= th('auth.email') ?></label>
                <input type="email" id="login-email" name="email" class="form-control"
                       placeholder="vous@gmail.com" autocomplete="email" required autofocus>
            </div>
            <div class="form-group" style="margin-bottom:8px;">
                <label for="login-password"><?= th('auth.password') ?></label>
                <div style="position:relative;">
                    <input type="password" id="login-password" name="password" class="form-control"
                           placeholder="••••••••" autocomplete="current-password" required style="padding-right:44px;">
                    <button type="button" id="toggle-password"
                            aria-label="<?= th('auth.password') ?>"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#4a5268;cursor:pointer;padding:4px;display:flex;align-items:center;">
                        <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="16" height="16">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div style="text-align:right;margin-bottom:14px;">
                <a href="index.php?page=forgot" style="font-size:12px;color:var(--text-faint);transition:color .2s ease;"
                   onmouseover="this.style.color='var(--cyan)'" onmouseout="this.style.color='var(--text-faint)'">
                    <?= th('auth.forgot_link') ?>
                </a>
            </div>
            <button type="submit" id="login-submit" class="btn btn-primary btn-lg">
                <?= th('auth.login_btn') ?>
            </button>
        </form>

        <div class="auth-sep"><?= th('common.or') ?></div>
        <div class="auth-switch"><?= th('auth.no_account') ?> <a href="index.php?page=register"><?= th('auth.create_access') ?></a></div>
        <div class="auth-switch"><a href="index.php?page=privacy">Confidentialité</a></div>

    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
document.getElementById('toggle-password').addEventListener('click',function(){
    const inp=document.getElementById('login-password'),icon=document.getElementById('eye-icon'),h=inp.type==='password';
    inp.type=h?'text':'password';
    icon.innerHTML=h?'<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>':'<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    this.style.color=h?'var(--cyan)':'#4a5268';
});

document.getElementById('login-form').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const btn=document.getElementById('login-submit'),errorBox=document.getElementById('auth-error');
    errorBox.style.display='none';
    btn.classList.add('loading');
    btn.disabled=true;
    btn.textContent='<?= th('auth.authenticating') ?>';
    const data=Object.fromEntries(new FormData(e.target));
    const res=await NEXUS.post('index.php?page=login&action=login',data);
    if(res.success){
        btn.textContent='<?= th('auth.access_granted') ?>';
        setTimeout(()=>{window.location.href=res.redirect;},400);
    } else {
        btn.classList.remove('loading');
        btn.disabled=false;
        btn.textContent='<?= th('auth.login_btn') ?>';
        errorBox.textContent=res.error||'<?= th('auth.login_btn') ?>';
        errorBox.style.display='block';
        document.getElementById('login-email').style.borderColor='var(--danger)';
        document.getElementById('login-password').style.borderColor='var(--danger)';
        setTimeout(()=>{document.getElementById('login-email').style.borderColor='';document.getElementById('login-password').style.borderColor='';},2000);
    }
});
</script>
</body>
</html>
