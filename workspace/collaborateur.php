<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$me  = currentUser();
$db  = getDB();
$uid = (int)($_GET['id'] ?? $me['id']);

// Récupérer l'utilisateur
$stmt = $db->prepare("SELECT id,prenom,nom,email,role,avatar,telephone,poste,bio,created_at,last_login,actif FROM users WHERE id=? AND actif=1");
$stmt->execute([$uid]);
$u = $stmt->fetch();
if (!$u) { header('Location: /workspace/dashboard.php'); exit; }

// Stats
$nbTaches    = $db->prepare("SELECT COUNT(*) FROM taches WHERE assigne_a=? AND statut='Terminé'"); $nbTaches->execute([$uid]); $nbTaches=$nbTaches->fetchColumn();
$nbProjets   = $db->prepare("SELECT COUNT(DISTINCT projet_id) FROM taches WHERE assigne_a=? AND projet_id IS NOT NULL"); $nbProjets->execute([$uid]); $nbProjets=$nbProjets->fetchColumn();
$tempsTotal  = $db->prepare("SELECT COALESCE(SUM(duree),0) FROM time_entries WHERE user_id=?");
try { $tempsTotal->execute([$uid]); $tempsTotal=(int)$tempsTotal->fetchColumn(); } catch(Exception $e){ $tempsTotal=0; }

// Projets actifs
$projetsStmt = $db->prepare("SELECT DISTINCT p.nom, p.statut, p.type_prestation FROM taches t LEFT JOIN projets p ON p.id=t.projet_id WHERE t.assigne_a=? AND p.statut NOT IN ('Clôturé','Livré') AND p.id IS NOT NULL LIMIT 5");
$projetsStmt->execute([$uid]);
$projets = $projetsStmt->fetchAll();

// Tâches récentes
$tachesStmt = $db->prepare("SELECT t.titre, t.statut, t.priorite, p.nom as projet_nom FROM taches t LEFT JOIN projets p ON p.id=t.projet_id WHERE t.assigne_a=? ORDER BY t.updated_at DESC LIMIT 5");
$tachesStmt->execute([$uid]);
$taches = $tachesStmt->fetchAll();

// Taux complétion
$totalTaches = $db->prepare("SELECT COUNT(*) FROM taches WHERE assigne_a=?"); $totalTaches->execute([$uid]); $totalTaches=(int)$totalTaches->fetchColumn();
$tauxCompletion = $totalTaches > 0 ? round(($nbTaches/$totalTaches)*100) : 0;

function fmtDur(int $s): string {
    $h=floor($s/3600); $m=floor(($s%3600)/60);
    return $h>0?$h.'h '.$m.'m':$m.'m';
}

$initiales = strtoupper(substr($u['prenom'],0,1).substr($u['nom'],0,1));
$roleColors = ['admin'=>'#e05252','manager'=>'#36A9E1','collaborateur'=>'#2ecc87'];
$roleColor  = $roleColors[$u['role']] ?? '#7a78a0';
$isSelf = $me['id'] === $u['id'];
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?> — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--warning:#f0a500;--danger:#e05252;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 50% at 20% 0%,rgba(41,35,92,0.6) 0%,transparent 60%);pointer-events:none;}

/* TOPBAR */
.topbar{position:sticky;top:0;z-index:100;background:rgba(19,18,42,0.95);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);height:56px;display:flex;align-items:center;padding:0 24px;gap:12px;}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;padding:6px 12px;border-radius:8px;transition:all .2s;}
.back-btn:hover{color:var(--accent);background:rgba(54,169,225,.08);}
.back-btn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.topbar-title{flex:1;font-size:14px;font-weight:700;color:#fff;}
.edit-btn{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:7px 16px;color:var(--muted);font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;}
.edit-btn:hover{border-color:var(--accent);color:var(--accent);}

/* PAGE */
.page{max-width:800px;margin:0 auto;padding:32px 20px 60px;position:relative;z-index:1;}

/* HERO CARD */
.hero-card{background:linear-gradient(135deg,rgba(41,35,92,.7),rgba(26,25,48,.9));border:1px solid var(--border);border-radius:20px;padding:32px;margin-bottom:20px;position:relative;overflow:hidden;}
.hero-card::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(54,169,225,.12) 0%,transparent 70%);border-radius:50%;}
.hero-inner{display:flex;align-items:flex-start;gap:24px;position:relative;z-index:1;}
.avatar-wrap{flex-shrink:0;}
.avatar{width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;color:#fff;border:3px solid rgba(54,169,225,.3);overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.online-dot{width:14px;height:14px;border-radius:50%;background:var(--success);border:3px solid var(--bg);position:absolute;bottom:4px;right:4px;}
.hero-info{flex:1;min-width:0;}
.hero-name{font-size:24px;font-weight:800;color:#fff;letter-spacing:-0.5px;margin-bottom:4px;}
.hero-poste{font-size:14px;color:var(--accent);font-weight:600;margin-bottom:8px;}
.hero-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.rbadge{display:inline-block;padding:4px 14px;border-radius:99px;font-size:11px;font-weight:700;}
.mbadge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:99px;font-size:11px;background:rgba(255,255,255,.06);color:var(--muted);}
.mbadge svg{width:12px;height:12px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.hero-bio{font-size:13px;color:rgba(255,255,255,.65);line-height:1.7;margin-bottom:14px;max-width:500px;}
.hero-contact{display:flex;gap:16px;flex-wrap:wrap;}
.contact-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);}
.contact-item svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}

