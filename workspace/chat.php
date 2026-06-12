<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$user = currentUser();
$db   = getDB();
$jours  = ['Sunday'=>'Dimanche','Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi'];
$moisFr = ['January'=>'janvier','February'=>'février','March'=>'mars','April'=>'avril','May'=>'mai','June'=>'juin','July'=>'juillet','August'=>'août','September'=>'septembre','October'=>'octobre','November'=>'novembre','December'=>'décembre'];
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Messages — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--danger:#e05252;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html,body{height:100%;overflow:hidden;background:var(--bg);}
body{font-family:'Poppins',sans-serif;color:var(--text);}

/* LAYOUT GLOBAL */
.app{display:flex;height:100vh;height:100dvh;}

/* SIDEBAR CANAUX */
.channels{width:260px;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;transition:transform .3s;z-index:200;}
.ch-top{height:56px;display:flex;align-items:center;padding:0 16px;gap:10px;border-bottom:1px solid var(--border);flex-shrink:0;}
.back-btn{display:flex;align-items:center;gap:5px;color:var(--muted);text-decoration:none;font-size:12px;font-weight:500;transition:color .2s;}
.back-btn:hover{color:var(--accent);}
.back-btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.ch-title{font-size:14px;font-weight:700;color:#fff;}
.ch-list{flex:1;overflow-y:auto;padding:8px 0;}
.ch-section{font-size:9px;font-weight:700;color:var(--muted);letter-spacing:2px;text-transform:uppercase;padding:10px 16px 4px;}
.ch-item{display:flex;align-items:center;gap:10px;padding:9px 16px;cursor:pointer;transition:background .15s;border-left:3px solid transparent;}
.ch-item:hover{background:rgba(54,169,225,.07);}
.ch-item.active{background:rgba(54,169,225,.1);border-left-color:var(--accent);}
.ch-av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;}
.ch-info{flex:1;min-width:0;}
.ch-name{font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ch-sub{font-size:10px;color:var(--muted);}
.ch-badge{background:var(--accent);color:#fff;font-size:9px;font-weight:700;border-radius:99px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;flex-shrink:0;}

/* ZONE CHAT */
.chat-zone{flex:1;display:flex;flex-direction:column;min-width:0;position:relative;}
.chat-header{height:56px;background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 16px;gap:12px;flex-shrink:0;}
.hamburger{display:none;background:none;border:none;color:var(--text);cursor:pointer;padding:4px;flex-shrink:0;}
.hamburger svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.chat-header-av{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;}
.chat-header-info{flex:1;min-width:0;}
.chat-header-name{font-size:14px;font-weight:700;color:#fff;}
.chat-header-sub{font-size:11px;color:var(--muted);}
.clear-btn{background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:5px 10px;font-size:11px;color:var(--muted);cursor:pointer;font-family:'Poppins',sans-serif;display:none;}
.clear-btn.show{display:block;}

/* MESSAGES */
.messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:2px;-webkit-overflow-scrolling:touch;}
.messages::-webkit-scrollbar{width:3px;}
.messages::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}
.day-sep{text-align:center;font-size:10px;font-weight:700;color:var(--muted);letter-spacing:1px;text-transform:uppercase;margin:12px 0;display:flex;align-items:center;gap:8px;}
.day-sep::before,.day-sep::after{content:'';flex:1;height:1px;background:var(--border);}
.msg-group{display:flex;flex-direction:column;gap:1px;margin-bottom:6px;}
.msg-row{display:flex;align-items:flex-end;gap:8px;}
.msg-row.mine{flex-direction:row-reverse;}
.msg-av{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;margin-bottom:2px;}
.msg-av.hidden{visibility:hidden;}
.msg-content{max-width:72%;display:flex;flex-direction:column;}
.msg-row.mine .msg-content{align-items:flex-end;}
.msg-sender{font-size:10px;font-weight:600;color:var(--muted);margin-bottom:3px;padding:0 4px;}
.msg-bubble{padding:9px 13px;border-radius:16px;font-size:13px;line-height:1.5;word-break:break-word;}
.msg-row:not(.mine) .msg-bubble{background:var(--bg3);color:var(--text);border-bottom-left-radius:4px;}
.msg-row.mine .msg-bubble{background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;border-bottom-right-radius:4px;}
.msg-bubble.ai{background:rgba(54,169,225,.08);border:1px solid rgba(54,169,225,.15);border-bottom-left-radius:4px;}
.msg-time{font-size:10px;color:var(--muted);margin-top:3px;padding:0 4px;}
.typing{display:flex;gap:4px;align-items:center;padding:10px 14px;}
.typing span{width:6px;height:6px;border-radius:50%;background:var(--muted);animation:bounce .8s ease infinite;}
.typing span:nth-child(2){animation-delay:.15s;}
.typing span:nth-child(3){animation-delay:.3s;}
@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}

