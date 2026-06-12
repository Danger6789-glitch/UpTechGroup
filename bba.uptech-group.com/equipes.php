<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Equipes — BBA</title>
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
    <h1 class="page-hero-title">Les equipes</h1>
  </div>
</div>
<div class="container" style="padding:40px 24px">
  <div class="teams-grid" id="teams-grid"><div class="loading-state">Chargement...</div></div>
</div>
<?php include 'footer.php'; ?>
<script src="app.js"></script>
<script>
async function init(){
  const [standings,players]=await Promise.all([fetch('api.php?action=standings').then(r=>r.json()),fetch('api.php?action=players').then(r=>r.json())]);
  const el=document.getElementById('teams-grid');
  if(!standings.success){el.innerHTML='<div class="loading-state">Erreur</div>';return;}
  el.innerHTML=standings.data.map(t=>{
    const color=teamColor(t.id);
    const teamPlayers=players.data?players.data.filter(p=>p.team===t.id):[];
    const pct=t.played>0?Math.round((t.wins/t.played)*100):0;
    return `<div class="team-page-card">
      <div class="tpc-header" style="background:linear-gradient(135deg,${color}18,${color}05);border-bottom:3px solid ${color}">
        <div class="tpc-logo" style="background:${color}18;border:3px solid ${color}55;color:${color}">
          ${t.logo?`<img src="assets/${t.logo}" style="width:60px;height:60px;border-radius:50%;object-fit:cover">`:teamShort(t.id)}
        </div>
        <div class="tpc-info"><div class="tpc-name" style="color:${color}">${t.name}</div><div class="tpc-city">Lome, Togo</div></div>
      </div>
      <div class="tpc-stats">
        ${[['V',t.wins,color],['D',t.losses,'#6b7280'],['J',t.played,'#111'],['Win%',pct+'%',color]].map(([l,v,c])=>`<div class="tpc-stat"><div class="tpc-stat-val" style="color:${c}">${v}</div><div class="tpc-stat-lbl">${l}</div></div>`).join('')}
      </div>
      <div class="tpc-players">
        <div class="tpc-players-title">Effectif (${teamPlayers.length} joueurs)</div>
        ${teamPlayers.map(p=>{const tier=getTier(p.level);const initials=p.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();return `<div class="tpc-player"><div class="tpc-p-av" style="background:${color}15;border:1.5px solid ${color}33;color:${color}">${p.photo?`<img src="assets/${p.photo}" style="width:32px;height:32px;border-radius:50%;object-fit:cover">`:initials}</div><div class="tpc-p-info"><div class="tpc-p-name">${p.name}</div><div class="tpc-p-sub">${p.position} · #${p.number}</div></div><span class="tier-badge" style="background:${tier.bg};color:${tier.color}">${tier.label}</span></div>`;}).join('')||'<div class="loading-state" style="padding:16px">Aucun joueur</div>'}
      </div>
    </div>`;
  }).join('');
  loadScoresBar();
}
init();
</script>
</body>
</html>
