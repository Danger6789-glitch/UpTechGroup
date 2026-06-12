<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$user      = currentUser();
$isManager = isManager();
$db        = getDB();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
<title>Fichiers — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--warning:#f0a500;--danger:#e05252;--purple:#9b8fff;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html,body{height:100%;overflow:hidden;background:var(--bg);font-family:'Poppins',sans-serif;color:var(--text);}
.app{display:flex;height:100vh;height:100dvh;}

/* SIDEBAR PROJETS */
.sidebar{width:280px;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;transition:transform .3s;z-index:200;}
.sb-top{height:56px;display:flex;align-items:center;padding:0 16px;gap:10px;border-bottom:1px solid var(--border);flex-shrink:0;}
.back-btn{display:flex;align-items:center;gap:5px;color:var(--muted);text-decoration:none;font-size:12px;font-weight:500;transition:color .2s;}
.back-btn:hover{color:var(--accent);}
.back-btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.sb-title{font-size:14px;font-weight:700;color:#fff;}
.sb-search{padding:10px 12px;border-bottom:1px solid var(--border);flex-shrink:0;}
.sb-search input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:7px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:12px;outline:none;}
.sb-search input:focus{border-color:var(--accent);}
.proj-list{flex:1;overflow-y:auto;padding:6px 0;}
.proj-item{display:flex;align-items:center;gap:10px;padding:10px 16px;cursor:pointer;transition:background .15s;border-left:3px solid transparent;}
.proj-item:hover{background:rgba(54,169,225,.07);}
.proj-item.active{background:rgba(54,169,225,.1);border-left-color:var(--accent);}
.proj-icon{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.proj-icon.commun{background:linear-gradient(135deg,#2ecc87,#26c6da);}
.proj-icon svg{width:16px;height:16px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;}
.proj-info{flex:1;min-width:0;}
.proj-name{font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.proj-sub{font-size:10px;color:var(--muted);}
.proj-count{background:var(--accent);color:#fff;font-size:9px;font-weight:700;border-radius:99px;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center;padding:0 4px;flex-shrink:0;}

/* ZONE PRINCIPALE */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
.topbar{height:56px;background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:10px;flex-shrink:0;}
.hamburger{display:none;background:none;border:none;color:var(--text);cursor:pointer;padding:4px;}
.hamburger svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.proj-title{flex:1;font-size:15px;font-weight:700;color:#fff;}
.cat-tabs{display:flex;gap:4px;overflow-x:auto;-webkit-overflow-scrolling:touch;}
.cat-tab{padding:5px 12px;border-radius:99px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:none;color:var(--muted);font-family:'Poppins',sans-serif;white-space:nowrap;transition:all .15s;}
.cat-tab.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.add-btn{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:8px;padding:0 16px;height:34px;color:#fff;font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0;}

/* GRILLE FICHIERS */
.content{flex:1;overflow-y:auto;padding:20px;}
.content::-webkit-scrollbar{width:3px;}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}
.files-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;}
.file-card{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:all .2s;cursor:pointer;}
.file-card:hover{border-color:rgba(54,169,225,.3);transform:translateY(-2px);}
.file-preview{height:100px;display:flex;align-items:center;justify-content:center;font-size:36px;}
.file-preview img{width:100%;height:100%;object-fit:cover;}
.file-preview.video-lien{background:linear-gradient(135deg,#1a1a2e,#16213e);position:relative;}
.file-preview.video-lien::after{content:'';position:absolute;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;}
.file-body{padding:10px 12px;}
.file-name{font-size:11px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:4px;}
.file-meta{font-size:10px;color:var(--muted);}
.file-actions{display:flex;gap:4px;margin-top:8px;}
.fa-btn{flex:1;padding:4px 0;border-radius:6px;font-size:10px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;border:1px solid var(--border);background:var(--bg3);color:var(--muted);transition:all .15s;text-align:center;}
.fa-btn:hover{border-color:var(--accent);color:var(--accent);}
.fa-btn.danger:hover{border-color:var(--danger);color:var(--danger);}
.empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--muted);text-align:center;padding:40px;min-height:300px;}
.empty svg{width:56px;height:56px;fill:none;stroke:currentColor;stroke-width:1;opacity:.15;margin-bottom:14px;stroke-linecap:round;}
.empty h3{font-size:16px;font-weight:700;color:#fff;margin-bottom:6px;}
.empty p{font-size:13px;}

/* DROP ZONE */
.drop-zone{border:2px dashed var(--border);border-radius:12px;padding:24px;text-align:center;margin-bottom:16px;transition:all .2s;cursor:pointer;}
.drop-zone.drag-over{border-color:var(--accent);background:rgba(54,169,225,.05);}
.drop-zone p{font-size:13px;color:var(--muted);}
.drop-zone strong{color:var(--accent);}

/* MODAL */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:500;align-items:center;justify-content:center;padding:16px;}
.overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;}
.modal-head{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;}
.modal-head h3{font-size:15px;font-weight:700;color:#fff;}
.modal-close{background:var(--bg3);border:1px solid var(--border);border-radius:7px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);}
.modal-close svg{width:11px;height:11px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;}
.modal-body{padding:16px 20px;}
.modal-foot{padding:12px 20px;display:flex;gap:8px;justify-content:flex-end;border-top:1px solid var(--border);}
.modal-tabs{display:flex;background:var(--bg3);border-radius:9px;padding:3px;margin-bottom:16px;gap:2px;}
.mtab{flex:1;padding:7px;border-radius:7px;font-size:12px;font-weight:500;color:var(--muted);cursor:pointer;border:none;background:none;font-family:'Poppins',sans-serif;transition:all .2s;text-align:center;}
.mtab.active{background:var(--card);color:#fff;}
.mtab-panel{display:none;}
.mtab-panel.active{display:block;}
label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;display:block;margin-bottom:5px;}
input,select,textarea{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:border-color .2s;-webkit-appearance:none;}
input:focus,select:focus,textarea:focus{border-color:var(--accent);}
select option{background:var(--bg2);}
.field{margin-bottom:12px;}
.btn-p{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:8px;padding:9px 20px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-s{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 16px;color:var(--muted);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}
/* PROGRESS */
.progress-wrap{margin-top:12px;display:none;}
.progress-bar{height:6px;background:var(--bg3);border-radius:99px;overflow:hidden;margin-bottom:6px;}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:99px;transition:width .3s;width:0%;}
.progress-text{font-size:11px;color:var(--muted);}
/* TOAST */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:10px 20px;font-size:13px;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;}
#toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
#toast.success{border-color:rgba(46,204,135,.4);color:var(--success);}
#toast.error{border-color:rgba(224,82,82,.4);color:var(--danger);}
.overlay-sb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:150;}
@media(max-width:640px){
  .sidebar{position:fixed;top:0;left:0;height:100%;transform:translateX(-100%);box-shadow:4px 0 24px rgba(0,0,0,.5);}
  .sidebar.open{transform:translateX(0);}
  .overlay-sb.open{display:block;}
  .hamburger{display:flex;}
  .files-grid{grid-template-columns:repeat(2,1fr);}
  .content{padding:12px;}
}
</style>
</head>
<body>
<div class="app">
  <div class="overlay-sb" id="overlaySb" onclick="closeSb()"></div>
  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <div class="sb-top">
      <a class="back-btn" href="dashboard.php"><svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>Workspace</a>
      <div class="sb-title">Fichiers</div>
    </div>
    <div class="sb-search">
      <input type="text" id="projSearch" placeholder="Rechercher un projet…" oninput="filterProjets(this.value)">
    </div>
    <div class="proj-list" id="projList">
      <div style="padding:20px;text-align:center;color:var(--muted);font-size:12px">Chargement…</div>
    </div>
  </div>

  <!-- MAIN -->
  <div class="main">
    <div class="topbar">
      <button class="hamburger" onclick="openSb()"><svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="proj-title" id="projTitle">Sélectionnez un dossier</div>
      <div class="cat-tabs">
        <button class="cat-tab active" onclick="setCat('',this)">Tous</button>
        <button class="cat-tab" onclick="setCat('document',this)">Documents</button>
        <button class="cat-tab" onclick="setCat('image',this)">Images</button>
        <button class="cat-tab" onclick="setCat('video',this)">Vidéos</button>
        <button class="cat-tab" onclick="setCat('archive',this)">Archives</button>
        <button class="cat-tab" onclick="setCat('autre',this)">Autres</button>
      </div>
      <button class="add-btn" onclick="openUpload()" id="addBtn" style="display:none">+ Ajouter</button>
    </div>
    <div class="content" id="content">
      <div class="empty">
        <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        <h3>Sélectionnez un dossier</h3>
        <p>Choisissez un projet dans la liste à gauche</p>
      </div>
    </div>
  </div>
</div>

<!-- MODAL UPLOAD -->
<div class="overlay" id="modalUpload">
  <div class="modal">
    <div class="modal-head">
      <h3>Ajouter des fichiers</h3>
      <div class="modal-close" onclick="closeUpload()"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
    </div>
    <div class="modal-body">
      <div class="modal-tabs">
        <button class="mtab active" onclick="switchMTab('upload',this)">Fichiers</button>
        <button class="mtab" onclick="switchMTab('video',this)">Lien vidéo</button>
      </div>

      <!-- TAB UPLOAD -->
      <div class="mtab-panel active" id="mtab-upload">
        <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5" stroke-linecap="round" style="margin-bottom:8px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <p><strong>Cliquez</strong> ou glissez vos fichiers ici</p>
          <p style="font-size:11px;margin-top:4px">Plusieurs fichiers acceptés · Max 256 MB par fichier</p>
          <input type="file" id="fileInput" multiple style="display:none" onchange="onFilesSelected(this.files)">
        </div>
        <div id="fileList" style="margin-bottom:12px"></div>
        <div class="field">
          <label>Description (optionnelle)</label>
          <input type="text" id="uploadDesc" placeholder="Ex: Charte graphique v2">
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
          <input type="checkbox" id="extraireZip" style="width:16px;height:16px;cursor:pointer">
          <label style="text-transform:none;letter-spacing:0;font-size:13px;margin:0;cursor:pointer" for="extraireZip">Extraire automatiquement les fichiers ZIP</label>
        </div>
        <div class="progress-wrap" id="progressWrap">
          <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
          <div class="progress-text" id="progressText">Upload en cours…</div>
        </div>
      </div>

      <!-- TAB VIDÉO LIEN -->
      <div class="mtab-panel" id="mtab-video">
        <div class="field">
          <label>Titre de la vidéo *</label>
          <input type="text" id="videoTitre" placeholder="Ex: Présentation client Kévin">
        </div>
        <div class="field">
          <label>Lien vidéo *</label>
          <input type="url" id="videoLien" placeholder="https://youtube.com/… ou drive.google.com/…">
        </div>
        <div class="field">
          <label>Description</label>
          <textarea id="videoDesc" rows="2" placeholder="Contexte ou notes sur cette vidéo…"></textarea>
        </div>
        <div style="padding:10px 14px;background:rgba(54,169,225,.06);border:1px solid rgba(54,169,225,.12);border-radius:8px;font-size:12px;color:var(--muted)">
          Supporte YouTube, Google Drive, Vimeo et tout lien HTTP. La vidéo reste hébergée sur la plateforme d'origine — aucun stockage sur le serveur.
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-s" onclick="closeUpload()">Annuler</button>
      <button class="btn-p" id="btnUpload" onclick="doUpload()">Uploader</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const IS_MANAGER = <?= $isManager ? 'true' : 'false' ?>;
const ME_ID      = <?= $user['id'] ?>;

let currentProjetId = null;
let currentCat      = '';
let allProjets      = [];
let uploadFiles     = [];
let currentMTab     = 'upload';

// ===== API =====
async function apiFichiers(params, method='GET') {
  const qs = new URLSearchParams(params).toString();
  const r  = await fetch('fichiers_api.php?' + qs);
  return r.json();
}

function toast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'show ' + type;
  setTimeout(() => t.className = '', 4000);
}

// ===== SIDEBAR =====
function openSb()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlaySb').classList.add('open'); }
function closeSb() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlaySb').classList.remove('open'); }

// ===== PROJETS =====
async function loadProjets() {
  const data = await apiFichiers({action:'projets'});
  allProjets = data;
  renderProjets(data);
  // Auto-select Ressources communes
  if (data.length > 0) selectProjet(data[0]);
}

function renderProjets(list) {
  document.getElementById('projList').innerHTML = list.map(p => {
    const isCommun = p.id === 0;
    return `<div class="proj-item ${currentProjetId===p.id?'active':''}" id="proj-${p.id}" onclick="selectProjet(${JSON.stringify(p).replace(/"/g,'&quot;')})">
      <div class="proj-icon ${isCommun?'commun':''}">
        ${isCommun
          ? '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>'
          : '<svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>'}
      </div>
      <div class="proj-info">
        <div class="proj-name">${p.nom}</div>
        <div class="proj-sub">${isCommun ? 'Logo, charte, templates…' : p.statut}</div>
      </div>
      ${p.nb_fichiers > 0 ? `<div class="proj-count">${p.nb_fichiers}</div>` : ''}
    </div>`;
  }).join('');
}

function filterProjets(q) {
  const filtered = q ? allProjets.filter(p => p.nom.toLowerCase().includes(q.toLowerCase())) : allProjets;
  renderProjets(filtered);
}

function selectProjet(p) {
  currentProjetId = p.id;
  document.getElementById('projTitle').textContent = p.nom;
  document.getElementById('addBtn').style.display = 'block';
  document.querySelectorAll('.proj-item').forEach(el => el.classList.remove('active'));
  document.getElementById('proj-'+p.id)?.classList.add('active');
  loadFichiers();
  closeSb();
}

// ===== FICHIERS =====
function setCat(cat, btn) {
  currentCat = cat;
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  loadFichiers();
}

async function loadFichiers() {
  if (currentProjetId === null) return;
  const params = {action:'fichiers', projet_id:currentProjetId};
  if (currentCat) params.categorie = currentCat;
  const data = await apiFichiers(params);
  renderFichiers(data);
}

const ICONS = {
  document: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#36A9E1" stroke-width="1.5" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`,
  image:    `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2ecc87" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>`,
  video:    `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f0a500" stroke-width="1.5" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>`,
  archive:  `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9b8fff" stroke-width="1.5" stroke-linecap="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>`,
  autre:    `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#7a78a0" stroke-width="1.5" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>`,
};
const CAT_BG = {document:'rgba(54,169,225,.12)',image:'rgba(46,204,135,.12)',video:'rgba(240,165,0,.12)',archive:'rgba(155,143,255,.12)',autre:'rgba(122,120,160,.12)'};

function fmtSize(bytes) {
  if (!bytes || bytes === 0) return 'Lien externe';
  if (bytes < 1024) return bytes + ' o';
  if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' Ko';
  return (bytes/1024/1024).toFixed(1) + ' Mo';
}

function renderFichiers(data) {
  const content = document.getElementById('content');
  if (!data.length) {
    content.innerHTML = `<div class="empty">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <h3>Aucun fichier</h3>
      <p>Uploadez votre premier fichier avec le bouton "+ Ajouter".</p>
    </div>`;
    return;
  }
  content.innerHTML = `<div class="files-grid">${data.map(f => {
    const isLien = f.nom_stockage?.startsWith('lien:');
    const isImg  = f.categorie === 'image' && !isLien;
    const preview = isImg
      ? `<div class="file-preview"><img src="fichiers_api.php?action=download&id=${f.id}" alt="${f.nom_affiche}" loading="lazy"></div>`
      : `<div class="file-preview" style="background:${CAT_BG[f.categorie]||CAT_BG.autre}">${ICONS[f.categorie]||ICONS.autre}</div>`;
    const actions = IS_MANAGER
      ? `<button class="fa-btn" onclick="event.stopPropagation();renommer(${f.id},'${f.nom_affiche.replace(/'/g,"\\'")}')">Renommer</button>
         <button class="fa-btn danger" onclick="event.stopPropagation();supprimerFichier(${f.id})">Suppr.</button>`
      : '';
    return `<div class="file-card" onclick="openFichier(${f.id},'${f.nom_stockage?.startsWith('lien:')?f.nom_stockage.slice(5):''}','${f.categorie}')">
      ${preview}
      <div class="file-body">
        <div class="file-name" title="${f.nom_affiche}">${f.nom_affiche}</div>
        <div class="file-meta">${fmtSize(f.taille)} · ${f.created_at?.slice(0,10)||''}</div>
        <div class="file-meta" style="margin-top:2px">${f.uploade_par_nom||''}</div>
        <div class="file-actions">${actions}</div>
      </div>
    </div>`;
  }).join('')}</div>`;
}

