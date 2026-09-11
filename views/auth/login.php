<!DOCTYPE html>
<html lang="<?= nexus_locale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>NEXUS — <?= th('auth.login_title') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-particles{position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:0}
        .particle{position:absolute;border-radius:50%;animation:particleFloat linear infinite;opacity:0}
        @keyframes particleFloat{0%{opacity:0;transform:translateY(100vh) scale(0)}10%{opacity:.5}90%{opacity:.2}100%{opacity:0;transform:translateY(-10vh) scale(1)}}
        .auth-scanline{position:fixed;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,rgba(0,217,255,.3),transparent);animation:scanDown 6s ease-in-out infinite;z-index:0;pointer-events:none}
        @keyframes scanDown{0%{top:-2px;opacity:0}5%{opacity:1}95%{opacity:.3}100%{top:100vh;opacity:0}}
        .auth-corner-glow{position:fixed;width:400px;height:400px;border-radius:50%;pointer-events:none;z-index:0}
        .auth-corner-glow.tl{top:-150px;left:-150px;background:radial-gradient(circle,rgba(0,80,200,.1) 0%,transparent 65%)}
        .auth-corner-glow.br{bottom:-150px;right:-150px;background:radial-gradient(circle,rgba(139,92,246,.08) 0%,transparent 65%)}
        .auth-card{animation:authCardIn .6s cubic-bezier(.34,1.2,.64,1)}
        @keyframes authCardIn{from{opacity:0;transform:translateY(30px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
        #login-submit{width:100%;margin-top:6px;position:relative;overflow:hidden}
        #login-submit.loading{pointer-events:none;opacity:.7}
        #login-submit.loading::after{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.15),transparent);animation:btnShimmer 1s ease infinite}
        @keyframes btnShimmer{to{left:100%}}
        .auth-sep{display:flex;align-items:center;gap:12px;margin:20px 0;color:#2a3040;font-size:11px;letter-spacing:1px;text-transform:uppercase;font-family:'JetBrains Mono',monospace}
        .auth-sep::before,.auth-sep::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,transparent,rgba(100,150,255,.15),transparent)}
    </style>
</head>
<body>
<div class="auth-corner-glow tl" aria-hidden="true"></div>
<div class="auth-corner-glow br" aria-hidden="true"></div>
<div class="auth-scanline"       aria-hidden="true"></div>
<div class="auth-particles"      id="auth-particles" aria-hidden="true"></div>

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

        <div class="demo-hint">
            <strong><?= th('auth.demo_accounts') ?></strong>
            alex@demo.nexus / demo1234<br>
            sam@demo.nexus / demo1234
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
(function(){
    const c=document.getElementById('auth-particles'),colors=['rgba(0,217,255,','rgba(61,125,255,','rgba(139,92,246,'];
    for(let i=0;i<18;i++){const p=document.createElement('div');p.className='particle';const s=Math.random()*3+1,col=colors[i%3];p.style.cssText=`width:${s}px;height:${s}px;left:${Math.random()*100}%;background:${col}${(Math.random()*.5+.2).toFixed(2)});animation-duration:${Math.random()*12+8}s;animation-delay:${Math.random()*10}s`;c.appendChild(p);}
})();

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
    btn.textContent='<?= th('auth.authenticating') ?>';
    const data=Object.fromEntries(new FormData(e.target));
    const res=await NEXUS.post('index.php?page=login&action=login',data);
    if(res.success){
        btn.textContent='<?= th('auth.access_granted') ?>';
        btn.style.background='linear-gradient(100deg,#1faa6e,#3ddc97)';
        setTimeout(()=>{window.location.href=res.redirect;},400);
    } else {
        btn.classList.remove('loading');
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
