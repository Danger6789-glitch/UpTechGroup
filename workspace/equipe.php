<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
if (!isAdmin()) { header('Location: dashboard.php'); exit; }
$user = currentUser();
$db   = getDB();

// ===== PERMISSIONS INTÉGRÉES (pas besoin de permissions.php externe) =====
function getAllModules(): array {
    return [
        'dashboard'   => ['label'=>'Tableau de bord',  'group'=>'Principal', 'desc'=>'Vue générale, KPIs, alertes'],
        'taches'      => ['label'=>'Mes tâches',        'group'=>'Principal', 'desc'=>'Voir et gérer ses propres tâches'],
        'calendrier'  => ['label'=>'Calendrier',        'group'=>'Principal', 'desc'=>'Événements et planification'],
        'chat'        => ['label'=>'Messages',          'group'=>'Principal', 'desc'=>'Chat interne équipe'],
        'fichiers'    => ['label'=>'Fichiers',          'group'=>'Principal', 'desc'=>'Accès aux fichiers projet'],
        'temps'       => ['label'=>'Suivi du temps',    'group'=>'Principal', 'desc'=>'Timer et saisie des heures'],
        'assistant'   => ['label'=>'Assistant IA',      'group'=>'Principal', 'desc'=>"Accès à l'assistant intelligent"],
        'projets'     => ['label'=>'Projets',           'group'=>'Gestion',   'desc'=>'Voir et gérer les projets'],
        'clients'     => ['label'=>'Clients',           'group'=>'Gestion',   'desc'=>'Voir et gérer les clients'],
        'crm'         => ['label'=>'CRM',               'group'=>'Gestion',   'desc'=>'Interactions et opportunités clients'],
        'finances'    => ['label'=>'Finances',          'group'=>'Gestion',   'desc'=>'Trésorerie et opérations'],
        'facturation' => ['label'=>'Facturation',       'group'=>'Gestion',   'desc'=>'Devis, factures et avoirs'],
        'charge'      => ['label'=>'Charge de travail', 'group'=>'Gestion',   'desc'=>'Vue charge équipe'],
        'rapports'    => ['label'=>'Rapport PDF',       'group'=>'Rapports',  'desc'=>'Rapports mensuels'],
        'export'      => ['label'=>'Export CSV',        'group'=>'Rapports',  'desc'=>'Exporter les données'],
        'stats'       => ['label'=>'Statistiques',      'group'=>'Rapports',  'desc'=>'Graphiques et indicateurs'],
    ];
}

function getPerms(PDO $db, int $userId, string $role): array {
    if (in_array($role, ['admin','manager'])) return array_keys(getAllModules());
    try {
        $s = $db->prepare("SELECT module FROM user_permissions WHERE user_id=? AND peut_voir=1");
        $s->execute([$userId]);
        $p = $s->fetchAll(PDO::FETCH_COLUMN);
        return $p ?: ['dashboard','taches','calendrier','chat','temps'];
    } catch(Exception $e) {
        return ['dashboard','taches','calendrier','chat','temps'];
    }
}

function savePerms(PDO $db, int $userId, array $modules): bool {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS user_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            module VARCHAR(50) NOT NULL,
            peut_voir TINYINT(1) DEFAULT 1,
            UNIQUE KEY uk_user_module (user_id,module),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$userId]);
        if (!empty($modules)) {
            $s = $db->prepare("INSERT INTO user_permissions (user_id,module,peut_voir) VALUES (?,?,1)");
            $allMods = array_keys(getAllModules());
            foreach ($modules as $m) {
                if (in_array($m, $allMods)) $s->execute([$userId, $m]);
            }
        }
        return true;
    } catch(Exception $e) { return false; }
}

// Récupérer utilisateurs
try {
    $users = $db->query("SELECT id,prenom,nom,email,role,actif,poste,DATE_FORMAT(last_login,'%d/%m/%Y à %H:%i') as last_login_fmt FROM users ORDER BY created_at DESC")->fetchAll();
} catch(Exception $e) { $users = []; }