function openFichier(id, lien, cat) {
  if (lien) { window.open(lien, '_blank'); return; }
  window.open('fichiers_api.php?action=download&id='+id, '_blank');
}

// ===== UPLOAD =====
function openUpload() {
  uploadFiles = [];
  document.getElementById('fileInput').value = '';
  document.getElementById('fileList').innerHTML = '';
  document.getElementById('uploadDesc').value = '';
  document.getElementById('extraireZip').checked = false;
  document.getElementById('videoTitre').value = '';
  document.getElementById('videoLien').value = '';
  document.getElementById('videoDesc').value = '';
  document.getElementById('progressWrap').style.display = 'none';
  document.getElementById('progressFill').style.width = '0%';
  document.getElementById('modalUpload').classList.add('open');
}
function closeUpload() { document.getElementById('modalUpload').classList.remove('open'); }
document.getElementById('modalUpload').addEventListener('click', e => { if(e.target===document.getElementById('modalUpload'))closeUpload(); });

function switchMTab(name, btn) {
  currentMTab = name;
  document.querySelectorAll('.mtab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.mtab').forEach(t=>t.classList.remove('active'));
  document.getElementById('mtab-'+name).classList.add('active');
  btn.classList.add('active');
  document.getElementById('btnUpload').textContent = name==='video' ? 'Enregistrer le lien' : 'Uploader';
}

