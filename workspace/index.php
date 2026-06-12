<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
if (!empty($_SESSION['user_id'])) {
    header('Location: /workspace/dashboard.php'); exit;
}
$msg = $_GET['msg'] ?? '';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="UP TECH GROUP — Workspace collaboratif. Gérez vos projets, clients et équipes depuis une seule plateforme sécurisée.">
<title>UP TECH GROUP — Workspace</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#29235C;--accent:#36A9E1;--bg:#0a0918;--bg2:#0f0e1a;--bg3:#13122a;
  --card:#1a1930;--border:rgba(54,169,225,0.12);--text:#e8e6f0;--muted:#7a78a0;
  --success:#2ecc87;--warning:#f0a500;--danger:#e05252;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{scroll-behavior:smooth;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;}

/* BG EFFECTS */
.bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(54,169,225,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(54,169,225,0.03) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0;}
.bg-orb1{position:fixed;top:-200px;left:-200px;width:600px;height:600px;background:radial-gradient(circle,rgba(41,35,92,0.6) 0%,transparent 70%);pointer-events:none;z-index:0;}
.bg-orb2{position:fixed;bottom:-200px;right:-200px;width:500px;height:500px;background:radial-gradient(circle,rgba(54,169,225,0.12) 0%,transparent 70%);pointer-events:none;z-index:0;}
.bg-orb3{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:800px;height:800px;background:radial-gradient(circle,rgba(41,35,92,0.2) 0%,transparent 60%);pointer-events:none;z-index:0;}

/* MAIN LAYOUT */
.page{position:relative;z-index:1;min-height:100vh;display:grid;grid-template-columns:1fr 480px;max-width:1400px;margin:0 auto;}

