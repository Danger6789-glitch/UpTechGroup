<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$stmt = $pdo->prepare("SELECT u.*, t.name as team_name, t.color as team_color FROM users u LEFT JOIN teams t ON t.id=u.team WHERE u.id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { session_destroy(); header('Location: login.php'); exit; }
$color = $user['role']==='admin' ? '#C8102E' : ($user['team_color'] ?: '#1D428A');
$initials = substr(implode('', array_map(fn($w)=>strtoupper($w[0]), explode(' ', $user['name']))), 0, 2);

// Récupérer les équipes pour les selects
$teamsStmt = $pdo->query("SELECT * FROM teams WHERE status='active' ORDER BY name ASC");
$allTeams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les matchs terminés pour saisie stats
$finishedStmt = $pdo->query("SELECT m.*, ht.name as home_name, at.name as away_name FROM matches m LEFT JOIN teams ht ON ht.id=m.home_team LEFT JOIN teams at ON at.id=m.away_team WHERE m.status='finished' ORDER BY m.match_date DESC");
$finishedMatches = $finishedStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les médias pour le CMS
$mediaStmt = $pdo->query("SELECT * FROM media ORDER BY created_at DESC LIMIT 50");
$allMedia = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les paramètres
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) $settings[$row['setting_key']] = $row['setting_value'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard &mdash; BBA</title>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
<style>
body{display:flex;min-height:100vh;background:var(--bg);}
.sidebar{width:230px;background:#111;height:100vh;position:sticky;top:0;display:flex;flex-direction:column;flex-shrink:0;overflow-y:auto;}
.sidebar-logo{padding:16px 20px;border-bottom:1px solid rgba(255,255,255,0.07);}
.sidebar-logo img{height:36px;object-fit:contain;}
.sidebar-logo-text{font-family:'Anton',sans-serif;font-size:17px;letter-spacing:2px;color:#C8102E;}
.sidebar-logo-sub{font-size:10px;color:#6b7280;letter-spacing:1.5px;text-transform:uppercase;margin-top:2px;}
.sidebar-nav{flex:1;padding:10px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;margin-bottom:2px;cursor:pointer;color:#6b7280;font-size:13px;font-weight:500;transition:all 0.15s;user-select:none;}
.nav-item:hover{background:rgba(255,255,255,0.06);color:#fff;}
.nav-item.active{background:rgba(200,16,46,0.2);color:#C8102E;font-weight:600;}
.nav-badge{margin-left:auto;background:#C8102E;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;display:none;}
.sidebar-user{padding:14px;border-top:1px solid rgba(255,255,255,0.07);}
.sidebar-user-info{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.user-av{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Anton',sans-serif;font-size:13px;flex-shrink:0;}
.user-name{font-size:12px;font-weight:600;color:#fff;}
.user-role{font-size:10px;color:#6b7280;}
.btn-logout{width:100%;padding:8px;border-radius:7px;background:rgba(255,255,255,0.06);color:#6b7280;border:none;cursor:pointer;font-size:12px;font-family:'Inter';transition:all 0.15s;}
.btn-logout:hover{background:rgba(255,255,255,0.1);color:#fff;}
.main-content{flex:1;overflow-y:auto;min-width:0;}
.topbar{background:#fff;border-bottom:1px solid var(--border);padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;position:sticky;top:0;z-index:50;}
.topbar-title{font-family:'Anton',sans-serif;font-size:20px;letter-spacing:1px;color:var(--text);}
.content{padding:24px;}
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
.kpi-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;display:flex;align-items:center;gap:12px;box-shadow:var(--card-shadow);}
.kpi-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.kpi-val{font-family:'Anton',sans-serif;font-size:28px;color:var(--text);line-height:1;}
.kpi-label{font-size:11px;color:var(--muted);font-weight:500;margin-top:2px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px;}
.dash-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--card-shadow);}
.dash-card-header{padding:14px 18px;border-bottom:1px solid var(--border);font-family:'Anton',sans-serif;font-size:17px;letter-spacing:1px;color:var(--text);display:flex;align-items:center;justify-content:space-between;}
.dash-card-body{padding:18px;}
.row-item{display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);}
.row-item:last-child{border-bottom:none;}
.row-item:hover{background:var(--bg);}
.btn{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:'Inter';transition:all 0.15s;display:inline-flex;align-items:center;gap:6px;}
.btn-primary{background:var(--secondary);color:#fff;}
.btn-primary:hover{background:#163580;}
.btn-red{background:var(--primary);color:#fff;}
.btn-red:hover{background:#a50d25;}
.btn-green{background:#006837;color:#fff;}
.btn-green:hover{background:#005229;}
.btn-outline{background:transparent;color:var(--text);border:1.5px solid var(--border);}
.btn-outline:hover{border-color:var(--secondary);color:var(--secondary);}
.btn-sm{padding:5px 12px;font-size:12px;}
.btn-xs{padding:3px 9px;font-size:11px;}
.page-section{display:none;}
.page-section.active{display:block;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--text);}
.field input,.field select,.field textarea{width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text);background:var(--bg);outline:none;font-family:'Inter';transition:border 0.2s;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--secondary);background:#fff;}
.field textarea{resize:vertical;min-height:100px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;display:none;}
.alert-success{background:rgba(29,66,138,0.08);border:1px solid rgba(29,66,138,0.2);color:var(--secondary);}
.alert-error{background:rgba(200,16,46,0.08);border:1px solid rgba(200,16,46,0.2);color:var(--primary);}
table{width:100%;border-collapse:collapse;}
th{padding:10px 14px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);text-align:left;border-bottom:1px solid var(--border);background:var(--bg);white-space:nowrap;}
td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:var(--bg);}
.tier-badge{display:inline-block;font-size:9px;font-weight:700;letter-spacing:1px;padding:2px 7px;border-radius:4px;text-transform:uppercase;}
.pending-card{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:14px;}
.pending-card:last-child{border-bottom:none;}
.pending-av{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Anton',sans-serif;font-size:15px;flex-shrink:0;}
.pending-info{flex:1;}
.pending-name{font-weight:700;font-size:14px;color:var(--text);}
.pending-detail{font-size:12px;color:var(--muted);margin-top:4px;line-height:1.8;}
.pending-actions{display:flex;gap:6px;flex-wrap:wrap;}
/* CMS */
.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin-top:14px;}
.media-item{border:1px solid var(--border);border-radius:8px;overflow:hidden;position:relative;cursor:pointer;}
.media-item img{width:100%;height:80px;object-fit:cover;display:block;}
.media-item-name{font-size:10px;color:var(--muted);padding:4px 6px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}
.media-item.selected{border-color:var(--secondary);border-width:2px;}
.media-item .del-btn{position:absolute;top:4px;right:4px;background:rgba(200,16,46,0.9);color:#fff;border:none;border-radius:4px;padding:2px 5px;font-size:10px;cursor:pointer;display:none;}
.media-item:hover .del-btn{display:block;}
.rich-editor{border:1.5px solid var(--border);border-radius:8px;overflow:hidden;}
.rich-toolbar{display:flex;gap:4px;padding:8px;background:var(--bg);border-bottom:1px solid var(--border);flex-wrap:wrap;}
.rich-toolbar button{padding:4px 8px;border:1px solid var(--border);border-radius:4px;background:#fff;cursor:pointer;font-size:12px;font-family:'Inter';}
.rich-toolbar button:hover{background:var(--secondary);color:#fff;border-color:var(--secondary);}
.rich-content{min-height:120px;padding:12px;outline:none;font-family:'Inter';font-size:14px;line-height:1.7;}
/* Team requests */
.team-req-card{padding:16px 18px;border-bottom:1px solid var(--border);}
.team-req-card:last-child{border-bottom:none;}
.team-req-name{font-weight:700;font-size:15px;display:flex;align-items:center;gap:10px;}
.team-color-dot{width:16px;height:16px;border-radius:50%;display:inline-block;flex-shrink:0;}
.team-req-detail{font-size:12px;color:var(--muted);margin-top:6px;line-height:1.9;}
.team-req-actions{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;align-items:center;}
/* Profile */
.profile-header{display:flex;align-items:center;gap:24px;margin-bottom:22px;background:#fff;border:1px solid var(--border);border-radius:12px;padding:22px;}
.profile-av-big{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Anton',sans-serif;font-size:28px;flex-shrink:0;overflow:hidden;}
.profile-av-big img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.profile-name{font-family:'Anton',sans-serif;font-size:26px;letter-spacing:1px;color:var(--text);}
.profile-sub{font-size:14px;color:var(--muted);margin-top:4px;line-height:1.7;}
.stat-grid-4{display:grid;grid-template-columns:repeat(4,1fr);}
.stat-cell{text-align:center;padding:16px 8px;border-right:1px solid var(--border);}
.stat-cell:last-child{border-right:none;}
.stat-big{font-family:'Anton',sans-serif;font-size:30px;line-height:1;}
.stat-lbl{font-size:10px;font-weight:700;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;margin-top:2px;}
@media(max-width:900px){.kpi-grid{grid-template-columns:1fr 1fr;}.grid-2{grid-template-columns:1fr;}.sidebar{display:none;}}
</style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">
    <?php
    $logoFile = $settings['site_logo'] ?? '';
    if ($logoFile && file_exists(__DIR__.'/assets/'.$logoFile)):?>
      <img src="assets/<?=htmlspecialchars($logoFile)?>" alt="BBA">
    <?php else:?>
      <div class="sidebar-logo-text">BBA</div>
    <?php endif;?>
    <div class="sidebar-logo-sub">Basketball Association</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-item active" onclick="showPage('dashboard',this)">&#9632; Tableau de bord</div>
    <div class="nav-item" onclick="showPage('players',this)">&#9632; Joueurs</div>
    <div class="nav-item" onclick="showPage('matches',this)">&#9632; Matchs</div>
    <div class="nav-item" onclick="showPage('stats',this)">&#9632; Statistiques</div>
    <?php if(in_array($user['role'],['admin','manager'])):?>
    <div class="nav-item" onclick="showPage('pending',this)" id="nav-pending">&#9632; Demandes <span class="nav-badge" id="badge-pending">0</span></div>
    <div class="nav-item" onclick="showPage('saisie',this)">&#9632; Saisir stats</div>
    <?php endif;?>
    <?php if($user['role']==='admin'):?>
    <div class="nav-item" onclick="showPage('teams',this)" id="nav-teams">&#9632; Équipes <span class="nav-badge" id="badge-teams">0</span></div>
    <div class="nav-item" onclick="showPage('cms',this)">&#9632; Actualités</div>
    <div class="nav-item" onclick="showPage('media',this)">&#9632; Médias</div>
    <div class="nav-item" onclick="showPage('parametres',this)">&#9632; Paramètres</div>
    <div class="nav-item" onclick="showPage('admin',this)">&#9632; Administration</div>
    <?php endif;?>
    <?php if($user['role']==='player'):?>
    <div class="nav-item" onclick="showPage('profile',this)">&#9632; Mon profil</div>
    <?php endif;?>
  </nav>
  <div class="sidebar-user">
    <div class="sidebar-user-info">
      <div class="user-av" style="background:<?=$color?>22;border:1.5px solid <?=$color?>44;color:<?=$color?>"><?=$initials?></div>
      <div>
        <div class="user-name"><?=htmlspecialchars($user['name'])?></div>
        <div class="user-role"><?=$user['role']==='admin'?'Super Admin':($user['role']==='manager'?'Responsable '.htmlspecialchars($user['team_name']??''):'Joueur')?></div>
      </div>
    </div>
    <button class="btn-logout" onclick="location.href='logout.php'">D&eacute;connexion</button>
  </div>
</div>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title" id="topbar-title">Tableau de bord</div>
    <div style="display:flex;align-items:center;gap:10px;">
      <span style="font-size:12px;color:var(--muted)">Saison <?=htmlspecialchars($settings['season_name']??'2025-26')?></span>
      <a href="index.php" class="btn btn-outline btn-sm">Site public</a>
    </div>
  </div>

  <div class="content">
    <div class="alert alert-success" id="global-success"></div>
    <div class="alert alert-error" id="global-error"></div>

    <!-- DASHBOARD -->
    <div id="page-dashboard" class="page-section active">
      <div class="kpi-grid" id="kpi-grid">
        <div class="loading-state" style="grid-column:1/-1">Chargement...</div>
      </div>
      <div class="grid-2">
        <div>
          <div class="dash-card-header" style="background:#fff;border:1px solid var(--border);border-radius:12px 12px 0 0;">Classement</div>
          <div class="dash-card" style="border-radius:0 0 12px 12px;" id="dash-standings"><div class="loading-state">Chargement...</div></div>
        </div>
        <div>
          <div class="dash-card-header" style="background:#fff;border:1px solid var(--border);border-radius:12px 12px 0 0;">Prochains matchs</div>
          <div class="dash-card" style="border-radius:0 0 12px 12px;" id="dash-matches"><div class="loading-state">Chargement...</div></div>
        </div>
      </div>
    </div>

    <!-- JOUEURS -->
    <div id="page-players" class="page-section">
      <div class="dash-card" id="players-list"><div class="loading-state">Chargement...</div></div>
    </div>

    <!-- MATCHS -->
    <div id="page-matches" class="page-section">
      <?php if($user['role']==='admin'):?>
      <div class="dash-card dash-card-body" style="margin-bottom:18px;">
        <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Planifier un match</div>
        <div class="alert alert-success" id="match-success"></div>
        <div class="form-grid">
          <div class="field"><label>Équipe domicile</label>
            <select id="m-home">
              <?php foreach($allTeams as $t):?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach;?>
            </select>
          </div>
          <div class="field"><label>Équipe visiteur</label>
            <select id="m-away">
              <?php foreach($allTeams as $t):?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach;?>
            </select>
          </div>
          <div class="field"><label>Date</label><input type="date" id="m-date"></div>
          <div class="field"><label>Heure</label><input type="time" id="m-time" value="18:00"></div>
        </div>
        <div class="field"><label>Lieu</label><input type="text" id="m-venue" placeholder="Salle Omnisports de Lom&eacute;"></div>
        <button class="btn btn-primary" onclick="createMatch()">Cr&eacute;er le match</button>
      </div>
      <div class="dash-card dash-card-body" style="margin-bottom:18px;">
        <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Enregistrer un score</div>
        <div class="alert alert-success" id="score-success"></div>
        <div class="field"><label>Match</label><select id="sc-match" onchange="fillScore(this)"><option value="">Choisir...</option></select></div>
        <div class="form-grid">
          <div class="field"><label id="sc-home-label">Domicile</label><input type="number" id="sc-home" value="0" min="0"></div>
          <div class="field"><label id="sc-away-label">Visiteur</label><input type="number" id="sc-away" value="0" min="0"></div>
        </div>
        <button class="btn btn-primary" onclick="saveScore()">Enregistrer le score</button>
      </div>
      <?php endif;?>
      <div class="dash-card" id="matches-list"><div class="loading-state">Chargement...</div></div>
    </div>

    <!-- STATS -->
    <div id="page-stats" class="page-section">
      <div style="display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap;" id="stat-tabs">
        <button class="btn btn-primary btn-sm" onclick="loadLeaders('pts','PPG',this)">Points</button>
        <button class="btn btn-outline btn-sm" onclick="loadLeaders('reb','RPG',this)">Rebonds</button>
        <button class="btn btn-outline btn-sm" onclick="loadLeaders('ast','APG',this)">Passes</button>
        <button class="btn btn-outline btn-sm" onclick="loadLeaders('stl','SPG',this)">Interceptions</button>
        <button class="btn btn-outline btn-sm" onclick="loadLeaders('blk','BPG',this)">Contres</button>
      </div>
      <div class="dash-card" style="overflow-x:auto;">
        <table>
          <thead><tr><th>#</th><th>Joueur</th><th>Équipe</th><th>Taille</th><th>J</th><th id="stat-col">PPG</th><th>Niveau</th><th>Valeur</th></tr></thead>
          <tbody id="stats-body"><tr><td colspan="8"><div class="loading-state">Chargement...</div></td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- DEMANDES JOUEURS -->
    <?php if(in_array($user['role'],['admin','manager'])):?>
    <div id="page-pending" class="page-section">
      <div class="dash-card" id="pending-list"><div class="loading-state">Chargement...</div></div>
    </div>

    <!-- SAISIE STATS -->
    <div id="page-saisie" class="page-section">
      <div class="dash-card dash-card-body">
        <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Saisir les stats d&apos;un joueur</div>
        <div class="alert alert-success" id="saisie-success"></div>
        <div class="alert alert-error" id="saisie-error"></div>
        <div class="form-grid">
          <div class="field"><label>Match termin&eacute;</label>
            <select id="ss-match">
              <option value="">Choisir un match...</option>
              <?php foreach($finishedMatches as $m):?>
              <option value="<?=$m['id']?>"><?=date('d/m/Y',strtotime($m['match_date']))?> &mdash; <?=htmlspecialchars($m['home_name'])?> vs <?=htmlspecialchars($m['away_name'])?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="field"><label>Joueur</label><select id="ss-player"><option value="">Choisir...</option></select></div>
        </div>
        <div class="form-grid">
          <div class="field"><label>Points</label><input type="number" id="ss-pts" value="0" min="0"></div>
          <div class="field"><label>Rebonds</label><input type="number" id="ss-reb" value="0" min="0"></div>
          <div class="field"><label>Passes d&eacute;cisives</label><input type="number" id="ss-ast" value="0" min="0"></div>
          <div class="field"><label>Interceptions</label><input type="number" id="ss-stl" value="0" min="0"></div>
          <div class="field"><label>Contres</label><input type="number" id="ss-blk" value="0" min="0"></div>
          <div class="field"><label>Paniers r&eacute;ussis</label><input type="number" id="ss-fgm" value="0" min="0"></div>
          <div class="field"><label>Paniers tent&eacute;s</label><input type="number" id="ss-fga" value="0" min="0"></div>
        </div>
        <button class="btn btn-primary" onclick="saisirStats()">Enregistrer les stats</button>
      </div>
    </div>
    <?php endif;?>

    <?php if($user['role']==='admin'):?>

    <!-- CANDIDATURES ÉQUIPES -->
    <div id="page-teams" class="page-section">
      <div style="display:flex;gap:8px;margin-bottom:14px;">
        <button class="btn btn-primary btn-sm" onclick="loadTeamRequests('pending',this)">En attente</button>
        <button class="btn btn-outline btn-sm" onclick="loadTeamRequests('accepted',this)">Accept&eacute;es</button>
        <button class="btn btn-outline btn-sm" onclick="loadTeamRequests('rejected',this)">Refus&eacute;es</button>
      </div>
      <div class="dash-card" id="team-requests-list"><div class="loading-state">Chargement...</div></div>
    </div>

    <!-- CMS ACTUALITÉS -->
    <div id="page-cms" class="page-section">
      <div class="grid-2" style="margin-bottom:18px;">
        <div class="dash-card dash-card-body">
          <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;" id="article-form-title">Nouvel article</div>
          <div class="alert alert-success" id="cms-success"></div>
          <input type="hidden" id="article-id" value="">
          <div class="field"><label>Titre <span style="color:var(--primary)">*</span></label><input type="text" id="a-title" placeholder="Titre de l'article"></div>
          <div class="form-grid">
            <div class="field"><label>Cat&eacute;gorie</label>
              <select id="a-cat">
                <option value="actualite">Actualit&eacute;</option>
                <option value="resultat">R&eacute;sultat</option>
                <option value="trade">Trade</option>
                <option value="annonce">Annonce</option>
                <option value="interview">Interview</option>
              </select>
            </div>
            <div class="field"><label>Type d&apos;&eacute;diteur</label>
              <select id="a-type" onchange="toggleEditor(this.value)">
                <option value="simple">Simple</option>
                <option value="rich">Riche</option>
              </select>
            </div>
          </div>
          <div class="field" id="simple-editor">
            <label>Contenu</label>
            <textarea id="a-content-simple" placeholder="Contenu de l'article..."></textarea>
          </div>
          <div class="field" id="rich-editor" style="display:none;">
            <label>Contenu (éditeur riche)</label>
            <div class="rich-editor">
              <div class="rich-toolbar">
                <button onclick="fmt('bold')" title="Gras"><strong>G</strong></button>
                <button onclick="fmt('italic')" title="Italique"><em>I</em></button>
                <button onclick="fmt('underline')" title="Soulign&eacute;"><u>S</u></button>
                <button onclick="fmt('insertUnorderedList')" title="Liste">&#8226; Liste</button>
                <button onclick="fmt('insertOrderedList')" title="Liste num&eacute;rot&eacute;e">1. Liste</button>
                <button onclick="fmt('formatBlock','h3')" title="Titre">Titre</button>
              </div>
              <div class="rich-content" id="a-content-rich" contenteditable="true" placeholder="Contenu de l'article..."></div>
            </div>
          </div>
          <div class="field">
            <label>Image associ&eacute;e</label>
            <select id="a-image">
              <option value="">Aucune image</option>
              <?php foreach($allMedia as $m):?>
              <option value="<?=$m['id']?>"><?=htmlspecialchars($m['original_name'])?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="field"><label>Statut</label>
            <select id="a-status">
              <option value="published">Publi&eacute;</option>
              <option value="draft">Brouillon</option>
            </select>
          </div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-primary" onclick="saveArticle()">Publier</button>
            <button class="btn btn-outline btn-sm" onclick="resetArticleForm()">Nouveau</button>
          </div>
        </div>
        <div>
          <div class="dash-card" id="articles-list"><div class="loading-state">Chargement...</div></div>
        </div>
      </div>
    </div>

    <!-- MÉDIATHÈQUE -->
    <div id="page-media" class="page-section">
      <div class="dash-card dash-card-body" style="margin-bottom:18px;">
        <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Uploader une image</div>
        <div class="alert alert-success" id="media-success"></div>
        <div class="form-grid">
          <div class="field"><label>Fichier (JPG, PNG, WEBP &mdash; max 5 Mo)</label><input type="file" id="media-file" accept="image/*"></div>
          <div class="field"><label>Type</label>
            <select id="media-type">
              <option value="image">Image article</option>
              <option value="banner">Banni&egrave;re</option>
              <option value="logo">Logo</option>
            </select>
          </div>
        </div>
        <button class="btn btn-primary" onclick="uploadMedia()">Uploader</button>
      </div>
      <div class="dash-card dash-card-body">
        <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">M&eacute;diath&egrave;que</div>
        <div class="media-grid" id="media-grid">
          <?php foreach($allMedia as $m):?>
          <div class="media-item" id="media-<?=$m['id']?>" onclick="selectMedia(<?=$m['id']?>, '<?=htmlspecialchars($m['filename'])?>')">
            <img src="assets/<?=htmlspecialchars($m['filename'])?>" alt="<?=htmlspecialchars($m['original_name'])?>">
            <div class="media-item-name"><?=htmlspecialchars($m['original_name'])?></div>
            <button class="del-btn" onclick="event.stopPropagation();deleteMedia(<?=$m['id']?>)">&#215;</button>
          </div>
          <?php endforeach;?>
          <?php if(empty($allMedia)):?>
          <div class="loading-state" style="grid-column:1/-1">Aucune image upload&eacute;e</div>
          <?php endif;?>
        </div>
      </div>
    </div>

    <!-- PARAMÈTRES -->
    <div id="page-parametres" class="page-section">
      <div class="grid-2">
        <div class="dash-card dash-card-body">
          <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Param&egrave;tres du site</div>
          <div class="alert alert-success" id="settings-success"></div>
          <div class="field"><label>Nom de la ligue</label><input type="text" id="s-name" value="<?=htmlspecialchars($settings['site_name']??'')?>"></div>
          <div class="field"><label>Slogan</label><input type="text" id="s-slogan" value="<?=htmlspecialchars($settings['site_slogan']??'')?>"></div>
          <div class="field"><label>Saison en cours</label><input type="text" id="s-season" value="<?=htmlspecialchars($settings['season_name']??'')?>"></div>
          <div class="field"><label>Email de contact</label><input type="email" id="s-email" value="<?=htmlspecialchars($settings['contact_email']??'')?>"></div>
          <div class="field"><label>WhatsApp de contact</label><input type="tel" id="s-whatsapp" value="<?=htmlspecialchars($settings['contact_whatsapp']??'')?>"></div>
          <button class="btn btn-primary" onclick="saveSettings()">Enregistrer</button>
        </div>
        <div class="dash-card dash-card-body">
          <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Page d&apos;accueil &mdash; H&eacute;ro</div>
          <div class="field"><label>Titre principal</label><input type="text" id="s-hero-title" value="<?=htmlspecialchars($settings['hero_title']??'')?>"></div>
          <div class="field"><label>Sous-titre</label><input type="text" id="s-hero-sub" value="<?=htmlspecialchars($settings['hero_subtitle']??'')?>"></div>
          <div class="field"><label>Image de fond (nom du fichier dans assets/)</label><input type="text" id="s-hero-img" value="<?=htmlspecialchars($settings['hero_image']??'')?>"><div style="font-size:11px;color:var(--muted);margin-top:4px;">Ex : hero-bg.jpg &mdash; uploadez d&apos;abord dans M&eacute;dias</div></div>
          <div class="field"><label>Logo du site (nom du fichier dans assets/)</label><input type="text" id="s-logo" value="<?=htmlspecialchars($settings['site_logo']??'')?>"></div>
          <div style="font-family:'Anton',sans-serif;font-size:17px;margin:14px 0;">R&eacute;seaux sociaux</div>
          <div class="field"><label>Facebook URL</label><input type="url" id="s-fb" value="<?=htmlspecialchars($settings['facebook_url']??'')?>"></div>
          <div class="field"><label>Instagram URL</label><input type="url" id="s-ig" value="<?=htmlspecialchars($settings['instagram_url']??'')?>"></div>
          <button class="btn btn-primary" onclick="saveHeroSettings()">Enregistrer</button>
        </div>
      </div>
    </div>

    <!-- ADMINISTRATION -->
    <div id="page-admin" class="page-section">
      <div class="grid-2" style="margin-bottom:18px;">
        <div class="dash-card dash-card-body">
          <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Cr&eacute;er un responsable</div>
          <div class="alert alert-success" id="manager-success"></div>
          <div class="field"><label>Nom complet</label><input type="text" id="mg-name"></div>
          <div class="field"><label>Email</label><input type="email" id="mg-email"></div>
          <div class="field"><label>Mot de passe</label><input type="password" id="mg-pwd"></div>
          <div class="field"><label>Équipe</label>
            <select id="mg-team">
              <?php foreach($allTeams as $t):?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach;?>
            </select>
          </div>
          <button class="btn btn-primary" onclick="createManager()">Cr&eacute;er</button>
        </div>
        <div class="dash-card dash-card-body">
          <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Trades de joueurs</div>
          <div class="alert alert-success" id="trade-success"></div>
          <div class="field"><label>Joueur</label><select id="tr-player"><option value="">Choisir...</option></select></div>
          <div class="field"><label>Vers l&apos;&eacute;quipe</label>
            <select id="tr-team">
              <?php foreach($allTeams as $t):?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach;?>
            </select>
          </div>
          <div class="field"><label>Date du trade</label><input type="date" id="tr-date" value="<?=date('Y-m-d')?>"></div>
          <div class="field"><label>Raison (optionnel)</label><input type="text" id="tr-reason" placeholder="Ex : manque de temps de jeu..."></div>
          <button class="btn btn-primary" onclick="doTrade()">Effectuer le trade</button>
        </div>
      </div>
      <div style="font-family:'Anton',sans-serif;font-size:18px;margin-bottom:12px;">Tous les utilisateurs</div>
      <div class="dash-card" style="overflow-x:auto;">
        <table>
          <thead><tr><th>Nom</th><th>Email</th><th>R&ocirc;le</th><th>&Eacute;quipe</th><th>Taille</th><th>Statut</th><th>Action</th></tr></thead>
          <tbody id="admin-users"><tr><td colspan="7"><div class="loading-state">Chargement...</div></td></tr></tbody>
        </table>
      </div>
    </div>

    <?php endif;?>

    <!-- PROFIL JOUEUR -->
    <?php if($user['role']==='player'):?>
    <div class="profile-header">
  <div style="position:relative;display:inline-block;">
    <?php if($user['photo'] && file_exists(__DIR__.'/assets/'.$user['photo'])):?>
      <div class="profile-av-big"><img src="assets/<?=htmlspecialchars($user['photo'])?>" alt="Photo" id="profile-img-preview"></div>
    <?php else:?>
      <div class="profile-av-big" style="background:<?=$color?>15;border:2px solid <?=$color?>44;color:<?=$color?>" id="profile-av-placeholder"><?=$initials?></div>
    <?php endif;?>
    <label for="photo-upload" style="position:absolute;bottom:0;right:0;width:28px;height:28px;border-radius:50%;background:var(--secondary);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;border:2px solid #fff;" title="Changer la photo">&#43;</label>
    <input type="file" id="photo-upload" accept="image/*" style="display:none" onchange="uploadPhoto(this)">
  </div>
  <div>
    <div class="profile-name"><?=htmlspecialchars($user['name'])?></div>
    <div class="profile-sub">
      <?=htmlspecialchars($user['team_name']??'')?>
      &bull; <?=htmlspecialchars($user['position']??'')?>
      &bull; #<?=htmlspecialchars($user['number']??'')?>
      &bull; <?=htmlspecialchars($user['height']??'')?>
    </div>
    <div id="photo-msg" style="font-size:12px;color:var(--secondary);margin-top:6px;display:none;">Photo mise &agrave; jour &mdash; rechargez la page</div>
  </div>
</div>
      <div class="grid-2">
        <div class="dash-card">
          <div class="dash-card-header">Stats saison</div>
          <div class="stat-grid-4" id="profile-stats"><div class="loading-state">Chargement...</div></div>
        </div>
        <div class="dash-card dash-card-body">
          <div style="font-family:'Anton',sans-serif;font-size:17px;margin-bottom:14px;">Niveau</div>
          <div style="display:flex;align-items:center;gap:16px;">
            <div style="font-family:'Anton',sans-serif;font-size:60px;color:var(--primary);line-height:1"><?=$user['level']?></div>
            <div style="flex:1">
              <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;margin-bottom:6px;"><div style="height:100%;width:<?=$user['level']?>%;background:var(--primary);border-radius:4px;"></div></div>
              <div style="font-size:11px;color:var(--muted)">Sur 100</div>
            </div>
          </div>
          <div style="margin-top:16px;font-size:13px;color:var(--muted);line-height:2.2;">
            <strong style="color:var(--text)">Valeur march&eacute; :</strong> <?=number_format($user['value'],0,',',' ')?> FCFA<br>
            <strong style="color:var(--text)">Taille :</strong> <?=htmlspecialchars($user['height']??'-')?><br>
            <strong style="color:var(--text)">Nationalit&eacute; :</strong> <?=htmlspecialchars($user['nationality']??'-')?>
          </div>
        </div>
      </div>
      <div style="margin-top:18px;">
        <div style="font-family:'Anton',sans-serif;font-size:18px;margin-bottom:12px;">Historique des matchs</div>
        <div class="dash-card" style="overflow-x:auto;" id="player-history"><div class="loading-state">Chargement...</div></div>
      </div>
    </div>
    <?php endif;?>

  </div>
</div>

<script src="app.js"></script>
<script>
const userRole = '<?=$user['role']?>';
const userTeam = '<?=$user['team']?>';
const userId = '<?=$user['id']?>';
let teamsCache = {};

function showAlert(id, msg, type='success') {
  const el = document.getElementById(id);
  if (!el) return;
  el.className = 'alert alert-' + type;
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 4000);
}

function showPage(name, el) {
  document.querySelectorAll('.page-section').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const section = document.getElementById('page-' + name);
  if (section) section.classList.add('active');
  if (el) el.classList.add('active');
  const titles = {
    dashboard:'Tableau de bord', players:'Joueurs', matches:'Matchs',
    stats:'Statistiques', pending:'Demandes joueurs', saisie:'Saisir les statistiques',
    teams:'Candidatures &eacute;quipes', cms:'Actualit&eacute;s', media:'M&eacute;diath&egrave;que',
    parametres:'Param&egrave;tres', admin:'Administration', profile:'Mon profil'
  };
  document.getElementById('topbar-title').innerHTML = titles[name] || name;
  if (name==='players') loadPlayers();
  if (name==='matches') loadMatches();
  if (name==='stats') loadLeaders('pts','PPG',document.querySelector('#stat-tabs button'));
  if (name==='pending') loadPending();
  if (name==='saisie') loadSaisie();
  if (name==='teams') loadTeamRequests('pending', null);
  if (name==='cms') loadArticles();
  if (name==='admin') loadAdmin();
  if (name==='profile') loadProfile();
}

async function getTeams() {
  if (Object.keys(teamsCache).length > 0) return teamsCache;
  try {
    const res = await fetch('team_api.php?action=list_teams');
    const json = await res.json();
    if (json.success) json.data.forEach(t => teamsCache[t.id] = t);
  } catch(e) {}
  return teamsCache;
}

function tColor(id) { return teamsCache[id]?.color || '#1D428A'; }
function tName(id) { return teamsCache[id]?.name || id; }
function tShort(id) { const n = teamsCache[id]?.name || id; return n.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase(); }

function getTier(l) {
  if (l>=85) return {label:'ÉLITE',color:'#ff6b35',bg:'rgba(255,107,53,0.1)'};
  if (l>=75) return {label:'GOLD',color:'#c9a84c',bg:'rgba(201,168,76,0.1)'};
  if (l>=60) return {label:'SILVER',color:'#6b7280',bg:'rgba(107,114,128,0.1)'};
  return {label:'BRONZE',color:'#cd7f32',bg:'rgba(205,127,50,0.1)'};
}

function formatFCFA(v) {
  if (!v) return '0 FCFA';
  if (v>=1000000) return (v/1000000).toFixed(1)+'M FCFA';
  return (v/1000).toFixed(0)+'K FCFA';
}

function fDate(d) { return d ? new Date(d).toLocaleDateString('fr-FR',{day:'numeric',month:'short',year:'numeric'}) : ''; }
function fDateShort(d) { return d ? new Date(d).toLocaleDateString('fr-FR',{day:'numeric',month:'short'}) : ''; }

function av(name, photo, color, size=34) {
  const init = name ? name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase() : '??';
  if (photo) return `<img src="assets/${photo}" style="width:${size}px;height:${size}px;border-radius:50%;object-fit:cover;display:block">`;
  return `<div style="width:${size}px;height:${size}px;border-radius:50%;background:${color}15;border:1.5px solid ${color}44;color:${color};display:flex;align-items:center;justify-content:center;font-family:'Anton',sans-serif;font-size:${Math.round(size*0.35)}px;flex-shrink:0">${init}</div>`;
}

// DASHBOARD
async function loadDashboard() {
  await getTeams();
  const [s, m, p] = await Promise.all([
    fetch('api.php?action=standings').then(r=>r.json()).catch(()=>({data:[]})),
    fetch('api.php?action=matches').then(r=>r.json()).catch(()=>({data:[]})),
    fetch('api.php?action=players').then(r=>r.json()).catch(()=>({data:[]})),
  ]);

  let pendingCount = 0;
  let teamsCount = 0;
  try {
    const reg = await fetch('api.php?action=registrations').then(r=>r.json());
    pendingCount = reg.data ? reg.data.length : 0;
    const tr = await fetch('team_api.php?action=list_requests&status=pending').then(r=>r.json());
    teamsCount = tr.data ? tr.data.length : 0;
  } catch(e) {}

  if (pendingCount > 0) { const b = document.getElementById('badge-pending'); if(b){b.textContent=pendingCount;b.style.display='inline';} }
  if (teamsCount > 0) { const b = document.getElementById('badge-teams'); if(b){b.textContent=teamsCount;b.style.display='inline';} }

  document.getElementById('kpi-grid').innerHTML = [
    {label:'Matchs jou&eacute;s', val:(s.data||[]).reduce((a,t)=>Math.max(a,parseInt(t.played)||0),0), color:'#1D428A', bg:'rgba(29,66,138,0.1)'},
    {label:'Joueurs actifs', val:(p.data||[]).length, color:'#006837', bg:'rgba(0,104,55,0.1)'},
    {label:'Demandes joueurs', val:pendingCount, color:'#C8102E', bg:'rgba(200,16,46,0.1)'},
    {label:'&Eacute;quipes candidates', val:teamsCount, color:'#c9a84c', bg:'rgba(201,168,76,0.1)'},
  ].map(k=>`<div class="kpi-card"><div class="kpi-icon" style="background:${k.bg};font-size:20px">&#9632;</div><div><div class="kpi-val" style="color:${k.color}">${k.val}</div><div class="kpi-label">${k.label}</div></div></div>`).join('');

  if (s.data && s.data.length) {
    document.getElementById('dash-standings').innerHTML = s.data.map((t,i) => {
      const c = t.color || tColor(t.id);
      const pct = t.played > 0 ? Math.round((t.wins/t.played)*100) : 0;
      return `<div class="row-item"><div style="font-family:'Anton',sans-serif;font-size:18px;color:var(--muted);width:20px">${i+1}</div>${av(t.name,t.logo,c,36)}<div style="flex:1;margin-left:8px"><div style="font-weight:600;font-size:13px">${t.name}</div><div style="font-size:11px;color:var(--muted)">${t.wins}V &mdash; ${t.losses}D</div></div><div style="font-weight:700;color:${c}">${pct}%</div></div>`;
    }).join('');
  } else {
    document.getElementById('dash-standings').innerHTML = '<div class="loading-state">Aucune &eacute;quipe</div>';
  }

  if (m.data && m.data.length) {
    const upcoming = m.data.filter(x=>x.status==='upcoming').slice(0,3);
    document.getElementById('dash-matches').innerHTML = upcoming.length ? upcoming.map(mt=>{
      const hc = mt.home_color || tColor(mt.home_team);
      const ac = mt.away_color || tColor(mt.away_team);
      const hn = mt.home_name || tName(mt.home_team);
      const an = mt.away_name || tName(mt.away_team);
      return `<div class="row-item"><div style="font-size:11px;color:var(--muted);min-width:70px">${fDateShort(mt.match_date)}</div><div style="flex:1;display:flex;align-items:center;gap:8px;justify-content:center"><span style="font-weight:700;color:${hc};font-size:13px">${hn}</span><span style="color:var(--muted);font-size:11px">vs</span><span style="font-weight:700;color:${ac};font-size:13px">${an}</span></div><span class="status-pill pill-upcoming">A venir</span></div>`;
    }).join('') : '<div class="loading-state">Aucun match &agrave; venir</div>';
  } else {
    document.getElementById('dash-matches').innerHTML = '<div class="loading-state">Aucun match programm&eacute;</div>';
  }
}

// JOUEURS
async function loadPlayers() {
  await getTeams();
  const res = await fetch('api.php?action=players').then(r=>r.json()).catch(()=>({data:[]}));
  const el = document.getElementById('players-list');
  if (!res.data || !res.data.length) { el.innerHTML='<div class="loading-state">Aucun joueur actif</div>'; return; }
  el.innerHTML = `<table><thead><tr><th>Joueur</th><th>Équipe</th><th>Poste</th><th>Taille</th><th>Niveau</th><th>Valeur</th></tr></thead><tbody>${res.data.map(p=>{
    const c = p.team_color || tColor(p.team);
    const tn = p.team_name || tName(p.team);
    const tier = getTier(p.level);
    return `<tr><td><div style="display:flex;align-items:center;gap:8px">${av(p.name,p.photo,c,34)}<span style="font-weight:600">${p.name}</span></div></td><td><span style="color:${c};font-weight:600;font-size:12px">${tn}</span></td><td style="color:var(--muted)">${p.position}</td><td style="color:var(--muted)">${p.height||'-'}</td><td><span class="tier-badge" style="background:${tier.bg};color:${tier.color}">${tier.label} ${p.level}</span></td><td style="font-weight:600;font-size:12px">${formatFCFA(p.value)}</td></tr>`;
  }).join('')}</tbody></table>`;
}

// MATCHS
async function loadMatches() {
  await getTeams();
  const res = await fetch('api.php?action=matches').then(r=>r.json()).catch(()=>({data:[]}));
  const el = document.getElementById('matches-list');

  if (userRole === 'admin') {
    const sel = document.getElementById('sc-match');
    if (sel && res.data) {
      const notFinished = res.data.filter(m=>m.status!=='finished');
      sel.innerHTML = '<option value="">Choisir...</option>' + notFinished.map(m=>`<option value="${m.id}" data-home="${m.home_team}" data-away="${m.away_team}" data-hn="${m.home_name||tName(m.home_team)}" data-an="${m.away_name||tName(m.away_team)}">${fDateShort(m.match_date)} &mdash; ${m.home_name||tName(m.home_team)} vs ${m.away_name||tName(m.away_team)}</option>`).join('');
    }
  }

  if (!res.data || !res.data.length) { el.innerHTML='<div class="loading-state">Aucun match</div>'; return; }
  el.innerHTML = res.data.map(m => {
    const hc = m.home_color || tColor(m.home_team);
    const ac = m.away_color || tColor(m.away_team);
    const hn = m.home_name || tName(m.home_team);
    const an = m.away_name || tName(m.away_team);
    let score = m.status==='finished' ? `<span style="font-family:'Anton',sans-serif;font-size:20px">${m.score_home} &mdash; ${m.score_away}</span>` : `<span style="color:var(--muted);font-size:13px">vs</span>`;
    let pill = m.status==='finished'?'<span class="status-pill pill-final">Final</span>':m.status==='live'?'<span class="status-pill pill-live">Live</span>':'<span class="status-pill pill-upcoming">A venir</span>';
    let actions = userRole==='admin' ? `<div style="display:flex;gap:4px;flex-shrink:0"><button class="btn btn-outline btn-xs" onclick="editMatch(${m.id},'${m.match_date}','${m.match_time||''}','${(m.venue||'').replace(/'/g,"\\'")}','${m.home_team}','${m.away_team}','${m.status}')">Modifier</button><button class="btn btn-red btn-xs" onclick="deleteMatch(${m.id})">Supprimer</button></div>` : '';
    return `<div class="row-item" style="display:grid;grid-template-columns:90px 1fr auto auto auto;gap:10px;align-items:center;flex-wrap:wrap"><div style="font-size:11px;color:var(--muted)">${fDateShort(m.match_date)}<br>${m.match_time?m.match_time.slice(0,5):''}</div><div style="display:flex;align-items:center;gap:10px;justify-content:center"><span style="font-weight:700;color:${hc};font-size:13px">${hn}</span>${score}<span style="font-weight:700;color:${ac};font-size:13px">${an}</span></div><div style="font-size:11px;color:var(--muted)">${m.venue||''}</div>${pill}${actions}</div>`;
  }).join('');
}

function fillScore(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (!opt.value) return;
  document.getElementById('sc-home-label').textContent = opt.dataset.hn || 'Domicile';
  document.getElementById('sc-away-label').textContent = opt.dataset.an || 'Visiteur';
}

// STATS
async function loadLeaders(stat, label, btn) {
  document.querySelectorAll('#stat-tabs button').forEach(b=>{b.className='btn btn-outline btn-sm';});
  if (btn) btn.className='btn btn-primary btn-sm';
  document.getElementById('stat-col').textContent = label;
  await getTeams();
  const res = await fetch('api.php?action=leaders&stat='+stat).then(r=>r.json()).catch(()=>({data:[]}));
  const body = document.getElementById('stats-body');
  if (!res.data || !res.data.length) { body.innerHTML='<tr><td colspan="8"><div class="loading-state">Aucune stat disponible</div></td></tr>'; return; }
  body.innerHTML = res.data.map((p,i)=>{
    const c = p.team_color || tColor(p.team);
    const tn = p.team_name || tName(p.team);
    const tier = getTier(p.level);
    return `<tr><td style="font-family:'Anton',sans-serif;font-size:18px;color:var(--muted)">${i+1}</td><td><div style="display:flex;align-items:center;gap:8px">${av(p.name,p.photo,c,32)}<span style="font-weight:600">${p.name}</span></div></td><td><span style="color:${c};font-weight:600;font-size:12px">${tn}</span></td><td style="color:var(--muted)">${p.height||'-'}</td><td style="text-align:center">${p.gp}</td><td style="text-align:center;font-family:'Anton',sans-serif;font-size:22px;color:var(--primary)">${p.stat_value}</td><td><span class="tier-badge" style="background:${tier.bg};color:${tier.color}">${tier.label} ${p.level}</span></td><td style="font-size:12px;font-weight:600">${formatFCFA(p.value)}</td></tr>`;
  }).join('');
}

// DEMANDES JOUEURS
async function loadPending() {
  await getTeams();
  const res = await fetch('api.php?action=registrations').then(r=>r.json()).catch(()=>({data:[]}));
  const el = document.getElementById('pending-list');
  if (!res.data || !res.data.length) { el.innerHTML='<div class="loading-state">Aucune demande en attente</div>'; return; }
  el.innerHTML = res.data.map(r=>{
    const c = tColor(r.team);
    const tn = tName(r.team);
    return `<div class="pending-card" id="reg-${r.id}"><div class="pending-av" style="background:${c}15;border:1.5px solid ${c}44;color:${c}">${r.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()}</div><div class="pending-info"><div class="pending-name">${r.name}</div><div class="pending-detail"><strong>Email :</strong> ${r.email}<br><strong>&Eacute;quipe :</strong> ${tn}<br><strong>Poste :</strong> ${r.position}<br><strong>Taille :</strong> ${r.height||'-'}<br><strong>T&eacute;l :</strong> ${r.phone||'-'}<br><strong>N&eacute; le :</strong> ${r.dob||'-'}<br><strong>Soumis le :</strong> ${fDate(r.submitted_at)}</div></div><div class="pending-actions"><button class="btn btn-green btn-sm" onclick="handleReg(${r.id},'accepted','${r.email}')">Accepter</button><button class="btn btn-red btn-sm" onclick="handleReg(${r.id},'rejected','${r.email}')">Refuser</button></div></div>`;
  }).join('');
}

async function handleReg(id, decision, email) {
  const form = new FormData();
  form.append('action','handle_registration');
  form.append('reg_id',id);
  form.append('decision',decision);
  form.append('email',email);
  const res = await fetch('api.php',{method:'POST',body:form}).then(r=>r.json());
  if (res.success) { document.getElementById('reg-'+id)?.remove(); showAlert('global-success', decision==='accepted'?'Joueur accept&eacute;.':'Joueur refus&eacute;.'); }
}

// SAISIE STATS
async function loadSaisie() {
  const res = await fetch('api.php?action=players'+(userTeam?'&team='+userTeam:'')).then(r=>r.json()).catch(()=>({data:[]}));
  const sel = document.getElementById('ss-player');
  if (sel && res.data) {
    sel.innerHTML = '<option value="">Choisir un joueur...</option>' + res.data.map(p=>`<option value="${p.id}">${p.name} (${p.team_name||tName(p.team)})</option>`).join('');
  }
}

async function saisirStats() {
  const matchId = document.getElementById('ss-match').value;
  const playerId = document.getElementById('ss-player').value;
  if (!matchId||!playerId) { showAlert('saisie-error','Choisis un match et un joueur.','error'); return; }
  const form = new FormData();
  form.append('action','submit_stats');
  form.append('match_id',matchId);
  form.append('player_id',playerId);
  ['pts','reb','ast','stl','blk','fgm','fga'].forEach(k => form.append(k==='fgm'?'fg_made':k==='fga'?'fg_attempted':k, document.getElementById('ss-'+k).value||0));
  const res = await fetch('stats_api.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert(res.success?'saisie-success':'saisie-error', res.message, res.success?'success':'error');
}

// CANDIDATURES ÉQUIPES
async function loadTeamRequests(status, btn) {
  if (btn) { document.querySelectorAll('#page-teams .btn').forEach(b=>{b.className='btn btn-outline btn-sm';}); btn.className='btn btn-primary btn-sm'; }
  const res = await fetch(`team_api.php?action=list_requests&status=${status}`).then(r=>r.json()).catch(()=>({data:[]}));
  const el = document.getElementById('team-requests-list');
  if (!res.data || !res.data.length) { el.innerHTML=`<div class="loading-state">Aucune candidature ${status==='pending'?'en attente':status==='accepted'?'accept&eacute;e':'refus&eacute;e'}</div>`; return; }
  el.innerHTML = res.data.map(r=>`<div class="team-req-card" id="treq-${r.id}"><div class="team-req-name"><span class="team-color-dot" style="background:${r.color}"></span>${r.team_name} &mdash; ${r.city}</div><div class="team-req-detail"><strong>Responsable :</strong> ${r.manager_name}<br><strong>Email :</strong> ${r.manager_email}<br><strong>T&eacute;l&eacute;phone :</strong> ${r.manager_phone||'-'}<br><strong>WhatsApp :</strong> ${r.whatsapp||'-'}<br><strong>Description :</strong> ${r.description||'Non renseign&eacute;e'}<br><strong>Soumis le :</strong> ${fDate(r.submitted_at)}</div>${status==='pending'?`<div class="team-req-actions"><input type="password" id="pwd-${r.id}" placeholder="D&eacute;finir un mot de passe pour le responsable" style="padding:7px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:12px;font-family:'Inter';flex:1;outline:none"><button class="btn btn-green btn-sm" onclick="acceptTeam(${r.id})">Accepter</button><button class="btn btn-red btn-sm" onclick="rejectTeam(${r.id})">Refuser</button></div>`:''}</div>`).join('');
}

async function acceptTeam(id) {
  const pwd = document.getElementById('pwd-'+id)?.value;
  if (!pwd || pwd.length < 6) { alert('Le mot de passe doit faire au moins 6 caract&egrave;res.'); return; }
  const form = new FormData();
  form.append('action','accept_team');
  form.append('req_id',id);
  form.append('password',pwd);
  const res = await fetch('team_api.php',{method:'POST',body:form}).then(r=>r.json());
  if (res.success) { showAlert('global-success','Équipe accept&eacute;e. Identifiants : '+res.manager_email+' / mot de passe d&eacute;fini.'); loadTeamRequests('pending',null); teamsCache={}; }
  else showAlert('global-error', res.message||'Erreur', 'error');
}

async function rejectTeam(id) {
  if (!confirm('Refuser cette candidature ?')) return;
  const form = new FormData();
  form.append('action','reject_team');
  form.append('req_id',id);
  await fetch('team_api.php',{method:'POST',body:form});
  showAlert('global-success','Candidature refus&eacute;e.');
  loadTeamRequests('pending',null);
}

// CMS ARTICLES
async function loadArticles() {
  const res = await fetch('upload.php?action=list_articles_admin').then(r=>r.json()).catch(()=>({data:[]}));
  const el = document.getElementById('articles-list');
  if (!res.data || !res.data.length) { el.innerHTML='<div class="loading-state">Aucun article</div>'; return; }
  const cats = {actualite:'Actualit&eacute;',resultat:'R&eacute;sultat',trade:'Trade',annonce:'Annonce',interview:'Interview'};
  el.innerHTML = res.data.map(a=>`<div class="row-item" style="flex-wrap:wrap;gap:8px"><div style="flex:1;min-width:0"><div style="font-weight:600;font-size:13px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap">${a.title}</div><div style="font-size:11px;color:var(--muted);margin-top:2px">${cats[a.category]||a.category} &bull; ${a.status==='published'?'<span style="color:#006837">Publi&eacute;</span>':'<span style="color:var(--muted)">Brouillon</span>'} &bull; ${fDateShort(a.created_at)}</div></div><div style="display:flex;gap:4px;flex-shrink:0"><button class="btn btn-outline btn-xs" onclick="editArticle(${a.id},'${a.title.replace(/'/g,"\\'")}','${a.status}','${a.category}','${a.article_type||'simple'}')">Modifier</button><button class="btn btn-red btn-xs" onclick="deleteArticle(${a.id})">Supprimer</button></div></div>`).join('');
}

function resetArticleForm() {
  document.getElementById('article-id').value='';
  document.getElementById('a-title').value='';
  document.getElementById('a-content-simple').value='';
  document.getElementById('a-content-rich').innerHTML='';
  document.getElementById('a-status').value='published';
  document.getElementById('a-cat').value='actualite';
  document.getElementById('a-type').value='simple';
  toggleEditor('simple');
  document.getElementById('article-form-title').textContent='Nouvel article';
}

function editArticle(id, title, status, cat, type) {
  document.getElementById('article-id').value=id;
  document.getElementById('a-title').value=title;
  document.getElementById('a-status').value=status;
  document.getElementById('a-cat').value=cat;
  document.getElementById('a-type').value=type;
  toggleEditor(type);
  document.getElementById('article-form-title').textContent='Modifier l\'article';
}

function toggleEditor(type) {
  document.getElementById('simple-editor').style.display = type==='simple'?'block':'none';
  document.getElementById('rich-editor').style.display = type==='rich'?'block':'none';
}

function fmt(cmd, val) { document.execCommand(cmd, false, val); }

async function saveArticle() {
  const type = document.getElementById('a-type').value;
  const content = type==='rich' ? document.getElementById('a-content-rich').innerHTML : document.getElementById('a-content-simple').value;
  const form = new FormData();
  form.append('action','save_article');
  const id = document.getElementById('article-id').value;
  if (id) form.append('id', id);
  form.append('title', document.getElementById('a-title').value);
  form.append('content', content);
  form.append('status', document.getElementById('a-status').value);
  form.append('category', document.getElementById('a-cat').value);
  form.append('article_type', type);
  form.append('image_id', document.getElementById('a-image').value);
  const res = await fetch('upload.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert('cms-success', res.message||'Article sauvegard&eacute;.');
  if (res.success) { resetArticleForm(); loadArticles(); }
}

async function deleteArticle(id) {
  if (!confirm('Supprimer cet article ?')) return;
  const form = new FormData();
  form.append('action','delete_article');
  form.append('id',id);
  await fetch('upload.php',{method:'POST',body:form});
  showAlert('global-success','Article supprim&eacute;.');
  loadArticles();
}

// MÉDIAS
async function uploadMedia() {
  const file = document.getElementById('media-file').files[0];
  if (!file) { showAlert('media-success','Aucun fichier s&eacute;lectionn&eacute;.','error'); return; }
  const form = new FormData();
  form.append('action','upload_image');
  form.append('image',file);
  form.append('type',document.getElementById('media-type').value);
  const res = await fetch('upload.php',{method:'POST',body:form}).then(r=>r.json());
  if (res.success) {
    showAlert('media-success','Image upload&eacute;e : '+res.filename);
    const grid = document.getElementById('media-grid');
    const div = document.createElement('div');
    div.className='media-item';
    div.id='media-'+res.id;
    div.innerHTML=`<img src="${res.url}" alt=""><div class="media-item-name">${file.name}</div><button class="del-btn" onclick="event.stopPropagation();deleteMedia(${res.id})">&#215;</button>`;
    grid.prepend(div);
  } else {
    showAlert('media-success', res.message, 'error');
  }
}

async function deleteMedia(id) {
  if (!confirm('Supprimer cette image ?')) return;
  const form = new FormData();
  form.append('action','delete_media');
  form.append('id',id);
  await fetch('upload.php',{method:'POST',body:form});
  document.getElementById('media-'+id)?.remove();
}

function selectMedia(id, filename) {
  document.querySelectorAll('.media-item').forEach(m=>m.classList.remove('selected'));
  document.getElementById('media-'+id)?.classList.add('selected');
}

// PARAMÈTRES
async function saveSettings() {
  const settings = {
    site_name: document.getElementById('s-name').value,
    site_slogan: document.getElementById('s-slogan').value,
    season_name: document.getElementById('s-season').value,
    contact_email: document.getElementById('s-email').value,
    contact_whatsapp: document.getElementById('s-whatsapp').value,
  };
  const form = new FormData();
  form.append('action','save_settings_bulk');
  form.append('settings',JSON.stringify(settings));
  const res = await fetch('upload.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert('settings-success', res.message||'Param&egrave;tres sauvegard&eacute;s.');
}

async function saveHeroSettings() {
  const settings = {
    hero_title: document.getElementById('s-hero-title').value,
    hero_subtitle: document.getElementById('s-hero-sub').value,
    hero_image: document.getElementById('s-hero-img').value,
    site_logo: document.getElementById('s-logo').value,
    facebook_url: document.getElementById('s-fb').value,
    instagram_url: document.getElementById('s-ig').value,
  };
  const form = new FormData();
  form.append('action','save_settings_bulk');
  form.append('settings',JSON.stringify(settings));
  const res = await fetch('upload.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert('settings-success', res.message||'Param&egrave;tres sauvegard&eacute;s.');
}

// ADMIN
async function loadAdmin() {
  await getTeams();
  const [users, players] = await Promise.all([
    fetch('admin.php?action=all_users').then(r=>r.json()).catch(()=>({data:[]})),
    fetch('api.php?action=players').then(r=>r.json()).catch(()=>({data:[]})),
  ]);
  const trSel = document.getElementById('tr-player');
  if (trSel && players.data) {
    trSel.innerHTML = '<option value="">Choisir un joueur...</option>' + players.data.map(p=>`<option value="${p.id}">${p.name} (${p.team_name||tName(p.team)})</option>`).join('');
  }
  if (users.data) {
    const statuses = {active:'pill-active',pending:'pill-pending',rejected:'pill-rejected'};
    document.getElementById('admin-users').innerHTML = users.data.map(u=>`<tr><td style="font-weight:600">${u.name}</td><td style="color:var(--muted)">${u.email}</td><td><span class="tier-badge" style="background:rgba(29,66,138,0.1);color:var(--secondary)">${u.role}</span></td><td>${u.team_name||'-'}</td><td style="color:var(--muted)">${u.height||'-'}</td><td><span class="status-pill ${statuses[u.status]||''}">${u.status}</span></td><td>${u.role!=='admin'?`<button class="btn btn-red btn-xs" onclick="deleteUser('${u.id}')">Supprimer</button>`:'-'}</td></tr>`).join('');
  }
}

// MATCH ACTIONS
async function createMatch() {
  const form = new FormData();
  form.append('action','create_match');
  form.append('home_team',document.getElementById('m-home').value);
  form.append('away_team',document.getElementById('m-away').value);
  form.append('match_date',document.getElementById('m-date').value);
  form.append('match_time',document.getElementById('m-time').value);
  form.append('venue',document.getElementById('m-venue').value);
  const res = await fetch('admin.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert('match-success', res.message||'Match cr&eacute;&eacute;.');
  if (res.success) loadMatches();
}

async function saveScore() {
  const matchId = document.getElementById('sc-match').value;
  if (!matchId) return;
  const form = new FormData();
  form.append('action','save_score');
  form.append('match_id',matchId);
  form.append('score_home',document.getElementById('sc-home').value);
  form.append('score_away',document.getElementById('sc-away').value);
  const res = await fetch('admin.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert('score-success', res.message||'Score enregistr&eacute;.');
  if (res.success) loadMatches();
}

async function deleteMatch(id) {
  if (!confirm('Supprimer d&eacute;finitivement ce match ?')) return;
  const form = new FormData();
  form.append('action','delete_match');
  form.append('match_id',id);
  const res = await fetch('admin.php',{method:'POST',body:form}).then(r=>r.json());
  if (res.success) { showAlert('global-success','Match supprim&eacute;.'); loadMatches(); }
  else showAlert('global-error', res.message||'Erreur.', 'error');
}

async function editMatch(id, date, time, venue, home, away, status) {
  const newDate = prompt('Nouvelle date (AAAA-MM-JJ) :', date);
  if (!newDate) return;
  const newTime = prompt('Heure (HH:MM) :', time ? time.slice(0,5) : '18:00');
  const newVenue = prompt('Lieu :', venue);
  const newStatus = prompt('Statut (upcoming / live / finished) :', status);
  const form = new FormData();
  form.append('action','update_match');
  form.append('match_id',id);
  form.append('match_date',newDate);
  form.append('match_time',newTime||'18:00');
  form.append('venue',newVenue||'');
  form.append('status',newStatus||status);
  form.append('home_team',home);
  form.append('away_team',away);
  const res = await fetch('admin.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert('global-success', res.message||'Match mis &agrave; jour.');
  loadMatches();
}

async function createManager() {
  const form = new FormData();
  form.append('action','create_manager');
  form.append('name',document.getElementById('mg-name').value);
  form.append('email',document.getElementById('mg-email').value);
  form.append('password',document.getElementById('mg-pwd').value);
  form.append('team',document.getElementById('mg-team').value);
  const res = await fetch('admin.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert('manager-success', res.message||'Responsable cr&eacute;&eacute;.');
  if (res.success) loadAdmin();
}

async function deleteUser(id) {
  if (!confirm('Supprimer cet utilisateur ?')) return;
  const form = new FormData();
  form.append('action','delete_user');
  form.append('user_id',id);
  await fetch('admin.php',{method:'POST',body:form});
  loadAdmin();
}

async function doTrade() {
  const form = new FormData();
  form.append('action','trade_player');
  form.append('player_id',document.getElementById('tr-player').value);
  form.append('to_team',document.getElementById('tr-team').value);
  form.append('trade_date',document.getElementById('tr-date').value);
  form.append('reason',document.getElementById('tr-reason').value);
  const res = await fetch('team_api.php',{method:'POST',body:form}).then(r=>r.json());
  showAlert('trade-success', res.message||'Trade effectu&eacute;.');
  if (res.success) { loadAdmin(); teamsCache={}; }
}

// PROFIL JOUEUR
async function loadProfile() {
  const [stats, hist] = await Promise.all([
    fetch('api.php?action=player_stats&player_id='+userId).then(r=>r.json()).catch(()=>({data:{}})),
    fetch('stats_api.php?action=player_history&player_id='+userId).then(r=>r.json()).catch(()=>({data:[]})),
  ]);
  await getTeams();
  if (stats.data) {
    document.getElementById('profile-stats').innerHTML = [
      ['PPG',stats.data.pts||0,'#C8102E'],
      ['RPG',stats.data.reb||0,'#1D428A'],
      ['APG',stats.data.ast||0,'#c9a84c'],
      ['Matchs',stats.data.gp||0,'#6b7280'],
    ].map(([l,v,c])=>`<div class="stat-cell"><div class="stat-big" style="color:${c}">${v}</div><div class="stat-lbl">${l}</div></div>`).join('');
  }
  const histEl = document.getElementById('player-history');
  if (hist.data && hist.data.length) {
    histEl.innerHTML = `<table><thead><tr><th>Match</th><th>Date</th><th>PTS</th><th>REB</th><th>AST</th><th>STL</th><th>BLK</th></tr></thead><tbody>${hist.data.map(s=>`<tr><td>${tName(s.home_team)} vs ${tName(s.away_team)}</td><td>${fDateShort(s.match_date)}</td><td style="font-weight:700">${s.pts}</td><td>${s.reb}</td><td>${s.ast}</td><td>${s.stl}</td><td>${s.blk}</td></tr>`).join('')}</tbody></table>`;
  } else {
    histEl.innerHTML = '<div class="loading-state">Aucun historique disponible</div>';
  }
}

// INIT
async function uploadPhoto(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2*1024*1024) { alert('Fichier trop lourd. Maximum 2 Mo.'); return; }
  const form = new FormData();
  form.append('action', 'upload_player_photo');
  form.append('image', file);
  const res = await fetch('upload.php', {method:'POST', body:form}).then(r=>r.json());
  if (res.success) {
    document.getElementById('photo-msg').style.display = 'block';
    const preview = document.getElementById('profile-img-preview');
    const placeholder = document.getElementById('profile-av-placeholder');
    if (preview) preview.src = res.url + '?t=' + Date.now();
    if (placeholder) placeholder.innerHTML = `<img src="${res.url}" style="width:80px;height:80px;border-radius:50%;object-fit:cover">`;
  } else {
    alert(res.message || 'Erreur upload');
  }
}
loadDashboard();
</script>
</body>
</html>