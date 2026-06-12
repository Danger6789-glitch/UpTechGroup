/**
 * UP TECH GROUP — Recherche globale + Notifications temps réel
 * Inclure ce fichier dans dashboard.php via <script src="search_notifs.js"></script>
 * Nécessite search_api.php sur le serveur
 */

(function() {
'use strict';

// ============ STYLES ============
const css = `
#srch-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0);z-index:2000;backdrop-filter:blur(0px);transition:all .2s;}
#srch-overlay.open{display:flex;align-items:flex-start;justify-content:center;padding-top:80px;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);}
#srch-box{background:#13122a;border:1px solid rgba(54,169,225,.2);border-radius:16px;width:100%;max-width:560px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.6);transform:translateY(-20px);opacity:0;transition:all .25s;}
#srch-overlay.open #srch-box{transform:translateY(0);opacity:1;}
#srch-input-wrap{display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid rgba(54,169,225,.1);}
#srch-input-wrap svg{width:18px;height:18px;fill:none;stroke:#7a78a0;stroke-width:2;stroke-linecap:round;flex-shrink:0;}
#srch-input{flex:1;background:none;border:none;outline:none;color:#e8e6f0;font-family:'Poppins',sans-serif;font-size:15px;}
#srch-input::placeholder{color:#7a78a0;}
#srch-kbd{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:6px;padding:3px 8px;font-size:11px;color:#7a78a0;font-family:monospace;cursor:pointer;}
#srch-results{max-height:400px;overflow-y:auto;}
.srch-section{padding:8px 0;}
.srch-section-label{font-size:10px;font-weight:700;color:#7a78a0;text-transform:uppercase;letter-spacing:2px;padding:6px 20px 4px;}
.srch-item{display:flex;align-items:center;gap:12px;padding:10px 20px;cursor:pointer;transition:background .15s;text-decoration:none;}
.srch-item:hover,.srch-item.active{background:rgba(54,169,225,.08);}
.srch-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.srch-icon svg{width:15px;height:15px;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.srch-title{font-size:13px;font-weight:600;color:#e8e6f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.srch-meta{font-size:11px;color:#7a78a0;margin-top:1px;}
.srch-empty{padding:32px 20px;text-align:center;color:#7a78a0;font-size:13px;}
.srch-tip{padding:12px 20px;border-top:1px solid rgba(54,169,225,.08);display:flex;gap:16px;flex-wrap:wrap;}
.srch-tip-item{font-size:11px;color:#7a78a0;display:flex;align-items:center;gap:5px;}
.srch-tip-item kbd{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:4px;padding:1px 6px;font-size:10px;font-family:monospace;}

/* NOTIF PANEL */
#notif-panel{display:none;position:fixed;top:60px;right:16px;width:320px;background:#13122a;border:1px solid rgba(54,169,225,.15);border-radius:14px;z-index:1000;box-shadow:0 12px 40px rgba(0,0,0,.5);max-height:480px;display:none;flex-direction:column;}
#notif-panel.open{display:flex;}
.notif-head{padding:14px 16px;border-bottom:1px solid rgba(54,169,225,.1);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.notif-head-title{font-size:13px;font-weight:700;color:#fff;}
.notif-read-all{font-size:11px;color:#36A9E1;cursor:pointer;background:none;border:none;font-family:'Poppins',sans-serif;}
.notif-list{overflow-y:auto;flex:1;}
.notif-item{display:flex;gap:10px;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.04);cursor:pointer;transition:background .15s;}
.notif-item:hover{background:rgba(54,169,225,.05);}
.notif-item.unread{background:rgba(54,169,225,.04);}
.notif-dot{width:8px;height:8px;border-radius:50%;background:#36A9E1;flex-shrink:0;margin-top:4px;}
.notif-dot.read{background:transparent;}
.notif-content{flex:1;min-width:0;}
.notif-msg{font-size:12px;color:#e8e6f0;line-height:1.5;}
.notif-time{font-size:10px;color:#7a78a0;margin-top:3px;}
.notif-empty{padding:32px;text-align:center;color:#7a78a0;font-size:12px;}

/* BADGE */
.notif-badge{position:absolute;top:-5px;right:-5px;background:#e05252;color:#fff;font-size:9px;font-weight:800;border-radius:99px;min-width:17px;height:17px;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid #0f0e1a;display:none;}
.notif-badge.show{display:flex;}

/* SEARCH BTN */
#srch-trigger{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.04);border:1px solid rgba(54,169,225,.12);border-radius:9px;padding:7px 14px;cursor:pointer;color:#7a78a0;font-family:'Poppins',sans-serif;font-size:12px;transition:all .2s;}
#srch-trigger:hover{border-color:rgba(54,169,225,.3);color:#e8e6f0;}
#srch-trigger svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
#srch-trigger kbd{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:4px;padding:1px 5px;font-size:10px;font-family:monospace;color:#7a78a0;}

@media(max-width:600px){
  #notif-panel{right:8px;left:8px;width:auto;}
  #srch-overlay{padding-top:20px;align-items:flex-start;}
}
`;
const style = document.createElement('style');
style.textContent = css;
document.head.appendChild(style);

// ============ SEARCH HTML ============
document.body.insertAdjacentHTML('beforeend', `
<div id="srch-overlay">
  <div id="srch-box">
    <div id="srch-input-wrap">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input id="srch-input" placeholder="Rechercher projets, tâches, clients, personnes…" autocomplete="off">
      <span id="srch-kbd" onclick="closeSearch()">Esc</span>
    </div>
    <div id="srch-results"></div>
    <div class="srch-tip">
      <div class="srch-tip-item"><kbd>↑↓</kbd> Naviguer</div>
      <div class="srch-tip-item"><kbd>↵</kbd> Ouvrir</div>
      <div class="srch-tip-item"><kbd>Esc</kbd> Fermer</div>
    </div>
  </div>
</div>
<div id="notif-panel">
  <div class="notif-head">
    <div class="notif-head-title">Notifications</div>
    <button class="notif-read-all" onclick="markAllRead()">Tout marquer lu</button>
  </div>
  <div class="notif-list" id="notif-list"></div>
</div>
`);

// ============ SEARCH LOGIC ============
let searchTimeout = null;
let searchResults = [];
let selectedIdx   = -1;

const overlay   = document.getElementById('srch-overlay');
const box       = document.getElementById('srch-box');
const input     = document.getElementById('srch-input');
const resultsEl = document.getElementById('srch-results');

function openSearch() {
  overlay.classList.add('open');
  setTimeout(() => input.focus(), 50);
}
function closeSearch() {
  overlay.classList.remove('open');
  input.value = '';
  resultsEl.innerHTML = '';
  selectedIdx = -1;
}

overlay.addEventListener('click', e => { if (e.target === overlay) closeSearch(); });

input.addEventListener('input', () => {
  clearTimeout(searchTimeout);
  const q = input.value.trim();
  if (q.length < 2) { resultsEl.innerHTML = ''; return; }
  searchTimeout = setTimeout(() => doSearch(q), 200);
});

input.addEventListener('keydown', e => {
  const items = resultsEl.querySelectorAll('.srch-item');
  if (e.key === 'ArrowDown') { e.preventDefault(); selectedIdx=Math.min(selectedIdx+1,items.length-1); highlight(items); }
  else if (e.key === 'ArrowUp') { e.preventDefault(); selectedIdx=Math.max(selectedIdx-1,0); highlight(items); }
  else if (e.key === 'Enter') { if(items[selectedIdx]) items[selectedIdx].click(); }
  else if (e.key === 'Escape') closeSearch();
});

function highlight(items) {
  items.forEach((it,i)=>it.classList.toggle('active',i===selectedIdx));
  if(items[selectedIdx]) items[selectedIdx].scrollIntoView({block:'nearest'});
}

const TYPE_ICONS = {
  projet:  {icon:'<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',color:'#36A9E1',bg:'rgba(54,169,225,.15)'},
  tache:   {icon:'<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',color:'#f0a500',bg:'rgba(240,165,0,.15)'},
  client:  {icon:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',color:'#2ecc87',bg:'rgba(46,204,135,.15)'},
  user:    {icon:'<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',color:'#9b8fff',bg:'rgba(155,143,255,.15)'},
  message: {icon:'<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',color:'#7a78a0',bg:'rgba(122,120,160,.15)'},
};
const TYPE_LABELS = {projet:'Projets',tache:'Tâches',client:'Clients',user:'Personnes',message:'Messages'};
const TYPE_LINKS  = {
  projet:  id=>'fichiers.php?projet_id='+id,
  tache:   id=>'dashboard.php#taches',
  client:  id=>'dashboard.php#clients',
  user:    id=>'collaborateur.php?id='+id,
  message: id=>'chat.php',
};

async function doSearch(q) {
  resultsEl.innerHTML = '<div class="srch-empty">Recherche en cours…</div>';
  const res  = await fetch('search_api.php?action=search&q=' + encodeURIComponent(q));
  const data = await res.json();
  searchResults = data.results || [];
  selectedIdx   = -1;

  if (!searchResults.length) {
    resultsEl.innerHTML = '<div class="srch-empty">Aucun résultat pour "<strong>' + q + '</strong>"</div>';
    return;
  }

  // Grouper par type
  const groups = {};
  searchResults.forEach(r => {
    if (!groups[r.type]) groups[r.type] = [];
    groups[r.type].push(r);
  });

  let html = '';
  for (const [type, items] of Object.entries(groups)) {
    const icon = TYPE_ICONS[type] || TYPE_ICONS.message;
    html += `<div class="srch-section"><div class="srch-section-label">${TYPE_LABELS[type]||type}</div>`;
    items.forEach(item => {
      const link = TYPE_LINKS[type] ? TYPE_LINKS[type](item.id) : '#';
      html += `<a class="srch-item" href="${link}">
        <div class="srch-icon" style="background:${icon.bg}">
          <svg viewBox="0 0 24 24" style="stroke:${icon.color}">${icon.icon}</svg>
        </div>
        <div>
          <div class="srch-title">${item.titre}</div>
          <div class="srch-meta">${item.meta||''}</div>
        </div>
      </a>`;
    });
    html += '</div>';
  }
  resultsEl.innerHTML = html;
}

// ============ KEYBOARD SHORTCUT ============
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
  if (e.key === 'Escape' && overlay.classList.contains('open')) closeSearch();
});

// ============ INJECT SEARCH TRIGGER IN TOPBAR ============
window.addEventListener('DOMContentLoaded', () => {
  const topbarRight = document.querySelector('.topbar-right');
  if (topbarRight) {
    const btn = document.createElement('div');
    btn.id = 'srch-trigger';
    btn.onclick = openSearch;
    btn.innerHTML = `<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> Rechercher <kbd>Ctrl K</kbd>`;
    topbarRight.prepend(btn);
  }
});

// ============ NOTIFICATIONS ============
let lastNotifId = 0;
let notifOpen   = false;
const notifPanel = document.getElementById('notif-panel');
const notifList  = document.getElementById('notif-list');

function toggleNotifPanel() {
  notifOpen = !notifOpen;
  notifPanel.classList.toggle('open', notifOpen);
  if (notifOpen) { markAllRead(); renderBadge(0); }
}

document.addEventListener('click', e => {
  if (notifOpen && !e.target.closest('#notif-panel') && !e.target.closest('[onclick*="toggleNotif"]')) {
    notifOpen = false;
    notifPanel.classList.remove('open');
  }
});

function renderBadge(count) {
  const badge = document.getElementById('notif-badge');
  if (!badge) return;
  badge.textContent = count > 9 ? '9+' : count;
  badge.classList.toggle('show', count > 0);
}

function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (diff < 60) return 'À l\'instant';
  if (diff < 3600) return Math.floor(diff/60)+'m';
  if (diff < 86400) return Math.floor(diff/3600)+'h';
  return Math.floor(diff/86400)+'j';
}

