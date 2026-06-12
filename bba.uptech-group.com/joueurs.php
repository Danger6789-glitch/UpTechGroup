<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Joueurs &mdash; BBA</title>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    <div class="page-hero-label">BBA &bull; Saison 2025-26</div>
    <h1 class="page-hero-title">Les joueurs</h1>
  </div>
</div>
<div class="container" style="padding:32px 24px">
  <div class="filter-tabs" id="filter-tabs">
    <button class="filter-tab active" onclick="filterPlayers('all',this)">Tous</button>
  </div>
  <div class="players-grid" id="players-grid">
    <div class="loading-state">Chargement...</div>
  </div>
</div>
<?php include 'footer.php'; ?>
<script src="app.js"></script>
<script>
let allPlayers = [];

async function init() {
  const [res, teamsRes] = await Promise.all([
    fetch('api.php?action=players').then(r=>r.json()).catch(()=>({data:[]})),
    fetch('team_api.php?action=list_teams').then(r=>r.json()).catch(()=>({data:[]})),
  ]);

  allPlayers = res.data || [];

  const tabs = document.getElementById('filter-tabs');
  if (teamsRes.success && teamsRes.data.length) {
    teamsRes.data.forEach(t => {
      const btn = document.createElement('button');
      btn.className = 'filter-tab';
      btn.textContent = t.name;
      btn.onclick = function() { filterPlayers(t.id, this); };
      tabs.appendChild(btn);
    });
  }

  renderPlayers(allPlayers);
  loadScoresBar();
}

function filterPlayers(team, btn) {
  document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const filtered = team === 'all' ? allPlayers : allPlayers.filter(p => p.team === team);
  renderPlayers(filtered);
}

function renderPlayers(data) {
  const el = document.getElementById('players-grid');
  if (!data || !data.length) {
    el.innerHTML = '<div class="loading-state">Aucun joueur actif pour le moment</div>';
    return;
  }
  el.innerHTML = data.map(p => {
    const color = p.team_color || '#1D428A';
    const teamName = p.team_name || p.team;
    const initials = p.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
    const tier = getTier(p.level);
    return `<div class="player-full-card">
      <div class="pfc-top" style="background:linear-gradient(135deg,${color}18,${color}05)">
        <div class="pfc-avatar" style="background:${color}15;border:3px solid ${color}44;color:${color}">
          ${p.photo
            ? `<img src="assets/${p.photo}" style="width:72px;height:72px;border-radius:50%;object-fit:cover">`
            : initials}
        </div>
        <div class="pfc-num" style="color:${color}22">#${p.number||''}</div>
      </div>
      <div class="pfc-body">
        <div class="pfc-name">${p.name}</div>
        <div class="pfc-badges">
          <span class="team-badge-sm" style="background:${color}15;color:${color}">${teamName}</span>
          <span class="pos-badge">${p.position||''}</span>
          ${p.height ? `<span class="pos-badge">${p.height}</span>` : ''}
        </div>
        <div class="pfc-tier" style="background:${tier.bg}">
          <span style="color:${tier.color};font-weight:700;font-size:12px">${tier.label} &bull; Niv. ${p.level}</span>
          <span style="font-size:12px;font-weight:600;color:#111">${formatFCFA(p.value)}</span>
        </div>
      </div>
    </div>`;
  }).join('');
}

function getTier(level) {
  if (level >= 85) return {label:'&Eacute;LITE', color:'#ff6b35', bg:'rgba(255,107,53,0.1)'};
  if (level >= 75) return {label:'GOLD', color:'#c9a84c', bg:'rgba(201,168,76,0.1)'};
  if (level >= 60) return {label:'SILVER', color:'#6b7280', bg:'rgba(107,114,128,0.1)'};
  return {label:'BRONZE', color:'#cd7f32', bg:'rgba(205,127,50,0.1)'};
}

function formatFCFA(v) {
  if (!v) return '0 FCFA';
  if (v >= 1000000) return (v/1000000).toFixed(1) + 'M FCFA';
  return (v/1000).toFixed(0) + 'K FCFA';
}

init();
</script>
</body>
</html>