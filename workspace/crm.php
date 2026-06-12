<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
if (!isManager()) { header('Location: /workspace/dashboard.php'); exit; }
$user = currentUser();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>CRM — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--warning:#f0a500;--danger:#e05252;--purple:#9b8fff;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 0% 0%,rgba(41,35,92,0.5) 0%,transparent 60%);pointer-events:none;}
.topbar{position:sticky;top:0;z-index:100;background:rgba(19,18,42,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);height:56px;display:flex;align-items:center;padding:0 24px;gap:16px;}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;padding:6px 12px;border-radius:8px;transition:all .2s;}
.back-btn:hover{color:var(--accent);}
.back-btn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.topbar-title{flex:1;font-size:15px;font-weight:700;color:#fff;}
.page{max-width:1200px;margin:0 auto;padding:24px 20px 60px;position:relative;z-index:1;}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;}
.kpi-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;}
.kpi-icon svg{width:15px;height:15px;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.kpi-val{font-size:22px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;}
.kpi-label{font-size:11px;color:var(--muted);margin-top:3px;}

/* LAYOUT */
.layout{display:grid;grid-template-columns:320px 1fr;gap:16px;}
.sidebar-crm{display:flex;flex-direction:column;gap:12px;}
.main-crm{display:flex;flex-direction:column;gap:14px;}

/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px;}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:8px;}
.card-title{font-size:13px;font-weight:700;color:#fff;}
.new-btn{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:8px;padding:6px 14px;color:#fff;font-family:'Poppins',sans-serif;font-size:11px;font-weight:600;cursor:pointer;}
.search-input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:12px;outline:none;margin-bottom:10px;}
.search-input:focus{border-color:var(--accent);}

/* CLIENT LIST */
.client-item{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;cursor:pointer;transition:background .15s;border:1px solid transparent;}
.client-item:hover{background:rgba(54,169,225,.06);}
.client-item.active{background:rgba(54,169,225,.1);border-color:rgba(54,169,225,.3);}
.client-av{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;}
.client-info{flex:1;min-width:0;}
.client-name{font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.client-sub{font-size:10px;color:var(--muted);margin-top:1px;}
.badge{display:inline-flex;align-items:center;padding:2px 7px;border-radius:99px;font-size:10px;font-weight:700;}
.bg-green{background:rgba(46,204,135,.15);color:var(--success);}
.bg-orange{background:rgba(240,165,0,.15);color:var(--warning);}
.bg-blue{background:rgba(54,169,225,.15);color:var(--accent);}
.bg-red{background:rgba(224,82,82,.15);color:var(--danger);}
.suivi-dot{width:7px;height:7px;border-radius:50%;background:var(--warning);flex-shrink:0;}

/* INTERACTION TYPES */
.type-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:10px;font-weight:700;}
.type-appel{background:rgba(54,169,225,.15);color:var(--accent);}
.type-email{background:rgba(46,204,135,.15);color:var(--success);}
.type-reunion{background:rgba(155,143,255,.15);color:var(--purple);}
.type-whatsapp{background:rgba(46,204,135,.15);color:var(--success);}
.type-visite{background:rgba(240,165,0,.15);color:var(--warning);}
.type-autre{background:rgba(122,120,160,.15);color:var(--muted);}

/* INTERACTION ITEM */
.inter-item{padding:12px;background:var(--bg3);border-radius:10px;margin-bottom:8px;border-left:3px solid transparent;}
.inter-item.appel{border-left-color:var(--accent);}
.inter-item.email{border-left-color:var(--success);}
.inter-item.reunion{border-left-color:var(--purple);}
.inter-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;}
.inter-sujet{font-size:12px;font-weight:700;color:#fff;}
.inter-meta{font-size:10px;color:var(--muted);margin-top:2px;}
.inter-body{font-size:12px;color:var(--text);line-height:1.6;}
.inter-suivi{font-size:11px;color:var(--warning);margin-top:6px;display:flex;align-items:center;gap:5px;}
.inter-suivi svg{width:12px;height:12px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.del-btn{background:rgba(224,82,82,.1);border:1px solid rgba(224,82,82,.2);border-radius:6px;padding:3px 8px;color:var(--danger);font-size:10px;cursor:pointer;font-family:'Poppins',sans-serif;white-space:nowrap;flex-shrink:0;}

/* OPP */
.opp-item{padding:12px;background:var(--bg3);border-radius:10px;margin-bottom:8px;}
.opp-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;}
.opp-titre{font-size:12px;font-weight:700;color:#fff;}
.opp-val{font-size:13px;font-weight:800;font-family:'Space Mono',monospace;color:var(--success);}
.opp-prob{height:4px;background:var(--bg);border-radius:99px;overflow:hidden;margin-bottom:6px;}
.opp-prob-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--primary),var(--accent));}
.opp-meta{font-size:10px;color:var(--muted);}

