<?php
// ============================================================
// UP TECH GROUP — Gestion Équipe & Permissions
// Accessible uniquement par l'Admin
// ============================================================
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireRole('admin');

$db = getDB();
$success = '';
$error = '';

// Définition des modules et leurs permissions
$modules = [
    'dashboard'   => ['label' => 'Dashboard & KPIs',        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    'projets'     => ['label' => 'Projets & Kanban',         'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    'taches'      => ['label' => 'Tâches',                   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    'crm'         => ['label' => 'CRM — Clients & Prospects','icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
    'calendrier'  => ['label' => 'Calendrier',               'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    'chat'        => ['label' => 'Chat interne',             'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
    'fichiers'    => ['label' => 'Fichiers & Documents',     'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
    'finances'    => ['label' => 'Finances & Trésorerie',    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    'facturation' => ['label' => 'Devis & Factures',         'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'temps'       => ['label' => 'Suivi du temps',           'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    'rapports'    => ['label' => 'Rapports PDF',             'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'ia'          => ['label' => 'Assistant IA',             'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
    'equipe'      => ['label' => 'Gestion Équipe',           'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
];

// Presets de rôles
$presets = [
    'admin'           => array_keys($modules),
    'manager'         => ['dashboard','projets','taches','crm','calendrier','chat','fichiers','finances','facturation','temps','rapports','ia'],
    'commercial'      => ['dashboard','crm','calendrier','chat'],
    'developpeur'     => ['dashboard','projets','taches','calendrier','chat','fichiers','temps','ia'],
    'community'       => ['dashboard','calendrier','chat','fichiers'],
    'collaborateur'   => ['dashboard','taches','calendrier','chat'],
];

// Traitement création / modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $nom       = trim($_POST['nom'] ?? '');
        $prenom    = trim($_POST['prenom'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? 'collaborateur';
        $perms     = $_POST['permissions'] ?? [];
        $perms_json = json_encode($perms);

        if ($action === 'create') {
            $password = $_POST['password'] ?? '';
            if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
                $error = 'Tous les champs obligatoires doivent être remplis.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $stmt = $db->prepare("INSERT INTO users (nom, prenom, email, password, role, permissions, actif) VALUES (?, ?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$nom, $prenom, $email, $hash, $role, $perms_json]);
                    $success = "Compte de $prenom $nom créé avec succès.";
                } catch (PDOException $e) {
                    $error = "Erreur : cet email est peut-être déjà utilisé.";
                }
            }
        } elseif ($action === 'update') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            try {
                $stmt = $db->prepare("UPDATE users SET nom=?, prenom=?, email=?, role=?, permissions=? WHERE id=?");
                $stmt->execute([$nom, $prenom, $email, $role, $perms_json, $user_id]);
                if (!empty($_POST['password'])) {
                    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $user_id]);
                }
                $success = "Profil de $prenom $nom mis à jour.";
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour.";
            }
        }
    }

    if ($action === 'toggle') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $actif   = (int)($_POST['actif'] ?? 0);
        $db->prepare("UPDATE users SET actif=? WHERE id=?")->execute([$actif, $user_id]);
        $success = "Statut mis à jour.";
    }

    if ($action === 'delete') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id !== $_SESSION['user_id']) {
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$user_id]);
            $success = "Collaborateur supprimé.";
        } else {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        }
    }
}

// Récupérer tous les utilisateurs sauf l'admin connecté
$users = $db->query("SELECT * FROM users ORDER BY role, prenom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Équipe & Permissions — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #29235C;
    --primary-light: #3a3480;
    --accent: #36A9E1;
    --accent-dark: #1d8abf;
    --bg: #0f0e1a;
    --bg2: #16152a;
    --bg3: #1e1c35;
    --card: #1a1930;
    --card2: #201e38;
    --border: rgba(54,169,225,0.15);
    --border2: rgba(255,255,255,0.06);
    --text: #e8e6f0;
    --muted: #7a78a0;
    --success: #2ecc87;
    --danger: #e05252;
    --warning: #f0a500;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}

/* ---- HEADER ---- */
.page-header {
    background: linear-gradient(135deg, var(--primary) 0%, #1a1635 100%);
    border-bottom: 1px solid var(--border);
    padding: 24px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.page-header h1 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
}
.page-header h1 svg { color: var(--accent); }
.btn-primary {
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}
.btn-primary:hover { background: var(--accent-dark); }

/* ---- ALERTS ---- */
.alert {
    margin: 20px 32px 0;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
}
.alert-success { background: rgba(46,204,135,0.12); border: 1px solid rgba(46,204,135,0.3); color: var(--success); }
.alert-error   { background: rgba(224,82,82,0.12);  border: 1px solid rgba(224,82,82,0.3);  color: var(--danger); }

/* ---- LAYOUT ---- */
.content { padding: 28px 32px; display: grid; grid-template-columns: 1fr 420px; gap: 28px; align-items: start; }
@media (max-width: 1100px) { .content { grid-template-columns: 1fr; } }

/* ---- TEAM LIST ---- */
.section-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--muted);
    margin-bottom: 14px;
}
.user-card {
    background: var(--card);
    border: 1px solid var(--border2);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: border-color 0.2s, transform 0.15s;
    cursor: pointer;
}
.user-card:hover { border-color: var(--border); transform: translateX(3px); }
.user-card.selected { border-color: var(--accent); background: var(--card2); }
.user-avatar {
    width: 46px; height: 46px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-light), var(--accent-dark));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.user-info { flex: 1; min-width: 0; }