/* LEFT PANEL */
.left{padding:60px 60px 60px 40px;display:flex;flex-direction:column;}
.brand{display:flex;align-items:center;gap:14px;margin-bottom:60px;}
.brand img{width:44px;height:44px;object-fit:contain;}
.brand-text h1{font-size:18px;font-weight:800;color:#fff;letter-spacing:-0.5px;}
.brand-text span{font-size:11px;color:var(--muted);display:block;letter-spacing:1px;text-transform:uppercase;}

.hero{flex:1;display:flex;flex-direction:column;justify-content:center;max-width:560px;}
.hero-tag{display:inline-flex;align-items:center;gap:8px;background:rgba(54,169,225,0.08);border:1px solid rgba(54,169,225,0.2);border-radius:99px;padding:6px 14px;font-size:12px;color:var(--accent);font-weight:600;margin-bottom:24px;width:fit-content;}
.hero-tag-dot{width:6px;height:6px;border-radius:50%;background:var(--success);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.8)}}
.hero h2{font-size:52px;font-weight:900;color:#fff;line-height:1.1;letter-spacing:-2px;margin-bottom:20px;}
.hero h2 span{background:linear-gradient(135deg,var(--accent),#7ec8e3);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{font-size:16px;color:var(--muted);line-height:1.7;margin-bottom:40px;max-width:460px;}

/* FEATURES GRID */
.features{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:48px;}
.feature{background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:12px;padding:14px 16px;display:flex;align-items:flex-start;gap:12px;transition:all .2s;}
.feature:hover{background:rgba(54,169,225,0.05);border-color:rgba(54,169,225,0.25);}
.feat-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.feat-icon svg{width:16px;height:16px;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.feat-title{font-size:12px;font-weight:700;color:#fff;margin-bottom:2px;}
.feat-desc{font-size:11px;color:var(--muted);line-height:1.4;}

/* STATS ROW */
.stats-row{display:flex;gap:32px;}
.stat-item{display:flex;flex-direction:column;gap:2px;}
.stat-val{font-size:24px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;letter-spacing:-1px;}
.stat-val span{font-size:14px;color:var(--accent);}
.stat-lbl{font-size:11px;color:var(--muted);}

/* LEFT FOOTER */
.left-footer{margin-top:48px;padding-top:24px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.footer-slogan{font-size:12px;color:var(--muted);font-style:italic;}
.footer-legal{font-size:11px;color:rgba(122,120,160,0.5);}

/* RIGHT PANEL — LOGIN */
.right{background:rgba(26,25,48,0.6);backdrop-filter:blur(20px);border-left:1px solid var(--border);display:flex;flex-direction:column;justify-content:center;padding:48px 44px;position:sticky;top:0;height:100vh;}
.login-head{margin-bottom:32px;}
.login-head h3{font-size:26px;font-weight:800;color:#fff;margin-bottom:6px;letter-spacing:-0.5px;}
.login-head p{font-size:13px;color:var(--muted);}

/* ALERT */
.alert{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.alert svg{width:16px;height:16px;flex-shrink:0;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.alert-info{background:rgba(54,169,225,0.1);border:1px solid rgba(54,169,225,0.25);color:var(--accent);}
.alert-error{background:rgba(224,82,82,0.1);border:1px solid rgba(224,82,82,0.25);color:#f08080;}

/* FORM */
.field{margin-bottom:18px;}
.field label{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:8px;}
.input-wrap{position:relative;}
.input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;fill:none;stroke:var(--muted);stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;pointer-events:none;transition:stroke .2s;}
.field:focus-within .input-icon{stroke:var(--accent);}
input[type="email"],input[type="password"]{
  width:100%;background:rgba(255,255,255,0.04);border:1px solid var(--border);
  border-radius:12px;padding:14px 16px 14px 44px;color:var(--text);
  font-family:'Poppins',sans-serif;font-size:14px;outline:none;
  transition:border-color .2s,background .2s;
}
input[type="email"]:focus,input[type="password"]:focus{border-color:rgba(54,169,225,0.5);background:rgba(54,169,225,0.04);}
.pwd-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);padding:4px;transition:color .2s;}
.pwd-toggle:hover{color:var(--accent);}
.pwd-toggle svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;}

/* SUBMIT BTN */
.submit-btn{
  width:100%;background:linear-gradient(135deg,var(--primary) 0%,#2a4a8a 50%,var(--accent) 100%);
  border:none;border-radius:12px;padding:15px;color:#fff;
  font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;
  cursor:pointer;margin-top:4px;position:relative;overflow:hidden;
  transition:opacity .2s,transform .1s;letter-spacing:0.3px;
}
.submit-btn::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.1),transparent);opacity:0;transition:opacity .2s;}
.submit-btn:hover::before{opacity:1;}
.submit-btn:hover{transform:translateY(-1px);}
.submit-btn:active{transform:translateY(0);}
.submit-btn:disabled{opacity:.5;cursor:not-allowed;transform:none;}
.btn-inner{display:flex;align-items:center;justify-content:center;gap:10px;}
.btn-spinner{width:18px;height:18px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:none;}
@keyframes spin{to{transform:rotate(360deg)}}

/* DIVIDER */
.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--muted);font-size:12px;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border);}

/* SECURITY BADGE */
.security-badges{display:flex;gap:8px;margin-top:20px;flex-wrap:wrap;}
.sec-badge{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;padding:6px 10px;font-size:10px;color:var(--muted);}
.sec-badge svg{width:12px;height:12px;fill:none;stroke:var(--success);stroke-width:2;stroke-linecap:round;}

/* RIGHT FOOTER */
.right-footer{margin-top:28px;text-align:center;font-size:11px;color:var(--muted);}
.right-footer a{color:var(--accent);text-decoration:none;}

/* MOBILE */
@media(max-width:900px){
  .page{grid-template-columns:1fr;grid-template-rows:auto 1fr;}
  .left{padding:32px 24px;order:2;}
  .right{position:relative;height:auto;padding:40px 24px;border-left:none;border-bottom:1px solid var(--border);order:1;}
  .hero h2{font-size:36px;}
  .features{grid-template-columns:1fr;}
  .stats-row{gap:20px;}
  .left-footer{flex-direction:column;gap:8px;text-align:center;}
}
@media(max-width:480px){
  .right{padding:32px 20px;}
  .hero h2{font-size:30px;}
}

/* FLOATING PARTICLES */
.particles{position:fixed;inset:0;pointer-events:none;z-index:0;overflow:hidden;}
.particle{position:absolute;border-radius:50%;animation:float linear infinite;}
@keyframes float{0%{transform:translateY(100vh) rotate(0deg);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-100px) rotate(720deg);opacity:0}}
</style>
</head>
<body>