/* EMPTY */
.empty{text-align:center;padding:28px;color:var(--muted);font-size:12px;}
.empty svg{width:40px;height:40px;opacity:.2;margin-bottom:10px;fill:none;stroke:currentColor;stroke-width:1.2;stroke-linecap:round;}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1000;align-items:center;justify-content:center;padding:16px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;}
.modal-head{padding:18px 22px 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);padding-bottom:14px;}
.modal-head h3{font-size:15px;font-weight:700;color:#fff;}
.modal-close{background:var(--bg3);border:1px solid var(--border);border-radius:8px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);}
.modal-close svg{width:11px;height:11px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;}
.modal-body{padding:18px 22px;}
.field{margin-bottom:13px;}
label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;display:block;margin-bottom:5px;}
input,select,textarea{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:border-color .2s;-webkit-appearance:none;}
input:focus,select:focus,textarea:focus{border-color:var(--accent);}
select option{background:var(--bg2);}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.modal-foot{padding:0 22px 20px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border);padding-top:14px;}
.btn-p{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:9px;padding:9px 22px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-s{background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:9px 16px;color:var(--muted);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}

/* TOAST */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:11px 20px;font-size:13px;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;}
#toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
#toast.success{border-color:rgba(46,204,135,.4);color:var(--success);}
#toast.error{border-color:rgba(224,82,82,.4);color:var(--danger);}

::-webkit-scrollbar{width:4px;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}
@media(max-width:900px){.layout{grid-template-columns:1fr;}.kpi-grid{grid-template-columns:repeat(2,1fr);}}
</style>
</head>
<body>
<div class="topbar">
  <a class="back-btn" href="dashboard.php"><svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg> Workspace</a>
  <div class="topbar-title">CRM — Suivi clients</div>
  <a class="new-btn" href="export_csv.php?type=crm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">Export CSV</a>
</div>

<div class="page">

  <div class="kpi-grid" id="kpiGrid">
    <?php for($i=0;$i<4;$i++): ?><div class="kpi-card" style="opacity:.3"><div style="height:60px"></div></div><?php endfor; ?>
  </div>

  <div class="layout">
    <div class="sidebar-crm">
      <!-- SUIVIS DU JOUR -->
      <div class="card" id="suivisCard" style="display:none">
        <div class="card-title" style="margin-bottom:10px;color:var(--warning)">⚑ Suivis du jour</div>
        <div id="suivisList"></div>
      </div>

      <!-- LISTE CLIENTS -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Clients & Prospects</div>
        </div>
        <input class="search-input" id="searchClient" placeholder="Rechercher…" oninput="filterClients(this.value)">
        <div id="clientList" style="max-height:500px;overflow-y:auto"></div>
      </div>
    </div>

    <div class="main-crm">
      <!-- WELCOME -->
      <div class="card" id="welcomeCard">
        <div class="empty">
          <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 5.5 5.5l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <p>Sélectionnez un client pour voir ses interactions et opportunités</p>
        </div>
      </div>

      <!-- FICHE CLIENT -->
      <div id="ficheClient" style="display:none">

        <!-- EN-TÊTE CLIENT -->
        <div class="card" style="margin-bottom:14px">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div style="display:flex;gap:14px;align-items:center">
              <div class="client-av" id="ficheAv" style="width:50px;height:50px;border-radius:12px;font-size:18px"></div>
              <div>
                <div style="font-size:17px;font-weight:800;color:#fff" id="ficheNom"></div>
                <div style="font-size:12px;color:var(--muted);margin-top:3px" id="ficheMeta"></div>
                <div style="margin-top:6px" id="ficheBadge"></div>
              </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button class="new-btn" onclick="openAddInter()">+ Interaction</button>
              <button class="new-btn" style="background:linear-gradient(135deg,#9b8fff,#36A9E1)" onclick="openAddOpp()">+ Opportunité</button>
            </div>
          </div>
        </div>

        <!-- INTERACTIONS + OPPORTUNITÉS -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Historique interactions</div>
              <span id="interCount" style="font-size:11px;color:var(--muted)"></span>
            </div>
            <div id="interList"></div>
          </div>
          <div class="card">
            <div class="card-header">
              <div class="card-title">Opportunités</div>
              <span id="oppPipeline" style="font-size:11px;color:var(--success);font-family:'Space Mono',monospace"></span>
            </div>
            <div id="oppList"></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- MODAL INTERACTION -->
