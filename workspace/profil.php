<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$user = currentUser();
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userFull = $stmt->fetch();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>UP TECH GROUP — Mon Profil</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--warning:#f0a500;--danger:#e05252;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 50% at 0% 0%,rgba(41,35,92,0.6) 0%,transparent 60%),radial-gradient(ellipse 50% 40% at 100% 100%,rgba(54,169,225,0.12) 0%,transparent 55%);pointer-events:none;z-index:0;}
.topbar{position:sticky;top:0;z-index:100;background:rgba(19,18,42,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 20px;height:56px;display:flex;align-items:center;gap:12px;}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;transition:color .2s;}
.back-btn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.back-btn:hover{color:var(--accent);}
.topbar h1{flex:1;font-size:15px;font-weight:700;color:#fff;}
.page{max-width:860px;margin:0 auto;padding:24px 16px 48px;position:relative;z-index:1;}
.profile-hero{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px;margin-bottom:20px;display:flex;align-items:center;gap:24px;}
.avatar-wrap{position:relative;flex-shrink:0;}
.avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;border:3px solid var(--border);overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.avatar-edit{position:absolute;bottom:0;right:0;width:26px;height:26px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid var(--bg);}
.avatar-edit svg{width:12px;height:12px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
#avatarInput{display:none;}
.hero-info h2{font-size:20px;font-weight:700;color:#fff;margin-bottom:4px;}
.hero-info .email{font-size:13px;color:var(--muted);margin-bottom:8px;}
.hero-badges{display:flex;gap:8px;flex-wrap:wrap;}
.rbadge{display:inline-block;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:700;}
.role-admin{background:rgba(224,82,82,0.2);color:#f08080;}
.role-manager{background:rgba(54,169,225,0.2);color:var(--accent);}
.role-collaborateur{background:rgba(46,204,135,0.2);color:var(--success);}
.mbadge{display:inline-flex;align-items:center;gap:5px;padding:3px 12px;border-radius:99px;font-size:11px;background:var(--bg3);color:var(--muted);}
.tabs{display:flex;gap:4px;background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:4px;margin-bottom:20px;overflow-x:auto;}
.tab{flex:1;padding:9px 14px;border-radius:9px;font-size:13px;font-weight:500;color:var(--muted);cursor:pointer;border:none;background:none;font-family:'Poppins',sans-serif;white-space:nowrap;transition:all .2s;text-align:center;}
.tab.active{background:var(--card);color:#fff;box-shadow:0 2px 8px rgba(0,0,0,.3);}
.tab-panel{display:none;}
.tab-panel.active{display:block;}
.scard{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:16px;}
.stitle{font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;margin-bottom:4px;}
.stitle svg{width:16px;height:16px;fill:none;stroke:var(--accent);stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.ssub{font-size:12px;color:var(--muted);margin-bottom:18px;}
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.field{margin-bottom:0;}
.field.full{grid-column:1/-1;}
label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;display:block;margin-bottom:6px;}
input,select,textarea{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:9px;padding:11px 14px;color:var(--text);font-family:'Poppins',sans-serif;font-size:14px;outline:none;transition:border-color .2s;-webkit-appearance:none;appearance:none;}
input:focus,select:focus,textarea:focus{border-color:var(--accent);}
input:disabled{opacity:.4;cursor:not-allowed;background:rgba(255,255,255,.02);}
select option{background:var(--bg2);}
.hint{font-size:11px;color:var(--muted);margin-top:5px;}
.sbar{height:4px;background:var(--bg3);border-radius:99px;overflow:hidden;margin-top:6px;}
.sbar-fill{height:100%;border-radius:99px;transition:width .3s,background .3s;width:0%;}
.sbar-text{font-size:10px;margin-top:4px;}
.aitem{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.aitem:last-child{border:none;}
.adot{width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:5px;}
.adot.g{background:var(--success);}
.abody{flex:1;}
.atitle{font-size:13px;color:#fff;font-weight:500;}
.atime{font-size:11px;color:var(--muted);margin-top:2px;}
.trow{display:flex;align-items:center;justify-content:space-between;padding:13px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.trow:last-child{border:none;}
.tinfo{flex:1;}
.tlabel{font-size:13px;font-weight:500;color:#fff;}
.tdesc{font-size:11px;color:var(--muted);margin-top:2px;}
.toggle{position:relative;width:44px;height:24px;flex-shrink:0;}
.toggle input{opacity:0;width:0;height:0;}
.tslider{position:absolute;inset:0;background:var(--bg3);border-radius:99px;cursor:pointer;transition:.3s;border:1px solid var(--border);}
.tslider::before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;background:var(--muted);border-radius:50%;transition:.3s;}
.toggle input:checked+.tslider{background:var(--accent);}
.toggle input:checked+.tslider::before{transform:translateX(20px);background:#fff;}
.dzone{background:rgba(224,82,82,.06);border:1px solid rgba(224,82,82,.2);border-radius:14px;padding:20px;margin-bottom:16px;}
.dtitle{font-size:14px;font-weight:700;color:var(--danger);margin-bottom:6px;}
.ddesc{font-size:12px;color:var(--muted);margin-bottom:14px;}
.btn-p{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:9px;padding:11px 24px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-d{background:rgba(224,82,82,.15);border:1px solid rgba(224,82,82,.3);border-radius:9px;padding:11px 20px;color:var(--danger);font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
.brow{display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;}
.alert{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:14px;}
.alert-s{background:rgba(46,204,135,.12);border:1px solid rgba(46,204,135,.3);color:var(--success);}
.alert-e{background:rgba(224,82,82,.12);border:1px solid rgba(224,82,82,.3);color:#f08080;}
.session-item{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.session-item:last-child{border:none;}
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:11px 20px;font-size:13px;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;}
#toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
#toast.success{border-color:rgba(46,204,135,.4);color:var(--success);}
#toast.error{border-color:rgba(224,82,82,.4);color:var(--danger);}
@media(max-width:600px){
  .profile-hero{flex-direction:column;text-align:center;}
  .hero-badges{justify-content:center;}
  .fgrid{grid-template-columns:1fr;}
  .field.full{grid-column:1;}
  .tab{padding:8px 10px;font-size:12px;}
  .brow{flex-direction:column;}
  .brow .btn-p,.brow .btn-d{width:100%;text-align:center;}
}
</style>
</head>
<body>

<div class="topbar">
  <a class="back-btn" href="dashboard.php">
    <svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>
    Retour
  </a>
  <h1>Mon Profil</h1>
</div>

<div class="page">

  <!-- HERO -->
  <div class="profile-hero">
    <div class="avatar-wrap">
      <div class="avatar" id="avatarDisplay">
        <?php if(!empty($userFull['avatar'])): ?>
          <img src="<?= htmlspecialchars($userFull['avatar']) ?>" alt="Avatar">
        <?php else: ?>
          <?= strtoupper(substr($userFull['prenom'],0,1) . substr($userFull['nom'],0,1)) ?>
        <?php endif; ?>
      </div>
      <div class="avatar-edit" onclick="document.getElementById('avatarInput').click()">
        <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </div>
      <input type="file" id="avatarInput" accept="image/*" onchange="previewAvatar(this)">
    </div>
    <div class="hero-info">
      <h2 id="heroName"><?= htmlspecialchars($userFull['prenom'] . ' ' . $userFull['nom']) ?></h2>
      <div class="email"><?= htmlspecialchars($userFull['email']) ?></div>
      <?php if(!empty($userFull['poste'])): ?>
        <div style="font-size:13px;color:var(--accent);margin-bottom:6px"><?= htmlspecialchars($userFull['poste']) ?></div>
      <?php endif; ?>
      <div class="hero-badges">
        <span class="rbadge role-<?= $userFull['role'] ?>"><?= ucfirst($userFull['role']) ?></span>
        <span class="mbadge">Depuis <?= date('M Y', strtotime($userFull['created_at'])) ?></span>
        <?php if($userFull['last_login']): ?>
          <span class="mbadge">Connecté le <?= date('d/m/Y', strtotime($userFull['last_login'])) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab active" onclick="showTab('infos',this)">Informations</button>
    <button class="tab" onclick="showTab('securite',this)">Sécurité</button>
    <button class="tab" onclick="showTab('activite',this)">Activité</button>
    <button class="tab" onclick="showTab('preferences',this)">Préférences</button>
  </div>

  <!-- ===== INFOS ===== -->
  <div class="tab-panel active" id="tab-infos">
    <div class="scard">
      <div class="stitle">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Informations personnelles
      </div>
      <div class="ssub">Modifiez vos informations visibles dans le workspace</div>
      <div id="alertInfos"></div>
      <div class="fgrid">
        <div class="field"><label>Prénom *</label><input type="text" id="prenom" value="<?= htmlspecialchars($userFull['prenom']) ?>"></div>
        <div class="field"><label>Nom *</label><input type="text" id="nom" value="<?= htmlspecialchars($userFull['nom']) ?>"></div>
        <div class="field full">
          <label>Adresse email</label>
          <input type="email" value="<?= htmlspecialchars($userFull['email']) ?>" disabled>
          <div class="hint">L'adresse email ne peut pas être modifiée. Contactez l'administrateur.</div>
        </div>
        <div class="field"><label>Téléphone</label><input type="tel" id="telephone" value="<?= htmlspecialchars($userFull['telephone'] ?? '') ?>" placeholder="+228 XX XX XX XX"></div>
        <div class="field"><label>Poste / Titre</label><input type="text" id="poste" value="<?= htmlspecialchars($userFull['poste'] ?? '') ?>" placeholder="Ex: Développeur fullstack"></div>
        <div class="field full"><label>Bio courte</label><textarea id="bio" rows="3" placeholder="Quelques mots sur vous…"><?= htmlspecialchars($userFull['bio'] ?? '') ?></textarea></div>
      </div>
      <div class="brow"><button class="btn-p" onclick="saveInfos()">Enregistrer les modifications</button></div>
    </div>
  </div>

  <!-- ===== SECURITE ===== -->
  <div class="tab-panel" id="tab-securite">

    <div class="scard">
      <div class="stitle">
        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Changer le mot de passe
      </div>
      <div class="ssub">Utilisez un mot de passe unique que vous n'utilisez nulle part ailleurs</div>
      <div id="alertSecurite"></div>
      <div style="display:flex;flex-direction:column;gap:14px">
        <div class="field"><label>Mot de passe actuel</label><input type="password" id="currentPass" placeholder="Votre mot de passe actuel"></div>
        <div class="field">
          <label>Nouveau mot de passe</label>
          <input type="password" id="newPass" placeholder="Min. 8 caractères" oninput="checkStrength(this.value)">
          <div class="sbar"><div class="sbar-fill" id="strengthFill"></div></div>
          <div class="sbar-text" id="strengthText"></div>
        </div>
        <div class="field">
          <label>Confirmer le nouveau mot de passe</label>
          <input type="password" id="confirmPass" placeholder="Répétez le mot de passe" oninput="checkMatch()">
          <div class="hint" id="matchHint"></div>
        </div>
      </div>
      <div class="brow"><button class="btn-p" onclick="changePassword()">Mettre à jour le mot de passe</button></div>
    </div>

    <div class="scard">
      <div class="stitle">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Authentification à deux facteurs
      </div>
      <div class="ssub">Un code à 6 chiffres est envoyé par email à chaque connexion</div>
      <div class="trow">
        <div class="tinfo">
          <div class="tlabel">Vérification par email</div>
          <div class="tdesc">Activez pour sécuriser davantage votre compte</div>
        </div>
        <label class="toggle">
          <input type="checkbox" id="toggle2fa" onchange="toggle2FA(this)">
          <span class="tslider"></span>
        </label>
      </div>
    </div>

    <div class="scard">
      <div class="stitle">
        <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Sessions actives
      </div>
      <div class="session-item">
        <div>
          <div style="font-size:13px;font-weight:600;color:#fff">Session actuelle</div>
          <div style="font-size:11px;color:var(--muted);margin-top:3px">Navigateur web · Connecté maintenant</div>
        </div>
        <span style="font-size:11px;padding:3px 10px;background:rgba(46,204,135,.15);color:var(--success);border-radius:99px;font-weight:600">Active</span>
      </div>
    </div>

  </div>

  <!-- ===== ACTIVITE ===== -->
  <div class="tab-panel" id="tab-activite">
    <div class="scard">
      <div class="stitle">
        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Historique d'activité
      </div>
      <div class="ssub">Vos dernières actions dans le workspace</div>
      <div class="aitem">
        <div class="adot g"></div>
        <div class="abody">
          <div class="atitle">Dernière connexion</div>
          <div class="atime"><?= $userFull['last_login'] ? date('d/m/Y à H:i', strtotime($userFull['last_login'])) : 'Première connexion' ?></div>
        </div>
      </div>
      <div class="aitem">
        <div class="adot"></div>
        <div class="abody">
          <div class="atitle">Compte créé</div>
          <div class="atime"><?= date('d/m/Y à H:i', strtotime($userFull['created_at'])) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== PREFERENCES ===== -->
  <div class="tab-panel" id="tab-preferences">
    <div class="scard">
      <div class="stitle">
        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Notifications
      </div>
      <div class="trow"><div class="tinfo"><div class="tlabel">Nouvelles tâches assignées</div><div class="tdesc">Notification quand une tâche vous est assignée</div></div><label class="toggle"><input type="checkbox" checked><span class="tslider"></span></label></div>
      <div class="trow"><div class="tinfo"><div class="tlabel">Mises à jour de projets</div><div class="tdesc">Changements de statut sur les projets</div></div><label class="toggle"><input type="checkbox" checked><span class="tslider"></span></label></div>
      <div class="trow"><div class="tinfo"><div class="tlabel">Rappels d'échéances</div><div class="tdesc">Rappel 24h avant la date limite</div></div><label class="toggle"><input type="checkbox"><span class="tslider"></span></label></div>
    </div>
    <div class="scard">
      <div class="stitle">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        Région et langue
      </div>
      <div style="display:flex;flex-direction:column;gap:14px;margin-top:14px">
        <div class="field"><label>Langue</label><select><option selected>Français</option><option>English</option></select></div>
        <div class="field"><label>Fuseau horaire</label><select><option selected>Africa/Lomé (GMT+0)</option><option>Europe/Paris (GMT+1/2)</option><option>Africa/Lagos (GMT+1)</option></select></div>
      </div>
      <div class="brow"><button class="btn-p" onclick="toast('Préférences enregistrées')">Enregistrer</button></div>
    </div>
    <div class="dzone">
      <div class="dtitle">Déconnexion</div>
      <div class="ddesc">Mettre fin à votre session sur cet appareil.</div>
      <a href="api.php?action=logout" class="btn-d">Se déconnecter</a>
    </div>
  </div>

</div>

<div id="toast"></div>

<script>
function showTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
}

function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const r = new FileReader();
    r.onload = e => { document.getElementById('avatarDisplay').innerHTML = `<img src="${e.target.result}" alt="">`; };
    r.readAsDataURL(input.files[0]);
    toast('Aperçu de la photo mis à jour');
  }
}

async function api(params) {
  const fd = new FormData();
  for (const [k, v] of Object.entries(params)) fd.append(k, v);
  const r = await fetch('api.php', { method: 'POST', body: fd });
  return r.json();
}

async function saveInfos() {
  const prenom = document.getElementById('prenom').value.trim();
  const nom    = document.getElementById('nom').value.trim();
  if (!prenom || !nom) { showAlert('alertInfos', 'Prénom et nom sont obligatoires.', 'e'); return; }
  const r = await api({ action: 'update_profil', prenom, nom, telephone: document.getElementById('telephone').value, poste: document.getElementById('poste').value, bio: document.getElementById('bio').value });
  if (r.success) { document.getElementById('heroName').textContent = prenom + ' ' + nom; showAlert('alertInfos', 'Profil mis à jour avec succès.', 's'); toast('Profil mis à jour'); }
  else showAlert('alertInfos', r.error || 'Erreur', 'e');
}

function checkStrength(pwd) {
  const fill = document.getElementById('strengthFill'), text = document.getElementById('strengthText');
  let s = 0;
  if (pwd.length >= 8) s++; if (pwd.length >= 12) s++;
  if (/[A-Z]/.test(pwd)) s++; if (/[0-9]/.test(pwd)) s++; if (/[^A-Za-z0-9]/.test(pwd)) s++;
  const L = [{ w:'0%',c:'transparent',t:'' },{ w:'20%',c:'var(--danger)',t:'Très faible' },{ w:'40%',c:'var(--warning)',t:'Faible' },{ w:'60%',c:'var(--warning)',t:'Correct' },{ w:'80%',c:'var(--accent)',t:'Fort' },{ w:'100%',c:'var(--success)',t:'Très fort' }][Math.min(s,5)];
  fill.style.width = L.w; fill.style.background = L.c; text.textContent = L.t; text.style.color = L.c;
}

function checkMatch() {
  const n = document.getElementById('newPass').value, c = document.getElementById('confirmPass').value, h = document.getElementById('matchHint');
  if (!c) { h.textContent = ''; return; }
  h.textContent = n === c ? 'Les mots de passe correspondent' : 'Ne correspondent pas';
  h.style.color = n === c ? 'var(--success)' : 'var(--danger)';
}

async function changePassword() {
  const current = document.getElementById('currentPass').value;
  const newP    = document.getElementById('newPass').value;
  const confirm = document.getElementById('confirmPass').value;
  if (!current || !newP || !confirm) { showAlert('alertSecurite', 'Veuillez remplir tous les champs.', 'e'); return; }
  if (newP.length < 8) { showAlert('alertSecurite', 'Minimum 8 caractères.', 'e'); return; }
  if (newP !== confirm) { showAlert('alertSecurite', 'Les mots de passe ne correspondent pas.', 'e'); return; }
  const r = await api({ action: 'change_password', current, new: newP });
  if (r.success) {
    showAlert('alertSecurite', 'Mot de passe changé avec succès.', 's');
    ['currentPass','newPass','confirmPass'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('strengthFill').style.width = '0%';
    document.getElementById('strengthText').textContent = '';
    document.getElementById('matchHint').textContent = '';
    toast('Mot de passe mis à jour');
  } else {
    showAlert('alertSecurite', r.error || 'Mot de passe actuel incorrect.', 'e');
  }
}

async function toggle2FA(el) {
  const r = await api({ action: 'toggle_2fa' });
  if (r.success) toast(r.actif ? '2FA activé' : '2FA désactivé');
  else { el.checked = !el.checked; toast('Erreur', 'error'); }
}

api({ action: 'statut_2fa' }).then(r => {
  const el = document.getElementById('toggle2fa');
  if (el) el.checked = r.actif == 1;
});

function showAlert(id, msg, type) {
  const el = document.getElementById(id);
  el.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
  setTimeout(() => { el.innerHTML = ''; }, 5000);
}

function toast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show ' + type;
  setTimeout(() => { t.className = ''; }, 3500);
}
</script>
</body>
</html>