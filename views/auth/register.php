<!DOCTYPE html>
<html lang="<?= nexus_locale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>NEXUS — <?= th('auth.register_title') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="assets/icons/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-card{animation:authCardIn .6s cubic-bezier(.34,1.2,.64,1)}
        @keyframes authCardIn{from{opacity:0;transform:translateY(30px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
        #register-submit{width:100%;margin-top:6px;position:relative;overflow:hidden}
        #register-submit.loading{pointer-events:none;opacity:.7}
        #register-submit.loading::after{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.15),transparent);animation:btnShimmer 1s ease infinite}
        @keyframes btnShimmer{to{left:100%}}
        .password-strength{display:flex;gap:4px;margin-top:8px}
        .strength-bar{flex:1;height:3px;border-radius:2px;background:rgba(255,255,255,.07);transition:background .3s ease}
        .strength-bar.weak{background:var(--danger)}.strength-bar.medium{background:var(--warning)}.strength-bar.strong{background:var(--success)}
        .strength-label{font-size:10px;color:var(--text-faint);margin-top:4px;font-family:'JetBrains Mono',monospace}
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
        <p class="subtitle"><?= th('auth.subtitle_register') ?></p>

        <div class="auth-error" id="auth-error" role="alert" aria-live="polite"></div>

        <form id="register-form" novalidate autocomplete="on">
            <div class="form-group">
                <label for="reg-name"><?= th('auth.name') ?></label>
                <input type="text" id="reg-name" name="name" class="form-control"
                       placeholder="<?= th('auth.name') ?>" autocomplete="name" minlength="2" required autofocus>
            </div>
            <div class="form-group">
                <label for="reg-email"><?= th('auth.email') ?></label>
                <input type="email" id="reg-email" name="email" class="form-control"
                       placeholder="vous@gmail.com" autocomplete="email" required>
            </div>
            <div class="form-group" style="margin-bottom:4px;">
                <label for="reg-password"><?= th('auth.password') ?> <span style="color:var(--text-muted);font-size:9px;text-transform:none;"><?= th('auth.password_min') ?></span></label>
                <div style="position:relative;">
                    <input type="password" id="reg-password" name="password" class="form-control"
                           placeholder="••••••••" autocomplete="new-password" minlength="8" required style="padding-right:44px;">
                    <button type="button" id="toggle-password"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#4a5268;cursor:pointer;padding:4px;display:flex;align-items:center;">
                        <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="16" height="16">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <div class="password-strength" aria-hidden="true">
                    <div class="strength-bar" id="sb1"></div><div class="strength-bar" id="sb2"></div>
                    <div class="strength-bar" id="sb3"></div><div class="strength-bar" id="sb4"></div>
                </div>
                <div class="strength-label" id="strength-label"></div>
            </div>
            <button type="submit" id="register-submit" class="btn btn-primary btn-lg">
                <?= th('auth.register_btn') ?>
            </button>
        </form>

        <div class="auth-switch" style="margin-top:18px;">
            <?= th('auth.already_account') ?> <a href="index.php?page=login"><?= th('auth.sign_in') ?></a>
        </div>
        <div class="auth-switch"><a href="index.php?page=privacy">Confidentialité</a></div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const _authT = {
    short:  '<?= th('auth.strength_short') ?>',
    weak:   '<?= th('auth.strength_weak') ?>',
    medium: '<?= th('auth.strength_medium') ?>',
    strong: '<?= th('auth.strength_strong') ?>',
    very:   '<?= th('auth.strength_very') ?>',
};

document.getElementById('toggle-password').addEventListener('click',function(){
    const inp=document.getElementById('reg-password'),icon=document.getElementById('eye-icon'),h=inp.type==='password';
    inp.type=h?'text':'password';
    icon.innerHTML=h?'<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>':'<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    this.style.color=h?'var(--cyan)':'#4a5268';
});

document.getElementById('reg-password').addEventListener('input',function(){
    const v=this.value;
    let score=0;
    if(v.length>=8) score++;
    if(v.length>=12) score++;
    if(/[A-Z]/.test(v)&&/[0-9]/.test(v)) score++;
    if(/[^A-Za-z0-9]/.test(v)) score++;
    const levels=[{cls:'weak',text:_authT.short},{cls:'weak',text:_authT.weak},{cls:'medium',text:_authT.medium},{cls:'strong',text:_authT.strong},{cls:'strong',text:_authT.very}];
    ['sb1','sb2','sb3','sb4'].forEach((id,i)=>{const el=document.getElementById(id);el.className='strength-bar';if(i<score)el.classList.add(levels[score].cls);});
    const lbl=document.getElementById('strength-label');
    lbl.textContent=v.length?levels[score].text:'';
    lbl.style.color=score<=1?'var(--danger)':score===2?'var(--warning)':'var(--success)';
});

document.getElementById('register-form').addEventListener('submit', async(e)=>{
    e.preventDefault();
    const btn=document.getElementById('register-submit'),errorBox=document.getElementById('auth-error');
    errorBox.style.display='none';
    btn.classList.add('loading');
    btn.textContent='<?= th('auth.initializing') ?>';
    const data=Object.fromEntries(new FormData(e.target));
    const res=await NEXUS.post('index.php?page=register&action=register',data);
    if(res.success){
        btn.textContent='<?= th('auth.initialized') ?>';
        btn.style.background='linear-gradient(100deg,#1faa6e,#3ddc97)';
        setTimeout(()=>{window.location.href=res.redirect;},500);
    } else {
        btn.classList.remove('loading');
        btn.textContent='<?= th('auth.register_btn') ?>';
        errorBox.textContent=res.error||'Error';
        errorBox.style.display='block';
    }
});
</script>
</body>
</html>