<div class="modal-overlay" id="modalInter">
  <div class="modal">
    <div class="modal-head"><h3>Nouvelle interaction</h3><div class="modal-close" onclick="closeModal('modalInter')"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div></div>
    <div class="modal-body">
      <div class="field-row">
        <div class="field"><label>Type *</label><select id="iType"><option>Appel</option><option>Email</option><option>Réunion</option><option>WhatsApp</option><option>Visite</option><option>Autre</option></select></div>
        <div class="field"><label>Date *</label><input type="datetime-local" id="iDate"></div>
      </div>
      <div class="field"><label>Sujet *</label><input id="iSujet" placeholder="Objet de l'interaction"></div>
      <div class="field"><label>Contenu / Notes</label><textarea id="iContenu" rows="3" placeholder="Détails de l'échange…"></textarea></div>
      <div class="field-row">
        <div class="field"><label>Durée (min)</label><input type="number" id="iDuree" min="1" placeholder="30"></div>
        <div class="field"><label>Prochain suivi</label><input type="date" id="iSuivi"></div>
      </div>
      <div class="field"><label>Note de suivi</label><input id="iNoteSuivi" placeholder="Action à faire lors du prochain contact"></div>
    </div>
    <div class="modal-foot"><button class="btn-s" onclick="closeModal('modalInter')">Annuler</button><button class="btn-p" onclick="saveInter()">Enregistrer</button></div>
  </div>
</div>

<!-- MODAL OPPORTUNITÉ -->
<div class="modal-overlay" id="modalOpp">
  <div class="modal">
    <div class="modal-head"><h3>Nouvelle opportunité</h3><div class="modal-close" onclick="closeModal('modalOpp')"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div></div>
    <div class="modal-body">
      <div class="field"><label>Titre *</label><input id="oTitre" placeholder="Développement application mobile…"></div>
      <div class="field-row">
        <div class="field"><label>Valeur estimée</label><input type="number" id="oValeur" min="0" placeholder="500000"></div>
        <div class="field"><label>Devise</label><select id="oDevise"><option>FCFA</option><option>EUR</option><option>USD</option></select></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Probabilité (%)</label><input type="number" id="oProb" min="0" max="100" value="50"></div>
        <div class="field"><label>Statut</label><select id="oStatut"><option>Identifiée</option><option>Qualifiée</option><option>Proposition</option><option>Négociation</option><option>Gagnée</option><option>Perdue</option></select></div>
      </div>
      <div class="field"><label>Date de clôture prévisionnelle</label><input type="date" id="oCloture"></div>
      <div class="field"><label>Notes</label><textarea id="oNotes" rows="2"></textarea></div>
    </div>
    <div class="modal-foot"><button class="btn-s" onclick="closeModal('modalOpp')">Annuler</button><button class="btn-p" onclick="saveOpp()">Enregistrer</button></div>
  </div>
</div>

<div id="toast"></div>

<script>
let allClients = [], currentClientId = null;
const TYPE_CLASS = {Appel:'appel',Email:'email','Réunion':'reunion',WhatsApp:'email',Visite:'reunion',Autre:'autre'};
const STATUT_COLORS = {'Client actif':'bg-green','Prospect':'bg-orange','Client inactif':'bg-red'};
const OPP_STATUT_COLORS = {Identifiée:'bg-blue',Qualifiée:'bg-blue',Proposition:'bg-orange',Négociation:'bg-orange',Gagnée:'bg-green',Perdue:'bg-red'};

async function api(p){const fd=new FormData();Object.entries(p).forEach(([k,v])=>fd.append(k,v));const r=await fetch('crm_api.php',{method:'POST',body:fd});return r.json();}
async function apiGet(p){const r=await fetch('crm_api.php?'+new URLSearchParams(p));return r.json();}
function toast(msg,type='success'){const t=document.getElementById('toast');t.textContent=msg;t.className='show '+type;setTimeout(()=>t.className='',3500);}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');}));
function fmt(n){return parseInt(n||0).toLocaleString('fr-FR');}

async function init(){
  await Promise.all([loadStats(),loadClients(),loadSuivisAuj()]);
}