<div class="bg-grid"></div>
<div class="bg-orb1"></div>
<div class="bg-orb2"></div>
<div class="bg-orb3"></div>

<!-- FLOATING PARTICLES -->
<div class="particles" id="particles"></div>

<div class="page">

  <!-- LEFT — PRÉSENTATION -->
  <div class="left">
    <div class="brand">
      <img src="assets/logo.png" alt="UP TECH GROUP">
      <div class="brand-text">
        <h1>UP TECH GROUP</h1>
        <span>Workspace collaboratif</span>
      </div>
    </div>

    <div class="hero">
      <div class="hero-tag">
        <div class="hero-tag-dot"></div>
        Plateforme active · Lomé, Togo
      </div>

      <h2>Pilotez votre<br>entreprise <span>numérique</span><br>en temps réel</h2>

      <p>Un espace de travail centralisé pour gérer vos projets, collaborateurs, clients et finances — conçu pour les équipes tech qui veulent aller vite.</p>

      <!-- FEATURES -->
      <div class="features">
        <div class="feature">
          <div class="feat-icon" style="background:rgba(54,169,225,.12)">
            <svg viewBox="0 0 24 24" style="stroke:#36A9E1"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
          </div>
          <div><div class="feat-title">Gestion de projets</div><div class="feat-desc">Kanban, tâches, deadlines et suivi en temps réel</div></div>
        </div>
        <div class="feature">
          <div class="feat-icon" style="background:rgba(46,204,135,.12)">
            <svg viewBox="0 0 24 24" style="stroke:#2ecc87"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div><div class="feat-title">Finances & CA</div><div class="feat-desc">Trésorerie, graphiques et tableaux de bord</div></div>
        </div>
        <div class="feature">
          <div class="feat-icon" style="background:rgba(155,143,255,.12)">
            <svg viewBox="0 0 24 24" style="stroke:#9b8fff"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </div>
          <div><div class="feat-title">Chat & IA</div><div class="feat-desc">Messagerie interne et assistant IA intégré</div></div>
        </div>
        <div class="feature">
          <div class="feat-icon" style="background:rgba(240,165,0,.12)">
            <svg viewBox="0 0 24 24" style="stroke:#f0a500"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <div><div class="feat-title">Calendrier partagé</div><div class="feat-desc">4 vues, événements synchronisés, rappels</div></div>
        </div>
        <div class="feature">
          <div class="feat-icon" style="background:rgba(224,82,82,.12)">
            <svg viewBox="0 0 24 24" style="stroke:#e05252"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          </div>
          <div><div class="feat-title">Fichiers & Documents</div><div class="feat-desc">Stockage par projet avec commentaires</div></div>
        </div>
        <div class="feature">
          <div class="feat-icon" style="background:rgba(54,169,225,.12)">
            <svg viewBox="0 0 24 24" style="stroke:#36A9E1"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <div><div class="feat-title">Sécurité 2FA</div><div class="feat-desc">Authentification à deux facteurs par email</div></div>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats-row">
        <div class="stat-item">
          <div class="stat-val">8<span>+</span></div>
          <div class="stat-lbl">Modules intégrés</div>
        </div>
        <div class="stat-item">
          <div class="stat-val">100<span>%</span></div>
          <div class="stat-lbl">Made in Togo</div>
        </div>
        <div class="stat-item">
          <div class="stat-val">24<span>/7</span></div>
          <div class="stat-lbl">Disponibilité</div>
        </div>
      </div>
    </div>

    <div class="left-footer">
      <div class="footer-slogan">"Parce que le numérique n'est pas un luxe, mais une nécessité"</div>
      <div class="footer-legal">UP TECH GROUP SARL U · NIF 1002104545 · Lomé, Togo</div>
    </div>
  </div>

  <!-- RIGHT — LOGIN -->
  <div class="right">
    <div class="login-head">
      <h3>Connexion</h3>
      <p>Accédez à votre espace de travail</p>
    </div>

    <?php if($msg==='session_expired'): ?>
    <div class="alert alert-info">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Votre session a expiré. Veuillez vous reconnecter.
    </div>
    <?php endif; ?>

    <div id="alertBox" style="display:none" class="alert alert-error">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span id="alertMsg"></span>
    </div>

    <div class="field">
      <label>Adresse email</label>
      <div class="input-wrap">
        <svg class="input-icon" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        <input type="email" id="email" placeholder="votre@email.com" autocomplete="email">
      </div>
    </div>

    <div class="field">
      <label>Mot de passe</label>
      <div class="input-wrap">
        <svg class="input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <input type="password" id="password" placeholder="Votre mot de passe" autocomplete="current-password">
        <button type="button" class="pwd-toggle" onclick="togglePwd()" id="pwdToggle">
          <svg id="eyeIcon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <button class="submit-btn" id="loginBtn" onclick="doLogin()">
      <div class="btn-inner">
        <div class="btn-spinner" id="spinner"></div>
        <span id="btnText">Se connecter</span>
        <svg id="btnArrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </div>
    </button>

    <div class="divider">accès sécurisé</div>

    <div class="security-badges">
      <div class="sec-badge">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Connexion chiffrée HTTPS
      </div>
      <div class="sec-badge">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        2FA disponible
      </div>
      <div class="sec-badge">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Sessions sécurisées
      </div>
      <div class="sec-badge">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Accès par rôles
      </div>
    </div>

    <div class="right-footer">
      <a href="https://uptech-group.com" target="_blank">uptech-group.com</a>
      · UP TECH GROUP © <?= date('Y') ?>
    </div>
  </div>

