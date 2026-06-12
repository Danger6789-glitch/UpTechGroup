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
<title>UP TECH GROUP — Tableau de bord financier</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
:root{
  --primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;
  --card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;
  --success:#2ecc87;--warning:#f0a500;--danger:#e05252;--purple:#9b8fff;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 0% 0%,rgba(41,35,92,0.5) 0%,transparent 60%);pointer-events:none;}

/* TOPBAR */
.topbar{position:sticky;top:0;z-index:100;background:rgba(19,18,42,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);height:56px;display:flex;align-items:center;padding:0 24px;gap:16px;}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;padding:6px 12px;border-radius:8px;transition:all .2s;white-space:nowrap;}
.back-btn:hover{color:var(--accent);background:rgba(54,169,225,.08);}
.back-btn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.topbar-title{flex:1;font-size:15px;font-weight:700;color:#fff;}
.year-select{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:7px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:13px;outline:none;cursor:pointer;}
.export-btn{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:7px 16px;color:var(--muted);font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .2s;}
.export-btn:hover{border-color:var(--accent);color:var(--accent);}
.export-btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}

/* PAGE */
.page{max-width:1200px;margin:0 auto;padding:24px 20px 48px;position:relative;z-index:1;}

/* KPI GRID */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;position:relative;overflow:hidden;transition:border-color .2s,transform .2s;}
.kpi-card:hover{border-color:rgba(54,169,225,.3);transform:translateY(-2px);}
.kpi-card::after{content:'';position:absolute;top:-20px;right:-20px;width:80px;height:80px;background:radial-gradient(circle,rgba(54,169,225,.08) 0%,transparent 70%);border-radius:50%;}
.kpi-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;}
.kpi-icon svg{width:18px;height:18px;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.kpi-val{font-size:22px;font-weight:800;color:#fff;letter-spacing:-0.5px;line-height:1;font-family:'Space Mono',monospace;}
.kpi-val small{font-size:12px;font-weight:400;font-family:'Poppins',sans-serif;color:var(--muted);margin-left:4px;}
.kpi-label{font-size:11px;color:var(--muted);margin-top:4px;font-weight:500;}
.kpi-badge{display:inline-flex;align-items:center;gap:4px;margin-top:8px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;}
.kpi-badge.up{background:rgba(46,204,135,.15);color:var(--success);}
.kpi-badge.down{background:rgba(224,82,82,.15);color:var(--danger);}
.kpi-badge.neutral{background:rgba(122,120,160,.15);color:var(--muted);}
.kpi-badge svg{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}

/* SECTION TITLE */
.section-title{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:2px;margin-bottom:14px;display:flex;align-items:center;gap:10px;}
.section-title::after{content:'';flex:1;height:1px;background:var(--border);}

/* CHART CARDS */
.chart-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px;}
.chart-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.chart-title{font-size:14px;font-weight:700;color:#fff;}
.chart-legend{display:flex;gap:14px;flex-wrap:wrap;}
.legend-item{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--muted);}
.legend-dot{width:10px;height:10px;border-radius:3px;}
.chart-wrap{position:relative;height:260px;}
.chart-wrap.tall{height:320px;}
.chart-wrap.short{height:200px;}

/* GRID LAYOUTS */
.grid-2{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;}
.grid-2-equal{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}

/* PIPELINE TABLE */
.pipeline-table{width:100%;border-collapse:collapse;margin-top:8px;}
.pipeline-table th{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;padding:0 10px 10px;text-align:left;border-bottom:1px solid var(--border);}
.pipeline-table td{font-size:13px;padding:10px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;}
.pipeline-table tr:last-child td{border:none;}
.pipeline-table tr:hover td{background:rgba(54,169,225,.03);}
.status-pill{display:inline-block;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;}
.progress-wrap{display:flex;align-items:center;gap:8px;}
.progress-bar{flex:1;height:5px;background:var(--bg3);border-radius:99px;overflow:hidden;}
.progress-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--primary),var(--accent));}

/* MARGE INDICATOR */
.marge-circle{position:relative;width:120px;height:120px;margin:0 auto 16px;}
.marge-circle svg{transform:rotate(-90deg);}
.marge-value{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;}
.marge-value .pct{font-size:24px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;}
.marge-value .lbl{font-size:10px;color:var(--muted);}

/* FACTURATION STATS */
.fact-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:10px;}
.fact-item{background:var(--bg3);border-radius:10px;padding:14px;text-align:center;}
.fact-val{font-size:20px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;}
.fact-lbl{font-size:10px;color:var(--muted);margin-top:3px;}

