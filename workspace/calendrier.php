<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$user = currentUser();
$isAdmin   = isAdmin();
$isManager = isManager();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>UP TECH GROUP — Calendrier</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Mono&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;
  --card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;
  --success:#2ecc87;--warning:#f0a500;--danger:#e05252;--purple:#9b8fff;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html,body{height:100%;overflow:hidden;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);}

/* TOPBAR */
.topbar{height:56px;background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:12px;flex-shrink:0;}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;padding:6px 10px;border-radius:8px;transition:all .2s;white-space:nowrap;}
.back-btn:hover{color:var(--accent);background:rgba(54,169,225,.08);}
.back-icon{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.topbar-title{flex:1;font-size:15px;font-weight:700;color:#fff;}
.topbar-actions{display:flex;align-items:center;gap:8px;}

/* LAYOUT */
.app{display:flex;flex-direction:column;height:100vh;}
.calendar-shell{flex:1;display:flex;flex-direction:column;overflow:hidden;}

/* CALENDAR HEADER */
.cal-header{background:var(--bg2);border-bottom:1px solid var(--border);padding:12px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;flex-shrink:0;}
.nav-group{display:flex;align-items:center;gap:6px;}
.nav-btn{background:var(--bg3);border:1px solid var(--border);border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text);transition:all .2s;}
.nav-btn:hover{border-color:var(--accent);color:var(--accent);}
.today-btn{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0 14px;height:34px;font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;color:var(--text);cursor:pointer;transition:all .2s;}
.today-btn:hover{border-color:var(--accent);color:var(--accent);}
.current-period{font-size:16px;font-weight:700;color:#fff;min-width:180px;}
.view-tabs{display:flex;background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:3px;gap:2px;margin-left:auto;}
.view-tab{padding:5px 14px;border-radius:7px;font-size:12px;font-weight:500;color:var(--muted);cursor:pointer;border:none;background:none;font-family:'Poppins',sans-serif;transition:all .2s;white-space:nowrap;}
.view-tab.active{background:var(--card);color:#fff;box-shadow:0 2px 6px rgba(0,0,0,.3);}
.new-event-btn{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:9px;padding:0 18px;height:36px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;white-space:nowrap;}

/* LEGEND */
.legend{display:flex;align-items:center;gap:12px;padding:8px 20px;background:var(--bg2);border-bottom:1px solid var(--border);flex-wrap:wrap;}
.legend-item{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);}
.legend-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0;}

/* CALENDAR BODY */
.cal-body{flex:1;overflow:auto;position:relative;-webkit-overflow-scrolling:touch;}

/* ====== MONTH VIEW ====== */
.month-grid{display:grid;grid-template-columns:repeat(7,1fr);height:100%;}
.month-header-row{display:contents;}
.month-day-header{background:var(--bg2);border-bottom:1px solid var(--border);border-right:1px solid var(--border);padding:8px 0;text-align:center;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;position:sticky;top:0;z-index:10;}
.month-day-header:last-child{border-right:none;}
.month-cell{border-right:1px solid var(--border);border-bottom:1px solid var(--border);padding:6px;min-height:110px;cursor:pointer;transition:background .15s;position:relative;vertical-align:top;}
.month-cell:last-child{border-right:none;}
.month-cell:hover{background:rgba(54,169,225,.04);}
.month-cell.other-month{opacity:.35;}
.month-cell.today .day-num{background:var(--accent);color:#fff;border-radius:50%;}
.month-cell.selected{background:rgba(54,169,225,.08);}
.day-num{width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:var(--text);border-radius:50%;margin-bottom:4px;transition:all .2s;}
.ev-pill{border-radius:5px;padding:2px 6px;font-size:10px;font-weight:600;color:#fff;margin-bottom:2px;cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;border-left:3px solid rgba(0,0,0,.2);}
.ev-pill:hover{opacity:.85;}
.more-events{font-size:10px;color:var(--accent);font-weight:600;cursor:pointer;padding:2px 0;}

/* ====== WEEK VIEW ====== */
.week-grid{display:grid;grid-template-columns:60px repeat(7,1fr);min-height:100%;}
.week-time-col{position:sticky;left:0;background:var(--bg2);z-index:5;border-right:1px solid var(--border);}
.week-day-header{background:var(--bg2);border-bottom:1px solid var(--border);border-right:1px solid var(--border);padding:10px 8px;text-align:center;position:sticky;top:0;z-index:10;}
.week-day-header.today-col{background:rgba(54,169,225,.08);}
.wdh-name{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;}
.wdh-num{font-size:20px;font-weight:800;color:#fff;line-height:1.2;}
.wdh-num.today-num{color:var(--accent);}
.week-corner{background:var(--bg2);border-bottom:1px solid var(--border);border-right:1px solid var(--border);position:sticky;top:0;z-index:11;}
.week-time-label{height:60px;display:flex;align-items:flex-start;justify-content:flex-end;padding:4px 8px 0;font-size:10px;color:var(--muted);font-family:'Space Mono',monospace;}
.week-slot{height:60px;border-bottom:1px solid rgba(255,255,255,.04);border-right:1px solid var(--border);position:relative;cursor:pointer;transition:background .15s;}
.week-slot:hover{background:rgba(54,169,225,.04);}
.week-slot.today-col{background:rgba(54,169,225,.03);}
.week-slot:nth-child(2n){border-bottom-style:dashed;}
.week-ev{position:absolute;left:2px;right:2px;border-radius:6px;padding:3px 6px;font-size:10px;font-weight:600;color:#fff;overflow:hidden;cursor:pointer;border-left:3px solid rgba(0,0,0,.25);z-index:2;}
.week-ev:hover{opacity:.85;}
.now-line{position:absolute;left:0;right:0;height:2px;background:var(--danger);z-index:3;}
.now-dot{position:absolute;left:-4px;top:-4px;width:10px;height:10px;border-radius:50%;background:var(--danger);}

/* ====== DAY VIEW ====== */
.day-grid{display:grid;grid-template-columns:60px 1fr;min-height:100%;}
.day-header{grid-column:1/-1;background:var(--bg2);border-bottom:1px solid var(--border);padding:14px 20px;position:sticky;top:0;z-index:10;}
.day-header h2{font-size:20px;font-weight:800;color:#fff;}
.day-header span{font-size:13px;color:var(--muted);}
.day-time-label{height:60px;display:flex;align-items:flex-start;justify-content:flex-end;padding:4px 8px 0;font-size:10px;color:var(--muted);font-family:'Space Mono',monospace;border-right:1px solid var(--border);}
.day-slot{height:60px;border-bottom:1px solid rgba(255,255,255,.04);position:relative;cursor:pointer;padding:0 8px;transition:background .15s;}
.day-slot:hover{background:rgba(54,169,225,.04);}
.day-ev{position:absolute;left:8px;right:8px;border-radius:8px;padding:4px 10px;font-size:11px;font-weight:600;color:#fff;cursor:pointer;border-left:4px solid rgba(0,0,0,.2);}

/* ====== AGENDA VIEW ====== */
.agenda-list{padding:20px;max-width:800px;margin:0 auto;}
.agenda-day-group{margin-bottom:24px;}
.agenda-day-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;display:flex;align-items:center;gap:10px;}
.agenda-day-label::after{content:'';flex:1;height:1px;background:var(--border);}
.agenda-day-label.today-label{color:var(--accent);}
.agenda-ev{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:8px;display:flex;gap:14px;cursor:pointer;transition:border-color .2s;border-left:4px solid;}
.agenda-ev:hover{border-color:rgba(54,169,225,.4);}
.agenda-ev-time{font-size:11px;font-weight:600;color:var(--muted);font-family:'Space Mono',monospace;white-space:nowrap;min-width:80px;margin-top:2px;}
.agenda-ev-body{flex:1;min-width:0;}
.agenda-ev-title{font-size:14px;font-weight:600;color:#fff;margin-bottom:3px;}
.agenda-ev-meta{font-size:11px;color:var(--muted);display:flex;gap:10px;flex-wrap:wrap;}
.agenda-empty{text-align:center;padding:60px 20px;color:var(--muted);}
.agenda-empty svg{opacity:.3;margin-bottom:16px;}
.agenda-empty p{font-size:14px;}

/* ====== MODAL ====== */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:1000;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.6);}
.modal-header{padding:20px 22px 0;display:flex;align-items:center;justify-content:space-between;}
.modal-header h3{font-size:16px;font-weight:700;color:#fff;}
.modal-close{background:var(--bg3);border:1px solid var(--border);border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);font-size:14px;transition:all .2s;}
.modal-close:hover{color:var(--danger);border-color:var(--danger);}
.modal-body{padding:20px 22px;}
.field{margin-bottom:14px;}
label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;display:block;margin-bottom:5px;}
input,select,textarea{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:10px 13px;color:var(--text);font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:border-color .2s;-webkit-appearance:none;appearance:none;}
input:focus,select:focus,textarea:focus{border-color:var(--accent);}
select option{background:var(--bg2);}
.color-row{display:flex;gap:8px;margin-top:6px;}
.color-opt{width:26px;height:26px;border-radius:6px;cursor:pointer;border:3px solid transparent;transition:all .2s;}
.color-opt.selected{border-color:#fff;transform:scale(1.1);}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.modal-footer{padding:0 22px 22px;display:flex;gap:10px;justify-content:flex-end;}
.btn-p{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:9px;padding:10px 22px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-s{background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:10px 18px;color:var(--muted);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}
.btn-d{background:rgba(224,82,82,.15);border:1px solid rgba(224,82,82,.3);border-radius:9px;padding:10px 18px;color:var(--danger);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}

/* EVENT DETAIL MODAL */
.ev-detail-header{padding:24px 22px 16px;border-bottom:1px solid var(--border);}
.ev-detail-type{display:inline-block;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:700;margin-bottom:10px;}
.ev-detail-title{font-size:20px;font-weight:800;color:#fff;margin-bottom:4px;}
.ev-detail-meta{font-size:12px;color:var(--muted);}
.ev-detail-body{padding:16px 22px;}
.detail-row{display:flex;gap:10px;margin-bottom:12px;align-items:flex-start;}
.detail-icon{width:18px;height:18px;flex-shrink:0;margin-top:1px;fill:none;stroke:var(--muted);stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;}
.detail-text{font-size:13px;color:var(--text);}
.detail-label{font-size:11px;color:var(--muted);margin-bottom:2px;}

/* TOAST */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:11px 20px;font-size:13px;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;}
#toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
#toast.success{border-color:rgba(46,204,135,.4);color:var(--success);}
#toast.error{border-color:rgba(224,82,82,.4);color:var(--danger);}

/* SCROLLBAR */
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}

/* RESPONSIVE */
@media(max-width:768px){
  .cal-header{padding:10px 12px;gap:8px;}
  .current-period{font-size:14px;min-width:130px;}
  .view-tabs{order:10;width:100%;}
  .view-tab{flex:1;text-align:center;padding:5px 8px;font-size:11px;}
  .new-event-btn span{display:none;}
  .legend{display:none;}
  .month-cell{min-height:70px;}
  .modal-overlay{padding:0;align-items:flex-end;}
  .modal{border-radius:20px 20px 0 0;max-height:95vh;}
  .field-row{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="app">

<!-- TOPBAR -->
<div class="topbar">
  <a class="back-btn" href="dashboard.php">
    <svg class="back-icon" viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>
    Workspace
  </a>
  <div class="topbar-title">Calendrier partagé</div>
  <div class="topbar-actions">
    <div style="font-size:11px;color:var(--muted);font-family:'Space Mono',monospace" id="clockDisplay"></div>
  </div>
</div>

<!-- CALENDAR SHELL -->
<div class="calendar-shell">

  <!-- CAL HEADER -->
  <div class="cal-header">
    <div class="nav-group">
      <button class="nav-btn" onclick="navigate(-1)" title="Précédent">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15,18 9,12 15,6"/></svg>
      </button>
      <button class="today-btn" onclick="goToToday()">Aujourd'hui</button>
      <button class="nav-btn" onclick="navigate(1)" title="Suivant">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,18 15,12 9,6"/></svg>
      </button>
    </div>
    <div class="current-period" id="currentPeriod"></div>
    <div class="view-tabs">
      <button class="view-tab active" onclick="setView('month',this)">Mois</button>
      <button class="view-tab" onclick="setView('week',this)">Semaine</button>
      <button class="view-tab" onclick="setView('day',this)">Jour</button>
      <button class="view-tab" onclick="setView('agenda',this)">Agenda</button>
    </div>
    <button class="new-event-btn" onclick="openNewEvent()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      <span>Nouvel événement</span>
    </button>
  </div>

  <!-- LEGEND -->
  <div class="legend">
    <div class="legend-item"><div class="legend-dot" style="background:#36A9E1"></div>Réunion</div>
    <div class="legend-item"><div class="legend-dot" style="background:#f0a500"></div>Tâche</div>
    <div class="legend-item"><div class="legend-dot" style="background:#9b8fff"></div>Deadline projet</div>
    <div class="legend-item"><div class="legend-dot" style="background:#2ecc87"></div>Congé</div>
    <div class="legend-item"><div class="legend-dot" style="background:#e05252"></div>Rappel</div>
  </div>

  <!-- CALENDAR BODY -->
  <div class="cal-body" id="calBody"></div>

</div><!-- /calendar-shell -->
</div><!-- /app -->

<!-- MODAL CREATION EVENEMENT -->
<div class="modal-overlay" id="modalEvent">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalEventTitle">Nouvel événement</h3>
      <div class="modal-close" onclick="closeModal('modalEvent')">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
    </div>
    <div class="modal-body">
      <input type="hidden" id="evId">
      <div class="field"><label>Titre *</label><input type="text" id="evTitre" placeholder="Titre de l'événement"></div>
      <div class="field">
        <label>Type</label>
        <select id="evType" onchange="updateColor()">
          <option value="reunion">Réunion</option>
          <option value="tache">Tâche</option>
          <option value="deadline">Deadline</option>
          <option value="conge">Congé</option>
          <option value="rappel">Rappel</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="field">
        <label>Couleur</label>
        <div class="color-row" id="colorRow">
          <div class="color-opt selected" style="background:#36A9E1" data-color="#36A9E1" onclick="selectColor(this)"></div>
          <div class="color-opt" style="background:#2ecc87" data-color="#2ecc87" onclick="selectColor(this)"></div>
          <div class="color-opt" style="background:#f0a500" data-color="#f0a500" onclick="selectColor(this)"></div>
          <div class="color-opt" style="background:#e05252" data-color="#e05252" onclick="selectColor(this)"></div>
          <div class="color-opt" style="background:#9b8fff" data-color="#9b8fff" onclick="selectColor(this)"></div>
          <div class="color-opt" style="background:#29235C" data-color="#29235C" onclick="selectColor(this)"></div>
          <div class="color-opt" style="background:#f06292" data-color="#f06292" onclick="selectColor(this)"></div>
        </div>
      </div>
      <div class="field-row">
        <div class="field"><label>Début *</label><input type="datetime-local" id="evDebut"></div>
        <div class="field"><label>Fin *</label><input type="datetime-local" id="evFin"></div>
      </div>
      <div class="field">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" id="evTouteJournee" style="width:auto" onchange="toggleAllDay()"> Toute la journée
        </label>
      </div>
      <div class="field"><label>Lieu</label><input type="text" id="evLieu" placeholder="Salle de réunion, lien visio…"></div>
      <div class="field"><label>Lien de réunion</label><input type="url" id="evLien" placeholder="https://meet.google.com/…"></div>
      <div class="field"><label>Projet lié</label><select id="evProjet"><option value="">— Aucun —</option></select></div>
      <div class="field"><label>Description</label><textarea id="evDesc" rows="3" placeholder="Notes, ordre du jour…"></textarea></div>
      <div class="field">
        <label>Récurrence</label>
        <select id="evRecurrence">
          <option value="aucune">Aucune</option>
          <option value="quotidien">Tous les jours</option>
          <option value="hebdomadaire">Toutes les semaines</option>
          <option value="mensuel">Tous les mois</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-d" id="deleteEvBtn" style="display:none;margin-right:auto" onclick="deleteEvent()">Supprimer</button>
      <button class="btn-s" onclick="closeModal('modalEvent')">Annuler</button>
      <button class="btn-p" onclick="saveEvent()">Enregistrer</button>
    </div>
  </div>
</div>

<!-- MODAL DETAIL EVENEMENT -->
<div class="modal-overlay" id="modalDetail">
  <div class="modal">
    <div class="ev-detail-header" id="detailHeader"></div>
    <div class="ev-detail-body" id="detailBody"></div>
    <div class="modal-footer">
      <button class="btn-s" onclick="closeModal('modalDetail')">Fermer</button>
      <button class="btn-p" id="editEvBtn" onclick="editEventFromDetail()">Modifier</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
// ===== STATE =====
let currentDate = new Date();
let currentView = 'month';
let events      = [];
let selectedEventId = null;
let selectedColor = '#36A9E1';

const JOURS    = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
const JOURS_S  = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
const MOIS     = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
const TYPE_COLORS = {reunion:'#36A9E1',tache:'#f0a500',deadline:'#9b8fff',conge:'#2ecc87',rappel:'#e05252',autre:'#7a78a0'};

// ===== CLOCK =====
function updateClock(){
  const now=new Date();
  document.getElementById('clockDisplay').textContent=now.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock,1000); updateClock();

// ===== API =====
async function api(params, method='POST'){
  const fd=new FormData();
  for(const[k,v]of Object.entries(params))fd.append(k,v);
  const r=await fetch('calendrier_api.php',{method,body:fd});
  return r.json();
}
async function apiGet(params){
  const qs=new URLSearchParams(params).toString();
  const r=await fetch('calendrier_api.php?'+qs);
  return r.json();
}

// ===== LOAD EVENTS =====
async function loadEvents(){
  let debut, fin;
  if(currentView==='month'){
    debut=new Date(currentDate.getFullYear(),currentDate.getMonth(),1);
    fin=new Date(currentDate.getFullYear(),currentDate.getMonth()+1,0);
  } else if(currentView==='week'){
    const d=new Date(currentDate);
    d.setDate(d.getDate()-d.getDay());
    debut=new Date(d);
    fin=new Date(d); fin.setDate(fin.getDate()+6);
  } else if(currentView==='day'){
    debut=new Date(currentDate); fin=new Date(currentDate);
  } else {
    debut=new Date(currentDate);
    fin=new Date(currentDate); fin.setMonth(fin.getMonth()+2);
  }
  events=await apiGet({action:'liste_evenements',debut:fmt(debut),fin:fmt(fin)});
  render();
}

function fmt(d){return d.toISOString().split('T')[0];}
function fmtDT(d){const p=d.toISOString();return p.slice(0,16);}
function parseDate(s){return s?new Date(s):new Date();}

// ===== NAVIGATION =====
function navigate(dir){
  if(currentView==='month') currentDate.setMonth(currentDate.getMonth()+dir);
  else if(currentView==='week') currentDate.setDate(currentDate.getDate()+dir*7);
  else if(currentView==='day') currentDate.setDate(currentDate.getDate()+dir);
  else currentDate.setMonth(currentDate.getMonth()+dir);
  loadEvents();
}
function goToToday(){currentDate=new Date();loadEvents();}
function setView(v,btn){
  currentView=v;
  document.querySelectorAll('.view-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  loadEvents();
}

// ===== UPDATE PERIOD LABEL =====
function updatePeriodLabel(){
  const el=document.getElementById('currentPeriod');
  if(currentView==='month') el.textContent=MOIS[currentDate.getMonth()]+' '+currentDate.getFullYear();
  else if(currentView==='week'){
    const d=new Date(currentDate);
    d.setDate(d.getDate()-d.getDay());
    const e=new Date(d); e.setDate(e.getDate()+6);
    el.textContent=d.getDate()+' — '+e.getDate()+' '+MOIS[e.getMonth()]+' '+e.getFullYear();
  }
  else if(currentView==='day') el.textContent=JOURS[currentDate.getDay()]+' '+currentDate.getDate()+' '+MOIS[currentDate.getMonth()]+' '+currentDate.getFullYear();
  else el.textContent='Agenda — '+MOIS[currentDate.getMonth()]+' '+currentDate.getFullYear();
}

// ===== RENDER =====
function render(){
  updatePeriodLabel();
  const body=document.getElementById('calBody');
  if(currentView==='month')  renderMonth(body);
  else if(currentView==='week')   renderWeek(body);
  else if(currentView==='day')    renderDay(body);
  else renderAgenda(body);
}

// ===== MONTH VIEW =====
function renderMonth(body){
  const year=currentDate.getFullYear(), month=currentDate.getMonth();
  const first=new Date(year,month,1);
  const last=new Date(year,month+1,0);
  const today=new Date(); today.setHours(0,0,0,0);
  let html='<div class="month-grid">';
  JOURS_S.forEach(j=>html+=`<div class="month-day-header">${j}</div>`);
  let day=new Date(first);
  day.setDate(day.getDate()-day.getDay());
  for(let w=0;w<6;w++){
    for(let d=0;d<7;d++){
      const isToday=day.getTime()===today.getTime();
      const isOther=day.getMonth()!==month;
      const dayStr=fmt(day);
      const dayEvs=events.filter(e=>e.debut&&e.debut.split(' ')[0]===dayStr||e.debut&&e.debut.slice(0,10)===dayStr);
      const max=3;
      html+=`<div class="month-cell${isOther?' other-month':''}${isToday?' today':''}" onclick="dayClick('${dayStr}')">
        <div class="day-num">${day.getDate()}</div>
        ${dayEvs.slice(0,max).map(e=>`<div class="ev-pill" style="background:${e.couleur||'#36A9E1'}" onclick="event.stopPropagation();showDetail('${e.id}')">${e.titre}</div>`).join('')}
        ${dayEvs.length>max?`<div class="more-events">+${dayEvs.length-max} autres</div>`:''}
      </div>`;
      day.setDate(day.getDate()+1);
    }
  }
  html+='</div>';
  body.innerHTML=html;
}

// ===== WEEK VIEW =====
function renderWeek(body){
  const today=new Date(); today.setHours(0,0,0,0);
  const weekStart=new Date(currentDate);
  weekStart.setDate(weekStart.getDate()-weekStart.getDay());
  const days=[];
  for(let i=0;i<7;i++){const d=new Date(weekStart);d.setDate(d.getDate()+i);days.push(d);}

  let html='<div class="week-grid">';
  // Corner + day headers
  html+=`<div class="week-corner"></div>`;
  days.forEach(d=>{
    const isT=d.getTime()===today.getTime();
    html+=`<div class="week-day-header${isT?' today-col':''}">
      <div class="wdh-name">${JOURS_S[d.getDay()]}</div>
      <div class="wdh-num${isT?' today-num':''}">${d.getDate()}</div>
    </div>`;
  });

  // Time slots
  for(let h=0;h<24;h++){
    html+=`<div class="week-time-col"><div class="week-time-label">${String(h).padStart(2,'0')}:00</div></div>`;
    days.forEach(d=>{
      const isT=d.getTime()===today.getTime();
      const dayStr=fmt(d);
      const slotEvs=events.filter(e=>{
        if(!e.debut)return false;
        const ed=e.debut.slice(0,10);
        if(ed!==dayStr)return false;
        const eh=parseInt(e.debut.slice(11,13));
        return eh===h;
      });
      html+=`<div class="week-slot${isT?' today-col':''}" onclick="slotClick('${dayStr}',${h})">
        ${slotEvs.map(e=>{
          const startM=parseInt(e.debut.slice(14,16));
          const top=(startM/60)*60;
          const dur=Math.max(30,(parseDate(e.fin)-parseDate(e.debut))/60000);
          const height=Math.min((dur/60)*60,60-top);
          return`<div class="week-ev" style="background:${e.couleur||'#36A9E1'};top:${top}px;height:${height}px" onclick="event.stopPropagation();showDetail('${e.id}')">${e.titre}</div>`;
        }).join('')}
        ${isT&&new Date().getHours()===h?`<div class="now-line" style="top:${(new Date().getMinutes()/60)*60}px"><div class="now-dot"></div></div>`:''}
      </div>`;
    });
  }
  html+='</div>';
  body.innerHTML=html;
  // Scroll to 8am
  setTimeout(()=>{body.scrollTop=8*60;},50);
}

// ===== DAY VIEW =====
function renderDay(body){
  const today=new Date(); today.setHours(0,0,0,0);
  const dayStr=fmt(currentDate);
  const dayEvs=events.filter(e=>e.debut&&e.debut.slice(0,10)===dayStr);
  const isT=currentDate.getTime()===today.getTime();

  let html=`<div class="day-grid">
    <div class="day-header" style="grid-column:1/-1">
      <h2>${JOURS[currentDate.getDay()]} ${currentDate.getDate()} ${MOIS[currentDate.getMonth()]} ${currentDate.getFullYear()}</h2>
      <span>${dayEvs.length} événement${dayEvs.length!==1?'s':''}</span>
    </div>`;
  for(let h=0;h<24;h++){
    const slotEvs=dayEvs.filter(e=>e.debut&&parseInt(e.debut.slice(11,13))===h);
    html+=`<div class="day-time-label">${String(h).padStart(2,'0')}:00</div>
    <div class="day-slot" onclick="slotClick('${dayStr}',${h})">
      ${slotEvs.map(e=>`<div class="day-ev" style="background:${e.couleur||'#36A9E1'}" onclick="event.stopPropagation();showDetail('${e.id}')">${e.titre} ${e.lieu?'— '+e.lieu:''}</div>`).join('')}
      ${isT&&new Date().getHours()===h?`<div class="now-line" style="top:${(new Date().getMinutes()/60)*60}px"><div class="now-dot"></div></div>`:''}
    </div>`;
  }
  html+='</div>';
  body.innerHTML=html;
  setTimeout(()=>{body.scrollTop=8*60;},50);
}

// ===== AGENDA VIEW =====
function renderAgenda(body){
  const start=new Date(currentDate);
  start.setDate(1);
  const evsByDay={};
  events.forEach(e=>{
    const d=e.debut?e.debut.slice(0,10):'';
    if(!evsByDay[d])evsByDay[d]=[];
    evsByDay[d].push(e);
  });
  const today=fmt(new Date());
  const days=Object.keys(evsByDay).sort();
  if(!days.length){
    body.innerHTML=`<div class="agenda-list"><div class="agenda-empty">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <p>Aucun événement sur cette période</p>
    </div></div>`;
    return;
  }
  let html='<div class="agenda-list">';
  days.forEach(d=>{
    const date=new Date(d+'T12:00:00');
    const isT=d===today;
    html+=`<div class="agenda-day-group">
      <div class="agenda-day-label${isT?' today-label':''}">${isT?'Aujourd\'hui — ':''}${JOURS[date.getDay()]} ${date.getDate()} ${MOIS[date.getMonth()]} ${date.getFullYear()}</div>
      ${evsByDay[d].map(e=>`
        <div class="agenda-ev" style="border-left-color:${e.couleur||'#36A9E1'}" onclick="showDetail('${e.id}')">
          <div class="agenda-ev-time">${e.toute_journee=='1'?'Toute la journée':e.debut?e.debut.slice(11,16)+' – '+e.fin.slice(11,16):''}</div>
          <div class="agenda-ev-body">
            <div class="agenda-ev-title">${e.titre}</div>
            <div class="agenda-ev-meta">
              ${e.type?`<span>${typeLabel(e.type)}</span>`:''}
              ${e.lieu?`<span>${e.lieu}</span>`:''}
              ${e.projet_nom?`<span>${e.projet_nom}</span>`:''}
            </div>
          </div>
        </div>`).join('')}
    </div>`;
  });
  html+='</div>';
  body.innerHTML=html;
}

// ===== CLICK HANDLERS =====
function dayClick(dayStr){openNewEvent(dayStr);}
function slotClick(dayStr,h){
  const dt=dayStr+'T'+String(h).padStart(2,'0')+':00';
  openNewEvent(dayStr,dt);
}

// ===== NEW EVENT MODAL =====
async function openNewEvent(day='',datetime=''){
  selectedEventId=null;
  document.getElementById('modalEventTitle').textContent='Nouvel événement';
  document.getElementById('evId').value='';
  document.getElementById('evTitre').value='';
  document.getElementById('evType').value='reunion';
  document.getElementById('evDesc').value='';
  document.getElementById('evLieu').value='';
  document.getElementById('evLien').value='';
  document.getElementById('evRecurrence').value='aucune';
  document.getElementById('evTouteJournee').checked=false;
  document.getElementById('deleteEvBtn').style.display='none';
  // Default times
  const now=datetime||new Date().toISOString().slice(0,16);
  const later=new Date(datetime||new Date());
  later.setHours(later.getHours()+1);
  document.getElementById('evDebut').value=now;
  document.getElementById('evFin').value=fmtDT(later);
  selectColorByValue('#36A9E1');
  // Load projects
  const projets=await apiGet({action:'liste_projets'});
  document.getElementById('evProjet').innerHTML='<option value="">— Aucun —</option>'+projets.map(p=>`<option value="${p.id}">${p.nom}</option>`).join('');
  openModal('modalEvent');
  document.getElementById('evTitre').focus();
}

async function saveEvent(){
  const titre=document.getElementById('evTitre').value.trim();
  const debut=document.getElementById('evDebut').value;
  const fin  =document.getElementById('evFin').value;
  if(!titre){toast('Le titre est obligatoire','error');return;}
  if(!debut||!fin){toast('Les dates sont obligatoires','error');return;}
  if(new Date(fin)<new Date(debut)){toast('La fin doit être après le début','error');return;}

  const id=document.getElementById('evId').value;
  const params={
    action: id?'modifier_evenement':'creer_evenement',
    titre, description:document.getElementById('evDesc').value,
    type:document.getElementById('evType').value,
    couleur:selectedColor, debut:debut.replace('T',' '),
    fin:fin.replace('T',' '),
    toute_journee:document.getElementById('evTouteJournee').checked?1:0,
    lieu:document.getElementById('evLieu').value,
    lien:document.getElementById('evLien').value,
    projet_id:document.getElementById('evProjet').value,
    recurrence:document.getElementById('evRecurrence').value,
    participants:'[]',
  };
  if(id)params.id=id;
  const r=await api(params);
  if(r.success){closeModal('modalEvent');toast(id?'Événement modifié':'Événement créé');loadEvents();}
  else toast(r.error||'Erreur','error');
}

async function deleteEvent(){
  const id=document.getElementById('evId').value;
  if(!id)return;
  if(!confirm('Supprimer cet événement ?'))return;
  const r=await api({action:'supprimer_evenement',id});
  if(r.success){closeModal('modalEvent');toast('Événement supprimé');loadEvents();}
  else toast(r.error||'Erreur','error');
}

// ===== DETAIL MODAL =====
async function showDetail(id){
  if(String(id).startsWith('tache_')||String(id).startsWith('projet_')){
    const ev=events.find(e=>String(e.id)===String(id));
    if(!ev)return;
    showDetailFromObj(ev,false);
    return;
  }
  const ev=await apiGet({action:'detail_evenement',id});
  showDetailFromObj(ev,true);
}

function showDetailFromObj(ev,editable){
  const header=document.getElementById('detailHeader');
  const body=document.getElementById('detailBody');
  const editBtn=document.getElementById('editEvBtn');
  const typeColors={reunion:'#36A9E1',tache:'#f0a500',deadline:'#9b8fff',conge:'#2ecc87',rappel:'#e05252',autre:'#7a78a0'};
  const c=ev.couleur||typeColors[ev.type]||'#36A9E1';
  header.innerHTML=`
    <div class="ev-detail-type" style="background:${c}22;color:${c}">${typeLabel(ev.type)}</div>
    <div class="ev-detail-title">${ev.titre}</div>
    <div class="ev-detail-meta">${ev.debut?ev.debut.slice(0,16).replace('T',' '):''}${ev.fin?' → '+ev.fin.slice(0,16).replace('T',''):''}</div>`;
  let rows='';
  if(ev.lieu)rows+=detailRow('<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/>',ev.lieu);
  if(ev.lien)rows+=`<div class="detail-row"><svg class="detail-icon" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg><a href="${ev.lien}" target="_blank" style="color:var(--accent);font-size:13px">Rejoindre la réunion</a></div>`;
  if(ev.projet_nom)rows+=detailRow('<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',ev.projet_nom);
  if(ev.description)rows+=detailRow('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',ev.description);
  if(ev.createur_nom)rows+=detailRow('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',ev.createur_nom);
  body.innerHTML=rows||'<p style="color:var(--muted);font-size:13px">Aucun détail supplémentaire.</p>';
  editBtn.style.display=editable?'block':'none';
  editBtn.onclick=()=>editEventFromId(ev.id);
  selectedEventId=ev.id;
  openModal('modalDetail');
}

function detailRow(svgPath,text){
  return`<div class="detail-row"><svg class="detail-icon" viewBox="0 0 24 24">${svgPath}</svg><div class="detail-text">${text}</div></div>`;
}

async function editEventFromDetail(){closeModal('modalDetail');await editEventFromId(selectedEventId);}
async function editEventFromId(id){
  const ev=await apiGet({action:'detail_evenement',id});
  document.getElementById('modalEventTitle').textContent='Modifier l\'événement';
  document.getElementById('evId').value=ev.id;
  document.getElementById('evTitre').value=ev.titre;
  document.getElementById('evType').value=ev.type;
  document.getElementById('evDesc').value=ev.description||'';
  document.getElementById('evLieu').value=ev.lieu||'';
  document.getElementById('evLien').value=ev.lien||'';
  document.getElementById('evRecurrence').value=ev.recurrence||'aucune';
  document.getElementById('evTouteJournee').checked=ev.toute_journee=='1';
  document.getElementById('evDebut').value=(ev.debut||'').replace(' ','T').slice(0,16);
  document.getElementById('evFin').value=(ev.fin||'').replace(' ','T').slice(0,16);
  selectColorByValue(ev.couleur||'#36A9E1');
  document.getElementById('deleteEvBtn').style.display='block';
  const projets=await apiGet({action:'liste_projets'});
  document.getElementById('evProjet').innerHTML='<option value="">— Aucun —</option>'+projets.map(p=>`<option value="${p.id}"${p.id==ev.projet_id?' selected':''}>${p.nom}</option>`).join('');
  openModal('modalEvent');
}

// ===== COLOR PICKER =====
function selectColor(el){
  document.querySelectorAll('.color-opt').forEach(c=>c.classList.remove('selected'));
  el.classList.add('selected');
  selectedColor=el.dataset.color;
}
function selectColorByValue(c){
  document.querySelectorAll('.color-opt').forEach(el=>{
    el.classList.toggle('selected',el.dataset.color===c);
    if(el.dataset.color===c)selectedColor=c;
  });
}
function updateColor(){selectedColor=TYPE_COLORS[document.getElementById('evType').value]||'#36A9E1';selectColorByValue(selectedColor);}

function toggleAllDay(){
  const checked=document.getElementById('evTouteJournee').checked;
  if(checked){
    const d=document.getElementById('evDebut').value.slice(0,10);
    document.getElementById('evDebut').value=d+'T00:00';
    document.getElementById('evFin').value=d+'T23:59';
  }
}

// ===== MODAL HELPERS =====
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');}));

// ===== HELPERS =====
function typeLabel(t){const m={reunion:'Réunion',tache:'Tâche',deadline:'Deadline',conge:'Congé',rappel:'Rappel',autre:'Autre'};return m[t]||t;}
function toast(msg,type='success'){const t=document.getElementById('toast');t.textContent=msg;t.className='show '+type;setTimeout(()=>t.className='',3500);}

// ===== INIT =====
loadEvents();
</script>
</body>
</html>
