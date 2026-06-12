<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$user      = currentUser();
$isManager = isManager();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>UP TECH GROUP — Suivi du temps</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--warning:#f0a500;--danger:#e05252;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 0% 0%,rgba(41,35,92,0.5) 0%,transparent 60%);pointer-events:none;}

/* TOPBAR */
.topbar{position:sticky;top:0;z-index:100;background:rgba(19,18,42,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);height:56px;display:flex;align-items:center;padding:0 24px;gap:16px;}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;padding:6px 12px;border-radius:8px;transition:all .2s;}
.back-btn:hover{color:var(--accent);background:rgba(54,169,225,.08);}
.back-btn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.topbar-title{flex:1;font-size:15px;font-weight:700;color:#fff;}

/* PAGE */
.page{max-width:1100px;margin:0 auto;padding:24px 20px 48px;position:relative;z-index:1;}

/* TIMER CARD */
.timer-card{background:linear-gradient(135deg,rgba(41,35,92,.8),rgba(54,169,225,.15));border:1px solid rgba(54,169,225,.3);border-radius:20px;padding:32px;margin-bottom:24px;text-align:center;position:relative;overflow:hidden;}
.timer-card::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:radial-gradient(circle,rgba(54,169,225,.15) 0%,transparent 70%);border-radius:50%;}
.timer-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:2px;margin-bottom:12px;}
.timer-display{font-size:72px;font-weight:900;color:#fff;font-family:'Space Mono',monospace;letter-spacing:-2px;line-height:1;margin-bottom:8px;transition:all .3s;}
.timer-display.running{color:var(--accent);text-shadow:0 0 30px rgba(54,169,225,.4);}
.timer-task{font-size:14px;color:rgba(255,255,255,.7);margin-bottom:24px;min-height:20px;}
.timer-controls{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:20px;}
.timer-btn{border:none;border-radius:12px;padding:14px 32px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;}
.timer-btn svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.btn-start{background:var(--success);color:#fff;}
.btn-start:hover{background:#27b579;transform:scale(1.02);}
.btn-stop{background:var(--danger);color:#fff;}
.btn-stop:hover{background:#c94343;transform:scale(1.02);}
.timer-form{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
.timer-select,.timer-input{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:10px 14px;color:var(--text);font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:border-color .2s;}
.timer-select{min-width:180px;}
.timer-input{min-width:220px;}
.timer-select:focus,.timer-input:focus{border-color:var(--accent);}
.timer-select option{background:var(--bg2);}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px;text-align:center;}
.kpi-val{font-size:28px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;margin-bottom:4px;}
.kpi-label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;}
.kpi-sub{font-size:10px;color:var(--accent);margin-top:4px;font-weight:600;}

/* GRID */
.grid-2{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;}

/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px;}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px;flex-wrap:wrap;}
.card-title{font-size:13px;font-weight:700;color:#fff;}

/* TABLE */
.tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
.tbl{width:100%;border-collapse:collapse;min-width:500px;}
.tbl th{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;padding:0 10px 10px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap;}
.tbl td{font-size:12px;padding:10px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;}
.tbl tr:last-child td{border:none;}
.tbl tr:hover td{background:rgba(54,169,225,.03);}
.mono{font-family:'Space Mono',monospace;font-size:12px;color:var(--accent);}

/* PROJET BAR */
.proj-bar-item{margin-bottom:12px;}
.proj-bar-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;}
.proj-bar-name{font-size:12px;font-weight:500;color:var(--text);}
.proj-bar-val{font-size:11px;font-family:'Space Mono',monospace;color:var(--accent);}
.proj-bar-wrap{height:6px;background:var(--bg3);border-radius:99px;overflow:hidden;}
.proj-bar-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--primary),var(--accent));transition:width .8s ease;}

/* FACTURABLE */
.fact-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;}
.fact-yes{background:rgba(46,204,135,.15);color:var(--success);}
.fact-no{background:rgba(122,120,160,.15);color:var(--muted);}

/* FILTER */
.filter-tabs{display:flex;gap:4px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:3px;}
.filter-tab{padding:5px 12px;border-radius:6px;font-size:11px;font-weight:500;color:var(--muted);cursor:pointer;border:none;background:none;font-family:'Poppins',sans-serif;transition:all .2s;white-space:nowrap;}
.filter-tab.active{background:var(--card);color:#fff;}

/* TOAST */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:11px 20px;font-size:13px;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;}
#toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
#toast.success{border-color:rgba(46,204,135,.4);color:var(--success);}
#toast.error{border-color:rgba(224,82,82,.4);color:var(--danger);}

::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}