/* LOADER */
.chart-loader{display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted);font-size:13px;gap:10px;}
.spinner{width:18px;height:18px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}

/* EMPTY */
.empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;height:180px;color:var(--muted);}
.empty-state svg{width:48px;height:48px;opacity:.3;margin-bottom:12px;}
.empty-state p{font-size:13px;}

/* SCROLLBAR */
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}

/* RESPONSIVE */
@media(max-width:900px){
  .kpi-grid{grid-template-columns:repeat(2,1fr);}
  .grid-2,.grid-3{grid-template-columns:1fr;}
  .grid-2-equal{grid-template-columns:1fr;}
}
@media(max-width:480px){
  .kpi-grid{grid-template-columns:repeat(2,1fr);gap:10px;}
  .kpi-val{font-size:18px;}
  .page{padding:16px 12px 48px;}
  .topbar{padding:0 14px;}
}
</style>
</head>
<body>

<div class="topbar">
  <a class="back-btn" href="dashboard.php">
    <svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>
    Workspace
  </a>
  <div class="topbar-title">Tableau de bord financier</div>
  <select class="year-select" id="yearSelect" onchange="changeYear(this.value)">
    <option><?= date('Y') ?></option>
  </select>
  <button class="export-btn" onclick="exportCSV()">
    <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Exporter
  </button>
</div>

<div class="page">

  <!-- KPI ROW -->
  <div class="kpi-grid" id="kpiGrid">
    <?php for($i=0;$i<4;$i++): ?>
    <div class="kpi-card"><div class="chart-loader"><div class="spinner"></div></div></div>
    <?php endfor; ?>
  </div>

  <!-- ROW 1 : Revenus/Dépenses + Trésorerie cumulée -->
  <div class="section-title">Évolution financière</div>
  <div class="grid-2" style="margin-bottom:16px">
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title">Revenus & Dépenses mensuels</div>
        <div class="chart-legend">
          <div class="legend-item"><div class="legend-dot" style="background:#36A9E1"></div>Revenus</div>
          <div class="legend-item"><div class="legend-dot" style="background:#e05252"></div>Dépenses</div>
          <div class="legend-item"><div class="legend-dot" style="background:rgba(46,204,135,.4)"></div>Prévu</div>
        </div>
      </div>
      <div class="chart-wrap"><canvas id="chartRevDep"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="chart-header"><div class="chart-title">Trésorerie cumulée</div></div>
      <div class="chart-wrap"><canvas id="chartCumul"></canvas></div>
    </div>
  </div>

  <!-- ROW 2 : Catégories + Marge + Facturation -->
  <div class="section-title">Répartition & Performance</div>
  <div class="grid-3">
    <div class="chart-card">
      <div class="chart-header"><div class="chart-title">Répartition des revenus</div></div>
      <div class="chart-wrap short"><canvas id="chartCategories"></canvas></div>
    </div>
    <div class="chart-card" style="text-align:center">
      <div class="chart-title" style="margin-bottom:20px">Marge nette</div>
      <div class="marge-circle">
        <svg width="120" height="120" viewBox="0 0 120 120">
          <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(54,169,225,0.1)" stroke-width="10"/>
          <circle cx="60" cy="60" r="50" fill="none" stroke="url(#margeGrad)" stroke-width="10" stroke-linecap="round" stroke-dasharray="314" stroke-dashoffset="314" id="margeCircle"/>
          <defs>
            <linearGradient id="margeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:#29235C"/>
              <stop offset="100%" style="stop-color:#36A9E1"/>
            </linearGradient>
          </defs>
        </svg>
        <div class="marge-value">
          <div class="pct" id="margePct">—</div>
          <div class="lbl">marge</div>
        </div>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:6px">Résultat net annuel</div>
      <div style="font-size:20px;font-weight:800;color:#fff;font-family:'Space Mono',monospace" id="netAnnuel">—</div>
      <div style="font-size:10px;color:var(--muted);margin-top:2px">FCFA</div>
    </div>
    <div class="chart-card">
      <div class="chart-title" style="margin-bottom:14px">Facturation</div>
      <div class="chart-wrap short"><canvas id="chartFacturation"></canvas></div>
      <div class="fact-grid" id="factGrid"></div>
    </div>
  </div>

  <!-- ROW 3 : Pipeline -->
  <div class="section-title">Pipeline commercial</div>
  <div class="grid-2-equal">
    <div class="chart-card">
      <div class="chart-header"><div class="chart-title">Valeur par statut de projet</div></div>
      <div class="chart-wrap"><canvas id="chartPipeline"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="chart-header"><div class="chart-title">Détail du pipeline</div></div>
      <div style="overflow-x:auto">
        <table class="pipeline-table" id="pipelineTable">
          <thead><tr><th>Statut</th><th>Projets</th><th>Valeur (FCFA)</th><th>Part</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ROW 4 : Top clients -->
  <div class="section-title">Top clients</div>
  <div class="chart-card" style="margin-bottom:20px">
    <div class="chart-header"><div class="chart-title">Chiffre d'affaires par client</div></div>
    <div class="chart-wrap"><canvas id="chartClients"></canvas></div>
  </div>

