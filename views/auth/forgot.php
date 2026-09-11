<!DOCTYPE html>
<html lang="<?= nexus_locale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>NEXUS — <?= th('auth.forgot_title') ?></title>
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
        #forgot-submit{width:100%;margin-top:6px}
        .reset-success{display:none;padding:16px;border-radius:var(--radius-sm);background:rgba(61,220,151,.08);border:1px solid rgba(61,220,151,.3);font-size:13px;color:var(--text-dim);line-height:1.7}
        .dev-link-box{margin-top:12px;padding:12px;background:rgba(91,140,255,.06);border:1px solid var(--border-soft);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:11px;word-break:break-all;color:var(--cyan)}
        .dev-link-box .label{display:block;font-size:9px;letter-spacing:1px;text-transform:uppercase;color:var(--text-faint);margin-bottom:6px}
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
(function(){
    const c=document.getElementById('auth-particles'),colors=['rgba(0,217,255,','rgba(61,125,255,','rgba(139,92,246,'];
    for(let i=0;i<14;i++){const p=document.createElement('div');p.className='particle';const s=Math.random()*3+1,col=colors[i%3];p.style.cssText=`width:${s}px;height:${s}px;left:${Math.random()*100}%;background:${col}${(Math.random()*.5+.2).toFixed(2)});animation-duration:${Math.random()*12+8}s;animation-delay:${Math.random()*10}s`;c.appendChild(p);}
})();

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