</div>

<script>
// ===== LOGIN =====
async function doLogin() {
  const btn     = document.getElementById('loginBtn');
  const spinner = document.getElementById('spinner');
  const arrow   = document.getElementById('btnArrow');
  const btnText = document.getElementById('btnText');
  const email   = document.getElementById('email').value.trim();
  const pass    = document.getElementById('password').value;

  if (!email || !pass) { showAlert('Veuillez remplir tous les champs.'); return; }

  btn.disabled = true;
  spinner.style.display = 'block';
  arrow.style.display = 'none';
  btnText.textContent = 'Connexion…';
  hideAlert();

  try {
    const fd = new FormData();
    fd.append('action', 'login');
    fd.append('email', email);
    fd.append('password', pass);

    const res  = await fetch('api.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      if (data.requires_2fa) {
        btnText.textContent = 'Code envoyé…';
        setTimeout(() => { window.location.href = data.redirect; }, 500);
      } else {
        btnText.textContent = 'Bienvenue !';
        setTimeout(() => { window.location.href = 'dashboard.php'; }, 300);
      }
    } else {
      showAlert(data.message || 'Identifiants incorrects.');
      btn.disabled = false;
      spinner.style.display = 'none';
      arrow.style.display = 'block';
      btnText.textContent = 'Se connecter';
      document.getElementById('password').value = '';
    }
  } catch(e) {
    showAlert('Erreur réseau. Veuillez réessayer.');
    btn.disabled = false;
    spinner.style.display = 'none';
    arrow.style.display = 'block';
    btnText.textContent = 'Se connecter';
  }
}

function showAlert(msg) {
  const box = document.getElementById('alertBox');
  document.getElementById('alertMsg').textContent = msg;
  box.style.display = 'flex';
}
function hideAlert() { document.getElementById('alertBox').style.display = 'none'; }

function togglePwd() {
  const inp  = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}

document.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

// ===== PARTICLES =====
const container = document.getElementById('particles');
for (let i = 0; i < 12; i++) {
  const p = document.createElement('div');
  p.className = 'particle';
  const size = Math.random() * 4 + 1;
  const colors = ['rgba(54,169,225,0.3)','rgba(41,35,92,0.5)','rgba(155,143,255,0.2)'];
  p.style.cssText = `
    width:${size}px;height:${size}px;
    left:${Math.random()*100}%;
    background:${colors[Math.floor(Math.random()*colors.length)]};
    animation-duration:${Math.random()*20+15}s;
    animation-delay:${Math.random()*10}s;
  `;
  container.appendChild(p);
}
</script>
</body>
</html>