</div>

<script>
let currentYear = <?= date('Y') ?>;
let charts = {};

const MOIS = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'];
const COLORS = ['#36A9E1','#2ecc87','#f0a500','#e05252','#9b8fff','#f06292','#29235C','#26c6da'];

Chart.defaults.color = '#7a78a0';
Chart.defaults.borderColor = 'rgba(54,169,225,0.1)';
Chart.defaults.font.family = 'Poppins';
Chart.defaults.font.size = 11;

// ===== API =====
async function api(action, extra = {}) {
  const params = new URLSearchParams({ action, year: currentYear, ...extra });
  const r = await fetch('finances_api.php?' + params);
  return r.json();
}

// ===== FORMAT =====
function fmt(n) { return Math.round(n).toLocaleString('fr-FR'); }
function fmtK(n) { return n >= 1000000 ? (n/1000000).toFixed(1)+'M' : n >= 1000 ? (n/1000).toFixed(0)+'K' : Math.round(n)+''; }

// ===== DESTROY CHART =====
function destroyChart(id) { if (charts[id]) { charts[id].destroy(); delete charts[id]; } }

// ===== YEAR CHANGE =====
async function changeYear(y) {
  currentYear = parseInt(y);
  await loadAll();
}

// ===== LOAD ALL =====
async function loadAll() {
  await Promise.all([
    loadKPIs(),
    loadRevDep(),
    loadCumul(),
    loadCategories(),
    loadPipeline(),
    loadClients(),
    loadFacturation(),
  ]);
}

// ===== KPIs =====
async function loadKPIs() {
  const d = await api('resume');
  const evDir = d.evolution > 0 ? 'up' : d.evolution < 0 ? 'down' : 'neutral';
  const evIcon = d.evolution > 0
    ? '<svg viewBox="0 0 24 24"><polyline points="18,15 12,9 6,15"/></svg>'
    : '<svg viewBox="0 0 24 24"><polyline points="6,9 12,15 18,9"/></svg>';

  const kpis = [
    {
      color:'#36A9E1', bg:'rgba(54,169,225,.1)', stroke:'#36A9E1',
      icon:'<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
      val: fmtK(d.ca_annee), unit:'FCFA', label:'CA ' + currentYear,
      badge:`<span class="kpi-badge ${evDir}">${evIcon} ${Math.abs(d.evolution)}% vs mois préc.</span>`
    },
    {
      color:'#e05252', bg:'rgba(224,82,82,.1)', stroke:'#e05252',
      icon:'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
      val: fmtK(d.dep_annee), unit:'FCFA', label:'Dépenses ' + currentYear,
      badge:`<span class="kpi-badge neutral">Charges totales</span>`
    },
    {
      color: d.net_annee >= 0 ? '#2ecc87' : '#e05252',
      bg: d.net_annee >= 0 ? 'rgba(46,204,135,.1)' : 'rgba(224,82,82,.1)',
      stroke: d.net_annee >= 0 ? '#2ecc87' : '#e05252',
      icon:'<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
      val: fmtK(Math.abs(d.net_annee)), unit:'FCFA', label:'Résultat net ' + currentYear,
      badge:`<span class="kpi-badge ${d.net_annee>=0?'up':'down'}">${d.marge}% de marge</span>`
    },
    {
      color:'#f0a500', bg:'rgba(240,165,0,.1)', stroke:'#f0a500',
      icon:'<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
      val: fmtK(d.ca_mois), unit:'FCFA', label:'CA ce mois',
      badge:`<span class="kpi-badge neutral">${fmtK(d.prevu_total)} FCFA prévu</span>`
    },
  ];

  document.getElementById('kpiGrid').innerHTML = kpis.map(k => `
    <div class="kpi-card">
      <div class="kpi-icon" style="background:${k.bg}">
        <svg viewBox="0 0 24 24" style="stroke:${k.color}">${k.icon}</svg>
      </div>
      <div class="kpi-val">${k.val} <small>${k.unit}</small></div>
      <div class="kpi-label">${k.label}</div>
      ${k.badge}
    </div>`).join('');

  // Marge circle
  document.getElementById('margePct').textContent = d.marge + '%';
  document.getElementById('netAnnuel').textContent = fmt(d.net_annee);
  const offset = 314 - (Math.min(Math.max(d.marge, 0), 100) / 100) * 314;
  document.getElementById('margeCircle').style.strokeDashoffset = offset;
  document.getElementById('margeCircle').style.stroke = d.marge >= 0 ? 'url(#margeGrad)' : '#e05252';
}