/* KPI ROW */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center;}
.kpi-val{font-size:26px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;letter-spacing:-1px;}
.kpi-label{font-size:10px;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:1px;}
.kpi-sub{font-size:11px;color:var(--accent);margin-top:4px;font-weight:600;}

/* COMPLETION BAR */
.completion-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:20px;}
.completion-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
.completion-title{font-size:13px;font-weight:700;color:#fff;}
.completion-pct{font-size:20px;font-weight:800;color:var(--success);font-family:'Space Mono',monospace;}
.completion-bar{height:8px;background:var(--bg3);border-radius:99px;overflow:hidden;}
.completion-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--primary),var(--success));transition:width 1.2s ease;}
.completion-sub{font-size:11px;color:var(--muted);margin-top:6px;}

/* GRID 2 */
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px;}
.card-title{font-size:13px;font-weight:700;color:#fff;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.card-title svg{width:15px;height:15px;fill:none;stroke:var(--accent);stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}

/* PROJET ITEM */
.projet-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.projet-item:last-child{border:none;}
.projet-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.projet-info{flex:1;min-width:0;}
.projet-name{font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.projet-type{font-size:10px;color:var(--muted);}
.statut-badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;}

/* TACHE ITEM */
.tache-item{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.tache-item:last-child{border:none;}
.tache-prio{width:6px;height:6px;border-radius:50%;flex-shrink:0;margin-top:5px;}
.tache-info{flex:1;min-width:0;}
.tache-title{font-size:12px;font-weight:500;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.tache-proj{font-size:10px;color:var(--muted);}

/* EMPTY */
.empty-state{text-align:center;padding:24px;color:var(--muted);font-size:12px;}

/* RESPONSIVE */
@media(max-width:600px){
  .hero-inner{flex-direction:column;align-items:center;text-align:center;}
  .hero-contact{justify-content:center;}
  .hero-badges{justify-content:center;}
  .kpi-row{grid-template-columns:repeat(2,1fr);}
  .grid2{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<div class="topbar">
  <a class="back-btn" href="dashboard.php">
    <svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>
    Workspace
  </a>
  <div class="topbar-title"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
  <?php if($isSelf): ?>
  <a class="edit-btn" href="profil.php">Modifier mon profil</a>
  <?php endif; ?>
</div>

<div class="page">

  <!-- HERO -->
  <div class="hero-card">
    <div class="hero-inner">
      <div class="avatar-wrap" style="position:relative">
        <div class="avatar">
          <?php if(!empty($u['avatar'])): ?>
          <img src="<?= htmlspecialchars($u['avatar']) ?>" alt="Avatar">
          <?php else: ?>
          <?= $initiales ?>
          <?php endif; ?>
        </div>
        <div class="online-dot" title="Membre actif"></div>
      </div>

      <div class="hero-info">
        <div class="hero-name"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
        <?php if(!empty($u['poste'])): ?>
        <div class="hero-poste"><?= htmlspecialchars($u['poste']) ?></div>
        <?php endif; ?>

        <div class="hero-badges">
          <span class="rbadge" style="background:<?= $roleColor ?>22;color:<?= $roleColor ?>"><?= ucfirst($u['role']) ?></span>
          <span class="mbadge">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Depuis <?= date('M Y', strtotime($u['created_at'])) ?>
          </span>
          <?php if($u['last_login']): ?>
          <span class="mbadge">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Actif le <?= date('d/m/Y', strtotime($u['last_login'])) ?>
          </span>
          <?php endif; ?>
        </div>

        <?php if(!empty($u['bio'])): ?>
        <div class="hero-bio"><?= htmlspecialchars($u['bio']) ?></div>
        <?php endif; ?>

        <div class="hero-contact">
          <?php if($isSelf || isManager()): ?>
          <div class="contact-item">
            <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <?= htmlspecialchars($u['email']) ?>
          </div>
          <?php endif; ?>
          <?php if(!empty($u['telephone'])): ?>
          <div class="contact-item">
            <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 5.5 5.5l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <?= htmlspecialchars($u['telephone']) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-row">
    <div class="kpi-card">
      <div class="kpi-val"><?= $nbTaches ?></div>
      <div class="kpi-label">Tâches terminées</div>
      <div class="kpi-sub">sur <?= $totalTaches ?> total</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val"><?= $nbProjets ?></div>
      <div class="kpi-label">Projets impliqués</div>
      <div class="kpi-sub">actifs</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val"><?= $tauxCompletion ?>%</div>
      <div class="kpi-label">Taux complétion</div>
      <div class="kpi-sub">tâches réalisées</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val"><?= $tempsTotal>0?fmtDur($tempsTotal):'—' ?></div>
      <div class="kpi-label">Temps logué</div>
      <div class="kpi-sub">total</div>
    </div>
  </div>

  <!-- COMPLETION BAR -->
  <div class="completion-card">
    <div class="completion-head">
      <div class="completion-title">Taux de complétion des tâches</div>
      <div class="completion-pct"><?= $tauxCompletion ?>%</div>
    </div>
    <div class="completion-bar">
      <div class="completion-fill" id="completionFill" style="width:0%"></div>
    </div>
    <div class="completion-sub"><?= $nbTaches ?> tâche(s) terminée(s) sur <?= $totalTaches ?> assignée(s)</div>
  </div>

  <!-- PROJETS + TÂCHES -->
  <div class="grid2">
    <div class="card">
      <div class="card-title">
        <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Projets actifs
      </div>
      <?php if($projets): ?>
        <?php
        $statutDots=['Prospection'=>'#7a78a0','Devis envoyé'=>'#f0a500','Signé'=>'#9b8fff','En cours'=>'#36A9E1','En test'=>'#26c6da','Livré'=>'#2ecc87'];
        foreach($projets as $p):
          $dot=$statutDots[$p['statut']]??'#36A9E1';
        ?>
        <div class="projet-item">
          <div class="projet-dot" style="background:<?= $dot ?>"></div>
          <div class="projet-info">
            <div class="projet-name"><?= htmlspecialchars($p['nom']) ?></div>
            <div class="projet-type"><?= htmlspecialchars($p['type_prestation']) ?></div>
          </div>
          <span class="statut-badge" style="background:<?= $dot ?>22;color:<?= $dot ?>"><?= htmlspecialchars($p['statut']) ?></span>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">Aucun projet actif</div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-title">
        <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Tâches récentes
      </div>
      <?php if($taches): ?>
        <?php
        $prioDots=['Haute'=>'#e05252','Moyenne'=>'#f0a500','Basse'=>'#36A9E1'];
        $statutLabels=['À faire'=>'#7a78a0','En cours'=>'#36A9E1','Bloqué'=>'#e05252','Terminé'=>'#2ecc87'];
        foreach($taches as $t):
          $dot=$prioDots[$t['priorite']]??'#7a78a0';
          $sc=$statutLabels[$t['statut']]??'#7a78a0';
        ?>
        <div class="tache-item">
          <div class="tache-prio" style="background:<?= $dot ?>"></div>
          <div class="tache-info">
            <div class="tache-title"><?= htmlspecialchars($t['titre']) ?></div>
            <div class="tache-proj"><?= htmlspecialchars($t['projet_nom']??'Sans projet') ?></div>
          </div>
          <span class="statut-badge" style="background:<?= $sc ?>22;color:<?= $sc ?>;font-size:9px"><?= htmlspecialchars($t['statut']) ?></span>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">Aucune tâche assignée</div>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
// Animation barre de complétion
setTimeout(() => {
  document.getElementById('completionFill').style.width = '<?= $tauxCompletion ?>%';
}, 200);
</script>
</body>
</html>
