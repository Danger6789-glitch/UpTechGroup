<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BBA &mdash; Bateauvi Basketball Association</title>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
<style>
.hero{background:var(--secondary);min-height:540px;display:flex;align-items:center;position:relative;overflow:hidden;}
.hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;opacity:0.25;}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,0,0,0.85) 40%,rgba(29,66,138,0.4));}
.hero-content{position:relative;z-index:2;max-width:1200px;margin:0 auto;padding:60px 24px;width:100%;}
.hero-tag{display:inline-block;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#fff;background:var(--primary);padding:5px 14px;border-radius:4px;margin-bottom:24px;}
.hero-title{font-family:'Anton',sans-serif;font-size:clamp(52px,9vw,110px);line-height:0.95;letter-spacing:2px;color:#fff;margin-bottom:20px;}
.hero-title span{color:#FCD116;}
.hero-sub{color:rgba(255,255,255,0.75);font-size:16px;line-height:1.7;max-width:500px;margin-bottom:36px;}
.hero-btns{display:flex;gap:14px;flex-wrap:wrap;}
.hero-btn-primary{padding:14px 32px;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;background:var(--primary);color:#fff;border:none;font-family:'Inter';transition:all 0.2s;}
.hero-btn-primary:hover{background:#a50d25;transform:translateY(-1px);}
.hero-btn-secondary{padding:14px 32px;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;background:transparent;color:#fff;border:2px solid rgba(255,255,255,0.4);font-family:'Inter';transition:all 0.2s;}
.hero-btn-secondary:hover{border-color:#fff;background:rgba(255,255,255,0.08);}
.main-grid{max-width:1200px;margin:40px auto;padding:0 24px;display:grid;grid-template-columns:1fr 340px;gap:24px;}
.sec-title{font-family:'Anton',sans-serif;font-size:22px;letter-spacing:1px;margin-bottom:14px;color:var(--text);}
.news-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:32px;}
.news-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:all 0.2s;cursor:pointer;}
.news-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.1);}
.news-img{width:100%;height:160px;object-fit:cover;background:var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:13px;}
.news-body{padding:16px;}
.news-date{font-size:11px;color:var(--muted);font-weight:600;margin-bottom:6px;}
.news-title{font-weight:700;font-size:15px;line-height:1.4;margin-bottom:6px;color:var(--text);}
.news-excerpt{font-size:13px;color:var(--muted);line-height:1.6;}
.sidebar-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:var(--card-shadow);}
.sidebar-card-header{padding:14px 20px;border-bottom:1px solid var(--border);font-family:'Anton',sans-serif;font-size:17px;letter-spacing:1px;color:var(--text);}
.standing-row{display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border);}
.standing-row:last-child{border-bottom:none;}
.standing-row:hover{background:var(--bg);}
.st-rank{font-family:'Anton',sans-serif;font-size:20px;color:var(--muted);width:20px;}
.st-logo{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Anton',sans-serif;font-size:12px;flex-shrink:0;overflow:hidden;}
.st-logo img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.st-info{flex:1;}
.st-name{font-weight:700;font-size:14px;color:var(--text);}
.st-record{font-size:11px;color:var(--muted);margin-top:1px;}
.st-pct{font-weight:700;font-size:14px;}
.next-match-box{padding:20px;}
.nm-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:16px;}
.nm-teams{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.nm-team{text-align:center;flex:1;}
.nm-logo{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Anton',sans-serif;font-size:18px;margin:0 auto 8px;overflow:hidden;}
.nm-logo img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.nm-name{font-weight:700;font-size:14px;color:var(--text);}
.nm-vs{font-size:14px;font-weight:700;color:var(--muted);}
.nm-details{font-size:13px;color:var(--muted);line-height:2;}
.nm-details strong{color:var(--text);}
@media(max-width:900px){.main-grid{grid-template-columns:1fr;}.news-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="scores-bar" id="scores-bar"></div>

<div class="hero">
  <div class="hero-bg" id="hero-bg"></div>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <div class="hero-tag">Saison inaugurale 2025-26</div>
    <h1 class="hero-title" id="hero-title">
      BATEAUVI<br><span>BASKETBALL</span>
    </h1>
    <p class="hero-sub" id="hero-sub">La premi&egrave;re ligue de basketball organis&eacute;e de Lom&eacute;. Suis les matchs, les stats et le classement en temps r&eacute;el.</p>
    <div class="hero-btns">
      <button class="hero-btn-primary" onclick="location.href='rejoindre.php'">Rejoindre la ligue</button>
      <button class="hero-btn-secondary" onclick="location.href='matches.php'">Voir les matchs</button>
    </div>
  </div>
</div>

<div class="main-grid">
  <div>
    <div style="margin-bottom:32px">
      <div class="sec-title">Actualit&eacute;s</div>
      <div class="news-grid" id="news-grid">
        <div class="loading-state" style="grid-column:1/-1">Chargement...</div>
      </div>
    </div>
    <div style="margin-bottom:32px">
      <div class="sec-title">Derniers r&eacute;sultats</div>
      <div id="results-list"><div class="loading-state">Chargement...</div></div>
    </div>
  </div>
  <div>
    <div class="sidebar-card">
      <div class="sidebar-card-header">Classement</div>
      <div id="sidebar-standings"><div class="loading-state">Chargement...</div></div>
    </div>
    <div class="sidebar-card">
      <div class="sidebar-card-header">Prochain match</div>
      <div class="next-match-box" id="next-match-box">
        <div class="loading-state">Chargement...</div>
      </div>
    </div>
    <div class="sidebar-card">
      <div class="sidebar-card-header">Leaders points</div>
      <div id="sidebar-leaders"><div class="loading-state">Chargement...</div></div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="app.js"></script>
<script>
async function init() {
  const [settings, articles, matches, standings, leaders] = await Promise.all([
    fetch('upload.php?action=get_settings').then(r=>r.json()).catch(()=>({data:{}})),
    fetch('upload.php?action=list_articles&status=published').then(r=>r.json()).catch(()=>({data:[]})),
    fetch('api.php?action=matches').then(r=>r.json()),
    fetch('api.php?action=standings').then(r=>r.json()),
    fetch('api.php?action=leaders&stat=pts').then(r=>r.json()),
  ]);

  if (settings.data) {
    if (settings.data.hero_image) {
    document.getElementById('hero-bg').style.backgroundImage = "url('assets/hero-bg.jpg')";
    }
  }

  const newsEl = document.getElementById('news-grid');
  if (articles.success && articles.data && articles.data.length) {
    newsEl.innerHTML = articles.data.slice(0,4).map(a => `
      <div class="news-card">
        ${a.filename ? '<img class="news-img" src="assets/'+a.filename+'" alt="">' : '<div class="news-img">Aucune image</div>'}
        <div class="news-body">
          <div class="news-date">${new Date(a.created_at).toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'})}</div>
          <div class="news-title">${a.title}</div>
          <div class="news-excerpt">${a.content ? a.content.substring(0,100)+'...' : ''}</div>
        </div>
      </div>`).join('');
  } else {
    newsEl.innerHTML = '<div class="loading-state" style="grid-column:1/-1">Aucune actualit&eacute; pour le moment</div>';
  }

  if (matches.success && matches.data && matches.data.length) {
    const finished = matches.data.filter(m => m.status === 'finished').slice(0,3);
    const resultsEl = document.getElementById('results-list');
    if (finished.length) {
      resultsEl.innerHTML = finished.map(m => {
        const hColor = teamColor(m.home_team);
        const aColor = teamColor(m.away_team);
        return '<div class="match-card-full">' +
          '<div class="mcf-date"><div class="mcf-num">' + new Date(m.match_date).toLocaleDateString('fr-FR',{day:'numeric',month:'short'}) + '</div></div>' +
          '<div class="mcf-teams">' +
            '<div class="mcf-team"><div class="mcf-logo" style="background:'+hColor+'15;border:2px solid '+hColor+'44;color:'+hColor+'">' + teamShort(m.home_team) + '</div><span class="mcf-name" style="color:'+hColor+'">' + teamName(m.home_team) + '</span></div>' +
            '<span class="big-score">' + m.score_home + ' - ' + m.score_away + '</span>' +
            '<div class="mcf-team"><div class="mcf-logo" style="background:'+aColor+'15;border:2px solid '+aColor+'44;color:'+aColor+'">' + teamShort(m.away_team) + '</div><span class="mcf-name" style="color:'+aColor+'">' + teamName(m.away_team) + '</span></div>' +
          '</div>' +
          '<span class="status-pill pill-final">Final</span>' +
        '</div>';
      }).join('');
    } else {
      resultsEl.innerHTML = '<div class="loading-state">Aucun r&eacute;sultat disponible</div>';
    }

    const next = matches.data.find(m => m.status === 'upcoming');
    const nmEl = document.getElementById('next-match-box');
    if (next) {
      const hColor = teamColor(next.home_team);
      const aColor = teamColor(next.away_team);
      nmEl.innerHTML =
        '<div class="nm-label">Match &agrave; venir</div>' +
        '<div class="nm-teams">' +
          '<div class="nm-team"><div class="nm-logo" style="background:'+hColor+'15;border:2px solid '+hColor+'44;color:'+hColor+'">' + teamShort(next.home_team) + '</div><div class="nm-name">' + teamName(next.home_team) + '</div></div>' +
          '<div class="nm-vs">VS</div>' +
          '<div class="nm-team"><div class="nm-logo" style="background:'+aColor+'15;border:2px solid '+aColor+'44;color:'+aColor+'">' + teamShort(next.away_team) + '</div><div class="nm-name">' + teamName(next.away_team) + '</div></div>' +
        '</div>' +
        '<div class="nm-details">' +
          '<strong>Date :</strong> ' + new Date(next.match_date).toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'}) + '<br>' +
          '<strong>Heure :</strong> ' + (next.match_time ? next.match_time.slice(0,5) : 'A confirmer') + '<br>' +
          '<strong>Lieu :</strong> ' + (next.venue || 'A confirmer') +
        '</div>';
    } else {
      nmEl.innerHTML = '<div class="loading-state">Aucun match programm&eacute;</div>';
    }
  }

  if (standings.success && standings.data && standings.data.length) {
    document.getElementById('sidebar-standings').innerHTML = standings.data.map((t,i) => {
      const color = teamColor(t.id);
      const pct = t.played > 0 ? Math.round((t.wins/t.played)*100) : 0;
      const logoHtml = t.logo
        ? '<img src="assets/'+t.logo+'" style="width:36px;height:36px;border-radius:50%;object-fit:cover">'
        : teamShort(t.id);
      return '<div class="standing-row">' +
        '<div class="st-rank">' + (i+1) + '</div>' +
        '<div class="st-logo" style="background:'+color+'15;border:1.5px solid '+color+'44;color:'+color+'">' + logoHtml + '</div>' +
        '<div class="st-info"><div class="st-name">' + t.name + '</div><div class="st-record">' + t.wins + 'V - ' + t.losses + 'D</div></div>' +
        '<div class="st-pct" style="color:'+color+'">' + pct + '%</div>' +
      '</div>';
    }).join('');
  }

  if (leaders.success && leaders.data && leaders.data.length) {
    document.getElementById('sidebar-leaders').innerHTML = leaders.data.slice(0,5).map((p,i) => {
      const color = teamColor(p.team);
      const initials = p.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
      return '<div class="standing-row">' +
        '<div class="st-rank">' + (i+1) + '</div>' +
        '<div class="st-logo" style="background:'+color+'15;border:1.5px solid '+color+'44;color:'+color+'">' + initials + '</div>' +
        '<div class="st-info"><div class="st-name">' + p.name + '</div><div class="st-record">' + teamName(p.team) + ' &bull; ' + p.position + '</div></div>' +
        '<div class="st-pct" style="color:var(--primary)">' + p.stat_value + '</div>' +
      '</div>';
    }).join('');
  } else {
    document.getElementById('sidebar-leaders').innerHTML = '<div class="loading-state">Aucune stat</div>';
  }

  loadScoresBar();
}
init();
</script>
</body>
</html>