$allModules = getAllModules();
$groups = [];
foreach ($allModules as $key => $mod) {
    $groups[$mod['group']][$key] = $mod;
}

$presets = [
    'commercial'  => ['label'=>'Commercial',          'modules'=>['dashboard','taches','calendrier','chat','clients','crm']],
    'cm'          => ['label'=>'Community Manager',   'modules'=>['dashboard','taches','calendrier','chat','fichiers','assistant']],
    'dev'         => ['label'=>'Développeur',         'modules'=>['dashboard','taches','calendrier','chat','fichiers','temps','projets','assistant']],
    'finance'     => ['label'=>'Responsable Finance', 'modules'=>['dashboard','taches','calendrier','chat','finances','facturation','rapports','export','stats']],
    'manager_gen' => ['label'=>'Manager général',     'modules'=>['dashboard','taches','calendrier','chat','fichiers','temps','projets','clients','crm','charge','stats']],
    'all'         => ['label'=>'Accès complet',       'modules'=>array_keys($allModules)],
    'minimal'     => ['label'=>'Accès minimal',       'modules'=>['dashboard','taches','calendrier','chat']],
];
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
<title>Équipe & Accès — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--warning:#f0a500;--danger:#e05252;--purple:#9b8fff;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 0% 0%,rgba(41,35,92,0.5) 0%,transparent 60%);pointer-events:none;}
.topbar{position:sticky;top:0;z-index:100;background:rgba(19,18,42,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);height:56px;display:flex;align-items:center;padding:0 24px;gap:16px;}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;transition:color .2s;}
.back-btn:hover{color:var(--accent);}
.back-btn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.topbar-title{flex:1;font-size:15px;font-weight:700;color:#fff;}
.add-btn{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:8px;padding:0 18px;height:34px;color:#fff;font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;cursor:pointer;}
.page{max-width:1100px;margin:0 auto;padding:24px 20px 60px;position:relative;z-index:1;}
.layout{display:grid;grid-template-columns:300px 1fr;gap:20px;}
/* USER LIST */
.list-head{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;}
.user-list{display:flex;flex-direction:column;gap:6px;}
.user-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:12px 14px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:10px;}
.user-card:hover{border-color:rgba(54,169,225,.3);}
.user-card.active{border-color:var(--accent);background:rgba(54,169,225,.08);}
.uav{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;}
.uav.off{background:var(--bg3);color:var(--muted);}
.uinfo{flex:1;min-width:0;}
.uname{font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.usub{font-size:10px;color:var(--muted);margin-top:1px;}
.ubadges{display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;}
.badge{display:inline-flex;align-items:center;padding:2px 7px;border-radius:99px;font-size:9px;font-weight:700;}
.bg-green{background:rgba(46,204,135,.15);color:var(--success);}
.bg-red{background:rgba(224,82,82,.15);color:var(--danger);}
.bg-blue{background:rgba(54,169,225,.15);color:var(--accent);}
.bg-orange{background:rgba(240,165,0,.15);color:var(--warning);}
.role-admin{background:rgba(224,82,82,.2);color:#f08080;}
.role-manager{background:rgba(54,169,225,.2);color:var(--accent);}
.role-collaborateur{background:rgba(46,204,135,.2);color:var(--success);}
.pct{font-size:10px;color:var(--accent);font-weight:700;margin-left:auto;flex-shrink:0;white-space:nowrap;}
/* DETAIL */
.detail{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
.detail-head{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;}
.dav{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;}
.dname{font-size:17px;font-weight:700;color:#fff;}
.dsub{font-size:12px;color:var(--muted);margin-top:3px;}
.detail-body{padding:20px 24px;}
/* TABS */
.tabs{display:flex;gap:3px;background:var(--bg3);border-radius:10px;padding:3px;margin-bottom:20px;}
.ptab{flex:1;padding:8px;border-radius:8px;font-size:12px;font-weight:500;color:var(--muted);cursor:pointer;border:none;background:none;font-family:'Poppins',sans-serif;transition:all .2s;text-align:center;}
.ptab.active{background:var(--card);color:#fff;}
.ptab-panel{display:none;}
.ptab-panel.active{display:block;}
/* FORM */
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
.field{margin-bottom:0;}
label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;display:block;margin-bottom:5px;}
input,select{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:9px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:border-color .2s;-webkit-appearance:none;}
input:focus,select:focus{border-color:var(--accent);}
input:disabled{opacity:.4;cursor:not-allowed;}
select option{background:var(--bg2);}
.role-note{font-size:12px;color:var(--muted);padding:10px 0 14px;}
.last-login-lbl{font-size:11px;color:var(--muted);margin-bottom:4px;}
.last-login-val{font-size:13px;color:var(--text);}
.save-row{display:flex;gap:10px;margin-top:18px;padding-top:14px;border-top:1px solid var(--border);}
/* PERMISSIONS */
.preset-bar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;}
.preset-btn{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:5px 12px;font-size:11px;font-weight:600;color:var(--muted);cursor:pointer;font-family:'Poppins',sans-serif;transition:all .15s;}
.preset-btn:hover{border-color:var(--accent);color:var(--accent);}
.perm-notice{font-size:12px;color:var(--muted);margin-bottom:14px;padding:10px 14px;background:rgba(54,169,225,.06);border:1px solid rgba(54,169,225,.12);border-radius:8px;}
.perm-group{margin-bottom:14px;}
.perm-group-title{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;display:flex;align-items:center;gap:8px;}
.perm-group-title::after{content:'';flex:1;height:1px;background:var(--border);}
.perm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:6px;}
.perm-item{display:flex;align-items:flex-start;gap:8px;padding:9px 11px;background:var(--bg3);border:1px solid transparent;border-radius:9px;cursor:pointer;transition:all .2s;user-select:none;}
.perm-item:hover{border-color:rgba(54,169,225,.2);}
.perm-item.checked{background:rgba(54,169,225,.08);border-color:rgba(54,169,225,.25);}
.perm-item.locked{opacity:.5;pointer-events:none;}
.pchk{width:16px;height:16px;border-radius:4px;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;transition:all .2s;}
.perm-item.checked .pchk{background:var(--accent);border-color:var(--accent);}
.pchk svg{width:10px;height:10px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;opacity:0;transition:opacity .2s;}
.perm-item.checked .pchk svg{opacity:1;}
.ptext{flex:1;}
.pname{font-size:11px;font-weight:600;color:#fff;}
.pdesc{font-size:10px;color:var(--muted);margin-top:1px;line-height:1.4;}
/* SECURITE */
.pass-wrap{position:relative;}
.pass-wrap input{padding-right:80px;}
.gen-btn{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:rgba(54,169,225,.15);border:1px solid rgba(54,169,225,.2);border-radius:6px;padding:3px 8px;color:var(--accent);font-size:10px;font-weight:700;cursor:pointer;font-family:'Poppins',sans-serif;}
.danger-zone{background:rgba(224,82,82,.05);border:1px solid rgba(224,82,82,.15);border-radius:12px;padding:16px;margin-top:16px;}
.danger-title{font-size:13px;font-weight:700;color:var(--danger);margin-bottom:10px;}
.action-row{display:flex;gap:8px;flex-wrap:wrap;}
/* BUTTONS */
.btn-p{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:9px;padding:9px 22px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-s{background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:9px 16px;color:var(--muted);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}
.btn-d{background:rgba(224,82,82,.1);border:1px solid rgba(224,82,82,.2);border-radius:9px;padding:9px 16px;color:var(--danger);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}
.btn-o{background:rgba(240,165,0,.1);border:1px solid rgba(240,165,0,.2);border-radius:9px;padding:9px 16px;color:var(--warning);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}
/* EMPTY */
.empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;color:var(--muted);text-align:center;}
.empty-state svg{width:48px;height:48px;fill:none;stroke:currentColor;stroke-width:1.2;stroke-linecap:round;opacity:.2;margin-bottom:14px;}
/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1000;align-items:center;justify-content:center;padding:16px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;width:100%;max-width:560px;max-height:92vh;overflow-y:auto;}
.modal-head{padding:18px 22px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1;}
.modal-head h3{font-size:15px;font-weight:700;color:#fff;}
.modal-close{background:var(--bg3);border:1px solid var(--border);border-radius:8px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);}
.modal-close svg{width:11px;height:11px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;}
.modal-body{padding:18px 22px;}
.modal-foot{padding:12px 22px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border);}
.new-perm-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;margin-top:8px;}
.npm-item{display:flex;align-items:center;gap:6px;padding:6px 8px;background:var(--bg3);border-radius:7px;cursor:pointer;border:1px solid transparent;transition:all .15s;}
.npm-item.on{background:rgba(54,169,225,.08);border-color:rgba(54,169,225,.25);}
.npm-chk{width:13px;height:13px;border-radius:3px;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s;}
.npm-item.on .npm-chk{background:var(--accent);border-color:var(--accent);}
.npm-chk svg{width:8px;height:8px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;opacity:0;}
.npm-item.on .npm-chk svg{opacity:1;}
.npm-lbl{font-size:10px;color:var(--text);}
/* TOAST */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:10px 20px;font-size:13px;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;}
#toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
#toast.success{border-color:rgba(46,204,135,.4);color:var(--success);}
#toast.error{border-color:rgba(224,82,82,.4);color:var(--danger);}
@media(max-width:900px){.layout{grid-template-columns:1fr;}.new-perm-grid{grid-template-columns:repeat(2,1fr);}}
</style>
</head>
<body>
<div class="topbar">
  <a class="back-btn" href="dashboard.php"><svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>Workspace</a>
  <div class="topbar-title">Équipe & Accès</div>
  <button class="add-btn" onclick="openNew()">+ Nouveau collaborateur</button>
