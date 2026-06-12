<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$user      = currentUser();
$isAdmin   = isAdmin();
$isManager = isManager();
$db        = getDB();

// ===== PERMISSIONS =====
// Logique simple et fiable : taches et dashboard toujours accessibles à tous
// Les autres modules via la table user_permissions
function hasPerm(string $m): bool {
    global $isAdmin, $isManager, $user, $db;
    if ($isAdmin || $isManager) return true;
    // Ces modules sont accessibles à TOUS les collaborateurs sans exception
    if (in_array($m, ['dashboard','taches','calendrier','chat','temps'])) return true;
    // Les autres : vérifier en base
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM user_permissions WHERE user_id=? AND module=? AND peut_voir=1");
        $s->execute([$user['id'], $m]);
        return (int)$s->fetchColumn() > 0;
    } catch(Exception $e) { return false; }
}

// ===== DONNÉES PHP =====
$jours  = ['Sunday'=>'Dimanche','Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi'];
$moisFr = ['January'=>'janvier','February'=>'février','March'=>'mars','April'=>'avril','May'=>'mai','June'=>'juin','July'=>'juillet','August'=>'août','September'=>'septembre','October'=>'octobre','November'=>'novembre','December'=>'décembre'];
$dateFr = $jours[date('l')].' '.date('d').' '.$moisFr[date('F')].' '.date('Y');
$initiales = strtoupper(substr($user['nom'],0,1));