@media(max-width:768px){
  .kpi-grid{grid-template-columns:repeat(3,1fr);gap:10px;}
  .kpi-val{font-size:20px;}
  .timer-display{font-size:52px;}
  .grid-2{grid-template-columns:1fr;}
  .page{padding:16px 12px 48px;}
}
</style>
</head>
<body>
<div class="topbar">
  <a class="back-btn" href="dashboard.php">
    <svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>
    Workspace
  </a>
  <div class="topbar-title">Suivi du temps</div>
</div>

<div class="page">

  <!-- TIMER -->
  <div class="timer-card">
    <div class="timer-label" id="timerLabel">Timer</div>
    <div class="timer-display" id="timerDisplay">00:00:00</div>
    <div class="timer-task" id="timerTask">Sélectionnez une tâche et démarrez le timer</div>

    <div class="timer-controls">
      <button class="timer-btn btn-start" id="startBtn" onclick="startTimer()">
        <svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        Démarrer
      </button>
      <button class="timer-btn btn-stop" id="stopBtn" onclick="stopTimer()" style="display:none">
        <svg viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
        Arrêter
      </button>
    </div>

    <div class="timer-form" id="timerForm">
      <select class="timer-select" id="timerTache">
        <option value="">— Sélectionner une tâche —</option>
      </select>
      <input class="timer-input" type="text" id="timerDesc" placeholder="Description (optionnel)">
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-val" id="statAuj">—</div>
      <div class="kpi-label">Aujourd'hui</div>
      <div class="kpi-sub" id="statAujSub"></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val" id="statSem">—</div>
      <div class="kpi-label">Cette semaine</div>
      <div class="kpi-sub" id="statSemSub"></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val" id="statMois">—</div>
      <div class="kpi-label">Ce mois</div>
      <div class="kpi-sub" id="statMoisSub"></div>
    </div>
  </div>

  <!-- ENTRÉES + PAR PROJET -->
  <div class="grid-2">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Historique</div>
        <div class="filter-tabs">
          <button class="filter-tab" onclick="setPeriode('aujourd_hui',this)">Aujourd'hui</button>
          <button class="filter-tab active" onclick="setPeriode('semaine',this)">Semaine</button>
          <button class="filter-tab" onclick="setPeriode('mois',this)">Mois</button>
          <button class="filter-tab" onclick="setPeriode('tout',this)">Tout</button>
        </div>
      </div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr>
            <th>Début</th>
            <th>Durée</th>
            <th>Tâche / Description</th>
            <th>Projet</th>
            <th>Facturable</th>
            <th></th>
          </tr></thead>
          <tbody id="timeTable"></tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:16px">Temps par projet</div>
      <div id="parProjet"></div>
    </div>
  </div>

</div>

<div id="toast"></div>

<script>
let timerInterval = null;
let timerStart    = null;
let isRunning     = false;
let currentPeriode = 'semaine';

// ===== API =====
async function api(p){const fd=new FormData();Object.entries(p).forEach(([k,v])=>fd.append(k,v));const r=await fetch('time_api.php',{method:'POST',body:fd});return r.json();}
async function apiGet(p){const r=await fetch('time_api.php?'+new URLSearchParams(p));return r.json();}

function toast(msg,type='success'){const t=document.getElementById('toast');t.textContent=msg;t.className='show '+type;setTimeout(()=>t.className='',3500);}
function pad(n){return String(n).padStart(2,'0');}
function fmtTime(sec){const h=Math.floor(sec/3600),m=Math.floor((sec%3600)/60),s=sec%60;return pad(h)+':'+pad(m)+':'+pad(s);}

// ===== TIMER DISPLAY =====
function updateDisplay(elapsed){
  document.getElementById('timerDisplay').textContent=fmtTime(elapsed);
}

function startDisplay(debut){
  if(timerInterval)clearInterval(timerInterval);
  timerStart=new Date(debut).getTime();
  isRunning=true;
  document.getElementById('timerDisplay').classList.add('running');
  document.getElementById('startBtn').style.display='none';
  document.getElementById('stopBtn').style.display='flex';
  document.getElementById('timerForm').style.opacity='.5';
  document.getElementById('timerForm').style.pointerEvents='none';
  document.getElementById('timerLabel').textContent='En cours';
  timerInterval=setInterval(()=>{
    const elapsed=Math.floor((Date.now()-timerStart)/1000);
    updateDisplay(elapsed);
  },1000);
}

function stopDisplay(){
  if(timerInterval)clearInterval(timerInterval);
  timerInterval=null; isRunning=false;
  document.getElementById('timerDisplay').classList.remove('running');
  document.getElementById('timerDisplay').textContent='00:00:00';
  document.getElementById('startBtn').style.display='flex';
  document.getElementById('stopBtn').style.display='none';
  document.getElementById('timerForm').style.opacity='1';
  document.getElementById('timerForm').style.pointerEvents='auto';
  document.getElementById('timerLabel').textContent='Timer';
  document.getElementById('timerTask').textContent='Sélectionnez une tâche et démarrez le timer';
}