/* ZONE SAISIE */
.input-zone{padding:12px 16px;border-top:1px solid var(--border);background:var(--bg2);flex-shrink:0;padding-bottom:max(12px,env(safe-area-inset-bottom));}
.input-wrap{display:flex;align-items:flex-end;gap:8px;background:var(--bg3);border:1px solid var(--border);border-radius:12px;padding:8px 8px 8px 14px;transition:border-color .2s;}
.input-wrap:focus-within{border-color:var(--accent);}
#msgInput{flex:1;background:none;border:none;outline:none;color:var(--text);font-family:'Poppins',sans-serif;font-size:14px;resize:none;max-height:120px;line-height:1.5;}
#msgInput::placeholder{color:var(--muted);}
.send-btn{width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,var(--primary),var(--accent));border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:opacity .2s;}
.send-btn:disabled{opacity:.4;cursor:not-allowed;}
.send-btn svg{width:16px;height:16px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;}

/* EMPTY STATE */
.empty-chat{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--muted);text-align:center;padding:24px;}
.empty-chat svg{width:48px;height:48px;fill:none;stroke:currentColor;stroke-width:1.2;opacity:.2;margin-bottom:12px;}
.empty-chat p{font-size:13px;}

/* OVERLAY MOBILE */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:150;}

/* MOBILE */
@media(max-width:640px){
  .channels{position:fixed;top:0;left:0;height:100%;transform:translateX(-100%);box-shadow:4px 0 24px rgba(0,0,0,.5);}
  .channels.open{transform:translateX(0);}
  .overlay.open{display:block;}
  .hamburger{display:flex;}
  .chat-header{padding:0 12px;}
  .messages{padding:12px;}
  .input-zone{padding:8px 12px;padding-bottom:max(8px,env(safe-area-inset-bottom));}
  #msgInput{font-size:16px;}/* prevent zoom iOS */
}
</style>
</head>
<body>
<div class="app">
  <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

  <!-- SIDEBAR CANAUX -->
  <div class="channels" id="sidebar">
    <div class="ch-top">
      <a class="back-btn" href="dashboard.php"><svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg> Workspace</a>
      <div class="ch-title">Messages</div>
    </div>
    <div class="ch-list" id="channelList">
      <div class="ch-section">Général</div>
      <div class="ch-item active" id="ch-general" onclick="switchChannel(null,'Équipe UP TECH GROUP','Canal général','#36A9E1','E')">
        <div class="ch-av" style="background:linear-gradient(135deg,#29235C,#36A9E1)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="ch-info"><div class="ch-name">Équipe UP TECH GROUP</div><div class="ch-sub">Canal général</div></div>
      </div>
      <div class="ch-section">Assistant IA</div>
      <div class="ch-item" id="ch-ai" onclick="switchChannel('ai','Assistant IA','Répondre à vos questions','#9b8fff','AI')">
        <div class="ch-av" style="background:linear-gradient(135deg,#5b4fd4,#9b8fff)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div>
        <div class="ch-info"><div class="ch-name">Assistant IA</div><div class="ch-sub">UP TECH AI</div></div>
      </div>
      <div class="ch-section">Messages privés</div>
      <div id="dmList"></div>
    </div>
  </div>

  <!-- ZONE CHAT -->
  <div class="chat-zone">
    <div class="chat-header">
      <button class="hamburger" onclick="openSidebar()"><svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="chat-header-av" id="chatAv" style="background:linear-gradient(135deg,#29235C,#36A9E1)">E</div>
      <div class="chat-header-info">
        <div class="chat-header-name" id="chatName">Équipe UP TECH GROUP</div>
        <div class="chat-header-sub" id="chatSub">Canal général</div>
      </div>
      <button class="clear-btn" id="clearAiBtn" onclick="clearAI()">Effacer</button>
    </div>

    <div class="messages" id="messages">
      <div class="empty-chat">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <p>Chargement des messages…</p>
      </div>
    </div>

    <div class="input-zone">
      <div class="input-wrap">
        <textarea id="msgInput" rows="1" placeholder="Écrire un message…"></textarea>
        <button class="send-btn" id="sendBtn" onclick="sendMessage()">
          <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const ME_ID   = <?= $user['id'] ?>;