async function loadNotifs() {
  const res  = await fetch('search_api.php?action=notifs&since='+lastNotifId);
  const data = await res.json();

  if (data.notifs && data.notifs.length) {
    // Nouvelles notifs → son de notification visuel
    const isNew = data.notifs.some(n => n.id > lastNotifId && !n.lu);
    if (isNew && lastNotifId > 0) pulseNotifIcon();
    lastNotifId = data.last_id;
    renderNotifList(data.notifs);
  }
  renderBadge(data.unread || 0);
}

function renderNotifList(notifs) {
  if (!notifs.length) {
    notifList.innerHTML = '<div class="notif-empty">Aucune notification</div>';
    return;
  }
  notifList.innerHTML = notifs.map(n => `
    <div class="notif-item ${n.lu=='0'?'unread':''}" onclick="readOne(${n.id},'${n.lien||''}')">
      <div class="notif-dot ${n.lu!='0'?'read':''}"></div>
      <div class="notif-content">
        <div class="notif-msg">${n.message}</div>
        <div class="notif-time">${timeAgo(n.created_at)}</div>
      </div>
    </div>`).join('');
}

function readOne(id, lien) {
  fetch('search_api.php', {method:'POST',body:new URLSearchParams({action:'read_one',id})});
  if (lien) window.location.href = lien;
}