</div>

<div class="page">
  <div class="layout">

    <!-- LISTE -->
    <div>
      <div class="list-head"><?= count($users) ?> membre(s)</div>
      <div class="user-list">
        <?php foreach($users as $u):
          $p = getPerms($db, $u['id'], $u['role']);
          $ini = strtoupper(substr($u['prenom'],0,1).substr($u['nom'],0,1));
          $fixed = in_array($u['role'],['admin','manager']);
        ?>
        <div class="user-card" onclick="loadUser(<?= $u['id'] ?>)" id="card-<?= $u['id'] ?>">
          <div class="uav <?= !$u['actif']?'off':'' ?>"><?= $ini ?></div>
          <div class="uinfo">
            <div class="uname"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
            <div class="usub"><?= htmlspecialchars($u['poste']??$u['email']) ?></div>
            <div class="ubadges">
              <span class="badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span>
              <?php if(!$u['actif']): ?><span class="badge bg-red">Inactif</span><?php endif; ?>
            </div>
          </div>
          <div class="pct"><?= $fixed?'Tout':count($p).' mod.' ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- DETAIL -->
    <div class="detail" id="detailPanel">
      <div class="empty-state" id="emptyState">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <p>Sélectionnez un membre pour modifier ses accès</p>
      </div>
      <div id="userDetail" style="display:none">
        <div class="detail-head">
          <div class="dav" id="dAv"></div>
          <div><div class="dname" id="dName"></div><div class="dsub" id="dSub"></div></div>
        </div>
        <div class="detail-body">
          <div class="tabs">
            <button class="ptab active" onclick="tab('infos',this)">Informations</button>
            <button class="ptab" onclick="tab('perms',this)">Permissions</button>
            <button class="ptab" onclick="tab('securite',this)">Sécurité</button>
          </div>

          <!-- INFOS -->
          <div class="ptab-panel active" id="ptab-infos">
            <input type="hidden" id="eId">
            <div class="fgrid">
              <div class="field"><label>Prénom *</label><input id="ePrenom"></div>
              <div class="field"><label>Nom *</label><input id="eNom"></div>
              <div class="field"><label>Email</label><input id="eEmail" disabled></div>
              <div class="field"><label>Poste</label><input id="ePoste" placeholder="Ex: Développeur web"></div>
            </div>
            <div class="field" style="margin-bottom:12px"><label>Rôle</label>
              <select id="eRole" onchange="onRole()">
                <option value="collaborateur">Collaborateur</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="role-note" id="roleNote"></div>
            <div class="last-login-lbl">Dernière connexion</div>
            <div class="last-login-val" id="dLogin"></div>
            <div class="save-row">
              <button class="btn-p" onclick="saveInfos()">Enregistrer</button>
            </div>
          </div>

          <!-- PERMISSIONS -->
          <div class="ptab-panel" id="ptab-perms">
            <div class="perm-notice" id="permNotice"></div>
            <div style="margin-bottom:14px">
              <div style="font-size:11px;font-weight:600;color:var(--muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.8px">Profil type</div>
              <div class="preset-bar">
                <?php foreach($presets as $k=>$p): ?>
                <button class="preset-btn" onclick="applyPreset('<?= $k ?>')"><?= $p['label'] ?></button>
                <?php endforeach; ?>
              </div>
            </div>
            <?php foreach($groups as $gname=>$mods): ?>
            <div class="perm-group">
              <div class="perm-group-title"><?= $gname ?></div>
              <div class="perm-grid">
                <?php foreach($mods as $key=>$mod): ?>
                <div class="perm-item" id="pi-<?= $key ?>" onclick="togglePerm('<?= $key ?>')">
                  <div class="pchk"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                  <div class="ptext"><div class="pname"><?= $mod['label'] ?></div><div class="pdesc"><?= $mod['desc'] ?></div></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
            <div class="save-row">
              <button class="btn-s" onclick="selAll()">Tout</button>
              <button class="btn-s" onclick="selNone()">Aucun</button>
              <button class="btn-p" onclick="savePerms()">Enregistrer les accès</button>
            </div>
          </div>

          <!-- SECURITE -->
          <div class="ptab-panel" id="ptab-securite">
            <div class="field" style="margin-bottom:14px">
              <label>Nouveau mot de passe</label>
              <div class="pass-wrap">
                <input type="text" id="newPass" placeholder="Laissez vide pour ne pas changer" autocomplete="off">
                <button class="gen-btn" onclick="genPass()">Générer</button>
              </div>
            </div>
            <button class="btn-p" onclick="resetPass()" style="margin-bottom:20px">Mettre à jour et envoyer email</button>
            <div class="danger-zone">
              <div class="danger-title">Zone sensible</div>
              <div class="action-row">
                <button class="btn-o" id="btnToggle" onclick="toggleActive()"></button>
                <button class="btn-d" id="btnDel" onclick="delUser()">Supprimer le compte</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL NOUVEAU -->