.user-name { font-weight: 600; font-size: 0.95rem; color: var(--text); }
.user-email { font-size: 0.78rem; color: var(--muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-meta { display: flex; align-items: center; gap: 10px; margin-top: 6px; flex-wrap: wrap; }
.badge {
    display: inline-flex; align-items: center;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.badge-admin     { background: rgba(54,169,225,0.15); color: var(--accent); }
.badge-manager   { background: rgba(41,35,92,0.5);    color: #a89ff0; }
.badge-commercial{ background: rgba(240,165,0,0.15);  color: var(--warning); }
.badge-developpeur{background: rgba(46,204,135,0.15); color: var(--success); }
.badge-community { background: rgba(224,82,82,0.12);  color: #f08080; }
.badge-collaborateur{background: rgba(255,255,255,0.06); color: var(--muted); }
.badge-actif   { background: rgba(46,204,135,0.15); color: var(--success); }
.badge-inactif { background: rgba(224,82,82,0.12);  color: var(--danger); }

.perm-count { font-size: 0.72rem; color: var(--muted); }
.user-actions { display: flex; gap: 8px; flex-shrink: 0; }
.btn-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border2);
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
}
.btn-icon:hover { border-color: var(--accent); color: var(--accent); }
.btn-icon.danger:hover { border-color: var(--danger); color: var(--danger); }

/* ---- FORM PANEL ---- */
.form-panel {
    background: var(--card);
    border: 1px solid var(--border2);
    border-radius: 14px;
    overflow: hidden;
    position: sticky;
    top: 24px;
}
.form-panel-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    padding: 18px 22px;
    display: flex; align-items: center; justify-content: space-between;
}
.form-panel-header h2 { font-size: 1rem; font-weight: 600; color: #fff; }
.form-panel-body { padding: 22px; }

.form-group { margin-bottom: 16px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 6px;
}
input[type="text"],
input[type="email"],
input[type="password"],
select {
    width: 100%;
    background: var(--bg2);
    border: 1px solid var(--border2);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem;
    transition: border-color 0.2s;
    outline: none;
}
input:focus, select:focus { border-color: var(--accent); }
select option { background: var(--bg2); }

/* PRESETS */
.presets-row {
    display: flex; gap: 8px; flex-wrap: wrap;
    margin-bottom: 18px;
}
.preset-btn {
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid var(--border2);
    background: var(--bg2);
    color: var(--muted);
    font-family: 'Poppins', sans-serif;
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.preset-btn:hover { border-color: var(--accent); color: var(--accent); }

/* PERMISSIONS GRID */
.perms-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 12px;
    display: flex; align-items: center; justify-content: space-between;
}
.perms-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 20px;
}
.perm-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--bg2);
    border: 1px solid var(--border2);
    border-radius: 8px;
    padding: 10px 12px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.perm-item:hover { border-color: rgba(54,169,225,0.3); }
.perm-item.checked { border-color: var(--accent); background: rgba(54,169,225,0.08); }
.perm-item input[type="checkbox"] { display: none; }
.perm-check {
    width: 18px; height: 18px;
    border-radius: 5px;
    border: 2px solid var(--border2);
    background: transparent;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
}
.perm-item.checked .perm-check {
    background: var(--accent);
    border-color: var(--accent);
}
.perm-check svg { display: none; }
.perm-item.checked .perm-check svg { display: block; }
.perm-icon { color: var(--muted); flex-shrink: 0; transition: color 0.2s; }
.perm-item.checked .perm-icon { color: var(--accent); }
.perm-text { font-size: 0.75rem; font-weight: 500; color: var(--muted); line-height: 1.3; transition: color 0.2s; }
.perm-item.checked .perm-text { color: var(--text); }

/* DIVIDER */
.divider {
    border: none;
    border-top: 1px solid var(--border2);
    margin: 18px 0;
}

.btn-submit {
    width: 100%;
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-submit:hover { background: var(--accent-dark); }
.btn-cancel {
    width: 100%;
    background: transparent;
    color: var(--muted);
    border: 1px solid var(--border2);
    padding: 10px;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem;
    cursor: pointer;
    margin-top: 8px;
    transition: all 0.2s;
}
.btn-cancel:hover { border-color: var(--muted); color: var(--text); }

/* Empty state */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--muted);
}
.empty-state svg { opacity: 0.3; margin-bottom: 12px; }
.empty-state p { font-size: 0.85rem; }
</style>
</head>
<body>