function onFilesSelected(files) {
  uploadFiles = Array.from(files);
  renderFileList();
}

function renderFileList() {
  const hasZip = uploadFiles.some(f => f.name.endsWith('.zip'));
  document.getElementById('extraireZip').closest('div').style.display = hasZip ? 'flex' : 'none';
  document.getElementById('fileList').innerHTML = uploadFiles.map((f,i) => `
    <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--bg3);border-radius:8px;margin-bottom:4px">
      <span style="flex:1;font-size:12px;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${f.name}</span>
      <span style="font-size:11px;color:var(--muted);flex-shrink:0">${fmtSize(f.size)}</span>
      <button onclick="removeFile(${i})" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:14px;flex-shrink:0">×</button>
    </div>`).join('');
}

function removeFile(i) {
  uploadFiles.splice(i, 1);
  renderFileList();
}

// Drag & Drop
const dz = document.getElementById('dropZone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
dz.addEventListener('drop', e => {
  e.preventDefault(); dz.classList.remove('drag-over');
  uploadFiles = Array.from(e.dataTransfer.files);
  renderFileList();
});

async function doUpload() {
  if (currentMTab === 'video') {
    const lien  = document.getElementById('videoLien').value.trim();
    const titre = document.getElementById('videoTitre').value.trim();
    if (!lien || !titre) { toast('Titre et lien obligatoires','error'); return; }
    const fd = new FormData();
    fd.append('action','lien_video');
    fd.append('lien', lien);
    fd.append('titre', titre);
    fd.append('description', document.getElementById('videoDesc').value);
    if (currentProjetId) fd.append('projet_id', currentProjetId);
    const r = await fetch('fichiers_api.php', {method:'POST',body:fd});
    const d = await r.json();
    if (d.success) { closeUpload(); toast('Lien vidéo ajouté'); loadFichiers(); updateProjCount(); }
    else toast(d.error||'Erreur','error');
    return;
  }

  if (!uploadFiles.length) { toast('Sélectionnez au moins un fichier','error'); return; }

  const btn = document.getElementById('btnUpload');
  btn.disabled = true; btn.textContent = 'Upload en cours…';
  document.getElementById('progressWrap').style.display = 'block';

  const fd = new FormData();
  fd.append('action','upload');
  if (currentProjetId) fd.append('projet_id', currentProjetId);
  fd.append('description', document.getElementById('uploadDesc').value);
  fd.append('extraire', document.getElementById('extraireZip').checked ? '1' : '0');
  uploadFiles.forEach(f => fd.append('fichiers[]', f));

  // XHR pour la progression
  await new Promise(resolve => {
    const xhr = new XMLHttpRequest();
    xhr.upload.onprogress = e => {
      if (e.lengthComputable) {
        const pct = Math.round((e.loaded/e.total)*100);
        document.getElementById('progressFill').style.width = pct + '%';
        document.getElementById('progressText').textContent = `Upload ${pct}% — ${uploadFiles.length} fichier(s)`;
      }
    };
    xhr.onload = () => {
      try {
        const d = JSON.parse(xhr.responseText);
        if (d.success) {
          closeUpload();
          toast(`${d.count} fichier(s) ajouté(s)`);
          loadFichiers();
          updateProjCount();
        } else toast(d.error||'Erreur','error');
      } catch(e) { toast('Erreur serveur','error'); }
      resolve();
    };
    xhr.onerror = () => { toast('Erreur réseau','error'); resolve(); };
    xhr.open('POST','fichiers_api.php');
    xhr.send(fd);
  });

  btn.disabled = false; btn.textContent = 'Uploader';
}

async function supprimerFichier(id) {
  if (!confirm('Supprimer ce fichier définitivement ?')) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('id',id);
  const r = await fetch('fichiers_api.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.success) { toast('Fichier supprimé'); loadFichiers(); updateProjCount(); }
  else toast(d.error||'Erreur','error');
}

async function renommer(id, nom) {
  const newNom = prompt('Nouveau nom :', nom);
  if (!newNom || newNom === nom) return;
  const fd = new FormData(); fd.append('action','renommer'); fd.append('id',id); fd.append('nom',newNom);
  const r = await fetch('fichiers_api.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.success) { toast('Fichier renommé'); loadFichiers(); }
  else toast(d.error||'Erreur','error');
}

async function updateProjCount() {
  const data = await apiFichiers({action:'projets'});
  allProjets = data;
  renderProjets(data);
  document.getElementById('proj-'+currentProjetId)?.classList.add('active');
}

// ===== INIT =====
loadProjets();
</script>
</body>
</html>