async function markAllRead() {
  await fetch('search_api.php', {method:'POST',body:new URLSearchParams({action:'read_notifs'})});
  renderBadge(0);
  document.querySelectorAll('.notif-item').forEach(el => el.classList.remove('unread'));
  document.querySelectorAll('.notif-dot').forEach(el => el.classList.add('read'));
}

function pulseNotifIcon() {
  const btn = document.getElementById('notif-icon-btn');
  if (!btn) return;
  btn.style.transform = 'scale(1.2)';
  btn.style.color = '#36A9E1';
  setTimeout(() => { btn.style.transform=''; btn.style.color=''; }, 500);
}

// ============ INJECT NOTIF BUTTON IN TOPBAR ============
window.addEventListener('DOMContentLoaded', () => {
  const topbarRight = document.querySelector('.topbar-right');
  if (topbarRight) {
    const btn = document.createElement('div');
    btn.className = 'icon-btn';
    btn.id = 'notif-icon-btn';
    btn.onclick = toggleNotifPanel;
    btn.style.position = 'relative';
    btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    <div class="notif-badge" id="notif-badge"></div>`;
    topbarRight.prepend(btn);
  }
  // Premier chargement + polling toutes les 10s
  loadNotifs();
  setInterval(loadNotifs, 10000);
});

// Exposer globalement
window.openSearch     = openSearch;
window.closeSearch    = closeSearch;
window.toggleNotif    = toggleNotifPanel;
window.markAllRead    = markAllRead;

})();