// ===== REVENUS / DÉPENSES =====
async function loadRevDep() {
  const data = await api('revenus_depenses_mois');
  destroyChart('revDep');
  const ctx = document.getElementById('chartRevDep').getContext('2d');
  charts['revDep'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: MOIS,
      datasets: [
        {
          label: 'Revenus',
          data: data.map(d => d.revenus),
          backgroundColor: 'rgba(54,169,225,0.8)',
          borderRadius: 6, borderSkipped: false,
        },
        {
          label: 'Dépenses',
          data: data.map(d => d.depenses),
          backgroundColor: 'rgba(224,82,82,0.7)',
          borderRadius: 6, borderSkipped: false,
        },
        {
          label: 'Prévu',
          data: data.map(d => d.prevu),
          type: 'line',
          borderColor: 'rgba(46,204,135,0.7)',
          backgroundColor: 'rgba(46,204,135,0.05)',
          borderWidth: 2, borderDash: [5,4],
          pointRadius: 3, tension: 0.4, fill: false,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.dataset.label + ' : ' + fmt(ctx.raw) + ' FCFA' } } },
      scales: {
        x: { grid: { color: 'rgba(54,169,225,0.06)' } },
        y: { grid: { color: 'rgba(54,169,225,0.06)' }, ticks: { callback: v => fmtK(v) } }
      }
    }
  });
}

// ===== CUMUL =====
async function loadCumul() {
  const data = await api('tresorerie_cumul');
  destroyChart('cumul');
  const ctx = document.getElementById('chartCumul').getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 260);
  gradient.addColorStop(0, 'rgba(54,169,225,0.3)');
  gradient.addColorStop(1, 'rgba(54,169,225,0)');
  charts['cumul'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.length ? data.map(d => d.mois) : MOIS.slice(0,1),
      datasets: [{
        data: data.length ? data.map(d => d.cumul) : [0],
        borderColor: '#36A9E1', backgroundColor: gradient,
        borderWidth: 2.5, fill: true, tension: 0.4,
        pointBackgroundColor: '#36A9E1', pointRadius: 4,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => fmt(ctx.raw) + ' FCFA cumulé' } } },
      scales: {
        x: { grid: { color: 'rgba(54,169,225,0.06)' } },
        y: { grid: { color: 'rgba(54,169,225,0.06)' }, ticks: { callback: v => fmtK(v) } }
      }
    }
  });
}

// ===== CATEGORIES =====
async function loadCategories() {
  const data = await api('categories');
  destroyChart('cat');
  const ctx = document.getElementById('chartCategories').getContext('2d');
  const labels = data.entrees.map(d => d.categorie);
  const values = data.entrees.map(d => parseFloat(d.total));
  if (!values.length) { ctx.canvas.parentElement.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg><p>Aucune donnée</p></div>'; return; }
  charts['cat'] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data: values, backgroundColor: COLORS, borderColor: '#1a1930', borderWidth: 3, hoverOffset: 8 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '65%',
      plugins: {
        legend: { position: 'right', labels: { boxWidth: 10, padding: 12 } },
        tooltip: { callbacks: { label: ctx => ctx.label + ' : ' + fmt(ctx.raw) + ' FCFA' } }
      }
    }
  });
}