async function loadStats(){
  const s=await apiGet({action:'stats'});
  document.getElementById('kpiGrid').innerHTML=[
    {ico:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',val:s.totalClients,label:'Clients actifs',color:'#2ecc87',bg:'rgba(46,204,135,.12)'},
    {ico:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',val:s.totalProspects,label:'Prospects',color:'#f0a500',bg:'rgba(240,165,0,.12)'},
    {ico:'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',val:fmt(s.pipelinePond)+' FCFA',label:'Pipeline pondéré',color:'#36A9E1',bg:'rgba(54,169,225,.12)'},
    {ico:'<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>',val:s.tauxConversion+'%',label:'Taux de conversion',color:'#9b8fff',bg:'rgba(155,143,255,.12)'},
  ].map(k=>`<div class="kpi-card"><div class="kpi-icon" style="background:${k.bg}"><svg viewBox="0 0 24 24" fill="none" stroke="${k.color}" stroke-width="1.8" stroke-linecap="round">${k.ico}</svg></div><div class="kpi-val">${k.val}</div><div class="kpi-label">${k.label}</div></div>`).join('');
}

async function loadSuivisAuj(){
  const data=await apiGet({action:'suivis_aujourd_hui'});
  const card=document.getElementById('suivisCard');
  const list=document.getElementById('suivisList');
  if(!data.length){card.style.display='none';return;}
  card.style.display='block';
  list.innerHTML=data.map(s=>`<div style="display:flex;gap:8px;align-items:center;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.04)"><div class="suivi-dot"></div><div><div style="font-size:12px;font-weight:600;color:#fff">${s.client_nom}</div><div style="font-size:11px;color:var(--muted)">${s.note_suivi||s.sujet}</div></div></div>`).join('');
}

async function loadClients(){
  allClients=await apiGet({action:'clients'});
  renderClients(allClients);
}

function renderClients(list){
  const el=document.getElementById('clientList');
  el.innerHTML=list.map(c=>{
    const ini=c.raison_sociale.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
    const colors={'Client actif':'#2ecc87','Prospect':'#f0a500','Client inactif':'#e05252'};
    const col=colors[c.statut]||'#7a78a0';
    return `<div class="client-item${c.id==currentClientId?' active':''}" onclick="openClient(${c.id})">
      <div class="client-av" style="background:${col}22;color:${col}">${ini}</div>
      <div class="client-info">
        <div class="client-name">${c.raison_sociale}</div>
        <div class="client-sub">${c.nb_interactions} interaction(s)${c.prochain_suivi?` · Suivi: ${c.prochain_suivi}`:''}</div>
      </div>
      ${c.prochain_suivi?'<div class="suivi-dot" title="Suivi prévu"></div>':''}
    </div>`;
  }).join('') || '<div style="padding:16px;text-align:center;color:var(--muted);font-size:12px">Aucun client</div>';
}

function filterClients(q){renderClients(allClients.filter(c=>c.raison_sociale.toLowerCase().includes(q.toLowerCase())));}

async function openClient(id){
  currentClientId=id;
  document.querySelectorAll('.client-item').forEach(el=>el.classList.remove('active'));
  const item=document.querySelector(`.client-item[onclick="openClient(${id})"]`);
  if(item)item.classList.add('active');
  document.getElementById('welcomeCard').style.display='none';
  document.getElementById('ficheClient').style.display='block';

  const c=allClients.find(x=>x.id==id);
  if(c){
    const ini=c.raison_sociale.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
    const colors={'Client actif':'#2ecc87','Prospect':'#f0a500','Client inactif':'#e05252'};
    const col=colors[c.statut]||'#7a78a0';
    document.getElementById('ficheAv').textContent=ini;
    document.getElementById('ficheAv').style.background=col+'22';
    document.getElementById('ficheAv').style.color=col;
    document.getElementById('ficheNom').textContent=c.raison_sociale;
    document.getElementById('ficheMeta').textContent=(c.contact_nom||'')+(c.email?' · '+c.email:'')+(c.telephone?' · '+c.telephone:'');
    document.getElementById('ficheBadge').innerHTML=`<span class="badge ${STATUT_COLORS[c.statut]||'bg-blue'}">${c.statut}</span>`;
  }

  await Promise.all([loadInter(id),loadOpp(id)]);
}

async function loadInter(id){
  const data=await apiGet({action:'liste_interactions',client_id:id});
  document.getElementById('interCount').textContent=data.length+' échange(s)';
  const el=document.getElementById('interList');
  if(!data.length){el.innerHTML='<div class="empty"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/></svg><p>Aucune interaction enregistrée</p></div>';return;}
  el.innerHTML=data.map(i=>`
    <div class="inter-item ${TYPE_CLASS[i.type_interaction]||'autre'}">
      <div class="inter-head">
        <div>
          <span class="type-badge type-${(i.type_interaction||'autre').toLowerCase()}">${i.type_interaction}</span>
          <div class="inter-sujet" style="margin-top:5px">${i.sujet}</div>
          <div class="inter-meta">${i.user_nom} · ${i.date_interaction.slice(0,16).replace('T',' ')}${i.duree_min?' · '+i.duree_min+'min':''}</div>
        </div>
        <button class="del-btn" onclick="delInter(${i.id})">✕</button>
      </div>
      ${i.contenu?`<div class="inter-body">${i.contenu}</div>`:''}
      ${i.prochain_suivi?`<div class="inter-suivi"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Suivi : ${i.prochain_suivi}${i.note_suivi?' — '+i.note_suivi:''}</div>`:''}
    </div>`).join('');
}

async function loadOpp(id){
  const data=await apiGet({action:'liste_opportunites',client_id:id});
  const total=data.filter(o=>!['Gagnée','Perdue'].includes(o.statut)).reduce((s,o)=>s+parseFloat(o.valeur),0);
  document.getElementById('oppPipeline').textContent=total>0?fmt(total)+' FCFA':'';
  const el=document.getElementById('oppList');
  if(!data.length){el.innerHTML='<div class="empty"><svg viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/></svg><p>Aucune opportunité</p></div>';return;}
  el.innerHTML=data.map(o=>`
    <div class="opp-item">
      <div class="opp-head">
        <div class="opp-titre">${o.titre}</div>
        <div class="opp-val">${fmt(o.valeur)} ${o.devise}</div>
      </div>
      <div class="opp-prob"><div class="opp-prob-fill" style="width:${o.probabilite}%"></div></div>
      <div class="opp-meta">
        <span class="badge ${OPP_STATUT_COLORS[o.statut]||'bg-blue'}">${o.statut}</span>
        · ${o.probabilite}% · ${o.date_cloture?o.date_cloture:'Pas de date'}
        <button class="del-btn" style="margin-left:8px" onclick="delOpp(${o.id})">✕</button>
      </div>
    </div>`).join('');
}

// INTERACTIONS
function openAddInter(){
  document.getElementById('iDate').value=new Date().toISOString().slice(0,16);
  document.getElementById('iSujet').value='';
  document.getElementById('iContenu').value='';
  document.getElementById('iDuree').value='';
  document.getElementById('iSuivi').value='';
  document.getElementById('iNoteSuivi').value='';
  openModal('modalInter');
}
async function saveInter(){
  const r=await api({action:'add_interaction',client_id:currentClientId,type_interaction:document.getElementById('iType').value,sujet:document.getElementById('iSujet').value,contenu:document.getElementById('iContenu').value,date_interaction:document.getElementById('iDate').value,duree_min:document.getElementById('iDuree').value,prochain_suivi:document.getElementById('iSuivi').value,note_suivi:document.getElementById('iNoteSuivi').value});
  if(r.success){closeModal('modalInter');toast('Interaction enregistrée');loadInter(currentClientId);loadClients();}
  else toast(r.error||'Erreur','error');
}
async function delInter(id){if(!confirm('Supprimer cette interaction ?'))return;const r=await api({action:'del_interaction',id});if(r.success){toast('Supprimé');loadInter(currentClientId);}else toast('Erreur','error');}

// OPPORTUNITÉS
function openAddOpp(){
  document.getElementById('oTitre').value='';document.getElementById('oValeur').value='';document.getElementById('oProb').value='50';document.getElementById('oCloture').value='';document.getElementById('oNotes').value='';
  openModal('modalOpp');
}
async function saveOpp(){
  const r=await api({action:'add_opportunite',client_id:currentClientId,titre:document.getElementById('oTitre').value,valeur:document.getElementById('oValeur').value,devise:document.getElementById('oDevise').value,probabilite:document.getElementById('oProb').value,statut:document.getElementById('oStatut').value,date_cloture:document.getElementById('oCloture').value,notes:document.getElementById('oNotes').value});
  if(r.success){closeModal('modalOpp');toast('Opportunité créée');loadOpp(currentClientId);loadStats();}
  else toast(r.error||'Erreur','error');
}
async function delOpp(id){if(!confirm('Supprimer cette opportunité ?'))return;const r=await api({action:'del_opportunite',id});if(r.success){toast('Supprimé');loadOpp(currentClientId);loadStats();}else toast('Erreur','error');}

init();
</script>
</body>
</html>