const ME_NOM  = <?= json_encode($user['nom']) ?>;

let currentChannel = null; // null = général, 'ai' = IA, number = DM user_id
let lastMsgId = 0;
let polling   = null;
let isAI      = false;
let sending   = false;

// ===== SIDEBAR MOBILE =====
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('open'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('open'); }

// ===== CHANGER DE CANAL =====
function switchChannel(id, name, sub, color, avText) {
  // Désactiver l'ancien
  document.querySelectorAll('.ch-item').forEach(i => i.classList.remove('active'));

  // Activer le nouveau
  const chId = id === null ? 'ch-general' : id === 'ai' ? 'ch-ai' : 'ch-dm-'+id;
  document.getElementById(chId)?.classList.add('active');

  currentChannel = id;
  isAI = id === 'ai';
  lastMsgId = 0;

  // Mettre à jour le header
  document.getElementById('chatAv').textContent = avText;
  document.getElementById('chatAv').style.background = `linear-gradient(135deg,${color}aa,${color})`;
  document.getElementById('chatName').textContent = name;
  document.getElementById('chatSub').textContent = sub;
  document.getElementById('clearAiBtn').className = isAI ? 'clear-btn show' : 'clear-btn';
  document.getElementById('msgInput').placeholder = isAI ? 'Posez votre question à l\'IA…' : 'Écrire un message…';

  // Vider et charger
  document.getElementById('messages').innerHTML = '<div class="empty-chat"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p>Chargement…</p></div>';

  clearInterval(polling);
  if (isAI) {
    loadMessages();
  } else {
    loadMessages();
    polling = setInterval(loadMessages, 2500);
  }

  closeSidebar();
}

// ===== CHARGER MESSAGES =====
async function loadMessages() {
  try {
    let url;
    if (isAI) {
      // Pour l'IA on recharge tout depuis chat_ai_history
      url = 'chat_api.php?action=historique&ai=1';
    } else if (currentChannel && currentChannel !== 'ai') {
      url = `chat_api.php?action=messages&destinataire_id=${currentChannel}&since=${lastMsgId}`;
    } else {
      url = `chat_api.php?action=messages&since=${lastMsgId}`;
    }

    const r   = await fetch(url);
    const msgs = await r.json();
    if (!Array.isArray(msgs) || msgs.length === 0) {
      if (lastMsgId === 0) showEmpty();
      return;
    }

    const container = document.getElementById('messages');
    const wasEmpty  = lastMsgId === 0;
    if (wasEmpty) container.innerHTML = '';

    msgs.forEach(m => {
      if (m.id && m.id > lastMsgId) lastMsgId = m.id;
      container.appendChild(buildMsg(m));
    });

    if (wasEmpty || isNearBottom()) scrollBottom();
  } catch(e) {}
}

function showEmpty() {
  document.getElementById('messages').innerHTML = `
    <div class="empty-chat">
      <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <p>Aucun message — commencez la conversation !</p>
    </div>`;
}

function buildMsg(m) {
  const mine    = m.expediteur_id == ME_ID || m.role === 'user';
  const isAiMsg = m.role === 'assistant' || m.role === 'user';
  const nom     = m.expediteur_nom || (m.role === 'assistant' ? 'Assistant IA' : ME_NOM);
  const ini     = nom.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
  const color   = mine ? '#36A9E1' : '#' + ((m.expediteur_id||0)*2654435761 & 0xFFFFFF).toString(16).padStart(6,'5');
  const time    = m.created_at ? m.created_at.slice(11,16) : '';
  const contenu = (m.contenu||'').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');

  const row = document.createElement('div');
  row.className = 'msg-group';
  row.innerHTML = `
    <div class="msg-row ${mine?'mine':''}">
      <div class="msg-av" style="background:${mine?'linear-gradient(135deg,#29235C,#36A9E1)':'linear-gradient(135deg,'+color+','+color+'bb)'};">${ini}</div>
      <div class="msg-content">
        ${!mine?`<div class="msg-sender">${nom}</div>`:''}
        <div class="msg-bubble ${m.role==='assistant'?'ai':''}">${contenu}</div>
        <div class="msg-time">${time}</div>
      </div>
    </div>`;
  return row;
}

