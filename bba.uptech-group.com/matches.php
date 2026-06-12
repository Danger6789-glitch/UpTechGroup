<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Matchs — BBA</title>
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
    <h1 class="page-hero-title">Calendrier des matchs</h1>
  </div>
</div>
<div class="container" style="padding:40px 24px">
  <div class="filter-tabs">
    <button class="filter-tab active" onclick="filterMatches('all',this)">Tous</button>
    <button class="filter-tab" onclick="filterMatches('upcoming',this)">A venir</button>
    <button class="filter-tab" onclick="filterMatches('finished',this)">Termines</button>
  </div>
  <div id="matches-content"><div class="loading-state">Chargement...</div></div>
</div>
<?php include 'footer.php'; ?>
<script src="app.js"></script>
<script>
let allMatches=[];
async function init(){
  const res=await fetch('api.php?action=matches');
  const json=await res.json();
  allMatches=json.data||[];
  renderMatches(allMatches);
  loadScoresBar();
}
function filterMatches(status,btn){
  document.querySelectorAll('.filter-tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  renderMatches(status==='all'?allMatches:allMatches.filter(m=>m.status===status));
}
function renderMatches(data){
  const el=document.getElementById('matches-content');
  if(!data.length){el.innerHTML='<div class="loading-state">Aucun match</div>';return;}
  let html='',lastMonth='';
  data.forEach(m=>{
    const d=new Date(m.match_date);
    const month=d.toLocaleDateString('fr-FR',{month:'long',year:'numeric'});
    if(month!==lastMonth){html+=`<div class="month-header">${month}</div>`;lastMonth=month;}
    const hColor=teamColor(m.home_team),aColor=teamColor(m.away_team);
    const score=m.status==='finished'?`<span class="big-score">${m.score_home} - ${m.score_away}</span>`:`<span class="vs-text">VS</span>`;
    const pill=m.status==='finished'?'<span class="status-pill pill-final">Final</span>':m.status==='live'?'<span class="status-pill pill-live">Live</span>':'<span class="status-pill pill-upcoming">A venir</span>';
    html+=`<div class="match-card-full">
      <div class="mcf-date"><div class="mcf-day">${d.toLocaleDateString('fr-FR',{weekday:'short'})}</div><div class="mcf-num">${d.getDate()} ${d.toLocaleDateString('fr-FR',{month:'short'})}</div><div class="mcf-time">${m.match_time?m.match_time.slice(0,5):''}</div></div>
      <div class="mcf-teams">
        <div class="mcf-team"><div class="mcf-logo" style="background:${hColor}15;border:2px solid ${hColor}44;color:${hColor}">${teamShort(m.home_team)}</div><span class="mcf-name" style="color:${hColor}">${teamName(m.home_team)}</span></div>
        ${score}
        <div class="mcf-team"><div class="mcf-logo" style="background:${aColor}15;border:2px solid ${aColor}44;color:${aColor}">${teamShort(m.away_team)}</div><span class="mcf-name" style="color:${aColor}">${teamName(m.away_team)}</span></div>
      </div>
      <div class="mcf-right">${pill}<div class="mcf-venue">${m.venue||''}</div></div>
    </div>`;
  });
  el.innerHTML=html;
}
init();
</script>
</body>
</html>