<div class="page-header">
    <h1>
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        Équipe &amp; Permissions
    </h1>
    <button class="btn-primary" onclick="openCreateForm()">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        Nouveau collaborateur
    </button>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="content">

    <!-- LISTE ÉQUIPE -->
    <div>
        <div class="section-title"><?= count($users) ?> membre<?= count($users) > 1 ? 's' : '' ?> dans l'équipe</div>

        <?php if (empty($users)): ?>
        <div class="empty-state">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p>Aucun collaborateur pour l'instant.<br>Créez le premier compte.</p>
        </div>
        <?php else: ?>
        <?php foreach ($users as $u):
            $perms = json_decode($u['permissions'] ?? '[]', true) ?: [];
            $initials = strtoupper(substr($u['prenom'],0,1) . substr($u['nom'],0,1));
        ?>
        <div class="user-card" id="card-<?= $u['id'] ?>" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
            <div class="user-avatar"><?= $initials ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                <div class="user-meta">
                    <span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span>
                    <span class="badge badge-<?= $u['actif'] ? 'actif' : 'inactif' ?>"><?= $u['actif'] ? 'Actif' : 'Inactif' ?></span>
                    <span class="perm-count"><?= count($perms) ?> module<?= count($perms) > 1 ? 's' : '' ?></span>
                </div>
            </div>
            <div class="user-actions" onclick="event.stopPropagation()">
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="actif" value="<?= $u['actif'] ? 0 : 1 ?>">
                    <button type="submit" class="btn-icon" title="<?= $u['actif'] ? 'Désactiver' : 'Activer' ?>">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <?php if ($u['actif']): ?>
                            <path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            <?php else: ?>
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <?php endif; ?>
                        </svg>
                    </button>
                </form>
                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce collaborateur ?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn-icon danger" title="Supprimer">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- FORMULAIRE PANEL -->
    <div class="form-panel" id="formPanel">
        <div class="form-panel-header">
            <h2 id="formTitle">Nouveau collaborateur</h2>
        </div>
        <div class="form-panel-body">
            <form method="POST" id="mainForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="user_id" id="formUserId" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label>Prénom *</label>
                        <input type="text" name="prenom" id="fieldPrenom" placeholder="Ex : Kofi" required>
                    </div>
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="nom" id="fieldNom" placeholder="Ex : Mensah" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="fieldEmail" placeholder="collaborateur@uptech-group.com" required>
                </div>

                <div class="form-group">
                    <label>Mot de passe <span id="pwdHint" style="font-weight:400;text-transform:none;letter-spacing:0;">(laisser vide = inchangé)</span></label>
                    <input type="password" name="password" id="fieldPassword" placeholder="Minimum 8 caractères">
                </div>

                <div class="form-group">
                    <label>Rôle</label>
                    <select name="role" id="fieldRole" onchange="applyPreset(this.value)">
                        <option value="collaborateur">Collaborateur</option>
                        <option value="commercial">Commercial</option>
                        <option value="developpeur">Développeur</option>
                        <option value="community">Community Manager</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <hr class="divider">

                <div class="perms-label">
                    <span>Accès aux modules</span>
                    <span id="permsCount" style="color:var(--accent);font-size:0.72rem;">0 sélectionné</span>
                </div>

                <!-- Presets rapides -->
                <div class="presets-row">
                    <button type="button" class="preset-btn" onclick="applyPreset('commercial')">Commercial</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('developpeur')">Dev</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('community')">CM</button>
                    <button type="button" class="preset-btn" onclick="applyPreset('manager')">Manager</button>
                    <button type="button" class="preset-btn" onclick="selectAll()">Tout</button>
                    <button type="button" class="preset-btn" onclick="clearAll()">Aucun</button>
                </div>

                <div class="perms-grid">
                    <?php foreach ($modules as $key => $mod): ?>
                    <div class="perm-item" id="perm-<?= $key ?>" onclick="togglePerm('<?= $key ?>')">
                        <input type="checkbox" name="permissions[]" value="<?= $key ?>" id="chk-<?= $key ?>">
                        <div class="perm-check">
                            <svg width="10" height="10" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <svg class="perm-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="<?= $mod['icon'] ?>"/>
                        </svg>
                        <span class="perm-text"><?= $mod['label'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">Créer le compte</button>
                <button type="button" class="btn-cancel" onclick="resetForm()">Annuler</button>
            </form>
        </div>
    </div>

</div>

<script>
// Presets de permissions
const presets = <?= json_encode($presets) ?>;

function applyPreset(role) {
    const perms = presets[role] || [];
    clearAll();
    perms.forEach(p => {
        const el = document.getElementById('perm-' + p);
        const chk = document.getElementById('chk-' + p);
        if (el && chk) { el.classList.add('checked'); chk.checked = true; }
    });
    // Sync select
    const sel = document.getElementById('fieldRole');
    if (sel && presets[role] !== undefined) sel.value = role;
    updateCount();
}

function togglePerm(key) {
    const el  = document.getElementById('perm-' + key);
    const chk = document.getElementById('chk-' + key);
    if (!el || !chk) return;
    const checked = !chk.checked;
    chk.checked = checked;
    el.classList.toggle('checked', checked);
    updateCount();
}

function selectAll() {
    document.querySelectorAll('.perm-item').forEach(el => {
        el.classList.add('checked');
        el.querySelector('input[type=checkbox]').checked = true;
    });
    updateCount();
}

function clearAll() {
    document.querySelectorAll('.perm-item').forEach(el => {
        el.classList.remove('checked');
        el.querySelector('input[type=checkbox]').checked = false;
    });
    updateCount();
}

function updateCount() {
    const n = document.querySelectorAll('.perm-item.checked').length;
    document.getElementById('permsCount').textContent = n + ' sélectionné' + (n > 1 ? 's' : '');
}

function openCreateForm() {
    resetForm();
    document.getElementById('formPanel').scrollIntoView({ behavior: 'smooth' });
}

function editUser(u) {
    // Highlight card
    document.querySelectorAll('.user-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('card-' + u.id)?.classList.add('selected');

    document.getElementById('formTitle').textContent  = 'Modifier — ' + u.prenom + ' ' + u.nom;
    document.getElementById('formAction').value       = 'update';
    document.getElementById('formUserId').value       = u.id;
    document.getElementById('fieldPrenom').value      = u.prenom;
    document.getElementById('fieldNom').value         = u.nom;
    document.getElementById('fieldEmail').value       = u.email;
    document.getElementById('fieldPassword').value    = '';
    document.getElementById('fieldRole').value        = u.role;
    document.getElementById('submitBtn').textContent  = 'Enregistrer les modifications';
    document.getElementById('pwdHint').style.display  = 'inline';

    // Set permissions
    clearAll();
    const perms = JSON.parse(u.permissions || '[]');
    perms.forEach(p => {
        const el  = document.getElementById('perm-' + p);
        const chk = document.getElementById('chk-' + p);
        if (el && chk) { el.classList.add('checked'); chk.checked = true; }
    });
    updateCount();

    document.getElementById('formPanel').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('formTitle').textContent  = 'Nouveau collaborateur';
    document.getElementById('formAction').value       = 'create';
    document.getElementById('formUserId').value       = '';
    document.getElementById('fieldPrenom').value      = '';
    document.getElementById('fieldNom').value         = '';
    document.getElementById('fieldEmail').value       = '';
    document.getElementById('fieldPassword').value    = '';
    document.getElementById('fieldRole').value        = 'collaborateur';
    document.getElementById('submitBtn').textContent  = 'Créer le compte';
    document.getElementById('pwdHint').style.display  = 'none';
    document.querySelectorAll('.user-card').forEach(c => c.classList.remove('selected'));
    clearAll();
}

// Init
updateCount();
</script>

</body>
</html>