// ===== ACTIONS =====
async function startTimer(){
  const tacheId=document.getElementById('timerTache').value;
  const desc   =document.getElementById('timerDesc').value;
  const r=await api({action:'start',tache_id:tacheId,description:desc});
  if(r.success){
    startDisplay(r.debut);
    const sel=document.getElementById('timerTache');
    const tacheNom=sel.options[sel.selectedIndex]?.text||'';
    document.getElementById('timerTask').textContent=tacheNom||desc||'Timer en cours…';
    toast('Timer démarré');
    loadStats();
  } else toast(r.error||'Erreur','error');
}

async function stopTimer(){
  const r=await api({action:'stop'});
  if(r.success){
    stopDisplay();
    toast('Temps enregistré : '+r.duree_fmt);
    loadAll();
  } else toast(r.error||'Erreur','error');
}

// ===== LOAD =====
async function loadAll(){
  await Promise.all([loadStats(),loadEntries(),loadTaches()]);
}

async function loadTaches(){
  const data=await apiGet({action:'mes_taches_select'});
  const sel=document.getElementById('timerTache');
  const current=sel.value;
  sel.innerHTML='<option value="">— Sélectionner une tâche —</option>'+
    data.map(t=>`<option value="${t.id}"${t.id==current?' selected':''}>${t.titre}${t.projet_nom?' ('+t.projet_nom+')':''}</option>`).join('');
}

async function loadStats(){
  const s=await apiGet({action:'stats'});
  document.getElementById('statAuj').textContent=s.auj_fmt||'0s';
  document.getElementById('statSem').textContent=s.sem_fmt||'0s';
  document.getElementById('statMois').textContent=s.mois_fmt||'0s';

  // Par projet
  const pp=document.getElementById('parProjet');
  if(!s.par_projet||!s.par_projet.length){
    pp.innerHTML='<div style="color:var(--muted);font-size:12px;text-align:center;padding:20px">Aucune donnée cette semaine</div>';
  } else {
    const max=Math.max(...s.par_projet.map(p=>p.total));
    pp.innerHTML=s.par_projet.map(p=>`
      <div class="proj-bar-item">
        <div class="proj-bar-head">
          <div class="proj-bar-name">${p.nom||'Sans projet'}</div>
          <div class="proj-bar-val">${p.total_fmt}</div>
        </div>
        <div class="proj-bar-wrap"><div class="proj-bar-fill" style="width:${max>0?Math.round((p.total/max)*100):0}%"></div></div>
      </div>`).join('');
  }

  // Reprendre le timer si actif
  if(s.timer_actif && !isRunning){
    const debut=new Date(Date.now()-s.elapsed*1000).toISOString();
    startDisplay(debut);
    const actif=await apiGet({action:'actif'});
    if(actif){document.getElementById('timerTask').textContent=actif.tache_titre||actif.description||'Timer en cours…';}
  }
}

async function loadEntries(){
  const data=await apiGet({action:'liste',periode:currentPeriode});
  const tbody=document.getElementById('timeTable');
  if(!data.length){
    tbody.innerHTML='<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">Aucune entrée sur cette période</td></tr>';
    return;
  }
  tbody.innerHTML=data.map(e=>`
    <tr>
      <td style="white-space:nowrap;font-size:11px;color:var(--muted)">${e.debut.slice(0,16).replace('T',' ')}</td>
      <td><span class="mono">${e.duree_fmt}</span></td>
      <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        ${e.tache_titre?`<div style="font-weight:600">${e.tache_titre}</div>`:''}
        ${e.description?`<div style="color:var(--muted);font-size:11px">${e.description}</div>`:''}
      </td>
      <td style="color:var(--muted);font-size:11px">${e.projet_nom||'—'}</td>
      <td><span class="fact-badge ${e.facturable=='1'?'fact-yes':'fact-no'}">${e.facturable=='1'?'Oui':'Non'}</span></td>
      <td>
        <button onclick="supprimerEntry(${e.id})" style="background:rgba(224,82,82,.12);border:1px solid rgba(224,82,82,.2);border-radius:6px;padding:3px 8px;color:var(--danger);font-size:10px;cursor:pointer;font-family:'Poppins',sans-serif">Suppr.</button>
      </td>
    </tr>`).join('');
}

async function supprimerEntry(id){
  if(!confirm('Supprimer cette entrée de temps ?'))return;
  const r=await api({action:'supprimer',id});
  if(r.success){toast('Entrée supprimée');loadAll();}
  else toast(r.error||'Erreur','error');
}

function setPeriode(p,btn){
  currentPeriode=p;
  document.querySelectorAll('.filter-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  loadEntries();
}

// ===== INIT =====
loadAll();
</script>
</body>
</html>
