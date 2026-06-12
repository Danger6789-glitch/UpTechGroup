<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statistiques — BBA</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="scores-bar" id="scores-bar"></div>
<div class="page-hero">
  <div class="page-hero-inner">
    <div class="page-hero-label">BBA · Saison 2025-26</div>
    <h1 class="page-hero-title">Statistiques</h1>
  </div>
</div>
<div class="container" style="padding:40px 24px">
  <div class="filter-tabs">
    <button class="filter-tab active" onclick="loadLeaders('pts','PPG',this)">Points</button>
    <button class="filter-tab" onclick="loadLeaders('reb','RPG',this)">Rebonds</button>
    <button class="filter-tab" onclick="loadLeaders('ast','APG',this)">Passes</button>
    <button class="filter-tab" onclick="loadLeaders('stl','SPG',this)">Interceptions</button>
    <button class="filter-tab" onclick="loadLeaders('blk','BPG',this)">Contres</button>
  </div>
  <div class="stats-table-wrap">
    <table class="stats-table">
      <thead><tr><th>#</th><th>Joueur</th><th>Equipe</th><th>Poste</th><th>J</th><th id="stat-col">PPG</th><th>FG%</th><th>Niveau</th><th>Valeur</th></tr></thead>
      <tbody id="stats-body"><tr><td colspan="9"><div class="loading-state">Chargement...</div></td></tr></tbody>
    </table>
  </div>
</div>
<?php include 'footer.php'; ?>
<script src="app.js"></script>
<script>
async function loadLeaders(stat,label,btn){
  document.querySelectorAll('.filter-tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('stat-col').textContent=label;
  const res=await fetch('api.php?action=leaders&stat='+stat);
  const json=await res.json();
  const body=document.getElementById('stats-body');
  if(!json.success||!json.data.length){body.innerHTML='<tr><td colspan="9"><div class="loading-state">Aucune stat disponible</div></td></tr>';return;}
  body.innerHTML=json.data.map((p,i)=>{
    const color=teamColor(p.team),tier=getTier(p.level);
    const initials=p.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
    return `<tr>
      <td class="rank-cell">${i+1}</td>
      <td><div class="player-cell"><div class="player-av" style="background:${color}15;border:1.5px solid ${color}44;color:${color}">${initials}</div><span class="player-cell-name">${p.name}</span></div></td>
      <td><span style="color:${color};font-weight:600;font-size:12px">${teamName(p.team)}</span></td>
      <td class="muted-cell">${p.position}</td>
      <td class="center-cell">${p.gp}</td>
      <td class="stat-highlight">${p.stat_value}</td>
      <td class="center-cell">${p.fg_pct||'-'}%</td>
      <td><span class="tier-badge" style="background:${tier.bg};color:${tier.color}">${tier.label} ${p.level}</span></td>
      <td class="center-cell value-cell">${formatFCFA(p.value)}</td>
    </tr>`;
  }).join('');
}
loadLeaders('pts','PPG',document.querySelector('.filter-tab'));
loadScoresBar();
</script>
</body>
</html>