function isNearBottom() {
  const c = document.getElementById('messages');
  return c.scrollHeight - c.scrollTop - c.clientHeight < 100;
}
function scrollBottom() {
  const c = document.getElementById('messages');
  c.scrollTop = c.scrollHeight;
}

// ===== ENVOYER =====
async function sendMessage() {
  if (sending) return;
  const input   = document.getElementById('msgInput');
  const contenu = input.value.trim();
  if (!contenu) return;

  sending = true;
  input.value = '';
  input.style.height = 'auto';
  document.getElementById('sendBtn').disabled = true;

  if (isAI) {
    // Afficher message user immédiatement
    const userMsg = {expediteur_id:ME_ID,expediteur_nom:ME_NOM,contenu,role:'user',created_at:new Date().toISOString()};
    document.getElementById('messages').querySelector('.empty-chat')?.remove();
    document.getElementById('messages').appendChild(buildMsg(userMsg));
    scrollBottom();

    // Indicateur de frappe
    const typing = document.createElement('div');
    typing.className = 'msg-group';
    typing.id = 'typing';
    typing.innerHTML = `<div class="msg-row"><div class="msg-av" style="background:linear-gradient(135deg,#5b4fd4,#9b8fff)">AI</div><div class="msg-content"><div class="msg-bubble ai"><div class="typing"><span></span><span></span><span></span></div></div></div></div>`;
    document.getElementById('messages').appendChild(typing);
    scrollBottom();

    try {
      const fd = new FormData();
      fd.append('action','ai_chat');
      fd.append('message',contenu);
      const r    = await fetch('chat_api.php',{method:'POST',body:fd});
      const data = await r.json();
      document.getElementById('typing')?.remove();
      if (data.reply) {
        const aiMsg = {expediteur_id:0,expediteur_nom:'Assistant IA',contenu:data.reply,role:'assistant',created_at:new Date().toISOString()};
        document.getElementById('messages').appendChild(buildMsg(aiMsg));
        scrollBottom();
      }
    } catch(e) {
      document.getElementById('typing')?.remove();
    }
  } else {
    const fd = new FormData();
    fd.append('action','envoyer');
    fd.append('contenu',contenu);
    if (currentChannel && currentChannel !== 'ai') fd.append('destinataire_id',currentChannel);
    try {
      await fetch('chat_api.php',{method:'POST',body:fd});
      loadMessages();
    } catch(e){}
  }

  sending = false;
  document.getElementById('sendBtn').disabled = false;
  input.focus();
}

// ===== EFFACER IA =====
async function clearAI() {
  if (!confirm('Effacer l\'historique de conversation avec l\'IA ?')) return;
  await fetch('chat_api.php?action=clear_ai');
  document.getElementById('messages').innerHTML = '<div class="empty-chat"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p>Conversation effacée</p></div>';
}

// ===== CHARGER CONTACTS DM =====
async function loadContacts() {
  const r    = await fetch('chat_api.php?action=contacts');
  const data = await r.json();
  const list = document.getElementById('dmList');
  list.innerHTML = data.map(u => {
    const ini   = u.nom_complet.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
    const color = '#' + ((u.id)*2654435761 & 0xFFFFFF).toString(16).padStart(6,'5');
    return `<div class="ch-item" id="ch-dm-${u.id}" onclick="switchChannel(${u.id},'${u.nom_complet.replace(/'/g,"\\'")}','Message privé','${color}','${ini}')">
      <div class="ch-av" style="background:${color}">${ini}</div>
      <div class="ch-info"><div class="ch-name">${u.nom_complet}</div><div class="ch-sub">${u.role}</div></div>
    </div>`;
  }).join('');
}

// ===== AUTO-RESIZE TEXTAREA =====
document.getElementById('msgInput').addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// ===== ENVOI ENTER (desktop: Enter, mobile: ignore) =====
document.getElementById('msgInput').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey && window.innerWidth > 640) {
    e.preventDefault();
    sendMessage();
  }
});

// ===== INIT =====
loadContacts();
switchChannel(null,'Équipe UP TECH GROUP','Canal général','#36A9E1','E');
</script>
</body>
</html>