<div class="modal-overlay" id="modalNew">
  <div class="modal">
    <div class="modal-head"><h3>Nouveau collaborateur</h3><div class="modal-close" onclick="closeNew()"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div></div>
    <div class="modal-body">
      <div class="fgrid" style="margin-bottom:12px">
        <div class="field"><label>Prénom *</label><input id="nPre"></div>
        <div class="field"><label>Nom *</label><input id="nNom"></div>
      </div>
      <div class="field" style="margin-bottom:12px"><label>Email *</label><input type="email" id="nEmail"></div>
      <div class="fgrid" style="margin-bottom:12px">
        <div class="field"><label>Mot de passe *</label>
          <div class="pass-wrap"><input type="text" id="nPass" placeholder="Min. 8 caractères" autocomplete="off"><button class="gen-btn" onclick="genNewPass()">Générer</button></div>
        </div>
        <div class="field"><label>Poste</label><input id="nPoste" placeholder="Ex: CM, Dev…"></div>
      </div>
      <div class="field" style="margin-bottom:14px"><label>Rôle</label>
        <select id="nRole" onchange="onNewRole()">
          <option value="collaborateur">Collaborateur</option>
          <option value="manager">Manager</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div id="nPermsWrap">
        <div style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Accès accordés</div>
        <div class="preset-bar" style="margin-bottom:8px">
          <?php foreach($presets as $k=>$p): ?>
          <button class="preset-btn" style="font-size:10px;padding:4px 9px" onclick="applyNewPreset('<?= $k ?>')"><?= $p['label'] ?></button>
          <?php endforeach; ?>
        </div>
        <div class="new-perm-grid" id="nPermGrid">
          <?php foreach($allModules as $key=>$mod): ?>
          <div class="npm-item" id="npm-<?= $key ?>" onclick="toggleNpm('<?= $key ?>')">
            <div class="npm-chk"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
            <span class="npm-lbl"><?= $mod['label'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div id="nRoleNote" style="display:none;font-size:12px;color:var(--muted);padding:10px;background:rgba(54,169,225,.06);border-radius:8px;border:1px solid rgba(54,169,225,.12);margin-top:8px">
        Les managers et admins ont automatiquement accès à tous les modules.
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-s" onclick="closeNew()">Annuler</button>
      <button class="btn-p" id="btnCreate" onclick="createUser()">Créer et envoyer email</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const PRESETS = <?= json_encode($presets) ?>;
const ALL_MODULES = <?= json_encode(array_keys($allModules)) ?>;
const USERS_DATA = <?= json_encode(array_map(fn($u) => [
  'id'      => $u['id'],
  'prenom'  => $u['prenom'],
  'nom'     => $u['nom'],
  'email'   => $u['email'],
  'role'    => $u['role'],
  'poste'   => $u['poste'] ?? '',
  'actif'   => (int)$u['actif'],
  'login'   => $u['last_login_fmt'] ?? 'Jamais connecté',
  'perms'   => getPerms($db, $u['id'], $u['role']),
], $users)) ?>;
const ME_ID = <?= $user['id'] ?>;

let curId = null, curPerms = [], newPerms = ['dashboard','taches','calendrier','chat','temps'];

async function api(p){const fd=new FormData();Object.entries(p).forEach(([k,v])=>{if(v!=null)fd.append(k,v);});const r=await fetch('api.php',{method:'POST',body:fd});return r.json();}
async function permsApi(p){const fd=new FormData();Object.entries(p).forEach(([k,v])=>fd.append(k,v));try{const r=await fetch('permissions_api.php',{method:'POST',body:fd});return r.json();}catch(e){return{success:false,error:'permissions_api.php introuvable — upload requis'};}}
function toast(msg,type='success'){const t=document.getElementById('toast');t.textContent=msg;t.className='show '+type;setTimeout(()=>t.className='',4000);}

function loadUser(id){
  curId=id;
  document.querySelectorAll('.user-card').forEach(c=>c.classList.remove('active'));
  document.getElementById('card-'+id)?.classList.add('active');
  const u=USERS_DATA.find(x=>x.id==id);if(!u)return;
  document.getElementById('emptyState').style.display='none';
  document.getElementById('userDetail').style.display='block';
  const ini=u.prenom[0].toUpperCase()+u.nom[0].toUpperCase();
  document.getElementById('dAv').textContent=ini;
  document.getElementById('dName').textContent=u.prenom+' '+u.nom;
  document.getElementById('dSub').textContent=(u.poste||u.email)+' · '+u.role;
  document.getElementById('eId').value=u.id;
  document.getElementById('ePrenom').value=u.prenom;
  document.getElementById('eNom').value=u.nom;
  document.getElementById('eEmail').value=u.email;
  document.getElementById('ePoste').value=u.poste||'';
  document.getElementById('eRole').value=u.role;
  document.getElementById('dLogin').textContent=u.login;
  document.getElementById('btnToggle').textContent=u.actif?'Désactiver le compte':'Activer le compte';
  document.getElementById('btnDel').style.display=u.id==1?'none':'';
  curPerms=[...u.perms];
  renderPerms(u.role);
  onRole();
}

function tab(name,btn){
  document.querySelectorAll('.ptab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.ptab').forEach(t=>t.classList.remove('active'));
  document.getElementById('ptab-'+name).classList.add('active');
  btn.classList.add('active');
}

function renderPerms(role){
  const locked=role==='admin'||role==='manager';
  document.getElementById('permNotice').textContent=locked
    ? 'Admins et managers ont accès à tous les modules automatiquement. Passez en "Collaborateur" pour restreindre.'
    : 'Cochez les modules que ce collaborateur peut voir dans sa sidebar.';
  ALL_MODULES.forEach(k=>{
    const el=document.getElementById('pi-'+k);if(!el)return;
    const on=locked||curPerms.includes(k);
    el.classList.toggle('checked',on);
    el.classList.toggle('locked',locked);
  });
}

function togglePerm(k){
  const u=USERS_DATA.find(x=>x.id==curId);
  if(!u||u.role==='admin'||u.role==='manager')return;
  if(curPerms.includes(k))curPerms=curPerms.filter(p=>p!==k);
  else curPerms.push(k);
  document.getElementById('pi-'+k).classList.toggle('checked',curPerms.includes(k));
}

function applyPreset(k){
  const u=USERS_DATA.find(x=>x.id==curId);
  if(!u||u.role!=='collaborateur'){toast('Uniquement pour les collaborateurs','error');return;}
  curPerms=[...PRESETS[k].modules];
  renderPerms('collaborateur');
  toast('Profil "'+PRESETS[k].label+'" appliqué — pensez à enregistrer');
}
function selAll(){curPerms=[...ALL_MODULES];renderPerms('collaborateur');}
function selNone(){curPerms=[];renderPerms('collaborateur');}

function onRole(){
  const r=document.getElementById('eRole').value;
  const notes={admin:'Admin : accès complet, gestion de toute la plateforme.',manager:'Manager : accès complet à la gestion sans admin équipe.',collaborateur:'Collaborateur : accès défini uniquement par les permissions cochées.'};
  document.getElementById('roleNote').textContent=notes[r]||'';
  renderPerms(r);
}

async function saveInfos(){
  const r=await api({action:'update_user_infos',id:document.getElementById('eId').value,prenom:document.getElementById('ePrenom').value,nom:document.getElementById('eNom').value,poste:document.getElementById('ePoste').value,role:document.getElementById('eRole').value});
  if(r.success){
    toast('Informations enregistrées');
    const u=USERS_DATA.find(x=>x.id==curId);
    if(u){u.prenom=document.getElementById('ePrenom').value;u.nom=document.getElementById('eNom').value;u.poste=document.getElementById('ePoste').value;u.role=document.getElementById('eRole').value;}
    document.getElementById('dName').textContent=document.getElementById('ePrenom').value+' '+document.getElementById('eNom').value;
    const card=document.getElementById('card-'+curId);
    if(card)card.querySelector('.uname').textContent=document.getElementById('ePrenom').value+' '+document.getElementById('eNom').value;
  }else toast(r.error||'Erreur','error');
}

async function savePerms(){
  const r=await permsApi({action:'save_permissions',user_id:curId,modules:JSON.stringify(curPerms)});
  if(r.success){
    toast('Accès enregistrés — '+curPerms.length+' module(s)');
    const u=USERS_DATA.find(x=>x.id==curId);if(u)u.perms=curPerms;
    const c=document.getElementById('card-'+curId);
    if(c){const pc=c.querySelector('.pct');if(pc)pc.textContent=curPerms.length+' mod.';}
  }else toast(r.error||'Erreur — vérifiez que permissions_api.php est bien uploadé','error');
}

function genPass(){const c='abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%';document.getElementById('newPass').value=Array.from({length:12},()=>c[Math.floor(Math.random()*c.length)]).join('');}
async function resetPass(){
  const p=document.getElementById('newPass').value;
  if(!p){toast('Entrez un mot de passe','error');return;}
  if(p.length<8){toast('Minimum 8 caractères','error');return;}
  const r=await api({action:'reset_password_user',id:curId,password:p});
  if(r.success){document.getElementById('newPass').value='';toast(r.email_envoye?'MDP mis à jour — Email envoyé':'MDP mis à jour');}
  else toast(r.error||'Erreur','error');
}
async function toggleActive(){
  const r=await api({action:'toggle_user',id:curId});
  if(r.success){toast('Statut mis à jour');setTimeout(()=>location.reload(),800);}
  else toast(r.error||'Erreur','error');
}
async function delUser(){
  const u=USERS_DATA.find(x=>x.id==curId);
  if(!confirm('Supprimer définitivement "'+u?.prenom+' '+u?.nom+'" ?\nCette action est irréversible.'))return;
  const r=await api({action:'delete_user',id:curId});
  if(r.success){toast('Compte supprimé');setTimeout(()=>location.reload(),800);}
  else toast(r.error||'Erreur','error');
}

// NOUVEAU COLLABORATEUR
function openNew(){newPerms=['dashboard','taches','calendrier','chat','temps'];renderNpm();document.getElementById('nPre').value='';document.getElementById('nNom').value='';document.getElementById('nEmail').value='';document.getElementById('nPass').value='';document.getElementById('nPoste').value='';document.getElementById('nRole').value='collaborateur';document.getElementById('nPermsWrap').style.display='block';document.getElementById('nRoleNote').style.display='none';document.getElementById('modalNew').classList.add('open');}
function closeNew(){document.getElementById('modalNew').classList.remove('open');}
document.getElementById('modalNew').addEventListener('click',e=>{if(e.target===document.getElementById('modalNew'))closeNew();});

function renderNpm(){ALL_MODULES.forEach(k=>{const el=document.getElementById('npm-'+k);if(!el)return;const on=newPerms.includes(k);el.classList.toggle('on',on);});}
function toggleNpm(k){if(newPerms.includes(k))newPerms=newPerms.filter(p=>p!==k);else newPerms.push(k);document.getElementById('npm-'+k).classList.toggle('on',newPerms.includes(k));}
function applyNewPreset(k){const r=document.getElementById('nRole').value;if(r!=='collaborateur'){toast('Profils disponibles pour les collaborateurs uniquement','error');return;}newPerms=[...PRESETS[k].modules];renderNpm();toast('Profil "'+PRESETS[k].label+'" appliqué');}
function onNewRole(){const r=document.getElementById('nRole').value;document.getElementById('nPermsWrap').style.display=r==='collaborateur'?'block':'none';document.getElementById('nRoleNote').style.display=r!=='collaborateur'?'block':'none';}
function genNewPass(){const c='abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%';document.getElementById('nPass').value=Array.from({length:12},()=>c[Math.floor(Math.random()*c.length)]).join('');}

async function createUser(){
  const btn=document.getElementById('btnCreate');btn.textContent='Création…';btn.disabled=true;
  const role=document.getElementById('nRole').value;
  const r=await api({action:'create_user',prenom:document.getElementById('nPre').value,nom:document.getElementById('nNom').value,email:document.getElementById('nEmail').value,password:document.getElementById('nPass').value,role,poste:document.getElementById('nPoste').value||''});
  btn.textContent='Créer et envoyer email';btn.disabled=false;
  if(r.success){
    if(role==='collaborateur'&&newPerms.length>0){
      await permsApi({action:'save_permissions',user_id:r.id,modules:JSON.stringify(newPerms)});
    }
    closeNew();toast(r.message||'Compte créé');setTimeout(()=>location.reload(),1500);
  }else toast(r.error||'Erreur','error');
}
</script>
</body>
</html>