// Alertes
$alertes = [];
try {
    $retard   = (int)$db->query("SELECT COUNT(*) FROM projets WHERE date_livraison < CURDATE() AND statut NOT IN ('Livré','Clôturé')")->fetchColumn();
    $bloquees = (int)$db->query("SELECT COUNT(*) FROM taches WHERE statut='Bloqué'")->fetchColumn();
    $demain   = date('Y-m-d', strtotime('+1 day'));
    $deadlines= (int)$db->query("SELECT COUNT(*) FROM taches WHERE date_echeance='$demain' AND statut NOT IN ('Terminé')")->fetchColumn();
    if ($retard   > 0) $alertes[] = ['type'=>'danger', 'msg'=>"{$retard} projet(s) en retard"];
    if ($bloquees > 0) $alertes[] = ['type'=>'warning','msg'=>"{$bloquees} tâche(s) bloquée(s)"];
    if ($deadlines> 0) $alertes[] = ['type'=>'warning','msg'=>"{$deadlines} tâche(s) arrivent à échéance demain"];
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
<title>UP TECH GROUP — Workspace</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--warning:#f0a500;--danger:#e05252;--purple:#9b8fff;--sidebar:230px;--topbar:56px;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html,body{height:100%;overflow:hidden;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);}
.app{display:flex;height:100vh;overflow:hidden;}

/* SIDEBAR */
.sidebar{width:var(--sidebar);background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;overflow-y:auto;z-index:300;transition:transform .3s;}
.sb-brand{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-shrink:0;}
.sb-brand img{width:32px;height:32px;object-fit:contain;}
.sb-brand-text h2{font-size:12px;font-weight:800;color:#fff;line-height:1.2;}
.sb-brand-text span{font-size:10px;color:var(--muted);}
.sb-nav{flex:1;padding:6px 0;}
.sb-section{font-size:9px;font-weight:700;color:var(--muted);letter-spacing:2px;text-transform:uppercase;padding:10px 16px 4px;}
.sb-item{display:flex;align-items:center;gap:9px;padding:8px 16px;font-size:12px;font-weight:500;color:var(--muted);border-left:3px solid transparent;transition:all .15s;text-decoration:none;cursor:pointer;white-space:nowrap;}
.sb-item:hover{background:rgba(54,169,225,.07);color:var(--text);border-left-color:rgba(54,169,225,.3);}
.sb-item.active{background:rgba(54,169,225,.1);color:#fff;border-left-color:var(--accent);}
.sb-item .ico svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.sb-badge{background:var(--danger);color:#fff;font-size:9px;font-weight:700;border-radius:99px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;margin-left:auto;}
.sb-user{padding:10px 16px;border-top:1px solid var(--border);flex-shrink:0;display:flex;align-items:center;gap:9px;}
.sb-av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;}
.sb-user-info{flex:1;min-width:0;}
.sb-user-name{font-size:11px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sb-user-role{font-size:9px;color:var(--muted);}
.sb-logout{color:var(--muted);text-decoration:none;display:flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:6px;transition:color .2s;flex-shrink:0;}
.sb-logout:hover{color:var(--danger);}
.sb-logout svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;}
.overlay.open{display:block;}

/* MAIN */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
.topbar{height:var(--topbar);background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:10px;flex-shrink:0;}
.menu-btn{display:none;background:none;border:none;color:var(--text);cursor:pointer;padding:4px;}
.menu-btn svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.page-title{flex:1;font-size:14px;font-weight:700;color:#fff;}
.topbar-right{display:flex;align-items:center;gap:8px;}
.notif-btn{background:var(--bg3);border:1px solid var(--border);border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);transition:all .2s;position:relative;flex-shrink:0;}
.notif-btn:hover{color:var(--accent);border-color:rgba(54,169,225,.3);}
.notif-btn svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.notif-dot{display:none;position:absolute;top:-2px;right:-2px;width:8px;height:8px;border-radius:50%;background:var(--danger);border:2px solid var(--bg2);}
.notif-panel{display:none;position:fixed;top:60px;right:16px;width:300px;background:var(--bg2);border:1px solid var(--border);border-radius:12px;z-index:500;box-shadow:0 8px 32px rgba(0,0,0,.5);max-height:360px;overflow-y:auto;}
.notif-panel.open{display:block;}
.notif-head{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.notif-head span{font-size:13px;font-weight:700;color:#fff;}
.notif-read{font-size:11px;color:var(--accent);background:none;border:none;cursor:pointer;font-family:'Poppins',sans-serif;}
.notif-item{padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.04);font-size:12px;color:var(--text);line-height:1.5;cursor:pointer;transition:background .15s;}
.notif-item:hover{background:rgba(54,169,225,.05);}
.notif-time{font-size:10px;color:var(--muted);margin-top:2px;}
.notif-empty{padding:20px;text-align:center;color:var(--muted);font-size:12px;}
.search-bar{background:var(--bg3);border:1px solid var(--border);border-radius:8px;height:34px;display:flex;align-items:center;padding:0 10px;gap:6px;cursor:pointer;color:var(--muted);font-size:12px;min-width:140px;transition:border-color .2s;}
.search-bar:hover{border-color:rgba(54,169,225,.3);}
.search-bar svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.search-kbd{margin-left:auto;background:var(--bg2);border:1px solid var(--border);border-radius:4px;padding:1px 5px;font-size:10px;}
.add-btn{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:8px;padding:0 16px;height:34px;color:#fff;font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;}

/* CONTENT */
.content{flex:1;overflow-y:auto;}
.content::-webkit-scrollbar{width:4px;}
.content::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}
.section{display:none;padding:20px;}
.section.active{display:block;}

/* CARDS */
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.card-title{font-size:13px;font-weight:700;color:#fff;}
.card-link{font-size:11px;color:var(--accent);text-decoration:none;font-weight:600;background:none;border:none;cursor:pointer;font-family:'Poppins',sans-serif;}

/* WELCOME */
.welcome{background:linear-gradient(135deg,var(--primary),rgba(54,169,225,.2));border:1px solid var(--border);border-radius:14px;padding:18px 22px;margin-bottom:14px;display:flex;align-items:center;gap:16px;position:relative;overflow:hidden;}
.welcome::after{content:'';position:absolute;top:-40px;right:-40px;width:150px;height:150px;background:radial-gradient(circle,rgba(54,169,225,.15),transparent 70%);border-radius:50%;}
.welcome img{width:44px;height:44px;object-fit:contain;flex-shrink:0;}
.welcome h2{font-size:16px;font-weight:800;color:#fff;}
.welcome p{font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;}

/* ALERTES */
.alert{display:flex;align-items:center;gap:10px;padding:9px 14px;border-radius:9px;margin-bottom:8px;font-size:12px;font-weight:500;}
.alert svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;flex-shrink:0;}
.alert.danger{background:rgba(224,82,82,.1);border:1px solid rgba(224,82,82,.25);color:#f08080;}
.alert.warning{background:rgba(240,165,0,.1);border:1px solid rgba(240,165,0,.25);color:var(--warning);}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;}
.kpi{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:15px;position:relative;overflow:hidden;transition:transform .2s;}
.kpi:hover{transform:translateY(-2px);}
.kpi-glow{position:absolute;top:-20px;right:-20px;width:70px;height:70px;border-radius:50%;}
.kpi-icon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:8px;}
.kpi-icon svg{width:14px;height:14px;fill:none;stroke-width:1.8;stroke-linecap:round;}
.kpi-val{font-size:22px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;letter-spacing:-1px;line-height:1;}
.kpi-label{font-size:11px;color:var(--muted);margin-top:3px;}
.kpi-trend{font-size:10px;font-weight:600;margin-top:4px;color:var(--muted);}

/* GRIDS */
.g2{display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px;}
.g2e{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.chart-wrap{position:relative;height:185px;}

/* PIPELINE */
.pipe-item{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.pipe-item:last-child{border:none;}
.pipe-label{font-size:11px;color:var(--text);width:100px;font-weight:500;}
.pipe-bar{flex:1;height:5px;background:var(--bg3);border-radius:99px;overflow:hidden;}
.pipe-fill{height:100%;border-radius:99px;transition:width .8s ease;}
.pipe-count{font-size:11px;color:var(--muted);width:20px;text-align:right;}

/* TABLE */
.tbl-wrap{overflow-x:auto;}
.tbl{width:100%;border-collapse:collapse;min-width:400px;}
.tbl th{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;padding:0 8px 10px;text-align:left;border-bottom:1px solid var(--border);}
.tbl td{font-size:12px;padding:10px 8px;border-bottom:1px solid rgba(255,255,255,.03);vertical-align:middle;}
.tbl tr:last-child td{border:none;}
.tbl tr:hover td{background:rgba(54,169,225,.02);}

/* BADGES */
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;}
.bg-green{background:rgba(46,204,135,.15);color:var(--success);}
.bg-blue{background:rgba(54,169,225,.15);color:var(--accent);}
.bg-orange{background:rgba(240,165,0,.15);color:var(--warning);}
.bg-red{background:rgba(224,82,82,.15);color:var(--danger);}
.bg-purple{background:rgba(155,143,255,.15);color:var(--purple);}
.bg-muted{background:rgba(122,120,160,.15);color:var(--muted);}
.role-admin{background:rgba(224,82,82,.2);color:#f08080;}
.role-manager{background:rgba(54,169,225,.2);color:var(--accent);}
.role-collaborateur{background:rgba(46,204,135,.2);color:var(--success);}

/* BOUTONS ACTION */
.act-btn{background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:4px 10px;font-size:11px;color:var(--muted);cursor:pointer;font-family:'Poppins',sans-serif;transition:all .15s;white-space:nowrap;}
.act-btn:hover{border-color:var(--accent);color:var(--accent);}
.act-btn.danger{background:rgba(224,82,82,.06);border-color:rgba(224,82,82,.2);color:var(--danger);}
.act-btn.danger:hover{background:rgba(224,82,82,.12);}

/* CHARGE */
.cw-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.cw-item:last-child{border:none;}
.cw-av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;}
.cw-name{font-size:12px;font-weight:600;color:#fff;}
.cw-sub{font-size:10px;color:var(--muted);}
.cw-bar-wrap{flex:1;height:6px;background:var(--bg3);border-radius:99px;overflow:hidden;}
.cw-bar-fill{height:100%;border-radius:99px;transition:width .6s ease;}
.cw-count{font-size:13px;font-weight:700;color:#fff;width:24px;text-align:right;flex-shrink:0;}

/* ACTIVITY */
.act-item{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.act-item:last-child{border:none;}
.act-icon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.act-icon svg{width:13px;height:13px;fill:none;stroke:#fff;stroke-width:1.8;stroke-linecap:round;}
.act-text{flex:1;font-size:12px;color:var(--text);line-height:1.5;}
.act-time{font-size:10px;color:var(--muted);white-space:nowrap;}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1000;align-items:center;justify-content:center;padding:16px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;width:100%;max-width:520px;max-height:92vh;overflow-y:auto;}
.modal-head{padding:18px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;}
.modal-head h3{font-size:15px;font-weight:700;color:#fff;}
.modal-close{background:var(--bg3);border:1px solid var(--border);border-radius:8px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);}
.modal-close svg{width:11px;height:11px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;}
.modal-body{padding:18px 20px;}
.modal-foot{padding:12px 20px;display:flex;gap:8px;justify-content:flex-end;border-top:1px solid var(--border);}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.field{margin-bottom:12px;}
label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;display:block;margin-bottom:5px;}
input,select,textarea{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:border-color .2s;-webkit-appearance:none;}
input:focus,select:focus,textarea:focus{border-color:var(--accent);}
input:disabled{opacity:.4;cursor:not-allowed;}
select option{background:var(--bg2);}
.btn-p{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:9px;padding:9px 22px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-s{background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:9px 16px;color:var(--muted);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}

/* SEARCH OVERLAY */
.search-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:2000;align-items:flex-start;justify-content:center;padding-top:80px;}
.search-overlay.open{display:flex;}
.search-box{background:var(--bg2);border:1px solid var(--border);border-radius:14px;width:100%;max-width:520px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.6);}
.search-input-wrap{display:flex;align-items:center;padding:14px 16px;border-bottom:1px solid var(--border);gap:10px;}
.search-input-wrap svg{width:16px;height:16px;fill:none;stroke:var(--muted);stroke-width:2;stroke-linecap:round;flex-shrink:0;}
.search-input-wrap input{flex:1;background:none;border:none;outline:none;color:var(--text);font-family:'Poppins',sans-serif;font-size:14px;}
.search-esc{font-size:11px;color:var(--muted);cursor:pointer;padding:3px 8px;background:var(--bg3);border-radius:5px;border:1px solid var(--border);flex-shrink:0;}
.search-results{max-height:320px;overflow-y:auto;padding:8px;}
.search-result-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;cursor:pointer;transition:background .15s;}
.search-result-item:hover{background:rgba(54,169,225,.07);}
.sr-type{font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;white-space:nowrap;}
.sr-label{font-size:13px;font-weight:600;color:#fff;}
.sr-sub{font-size:11px;color:var(--muted);}

/* MODE TABS TACHES */
.mode-tabs{display:flex;background:var(--bg3);border:1px solid var(--border);border-radius:7px;overflow:hidden;}
.mode-tab{padding:5px 12px;font-size:11px;font-weight:600;cursor:pointer;border:none;background:transparent;color:var(--muted);font-family:'Poppins',sans-serif;transition:all .15s;}
.mode-tab.active{background:var(--accent);color:#fff;}

/* TOAST */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:10px 20px;font-size:13px;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;max-width:90vw;text-align:center;}
#toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
#toast.success{border-color:rgba(46,204,135,.4);color:var(--success);}
#toast.error{border-color:rgba(224,82,82,.4);color:var(--danger);}

/* BOTTOM NAV MOBILE */
.bottom-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:var(--bg2);border-top:1px solid var(--border);z-index:100;padding:6px 0 max(6px,env(safe-area-inset-bottom));}
.bn-wrap{display:flex;justify-content:space-around;}
.bn-item{display:flex;flex-direction:column;align-items:center;gap:2px;padding:4px 8px;cursor:pointer;color:var(--muted);font-size:9px;font-weight:500;border:none;background:none;font-family:'Poppins',sans-serif;transition:color .2s;}
.bn-item.active,.bn-item:hover{color:var(--accent);}
.bn-item svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}

@media(max-width:768px){
  .sidebar{position:fixed;top:0;left:0;height:100%;transform:translateX(-100%);box-shadow:4px 0 24px rgba(0,0,0,.5);}
  .sidebar.open{transform:translateX(0);}
  .menu-btn{display:flex;}
  .bottom-nav{display:block;}
  .section{padding:12px 12px 80px;}
  .kpi-grid{grid-template-columns:repeat(2,1fr);}
  .g2,.g2e,.frow{grid-template-columns:1fr;}
  .search-bar span:not(.search-kbd){display:none;}
  .search-bar{min-width:auto;}
}
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}
</style>
</head>
<body>
<div class="app">
<div class="overlay" id="overlay" onclick="closeSb()"></div>

<!-- ===== SIDEBAR ===== -->
<nav class="sidebar" id="sidebar">
  <div class="sb-brand">
    <img src="assets/logo.png" alt="UP TECH GROUP">
    <div class="sb-brand-text"><h2>UP TECH GROUP</h2><span>Workspace</span></div>
  </div>
  <div class="sb-nav">
    <div class="sb-section">Principal</div>
    <a class="sb-item active" id="nav-dashboard" onclick="show('dashboard')">
      <span class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></span>Tableau de bord
    </a>
    <a class="sb-item" id="nav-taches" onclick="show('taches')">
      <span class="ico"><svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>Mes tâches
      <span class="sb-badge" id="taskBadge" style="display:none">0</span>
    </a>
    <?php if(hasPerm('stats')): ?>
    <a class="sb-item" id="nav-stats" onclick="show('stats')">
      <span class="ico"><svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>Statistiques
    </a>
    <?php endif; ?>
    <?php if(hasPerm('calendrier')): ?>
    <a class="sb-item" href="calendrier.php">
      <span class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>Calendrier
    </a>
    <?php endif; ?>
    <?php if(hasPerm('chat')): ?>
    <a class="sb-item" href="chat.php">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>Messages
    </a>
    <?php endif; ?>
    <?php if(hasPerm('fichiers')): ?>
    <a class="sb-item" href="fichiers.php">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></span>Fichiers
    </a>
    <?php endif; ?>
    <?php if(hasPerm('temps')): ?>
    <a class="sb-item" href="time.php">
      <span class="ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>Suivi du temps
    </a>
    <?php endif; ?>

    <?php if(hasPerm('projets')||hasPerm('clients')||hasPerm('crm')||hasPerm('finances')||hasPerm('facturation')||hasPerm('charge')||hasPerm('export')||hasPerm('rapports')): ?>
    <div class="sb-section">Gestion</div>
    <?php endif; ?>
    <?php if(hasPerm('projets')): ?>
    <a class="sb-item" id="nav-projets" onclick="show('projets')">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></span>Projets
    </a>
    <?php endif; ?>
    <?php if(hasPerm('clients')): ?>
    <a class="sb-item" id="nav-clients" onclick="show('clients')">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>Clients
    </a>
    <?php endif; ?>
    <?php if(hasPerm('charge')): ?>
    <a class="sb-item" id="nav-charge" onclick="show('charge')">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>Charge travail
    </a>
    <?php endif; ?>
    <?php if(hasPerm('crm')): ?>
    <a class="sb-item" href="crm.php">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12"/></svg></span>CRM
    </a>
    <?php endif; ?>
    <?php if(hasPerm('finances')): ?>
    <a class="sb-item" href="finances.php">
      <span class="ico"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>Finances
    </a>
    <?php endif; ?>
    <?php if(hasPerm('facturation')): ?>
    <a class="sb-item" href="facturation.php">
      <span class="ico"><svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>Facturation
    </a>
    <?php endif; ?>
    <?php if(hasPerm('export')): ?>
    <a class="sb-item" href="export_csv.php">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>Export CSV
    </a>
    <?php endif; ?>
    <?php if(hasPerm('rapports')): ?>
    <a class="sb-item" href="rapport_pdf.php?select=1" target="_blank">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>Rapport PDF
    </a>
    <?php endif; ?>

    <?php if($isAdmin): ?>
    <div class="sb-section">Administration</div>
    <a class="sb-item" href="equipe.php">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>Équipe & Accès
    </a>
    <?php endif; ?>

    <div class="sb-section">Compte</div>
    <a class="sb-item" href="profil.php">
      <span class="ico"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Mon profil
    </a>
  </div>
  <div class="sb-user">
    <div class="sb-av"><?= $initiales ?></div>
    <div class="sb-user-info">
      <div class="sb-user-name"><?= htmlspecialchars($user['nom']) ?></div>
      <div class="sb-user-role"><?= ucfirst($user['role']) ?></div>
    </div>
    <a class="sb-logout" href="api.php?action=logout" title="Déconnexion">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>
</nav>

<!-- ===== MAIN ===== -->
<div class="main">
  <div class="topbar">
    <button class="menu-btn" onclick="toggleSb()"><svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
    <div class="page-title" id="pageTitle">Tableau de bord</div>
    <div class="topbar-right">
      <div class="search-bar" onclick="openSearch()">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span>Rechercher</span>
        <span class="search-kbd">Ctrl K</span>
      </div>
      <div class="notif-btn" id="notifBtn" onclick="toggleNotifs()">
        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <div class="notif-dot" id="notifDot"></div>
      </div>
      <?php if($isManager): ?><button class="add-btn" onclick="openAdd()">+ Nouveau</button><?php endif; ?>
    </div>
  </div>

  <!-- PANEL NOTIFS -->
  <div class="notif-panel" id="notifPanel">
    <div class="notif-head">
      <span>Notifications</span>
      <button class="notif-read" onclick="markAllRead()">Tout lire</button>
    </div>
    <div id="notifList"><div class="notif-empty">Aucune notification</div></div>
  </div>

  <div class="content">

  <!-- ===== DASHBOARD ===== -->
  <div class="section active" id="sec-dashboard">
    <div class="welcome">
      <img src="assets/logo.png" alt="">
      <div>
        <h2>Bonjour, <?= htmlspecialchars(explode(' ',$user['nom'])[0]) ?> !</h2>
        <p><?= $dateFr ?> · UP TECH GROUP Workspace</p>
      </div>
    </div>
    <?php foreach($alertes as $a): ?>
    <div class="alert <?= $a['type'] ?>">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($a['msg']) ?>
    </div>
    <?php endforeach; ?>
    <div class="kpi-grid" id="kpiGrid">
      <?php for($i=0;$i<4;$i++): ?><div class="kpi" style="opacity:.15"><div style="height:75px"></div></div><?php endfor; ?>
    </div>
    <div class="g2">
      <div class="card">
        <div class="card-head"><div class="card-title">Activité mensuelle</div><a href="finances.php" class="card-link">Détails →</a></div>
        <div class="chart-wrap"><canvas id="chartActivite"></canvas></div>
      </div>
      <div class="card">
        <div class="card-head"><div class="card-title">Pipeline projets</div><button class="card-link" onclick="show('projets')">Voir →</button></div>
        <div id="pipelineList"></div>
      </div>
    </div>
    <div class="g2e">
      <div class="card">
        <div class="card-head"><div class="card-title">Tâches prioritaires</div><button class="card-link" onclick="show('taches')">Toutes →</button></div>
        <div id="dashTaches"></div>
      </div>
      <div class="card">
        <div class="card-head"><div class="card-title">Activité récente</div></div>
        <div id="dashActivity"></div>
      </div>
    </div>
  </div>

  <!-- ===== TÂCHES — toujours générée pour tout le monde ===== -->
  <div class="section" id="sec-taches">
    <div class="card">
      <div class="card-head">
        <div class="card-title" id="tacheTitle">Mes tâches</div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
          <?php if($isManager): ?>
          <div class="mode-tabs">
            <button class="mode-tab active" id="tabMes" onclick="setTacheMode('mes')">Mes tâches</button>
            <button class="mode-tab" id="tabToutes" onclick="setTacheMode('toutes')">Toutes</button>
          </div>
          <?php endif; ?>
          <select id="tacheFilter" onchange="loadTaches()" style="background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:5px 8px;color:var(--text);font-size:11px;font-family:'Poppins',sans-serif;">
            <option value="">Tous les statuts</option>
            <option>À faire</option><option>En cours</option><option>Bloqué</option><option>Terminé</option>
          </select>
          <?php if($isManager): ?><button class="btn-p" style="height:30px;font-size:11px;padding:0 12px" onclick="openModalTache()">+ Tâche</button><?php endif; ?>
        </div>
      </div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Tâche</th><th>Projet</th><th>Priorité</th><th>Échéance</th><th>Statut</th>
              <?php if($isManager): ?><th>Actions</th><?php endif; ?>
            </tr>
          </thead>
          <tbody id="tacheTable"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ===== STATS ===== -->
  <?php if(hasPerm('stats')): ?>
  <div class="section" id="sec-stats">
    <div class="kpi-grid" id="statsKpi"></div>
    <div class="g2e">
      <div class="card"><div class="card-title" style="margin-bottom:12px">Répartition des tâches</div><div class="chart-wrap"><canvas id="chartTaches"></canvas></div></div>
      <div class="card"><div class="card-title" style="margin-bottom:12px">Statuts clients</div><div class="chart-wrap"><canvas id="chartClients"></canvas></div></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== PROJETS ===== -->
  <?php if(hasPerm('projets')): ?>
  <div class="section" id="sec-projets">
    <div class="card">
      <div class="card-head">
        <div class="card-title">Projets</div>
        <div style="display:flex;gap:6px;align-items:center">
          <select id="projetStatutFilter" onchange="loadProjets()" style="background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:5px 8px;color:var(--text);font-size:11px;font-family:'Poppins',sans-serif;">
            <option value="">Tous les statuts</option>
            <option>Prospection</option><option>Devis envoyé</option><option>Signé</option>
            <option>En cours</option><option>En test</option><option>Livré</option><option>Clôturé</option>
          </select>
          <?php if($isManager): ?><button class="btn-p" style="height:30px;font-size:11px;padding:0 12px" onclick="openModalProjet()">+ Projet</button><?php endif; ?>
        </div>
      </div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>Nom</th><th>Client</th><th>Type</th><th>Statut</th><th>Livraison</th><th>Budget</th><?php if($isManager): ?><th>Actions</th><?php endif; ?></tr></thead>
          <tbody id="projetTable"></tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== CLIENTS ===== -->
  <?php if(hasPerm('clients')): ?>
  <div class="section" id="sec-clients">
    <div class="card">
      <div class="card-head">
        <div class="card-title">Clients & Prospects</div>
        <?php if($isManager): ?><button class="btn-p" style="height:30px;font-size:11px;padding:0 12px" onclick="openModalClient()">+ Client</button><?php endif; ?>
      </div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>Nom</th><th>Type</th><th>Statut</th><th>Contact</th><th>Téléphone</th><th>Pays</th><?php if($isManager): ?><th>Actions</th><?php endif; ?></tr></thead>
          <tbody id="clientTable"></tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== CHARGE ===== -->
  <?php if(hasPerm('charge')): ?>
  <div class="section" id="sec-charge">
    <div class="card">
      <div class="card-head"><div class="card-title">Charge de travail — Équipe</div><button class="act-btn" onclick="loadCharge()">Actualiser</button></div>
      <div id="chargeList"></div>
    </div>
  </div>
  <?php endif; ?>

  </div><!-- /content -->
</div><!-- /main -->

<!-- BOTTOM NAV -->
<nav class="bottom-nav"><div class="bn-wrap">
  <button class="bn-item active" id="bn-dashboard" onclick="show('dashboard')"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Accueil</button>
  <button class="bn-item" id="bn-taches" onclick="show('taches')"><svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Tâches</button>
  <button class="bn-item" onclick="location.href='chat.php'"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages</button>
  <button class="bn-item" onclick="location.href='profil.php'"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profil</button>
</div></nav>
</div><!-- /app -->

<!-- SEARCH OVERLAY -->
<div class="search-overlay" id="searchOverlay" onclick="e=>e.target===this&&closeSearch()">
  <div class="search-box">
    <div class="search-input-wrap">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input id="searchInput" placeholder="Rechercher projets, clients, tâches…" autocomplete="off">
      <span class="search-esc" onclick="closeSearch()">Esc</span>
    </div>
    <div class="search-results" id="searchResults">
      <div style="padding:16px;text-align:center;color:var(--muted);font-size:12px">Tapez pour rechercher…</div>
    </div>
  </div>
</div>

<!-- MODAL TÂCHE -->
<div class="modal-overlay" id="modalTache">
  <div class="modal">
    <div class="modal-head"><h3 id="modalTacheTitle">Nouvelle tâche</h3><div class="modal-close" onclick="closeModal('modalTache')"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div></div>
    <div class="modal-body">
      <input type="hidden" id="tId">
      <div class="field"><label>Titre *</label><input id="tTitre" placeholder="Nom de la tâche"></div>
      <div class="frow">
        <div class="field"><label>Assigner à</label><select id="tAssigne"><option value="">— Non assigné —</option></select></div>
        <div class="field"><label>Projet</label><select id="tProjet"><option value="">— Aucun —</option></select></div>
      </div>
      <div class="frow">
        <div class="field"><label>Priorité</label><select id="tPrio"><option>Haute</option><option selected>Moyenne</option><option>Basse</option></select></div>
        <div class="field"><label>Statut</label><select id="tStatut"><option>À faire</option><option>En cours</option><option>Bloqué</option><option>Terminé</option></select></div>
      </div>
      <div class="frow">
        <div class="field"><label>Date début</label><input type="date" id="tDateDebut"></div>
        <div class="field"><label>Échéance</label><input type="date" id="tDate"></div>
      </div>
      <div class="field"><label>Estimation (h)</label><input type="number" id="tEstim" min="0" step="0.5" placeholder="Ex: 4"></div>
      <div class="field"><label>Description</label><textarea id="tDesc" rows="3" placeholder="Détails, instructions…"></textarea></div>
    </div>
    <div class="modal-foot"><button class="btn-s" onclick="closeModal('modalTache')">Annuler</button><button class="btn-p" onclick="saveTache()">Enregistrer</button></div>
  </div>
</div>

<!-- MODAL PROJET -->
<div class="modal-overlay" id="modalProjet">
  <div class="modal">
    <div class="modal-head"><h3 id="modalProjetTitle">Nouveau projet</h3><div class="modal-close" onclick="closeModal('modalProjet')"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div></div>
    <div class="modal-body">
      <input type="hidden" id="pId">
      <div class="field"><label>Nom *</label><input id="pNom" placeholder="Nom du projet"></div>
      <div class="frow">
        <div class="field"><label>Client</label><select id="pClient"><option value="">— Aucun —</option></select></div>
        <div class="field"><label>Manager</label><select id="pManager"><option value="">— Non assigné —</option></select></div>
      </div>
      <div class="frow">
        <div class="field"><label>Type</label>
          <select id="pType"><option>Développement web</option><option>Application mobile</option><option>Développement logiciel</option><option>Conseil IT</option><option>Formation</option><option>Maintenance</option><option>Design graphique</option><option>Affiche / Flyer</option><option>Community management</option><option>Autre</option></select>
        </div>
        <div class="field"><label>Statut</label>
          <select id="pStatut"><option>Prospection</option><option>Devis envoyé</option><option>Signé</option><option>En cours</option><option>En test</option><option>Livré</option><option>Clôturé</option></select>
        </div>
      </div>
      <div class="frow">
        <div class="field"><label>Priorité</label><select id="pPriorite"><option>Haute</option><option selected>Moyenne</option><option>Basse</option></select></div>
        <div class="field"><label>Budget (FCFA)</label><input type="number" id="pBudget" min="0" placeholder="0"></div>
      </div>
      <div class="frow">
        <div class="field"><label>Date début</label><input type="date" id="pDateDebut"></div>
        <div class="field"><label>Livraison</label><input type="date" id="pLiv"></div>
      </div>
      <div class="field"><label>Description</label><textarea id="pDesc" rows="2" placeholder="Périmètre, livrables…"></textarea></div>
      <div class="field"><label>Lien Drive</label><input id="pLienDrive" type="url" placeholder="https://drive.google.com/…"></div>
    </div>
    <div class="modal-foot"><button class="btn-s" onclick="closeModal('modalProjet')">Annuler</button><button class="btn-p" onclick="saveProjet()">Enregistrer</button></div>
  </div>
</div>

<!-- MODAL CLIENT -->
<div class="modal-overlay" id="modalClient">
  <div class="modal">
    <div class="modal-head"><h3 id="modalClientTitle">Nouveau client</h3><div class="modal-close" onclick="closeModal('modalClient')"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div></div>
    <div class="modal-body">
      <input type="hidden" id="cId">
      <div class="frow">
        <div class="field"><label>Raison sociale *</label><input id="cNom"></div>
        <div class="field"><label>Type</label><select id="cType"><option>Entreprise</option><option>ONG</option><option>Institution</option><option>Particulier</option></select></div>
      </div>
      <div class="frow">
        <div class="field"><label>Statut</label><select id="cStatut"><option>Prospect</option><option>Client actif</option><option>Client inactif</option></select></div>
        <div class="field"><label>Secteur</label><input id="cSecteur" placeholder="Ex: Santé, Finance…"></div>
      </div>
      <div class="frow">
        <div class="field"><label>Contact</label><input id="cContact"></div>
        <div class="field"><label>Téléphone</label><input id="cTel"></div>
      </div>
      <div class="frow">
        <div class="field"><label>Email</label><input type="email" id="cEmail"></div>
        <div class="field"><label>Pays</label><input id="cPays" value="Togo"></div>
      </div>
      <div class="field"><label>Adresse</label><input id="cAdresse"></div>
      <div class="field"><label>Site web</label><input type="url" id="cSiteWeb" placeholder="https://…"></div>
      <div class="field"><label>Notes</label><textarea id="cNotes" rows="2"></textarea></div>
    </div>
    <div class="modal-foot"><button class="btn-s" onclick="closeModal('modalClient')">Annuler</button><button class="btn-p" onclick="saveClient()">Enregistrer</button></div>
  </div>
</div>

<?php if($isAdmin): ?>
<!-- MODAL USER -->
<div class="modal-overlay" id="modalUser">
  <div class="modal">
    <div class="modal-head"><h3>Ajouter un collaborateur</h3><div class="modal-close" onclick="closeModal('modalUser')"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div></div>
    <div class="modal-body">
      <div class="frow">
        <div class="field"><label>Prénom *</label><input id="uPrenom"></div>
        <div class="field"><label>Nom *</label><input id="uNom"></div>
      </div>
      <div class="field"><label>Email *</label><input type="email" id="uEmail"></div>
      <div class="field"><label>Mot de passe *</label><input type="text" id="uPass" placeholder="Min. 8 caractères" autocomplete="off"></div>
      <div class="frow">
        <div class="field"><label>Rôle</label><select id="uRole"><option value="collaborateur">Collaborateur</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>
        <div class="field"><label>Poste</label><input id="uPoste" placeholder="Ex: Community Manager"></div>
      </div>
    </div>
    <div class="modal-foot"><button class="btn-s" onclick="closeModal('modalUser')">Annuler</button><button class="btn-p" id="btnCreateUser" onclick="saveUser()">Créer et envoyer email</button></div>
  </div>
</div>
<?php endif; ?>

<div id="toast"></div>

<script>
Chart.defaults.color='#7a78a0';
Chart.defaults.borderColor='rgba(54,169,225,0.08)';
Chart.defaults.font.family='Poppins';
Chart.defaults.font.size=11;

const IS_MANAGER = <?= $isManager?'true':'false' ?>;
const IS_ADMIN   = <?= $isAdmin?'true':'false' ?>;
const ME_ID      = <?= $user['id'] ?>;
const ME_NOM     = <?= json_encode($user['nom']) ?>;

let currentSection = 'dashboard';
let tacheMode      = 'mes';
let charts         = {};

// ===== API =====
async function api(p) {
  const fd = new FormData();
  Object.entries(p).forEach(([k,v]) => { if(v!==null&&v!==undefined) fd.append(k,v); });
  try { const r = await fetch('api.php',{method:'POST',body:fd}); return r.json(); }
  catch(e) { return {error:'Erreur réseau'}; }
}
async function apiGet(p) {
  try { const r = await fetch('api.php?'+new URLSearchParams(p)); return r.json(); }
  catch(e) { return {}; }
}

// ===== TOAST =====
function toast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'show '+type;
  setTimeout(()=>t.className='', 4000);
}

// ===== NAVIGATION =====
const TITLES = {dashboard:'Tableau de bord',taches:'Mes tâches',stats:'Statistiques',projets:'Projets',clients:'Clients',charge:'Charge de travail'};
function show(name) {
  document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.sb-item,.bn-item').forEach(i=>i.classList.remove('active'));
  const s = document.getElementById('sec-'+name);
  if (s) s.classList.add('active'); else return;
  document.getElementById('nav-'+name)?.classList.add('active');
  document.getElementById('bn-'+name)?.classList.add('active');
  document.getElementById('pageTitle').textContent = TITLES[name]||name;
  document.title = 'UP TECH GROUP — '+(TITLES[name]||name);
  currentSection = name; closeSb();
  if (name==='dashboard') loadDashboard();
  else if (name==='taches') loadTaches();
  else if (name==='stats') loadStats();
  else if (name==='projets') loadProjets();
  else if (name==='clients') loadClients();
  else if (name==='charge') loadCharge();
}
function toggleSb() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('overlay').classList.toggle('open'); }
function closeSb()  { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('open'); }

// ===== DASHBOARD =====
async function loadDashboard() {
  const s = await api({action:'stats'});
  if (!s || s.error) return;

  // Badge
  const badge = document.getElementById('taskBadge');
  if (badge && s.taches_total > 0) { badge.textContent=s.taches_total; badge.style.display='flex'; }
  else if (badge) badge.style.display='none';

  // KPIs
  const kpis = [
    {ico:'<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',val:fmtN(s.ca_mois),label:'CA ce mois (FCFA)',sub:'Solde: '+fmtN(s.solde||0)+' FCFA',color:'#2ecc87',bg:'rgba(46,204,135,.12)'},
    {ico:'<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',val:s.projets_en_cours||0,label:'Projets en cours',sub:(s.contrats_signes||0)+' contrats signés',color:'#36A9E1',bg:'rgba(54,169,225,.12)'},
    {ico:'<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',val:s.taches_total||0,label:'Tâches ouvertes',sub:(s.taches_bloquees||0)>0?(s.taches_bloquees)+' bloquée(s)':'Aucun blocage',color:(s.taches_bloquees||0)>0?'#e05252':'#f0a500',bg:(s.taches_bloquees||0)>0?'rgba(224,82,82,.12)':'rgba(240,165,0,.12)'},
    {ico:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',val:parseInt(s.total_clients||0)+parseInt(s.total_prospects||0),label:'Clients & Prospects',sub:(s.total_clients||0)+' actifs · '+(s.total_prospects||0)+' prospects',color:'#9b8fff',bg:'rgba(155,143,255,.12)'},
  ];
  document.getElementById('kpiGrid').innerHTML = kpis.map(k=>`
    <div class="kpi">
      <div class="kpi-glow" style="background:radial-gradient(circle,${k.color}22 0%,transparent 70%)"></div>
      <div class="kpi-icon" style="background:${k.bg}"><svg viewBox="0 0 24 24" fill="none" stroke="${k.color}" stroke-width="1.8" stroke-linecap="round">${k.ico}</svg></div>
      <div class="kpi-val">${k.val}</div>
      <div class="kpi-label">${k.label}</div>
      <div class="kpi-trend">${k.sub}</div>
    </div>`).join('');

  // Graphique activité - isolé pour ne pas bloquer le reste
  (async () => {
    try {
      const r = await fetch('finances_api.php?action=revenus_depenses_mois');
      if (!r.ok) return;
      const mois = await r.json();
      if (!Array.isArray(mois)||!mois.length) return;
      if (charts.activite) charts.activite.destroy();
      const ctx = document.getElementById('chartActivite')?.getContext('2d');
      if (!ctx) return;
      const g = ctx.createLinearGradient(0,0,0,185);
      g.addColorStop(0,'rgba(54,169,225,.3)'); g.addColorStop(1,'rgba(54,169,225,0)');
      charts.activite = new Chart(ctx, {type:'line',data:{
        labels:['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'],
        datasets:[
          {label:'Revenus',data:mois.map(m=>parseFloat(m.revenus)||0),borderColor:'#36A9E1',backgroundColor:g,borderWidth:2,fill:true,tension:.4,pointRadius:3},
          {label:'Dépenses',data:mois.map(m=>parseFloat(m.depenses)||0),borderColor:'#e05252',backgroundColor:'transparent',borderWidth:1.5,borderDash:[4,3],tension:.4,pointRadius:2}
        ]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(54,169,225,.05)'}},y:{grid:{color:'rgba(54,169,225,.05)'},ticks:{callback:v=>fmtK(v)}}}}
      });
    } catch(e) { /* finances_api.php inaccessible - graphique ignoré */ }
  })();

  // Pipeline
  try {
    const pl = await api({action:'pipeline'});
    const colors = {'Prospection':'#7a78a0','Devis envoyé':'#f0a500','Signé':'#9b8fff','En cours':'#36A9E1','En test':'#26c6da','Livré':'#2ecc87','Clôturé':'#3a3860'};
    const max = Math.max(...(pl||[]).map(p=>parseInt(p.nb)),1);
    document.getElementById('pipelineList').innerHTML = (pl||[]).map(p=>`
      <div class="pipe-item">
        <div class="pipe-label">${p.statut}</div>
        <div class="pipe-bar"><div class="pipe-fill" style="width:${Math.round((parseInt(p.nb)/max)*100)}%;background:${colors[p.statut]||'#36A9E1'}"></div></div>
        <div class="pipe-count">${p.nb}</div>
      </div>`).join('') || '<div style="color:var(--muted);font-size:12px;padding:8px">Aucun projet</div>';
  } catch(e){}

  // Tâches prioritaires du dashboard (mes tâches non terminées)
  try {
    const taches = await api({action:'mes_taches'});
    const urgent = (taches||[]).filter(t=>t.statut!=='Terminé').slice(0,5);
    const SC = {'À faire':'#7a78a0','En cours':'#36A9E1','Bloqué':'#e05252','Terminé':'#2ecc87'};
    document.getElementById('dashTaches').innerHTML = urgent.length
      ? urgent.map(t=>`<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04)">
          <div style="width:7px;height:7px;border-radius:50%;background:${SC[t.statut]||'#7a78a0'};flex-shrink:0"></div>
          <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:500;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${t.titre}</div><div style="font-size:10px;color:var(--muted)">${t.projet_nom||'Sans projet'} · ${t.priorite}</div></div>
          <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;background:${SC[t.statut]}22;color:${SC[t.statut]}">${t.statut}</span>
        </div>`).join('')
      : '<div style="color:var(--muted);font-size:12px;text-align:center;padding:16px">Aucune tâche en cours</div>';
  } catch(e){}

  // Activité récente
  try {
    const projets = await api({action:'projets'});
    document.getElementById('dashActivity').innerHTML = (projets||[]).slice(0,4).map(p=>`
      <div class="act-item">
        <div class="act-icon" style="background:rgba(54,169,225,.15)"><svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>
        <div class="act-text"><strong>${p.nom}</strong><br><span style="color:var(--muted)">${p.statut} · ${p.type_prestation}</span></div>
        <div class="act-time">${(p.updated_at||'').split(' ')[0]}</div>
      </div>`).join('') || '<div style="color:var(--muted);font-size:12px;text-align:center;padding:16px">Aucun projet</div>';
  } catch(e){}

  loadNotifs();
}

// ===== TÂCHES =====
function statutStyle(statut) {
  const s = {'À faire':{bg:'rgba(122,120,160,.15)',c:'#7a78a0'},'En cours':{bg:'rgba(54,169,225,.15)',c:'#36A9E1'},'Bloqué':{bg:'rgba(224,82,82,.15)',c:'#e05252'},'Terminé':{bg:'rgba(46,204,135,.15)',c:'#2ecc87'}};
  return s[statut]||s['À faire'];
}

function statutSelect(tId, statut) {
  const ss = statutStyle(statut);
  const opts = ['À faire','En cours','Bloqué','Terminé'].map(o=>`<option ${o===statut?'selected':''}>${o}</option>`).join('');
  return `<select onchange="updateTacheStatut(${tId},this.value)"
    style="background:${ss.bg};border:1px solid ${ss.c}44;border-radius:6px;padding:4px 8px;color:${ss.c};font-size:11px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;outline:none;">${opts}</select>`;
}

function setTacheMode(mode) {
  tacheMode = mode;
  ['Mes','Toutes'].forEach(m => {
    const el = document.getElementById('tab'+m);
    if (el) el.classList.toggle('active', (m==='Mes'&&mode==='mes')||(m==='Toutes'&&mode==='toutes'));
  });
  document.getElementById('tacheTitle').textContent = mode==='toutes' ? 'Toutes les tâches' : 'Mes tâches';
  loadTaches();
}

async function loadTaches() {
  const tbody = document.getElementById('tacheTable');
  if (!tbody) return;
  const filter = document.getElementById('tacheFilter')?.value||'';
  const action = (tacheMode==='toutes'&&IS_MANAGER) ? 'toutes_taches' : 'mes_taches';
  let data = await api({action});
  if (!Array.isArray(data)) data = [];
  if (filter) data = data.filter(t=>t.statut===filter);

  // Mettre à jour le badge
  const badge = document.getElementById('taskBadge');
  const nonTerminees = data.filter(t=>t.statut!=='Terminé').length;
  if (badge) { badge.textContent=nonTerminees; badge.style.display=nonTerminees>0?'flex':'none'; }

  const PRIO = {Haute:'bg-red',Moyenne:'bg-orange',Basse:'bg-blue'};
  const today = new Date().toISOString().split('T')[0];

  tbody.innerHTML = data.length ? data.map(t=>`
    <tr>
      <td style="font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${t.titre||''}">${t.titre}</td>
      <td style="color:var(--muted);font-size:11px">${t.projet_nom||'—'}</td>
      <td><span class="badge ${PRIO[t.priorite]||'bg-blue'}">${t.priorite}</span></td>
      <td style="color:${t.date_echeance&&t.date_echeance<today?'var(--danger)':'var(--muted)'};font-size:11px">${t.date_echeance||'—'}</td>
      <td>${statutSelect(t.id, t.statut)}</td>
      <td style="display:flex;gap:4px;align-items:center;flex-wrap:wrap">${tacheActions(t)}</td>
    </tr>`).join('')
  : `<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">Aucune tâche assignée</td></tr>`;
}

function tacheActions(t) {
  const nom = (t.titre||'').replace(/'/g,"\\'");
  let html = '';
  if (t.statut !== 'Terminé') {
    html += '<button onclick="terminerTache('+t.id+',\''+nom+'\')" style="background:rgba(46,204,135,.12);border:1px solid rgba(46,204,135,.3);border-radius:6px;padding:4px 10px;font-size:11px;color:var(--success);cursor:pointer;font-family:Poppins,sans-serif;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:4px">✓ Terminer</button>';
  } else {
    html += '<span style="font-size:11px;color:var(--success);font-weight:600">✓ Terminé</span>';
  }
  if (IS_MANAGER) {
    html += '<button class="act-btn" onclick="editTache('+t.id+')">Modifier</button>';
    html += '<button class="act-btn danger" onclick="delTache('+t.id+',\''+nom+'\')">Suppr.</button>';
  }
  return html;
}

async function terminerTache(id, titre) {
  if (!confirm('Marquer "'+titre+'" comme terminée ?')) return;
  const r = await api({action:'terminer_tache', id, titre});
  if (r.success) {

    toast('Tâche marquée comme terminée — équipe notifiée');
    loadTaches();
    loadDashboard();
  } else toast(r.error||'Erreur','error');
}

async function updateTacheStatut(id, statut) {
  const r = await api({action:'update_tache_statut',id,statut,progression:statut==='Terminé'?100:0});
  if (r.success) { toast('Statut mis à jour'); loadTaches(); }
  else toast(r.error||'Erreur','error');
}

async function editTache(id) {
  const t = await apiGet({action:'get_tache',id});
  if (!t||!t.id) { toast('Erreur','error'); return; }
  document.getElementById('tId').value=t.id;
  document.getElementById('tTitre').value=t.titre||'';
  document.getElementById('tDesc').value=t.description||'';
  document.getElementById('tPrio').value=t.priorite||'Moyenne';
  document.getElementById('tStatut').value=t.statut||'À faire';
  document.getElementById('tDateDebut').value=t.date_debut||'';
  document.getElementById('tDate').value=t.date_echeance||'';
  document.getElementById('tEstim').value=t.estimation_heures||'';
  document.getElementById('modalTacheTitle').textContent='Modifier la tâche';
  const [users,projets] = await Promise.all([api({action:'liste_users'}),api({action:'projets'})]);
  document.getElementById('tAssigne').innerHTML='<option value="">— Non assigné —</option>'+users.map(u=>`<option value="${u.id}"${u.id==t.assigne_a?' selected':''}>${u.nom_complet}</option>`).join('');
  document.getElementById('tProjet').innerHTML='<option value="">— Aucun —</option>'+projets.map(p=>`<option value="${p.id}"${p.id==t.projet_id?' selected':''}>${p.nom}</option>`).join('');
  openModal('modalTache');
}

async function delTache(id, nom) {
  if (!confirm(`Supprimer "${nom}" ?`)) return;
  const r = await api({action:'delete_tache',id});
  if (r.success) { toast('Tâche supprimée'); loadTaches(); }
  else toast(r.error||'Erreur','error');
}

async function saveTache() {
  const id = document.getElementById('tId').value;
  const p = {
    titre:             document.getElementById('tTitre').value,
    description:       document.getElementById('tDesc').value,
    assigne_a:         document.getElementById('tAssigne').value,
    projet_id:         document.getElementById('tProjet').value,
    priorite:          document.getElementById('tPrio').value,
    statut:            document.getElementById('tStatut').value,
    date_debut:        document.getElementById('tDateDebut').value,
    date_echeance:     document.getElementById('tDate').value,
    estimation_heures: document.getElementById('tEstim').value,
  };
  if (!p.titre.trim()) { toast('Titre obligatoire','error'); return; }
  p.action = id ? 'update_tache' : 'create_tache';
  if (id) p.id = id;
  const r = await api(p);
  if (r.success) { closeModal('modalTache'); toast(id?'Tâche mise à jour':'Tâche créée'); loadTaches(); if(!id)loadDashboard(); }
  else toast(r.error||'Erreur','error');
}

// ===== STATS =====
async function loadStats() {
  const s = await api({action:'stats'});
  document.getElementById('statsKpi').innerHTML=[
    {ico:'<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',val:fmtN(s.ca_total||0),label:'CA total (FCFA)',color:'#2ecc87',bg:'rgba(46,204,135,.12)'},
    {ico:'<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>',val:s.contrats_signes||0,label:'Contrats signés',color:'#36A9E1',bg:'rgba(54,169,225,.12)'},
    {ico:'<polyline points="9 11 12 14 22 4"/>',val:s.taches_terminees||0,label:'Tâches terminées',color:'#9b8fff',bg:'rgba(155,143,255,.12)'},
    {ico:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',val:s.equipe||0,label:'Membres équipe',color:'#f0a500',bg:'rgba(240,165,0,.12)'},
  ].map(k=>`<div class="kpi"><div class="kpi-icon" style="background:${k.bg}"><svg viewBox="0 0 24 24" fill="none" stroke="${k.color}" stroke-width="1.8" stroke-linecap="round">${k.ico}</svg></div><div class="kpi-val">${k.val}</div><div class="kpi-label">${k.label}</div></div>`).join('');
  if(charts.taches)charts.taches.destroy();
  charts.taches=new Chart(document.getElementById('chartTaches'),{type:'doughnut',data:{labels:['À faire','En cours','Bloqué','Terminé'],datasets:[{data:[Math.max(0,(s.taches_total||0)-(s.taches_en_cours||0)-(s.taches_bloquees||0)),(s.taches_en_cours||0),(s.taches_bloquees||0),(s.taches_terminees||0)],backgroundColor:['#7a78a0','#36A9E1','#e05252','#2ecc87'],borderColor:'#1a1930',borderWidth:3}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'right',labels:{boxWidth:10,padding:10}}}}});
  if(charts.clients)charts.clients.destroy();
  charts.clients=new Chart(document.getElementById('chartClients'),{type:'doughnut',data:{labels:['Actifs','Prospects'],datasets:[{data:[(s.total_clients||0),(s.total_prospects||0)],backgroundColor:['#2ecc87','#f0a500'],borderColor:'#1a1930',borderWidth:3}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'right',labels:{boxWidth:10,padding:10}}}}});
}

// ===== PROJETS =====
async function loadProjets() {
  const filtre = document.getElementById('projetStatutFilter')?.value||'';
  const data   = await api({action:'projets',...(filtre?{statut:filtre}:{})});
  const SC     = {Prospection:'bg-purple','Devis envoyé':'bg-orange',Signé:'bg-blue','En cours':'bg-blue','En test':'bg-blue',Livré:'bg-green',Clôturé:'bg-muted'};
  const today  = new Date().toISOString().split('T')[0];
  document.getElementById('projetTable').innerHTML = (data||[]).length ? data.map(p=>`
    <tr>
      <td style="font-weight:600;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${p.nom||''}">${p.nom}</td>
      <td style="color:var(--muted)">${p.client_nom||'—'}</td>
      <td style="color:var(--muted);font-size:11px">${p.type_prestation}</td>
      <td><span class="badge ${SC[p.statut]||'bg-blue'}">${p.statut}</span></td>
      <td style="font-size:11px;color:${p.date_livraison&&p.date_livraison<today?'var(--danger)':'var(--muted)'}">${p.date_livraison||'—'}</td>
      <td style="font-family:'Space Mono',monospace;font-size:11px">${p.budget>0?fmtN(p.budget)+' F':'—'}</td>
      ${IS_MANAGER?projetActions(p):''}
    </tr>`).join('')
  : `<tr><td colspan="${IS_MANAGER?7:6}" style="text-align:center;color:var(--muted);padding:20px">Aucun projet</td></tr>`;
}

function projetActions(p) {
  const nom = (p.nom||'').replace(/'/g,"\\'");
  return '<td style="display:flex;gap:4px;flex-wrap:wrap">'
    + '<button class="act-btn" onclick="editProjet('+p.id+')">Modifier</button>'
    + '<button class="act-btn" onclick="dupliquerProjet('+p.id+')">Copier</button>'
    + '<button class="act-btn danger" onclick="delProjet('+p.id+',\''+nom+'\')">Suppr.</button>'
    + '</td>';
}

async function editProjet(id) {
  const p = await apiGet({action:'get_projet',id});
  if (!p||!p.id) { toast('Erreur','error'); return; }
  document.getElementById('pId').value=p.id;
  document.getElementById('pNom').value=p.nom||'';
  document.getElementById('pDesc').value=p.description||'';
  document.getElementById('pStatut').value=p.statut||'Prospection';
  document.getElementById('pPriorite').value=p.priorite||'Moyenne';
  document.getElementById('pBudget').value=p.budget||'';
  document.getElementById('pDateDebut').value=p.date_debut||'';
  document.getElementById('pLiv').value=p.date_livraison||'';
  document.getElementById('pLienDrive').value=p.lien_drive||'';
  document.getElementById('pType').value=p.type_prestation||'Développement web';
  document.getElementById('modalProjetTitle').textContent='Modifier le projet';
  const [clients,users] = await Promise.all([api({action:'clients'}),api({action:'liste_users'})]);
  document.getElementById('pClient').innerHTML='<option value="">— Aucun —</option>'+clients.map(c=>`<option value="${c.id}"${c.id==p.client_id?' selected':''}>${c.raison_sociale}</option>`).join('');
  document.getElementById('pManager').innerHTML='<option value="">— Non assigné —</option>'+users.map(u=>`<option value="${u.id}"${u.id==p.manager_id?' selected':''}>${u.nom_complet}</option>`).join('');
  openModal('modalProjet');
}
async function delProjet(id,nom) {
  if(!confirm(`Supprimer "${nom}" ?`))return;
  const r=await api({action:'delete_projet',id});
  if(r.success){toast('Projet supprimé');loadProjets();}else toast(r.error||'Erreur','error');
}
async function dupliquerProjet(id) {
  if(!confirm('Dupliquer ce projet ?'))return;
  const r=await api({action:'dupliquer_projet',id});
  if(r.success){toast('Projet dupliqué');loadProjets();}else toast(r.error||'Erreur','error');
}
async function saveProjet() {
  const id=document.getElementById('pId').value;
  const p={nom:document.getElementById('pNom').value,client_id:document.getElementById('pClient').value,manager_id:document.getElementById('pManager').value,type_prestation:document.getElementById('pType').value,statut:document.getElementById('pStatut').value,priorite:document.getElementById('pPriorite').value,description:document.getElementById('pDesc').value,lien_drive:document.getElementById('pLienDrive').value,date_debut:document.getElementById('pDateDebut').value,date_livraison:document.getElementById('pLiv').value,budget:document.getElementById('pBudget').value};
  if(!p.nom.trim()){toast('Nom obligatoire','error');return;}
  p.action=id?'update_projet':'create_projet';if(id)p.id=id;
  const r=await api(p);
  if(r.success){closeModal('modalProjet');toast(id?'Projet mis à jour':'Projet créé');loadProjets();}else toast(r.error||'Erreur','error');
}

// ===== CLIENTS =====
async function loadClients() {
  const data=await api({action:'clients'});
  const SC={'Client actif':'bg-green',Prospect:'bg-orange','Client inactif':'bg-muted'};
  document.getElementById('clientTable').innerHTML=(data||[]).length?data.map(c=>`
    <tr>
      <td style="font-weight:600">${c.raison_sociale}</td>
      <td style="color:var(--muted)">${c.type}</td>
      <td><span class="badge ${SC[c.statut]||'bg-blue'}">${c.statut}</span></td>
      <td style="color:var(--muted)">${c.contact_nom||'—'}</td>
      <td style="color:var(--muted)">${c.telephone||'—'}</td>
      <td style="color:var(--muted)">${c.pays}</td>
      ${IS_MANAGER?`<td style="display:flex;gap:4px">
        <button class="act-btn" onclick="editClient(${c.id})">Modifier</button>
        <button class="act-btn danger" onclick="delClient(${c.id},'${c.raison_sociale.replace(/'/g,"\\'")}')">Suppr.</button>
      </td>`:''}
    </tr>`).join('')
  : `<tr><td colspan="${IS_MANAGER?7:6}" style="text-align:center;color:var(--muted);padding:20px">Aucun client</td></tr>`;
}

function clientActions(cli) {
  const nom = (cli.raison_sociale||'').replace(/'/g,"\\'");
  return '<td style="display:flex;gap:4px">'
    + '<button class="act-btn" onclick="editClient('+cli.id+')">Modifier</button>'
    + '<button class="act-btn danger" onclick="delClient('+cli.id+',\''+nom+'\')">Suppr.</button>'
    + '</td>';
}

async function editClient(id) {
  const c=await apiGet({action:'get_client',id});
  if(!c||!c.id){toast('Erreur','error');return;}
  const fields=[['cId','id'],['cNom','raison_sociale'],['cSecteur','secteur'],['cContact','contact_nom'],['cTel','telephone'],['cEmail','email'],['cPays','pays'],['cAdresse','adresse'],['cSiteWeb','site_web'],['cNotes','notes']];
  fields.forEach(([el,key])=>{const e=document.getElementById(el);if(e)e.value=c[key]||'';});
  document.getElementById('cType').value=c.type||'Entreprise';
  document.getElementById('cStatut').value=c.statut||'Prospect';
  document.getElementById('modalClientTitle').textContent='Modifier le client';
  openModal('modalClient');
}
async function delClient(id,nom) {
  if(!confirm('Supprimer "'+nom+'" ?'))return;
  const r=await api({action:'delete_client',id});
  if(r.needs_confirm){if(!confirm(r.error+'\nForcer ?'))return;const r2=await api({action:'delete_client',id,force:1});if(r2.success){toast('Client supprimé');loadClients();}else toast(r2.error||'Erreur','error');return;}
  if(r.success){toast('Client supprimé');loadClients();}else toast(r.error||'Erreur','error');
}
async function saveClient() {
  const id=document.getElementById('cId').value;
  const p={raison_sociale:document.getElementById('cNom').value,type:document.getElementById('cType').value,statut:document.getElementById('cStatut').value,secteur:document.getElementById('cSecteur').value,contact_nom:document.getElementById('cContact').value,telephone:document.getElementById('cTel').value,email:document.getElementById('cEmail').value,pays:document.getElementById('cPays').value,adresse:document.getElementById('cAdresse').value,site_web:document.getElementById('cSiteWeb').value,notes:document.getElementById('cNotes').value};
  if(!p.raison_sociale.trim()){toast('Raison sociale obligatoire','error');return;}
  p.action=id?'update_client':'create_client';if(id)p.id=id;
  const r=await api(p);
  if(r.success){closeModal('modalClient');toast(id?'Client mis à jour':'Client ajouté');loadClients();}else toast(r.error||'Erreur','error');
}

// ===== CHARGE =====
async function loadCharge() {
  const data=await api({action:'charge_travail'});
  if(!data||!data.length){document.getElementById('chargeList').innerHTML='<div style="color:var(--muted);font-size:12px;padding:16px;text-align:center">Aucun collaborateur actif</div>';return;}
  const max=Math.max(...data.map(u=>parseInt(u.nb_taches)||0),1);
  document.getElementById('chargeList').innerHTML=data.map(u=>`
    <div class="cw-item">
      <div class="cw-av">${(u.nom||'?').split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()}</div>
      <div style="flex:1;min-width:0">
        <div class="cw-name">${u.nom}</div>
        <div class="cw-sub">${u.poste||'—'} · ${parseInt(u.taches_haute)>0?u.taches_haute+' haute(s)':'aucune haute'}</div>
      </div>
      <div class="cw-bar-wrap"><div class="cw-bar-fill" style="width:${Math.round(((parseInt(u.nb_taches)||0)/max)*100)}%;background:${parseInt(u.nb_taches)>5?'var(--danger)':parseInt(u.nb_taches)>3?'var(--warning)':'var(--accent)'}"></div></div>
      <div class="cw-count">${u.nb_taches}</div>
    </div>`).join('');
}

// ===== MODALS =====
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){
  document.getElementById(id).classList.remove('open');
  const hid=document.querySelector('#'+id+' input[type=hidden]');if(hid)hid.value='';
  const h3=document.querySelector('#'+id+' .modal-head h3');
  const defaults={modalTache:'Nouvelle tâche',modalProjet:'Nouveau projet',modalClient:'Nouveau client'};
  if(h3&&defaults[id])h3.textContent=defaults[id];
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');}));

async function openModalTache(){
  closeModal('modalTache');
  ['tTitre','tDesc','tDateDebut','tEstim'].forEach(id=>{const e=document.getElementById(id);if(e)e.value='';});
  document.getElementById('tId').value='';
  document.getElementById('tPrio').value='Moyenne';
  document.getElementById('tStatut').value='À faire';
  document.getElementById('tDate').value=new Date(Date.now()+7*86400000).toISOString().split('T')[0];
  const [users,projets]=await Promise.all([api({action:'liste_users'}),api({action:'projets'})]);
  document.getElementById('tAssigne').innerHTML='<option value="">— Non assigné —</option>'+users.map(u=>`<option value="${u.id}">${u.nom_complet}</option>`).join('');
  document.getElementById('tProjet').innerHTML='<option value="">— Aucun —</option>'+projets.map(p=>`<option value="${p.id}">${p.nom}</option>`).join('');
  openModal('modalTache');
}
async function openModalProjet(){
  closeModal('modalProjet');
  ['pNom','pDesc','pBudget','pLiv','pLienDrive'].forEach(id=>{const e=document.getElementById(id);if(e)e.value='';});
  document.getElementById('pId').value='';
  document.getElementById('pDateDebut').value=new Date().toISOString().split('T')[0];
  document.getElementById('pStatut').value='Prospection';
  document.getElementById('pPriorite').value='Moyenne';
  const [clients,users]=await Promise.all([api({action:'clients'}),api({action:'liste_users'})]);
  document.getElementById('pClient').innerHTML='<option value="">— Aucun —</option>'+clients.map(c=>`<option value="${c.id}">${c.raison_sociale}</option>`).join('');
  document.getElementById('pManager').innerHTML='<option value="">— Non assigné —</option>'+users.map(u=>`<option value="${u.id}">${u.nom_complet}</option>`).join('');
  openModal('modalProjet');
}
function openModalClient(){
  closeModal('modalClient');
  ['cNom','cSecteur','cContact','cTel','cEmail','cAdresse','cSiteWeb','cNotes'].forEach(id=>{const e=document.getElementById(id);if(e)e.value='';});
  document.getElementById('cId').value='';
  document.getElementById('cPays').value='Togo';
  document.getElementById('cType').value='Entreprise';
  document.getElementById('cStatut').value='Prospect';
  openModal('modalClient');
}
function openModalUser(){openModal('modalUser');}
function openAdd(){
  if(currentSection==='projets')openModalProjet();
  else if(currentSection==='clients')openModalClient();
  else openModalTache();
}
async function saveUser(){
  const btn=document.getElementById('btnCreateUser');btn.textContent='Création…';btn.disabled=true;
  const r=await api({action:'create_user',prenom:document.getElementById('uPrenom').value,nom:document.getElementById('uNom').value,email:document.getElementById('uEmail').value,password:document.getElementById('uPass').value,role:document.getElementById('uRole').value,poste:document.getElementById('uPoste')?.value||''});
  btn.textContent='Créer et envoyer email';btn.disabled=false;
  if(r.success){closeModal('modalUser');toast(r.message||'Compte créé');}else toast(r.error||'Erreur','error');
}

// ===== NOTIFICATIONS =====
async function loadNotifs(){
  try{
    const r=await api({action:'notifs'});
    const dot=document.getElementById('notifDot');
    const list=document.getElementById('notifList');
    if(!r||!r.length){if(dot)dot.style.display='none';if(list)list.innerHTML='<div class="notif-empty">Aucune notification</div>';return;}
    if(dot)dot.style.display='block';
    if(list)list.innerHTML=r.map(n=>'<div class="notif-item" onclick="clickNotif('+n.id+',\''+( n.lien||'').replace(/'/g,"\\'")+'\')"><div>'+n.message+'</div><div class="notif-time">'+(n.created_at||'')+'</div></div>').join('');
  }catch(e){}
}
function toggleNotifs(){
  const p=document.getElementById('notifPanel');
  p.classList.toggle('open');
  if(p.classList.contains('open'))markAllRead();
}
async function markAllRead(){
  try{await api({action:'read_notifs'});document.getElementById('notifDot').style.display='none';}catch(e){}
}
async function clickNotif(id,lien){
  try{await api({action:'marquer_notif_lue',id});}catch(e){}
  document.getElementById('notifPanel').classList.remove('open');
  if(lien)window.location.href=lien;
}
document.addEventListener('click',e=>{
  if(!e.target.closest('#notifBtn')&&!e.target.closest('#notifPanel'))
    document.getElementById('notifPanel')?.classList.remove('open');
});
setInterval(loadNotifs,30000);

// ===== RECHERCHE =====
function openSearch(){
  document.getElementById('searchOverlay').classList.add('open');
  document.getElementById('searchInput').value='';
  document.getElementById('searchResults').innerHTML='<div style="padding:16px;text-align:center;color:var(--muted);font-size:12px">Tapez pour rechercher…</div>';
  setTimeout(()=>document.getElementById('searchInput').focus(),50);
}
function closeSearch(){document.getElementById('searchOverlay').classList.remove('open');}
document.getElementById('searchOverlay').addEventListener('click',e=>{if(e.target===document.getElementById('searchOverlay'))closeSearch();});
document.addEventListener('keydown',e=>{
  if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();openSearch();}
  if(e.key==='Escape'&&document.getElementById('searchOverlay').classList.contains('open'))closeSearch();
});
function debounce(fn,ms){let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};}
document.getElementById('searchInput').addEventListener('input',debounce(async function(){
  const q=this.value.trim();
  const res=document.getElementById('searchResults');
  if(!q||q.length<2){res.innerHTML='<div style="padding:16px;text-align:center;color:var(--muted);font-size:12px">Tapez au moins 2 caractères…</div>';return;}
  res.innerHTML='<div style="padding:16px;text-align:center;color:var(--muted);font-size:12px">Recherche…</div>';
  const ql=q.toLowerCase();
  const [projets,clients,taches]=await Promise.all([api({action:'projets'}),api({action:'clients'}),api({action:'mes_taches'})]);
  const results=[];
  (projets||[]).filter(p=>(p.nom||'').toLowerCase().includes(ql)).slice(0,3).forEach(p=>results.push({type:'Projet',col:'#36A9E1',label:p.nom,sub:p.statut,fn:()=>{closeSearch();show('projets');}}));
  (clients||[]).filter(c=>(c.raison_sociale||'').toLowerCase().includes(ql)).slice(0,3).forEach(c=>results.push({type:'Client',col:'#2ecc87',label:c.raison_sociale,sub:c.statut,fn:()=>{closeSearch();show('clients');}}));
  (taches||[]).filter(t=>(t.titre||'').toLowerCase().includes(ql)).slice(0,3).forEach(t=>results.push({type:'Tâche',col:'#f0a500',label:t.titre,sub:t.statut,fn:()=>{closeSearch();show('taches');}}));
  if(!results.length){res.innerHTML='<div style="padding:16px;text-align:center;color:var(--muted);font-size:12px">Aucun résultat</div>';return;}
  window._sf=results.map(r=>r.fn);
  res.innerHTML=results.map((r,i)=>'<div class="search-result-item" onclick="window._sf['+i+']()">'
    +'<span class="sr-type" style="background:'+r.col+'22;color:'+r.col+'">'+r.type+'</span>'
    +'<div style="flex:1;min-width:0"><div class="sr-label">'+r.label+'</div><div class="sr-sub">'+r.sub+'</div></div>'
    +'</div>').join('');
},280));

// ===== HELPERS =====
function fmtN(n){return parseInt(n||0).toLocaleString('fr-FR');}
function fmtK(n){return n>=1000000?(n/1000000).toFixed(1)+'M':n>=1000?(n/1000).toFixed(0)+'K':Math.round(n)+'';}

// ===== INIT =====
loadDashboard();
setInterval(()=>{if(currentSection==='taches')loadTaches();},30000);
</script>
</body>
</html>