// ===== PIPELINE =====
async function loadPipeline() {
  const data = await api('pipeline');
  destroyChart('pipeline');
  const ctx = document.getElementById('chartPipeline').getContext('2d');
  const colors = { 'Prospection':'#7a78a0','Devis envoyé':'#f0a500','Signé':'#9b8fff','En cours':'#36A9E1','En test':'#26c6da','Livré':'#2ecc87','Clôturé':'#3a3860' };
  const total = data.reduce((s, d) => s + d.valeur, 0);

  charts['pipeline'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(d => d.statut),
      datasets: [{
        data: data.map(d => d.valeur),
        backgroundColor: data.map(d => colors[d.statut] || '#36A9E1'),
        borderRadius: 8, borderSkipped: false,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, indexAxis: 'y',
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => fmt(ctx.raw) + ' FCFA — ' + (data[ctx.dataIndex].nb) + ' projet(s)' } } },
      scales: { x: { grid: { color: 'rgba(54,169,225,0.06)' }, ticks: { callback: v => fmtK(v) } }, y: { grid: { display: false } } }
    }
  });

  // Table
  const tbody = document.querySelector('#pipelineTable tbody');
  tbody.innerHTML = data.map(d => {
    const pct = total > 0 ? Math.round((d.valeur / total) * 100) : 0;
    const c = colors[d.statut] || '#36A9E1';
    return `<tr>
      <td><span class="status-pill" style="background:${c}22;color:${c}">${d.statut}</span></td>
      <td style="font-weight:600;color:#fff">${d.nb}</td>
      <td style="font-family:'Space Mono',monospace;font-size:12px">${fmt(d.valeur)}</td>
      <td><div class="progress-wrap"><div class="progress-bar"><div class="progress-fill" style="width:${pct}%"></div></div><span style="font-size:11px;color:var(--muted);white-space:nowrap">${pct}%</span></div></td>
    </tr>`;
  }).join('');
}

// ===== TOP CLIENTS =====
async function loadClients() {
  const data = await api('top_clients');
  destroyChart('clients');
  const ctx = document.getElementById('chartClients').getContext('2d');
  if (!data.length) {
    ctx.canvas.parentElement.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><p>Aucun client avec chiffre d\'affaires enregistré</p></div>';
    return;
  }
  const gradient = ctx.createLinearGradient(0, 0, 600, 0);
  gradient.addColorStop(0, '#29235C');
  gradient.addColorStop(1, '#36A9E1');
  charts['clients'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(d => d.raison_sociale),
      datasets: [{
        data: data.map(d => parseFloat(d.ca)),
        backgroundColor: gradient,
        borderRadius: 8, borderSkipped: false,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => fmt(ctx.raw) + ' FCFA — ' + data[ctx.dataIndex].nb_projets + ' projet(s)' } } },
      scales: {
        x: { grid: { display: false } },
        y: { grid: { color: 'rgba(54,169,225,0.06)' }, ticks: { callback: v => fmtK(v) } }
      }
    }
  });
}

// ===== FACTURATION =====
async function loadFacturation() {
  const d = await api('facturation');
  destroyChart('fact');
  const ctx = document.getElementById('chartFacturation').getContext('2d');
  charts['fact'] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Payées', 'En attente', 'Annulées'],
      datasets: [{ data: [d.payees, d.attente, d.annulees], backgroundColor: ['#2ecc87','#f0a500','#e05252'], borderColor: '#1a1930', borderWidth: 3, hoverOffset: 6 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '60%',
      plugins: { legend: { position: 'right', labels: { boxWidth: 10, padding: 10 } } }
    }
  });
  document.getElementById('factGrid').innerHTML = `
    <div class="fact-item"><div class="fact-val" style="color:#2ecc87">${fmt(d.montant_total)}</div><div class="fact-lbl">FCFA encaissés</div></div>
    <div class="fact-item"><div class="fact-val" style="color:#f0a500">${fmt(d.montant_attente)}</div><div class="fact-lbl">FCFA en attente</div></div>`;
}

// ===== EXPORT CSV =====
function exportCSV() {
  const rows = [['Mois','Revenus (FCFA)','Dépenses (FCFA)','Net (FCFA)']];
  const chart = charts['revDep'];
  if (!chart) { alert('Chargez d\'abord le tableau de bord.'); return; }
  const rev = chart.data.datasets[0].data;
  const dep = chart.data.datasets[1].data;
  MOIS.forEach((m, i) => rows.push([m + ' ' + currentYear, rev[i]||0, dep[i]||0, (rev[i]||0)-(dep[i]||0)]));
  const csv = rows.map(r => r.join(';')).join('\n');
  const a = document.createElement('a');
  a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
  a.download = `finances_uptechgroup_${currentYear}.csv`;
  a.click();
}

// ===== LOAD YEARS =====
async function loadYears() {
  const years = await api('annees');
  const sel = document.getElementById('yearSelect');
  sel.innerHTML = years.map(y => `<option value="${y}" ${y==currentYear?'selected':''}>${y}</option>`).join('');
}

// ===== INIT =====
loadYears().then(() => loadAll());
</script>
</body>
</html>
