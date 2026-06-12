function teamColor(id, teams) {
  if (teams && teams[id]) return teams[id].color || '#1D428A';
  return '#1D428A';
}

function teamName(id, teams) {
  if (teams && teams[id]) return teams[id].name;
  return id;
}

function teamShort(id, teams) {
  if (teams && teams[id]) {
    return teams[id].name.split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
  }
  return id.slice(0,2).toUpperCase();
}

function teamLogo(id, teams) {
  if (teams && teams[id] && teams[id].logo) return 'assets/' + teams[id].logo;
  return null;
}

function formatFCFA(v) {
  if (!v) return '0 FCFA';
  if (v >= 1000000) return (v/1000000).toFixed(1) + 'M FCFA';
  return (v/1000).toFixed(0) + 'K FCFA';
}

function getTier(level) {
  if (level >= 85) return {label:'ÉLITE', color:'#ff6b35', bg:'rgba(255,107,53,0.1)'};
  if (level >= 75) return {label:'GOLD', color:'#c9a84c', bg:'rgba(201,168,76,0.1)'};
  if (level >= 60) return {label:'SILVER', color:'#6b7280', bg:'rgba(107,114,128,0.1)'};
  return {label:'BRONZE', color:'#cd7f32', bg:'rgba(205,127,50,0.1)'};
}

function formatDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('fr-FR', {day:'numeric', month:'short', year:'numeric'});
}

function formatDateShort(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('fr-FR', {day:'numeric', month:'short'});
}

function toggleMenu() {
  const m = document.getElementById('mobile-menu');
  if (!m) return;
  m.classList.toggle('open');
}

function avatarHtml(name, photo, color, size=34) {
  const initials = name ? name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase() : '??';
  if (photo) {
    return `<img src="assets/${photo}" style="width:${size}px;height:${size}px;border-radius:50%;object-fit:cover">`;
  }
  return `<div style="width:${size}px;height:${size}px;border-radius:50%;background:${color}15;border:1.5px solid ${color}44;color:${color};display:flex;align-items:center;justify-content:center;font-family:'Anton',sans-serif;font-size:${Math.round(size*0.35)}px;flex-shrink:0">${initials}</div>`;
}

// Cache des équipes
let _teamsCache = null;

async function getTeams() {
  if (_teamsCache) return _teamsCache;
  try {
    const res = await fetch('team_api.php?action=list_teams');
    const json = await res.json();
    if (json.success && json.data) {
      _teamsCache = {};
      json.data.forEach(t => { _teamsCache[t.id] = t; });
    }
  } catch(e) { _teamsCache = {}; }
  return _teamsCache || {};
}

async function loadScoresBar() {
  const el = document.getElementById('scores-bar');
  if (!el) return;
  try {
    const [matchRes, teams] = await Promise.all([
      fetch('api.php?action=matches').then(r=>r.json()),
      getTeams()
    ]);
    if (!matchRes.success || !matchRes.data.length) { el.style.display='none'; return; }
    const recent = matchRes.data.filter(m => m.status==='finished'||m.status==='live').slice(0,5);
    const upcoming = matchRes.data.filter(m => m.status==='upcoming').slice(0,3);
    const all = [...recent, ...upcoming];
    if (!all.length) { el.style.display='none'; return; }
    el.innerHTML = '<div class="scores-inner">' + all.map((m,i) => {
      const hColor = teamColor(m.home_team, teams);
      const aColor = teamColor(m.away_team, teams);
      const hShort = teamShort(m.home_team, teams);
      const aShort = teamShort(m.away_team, teams);
      let content = '';
      if (m.status==='finished') {
        content = `<div class="score-item"><span style="color:${hColor};font-weight:700">${hShort}</span><span class="score-nums">${m.score_home} - ${m.score_away}</span><span style="color:${aColor};font-weight:700">${aShort}</span><span class="score-status final">Final</span></div>`;
      } else if (m.status==='live') {
        content = `<div class="score-item"><span style="color:${hColor};font-weight:700">${hShort}</span><span class="score-nums live-score">${m.score_home??0} - ${m.score_away??0}</span><span style="color:${aColor};font-weight:700">${aShort}</span><span class="score-status live">Live</span></div>`;
      } else {
        content = `<div class="score-item"><span style="color:${hColor};font-weight:700">${hShort}</span><span class="score-nums" style="color:rgba(255,255,255,0.6)">vs</span><span style="color:${aColor};font-weight:700">${aShort}</span><span class="score-status upcoming">${formatDateShort(m.match_date)}</span></div>`;
      }
      return content + (i < all.length-1 ? '<span class="score-sep">|</span>' : '');
    }).join('') + '</div>';
  } catch(e) { el.style.display='none'; }
}
