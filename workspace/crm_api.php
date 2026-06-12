<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
header('Content-Type: application/json');
requireAuth();
if (!isManager()) { echo json_encode(['error'=>'Accès non autorisé']); exit; }
$user   = currentUser();
$db     = getDB();
$action = $_REQUEST['action'] ?? '';

// ===== INTERACTIONS =====
if ($action === 'liste_interactions') {
    $clientId = (int)($_GET['client_id'] ?? 0);
    $sql = "SELECT i.*, CONCAT(u.prenom,' ',u.nom) as user_nom FROM crm_interactions i LEFT JOIN users u ON u.id=i.user_id WHERE i.client_id=? ORDER BY i.date_interaction DESC LIMIT 50";
    $stmt = $db->prepare($sql); $stmt->execute([$clientId]);
    echo json_encode($stmt->fetchAll()); exit;
}

if ($action === 'add_interaction') {
    $db->prepare("INSERT INTO crm_interactions (client_id,user_id,type_interaction,sujet,contenu,date_interaction,duree_min,prochain_suivi,note_suivi) VALUES (?,?,?,?,?,?,?,?,?)")
       ->execute([(int)$_POST['client_id'],$user['id'],$_POST['type_interaction'],$_POST['sujet'],$_POST['contenu'],$_POST['date_interaction'],$_POST['duree_min']?:null,$_POST['prochain_suivi']?:null,$_POST['note_suivi']]);
    echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]); exit;
}

if ($action === 'del_interaction') {
    $db->prepare("DELETE FROM crm_interactions WHERE id=?")->execute([(int)$_POST['id']]);
    echo json_encode(['success'=>true]); exit;
}

// ===== OPPORTUNITES =====
if ($action === 'liste_opportunites') {
    $clientId = (int)($_GET['client_id'] ?? 0);
    $sql = "SELECT o.*, CONCAT(u.prenom,' ',u.nom) as user_nom FROM crm_opportunites o LEFT JOIN users u ON u.id=o.user_id WHERE o.client_id=? ORDER BY o.updated_at DESC";
    $stmt = $db->prepare($sql); $stmt->execute([$clientId]);
    echo json_encode($stmt->fetchAll()); exit;
}

if ($action === 'add_opportunite') {
    $db->prepare("INSERT INTO crm_opportunites (client_id,user_id,titre,valeur,devise,probabilite,statut,date_cloture,notes) VALUES (?,?,?,?,?,?,?,?,?)")
       ->execute([(int)$_POST['client_id'],$user['id'],$_POST['titre'],(float)$_POST['valeur'],$_POST['devise'],(int)$_POST['probabilite'],$_POST['statut'],$_POST['date_cloture']?:null,$_POST['notes']]);
    echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]); exit;
}

if ($action === 'update_opportunite') {
    $db->prepare("UPDATE crm_opportunites SET statut=?,probabilite=?,valeur=?,date_cloture=?,notes=?,updated_at=NOW() WHERE id=?")
       ->execute([$_POST['statut'],(int)$_POST['probabilite'],(float)$_POST['valeur'],$_POST['date_cloture']?:null,$_POST['notes'],(int)$_POST['id']]);
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'del_opportunite') {
    $db->prepare("DELETE FROM crm_opportunites WHERE id=?")->execute([(int)$_POST['id']]);
    echo json_encode(['success'=>true]); exit;
}

// ===== LISTE CLIENTS =====
if ($action === 'clients') {
    $stmt = $db->query("SELECT c.id, c.raison_sociale, c.statut, c.type, c.contact_nom, c.email, c.telephone, c.pays,
                        (SELECT COUNT(*) FROM crm_interactions WHERE client_id=c.id) as nb_interactions,
                        (SELECT MAX(date_interaction) FROM crm_interactions WHERE client_id=c.id) as derniere_interaction,
                        (SELECT MIN(prochain_suivi) FROM crm_interactions WHERE client_id=c.id AND prochain_suivi >= CURDATE()) as prochain_suivi,
                        (SELECT COUNT(*) FROM crm_opportunites WHERE client_id=c.id AND statut NOT IN ('Gagnée','Perdue')) as nb_opportunites,
                        (SELECT COALESCE(SUM(valeur*(probabilite/100)),0) FROM crm_opportunites WHERE client_id=c.id AND statut NOT IN ('Gagnée','Perdue')) as pipeline_pondere
                        FROM clients c ORDER BY c.raison_sociale ASC");
    echo json_encode($stmt->fetchAll()); exit;
}

// ===== SUIVIS DU JOUR =====
if ($action === 'suivis_aujourd_hui') {
    $stmt = $db->query("SELECT i.*, c.raison_sociale as client_nom FROM crm_interactions i LEFT JOIN clients c ON c.id=i.client_id WHERE i.prochain_suivi=CURDATE() ORDER BY i.prochain_suivi ASC");
    echo json_encode($stmt->fetchAll()); exit;
}

// ===== STATS CRM =====
if ($action === 'stats') {
    $totalClients  = $db->query("SELECT COUNT(*) FROM clients WHERE statut='Client actif'")->fetchColumn();
    $totalProspects= $db->query("SELECT COUNT(*) FROM clients WHERE statut='Prospect'")->fetchColumn();
    $totalInter    = $db->query("SELECT COUNT(*) FROM crm_interactions")->fetchColumn();
    $suivisAuj     = $db->query("SELECT COUNT(*) FROM crm_interactions WHERE prochain_suivi=CURDATE()")->fetchColumn();
    $pipelineTotal = $db->query("SELECT COALESCE(SUM(valeur),0) FROM crm_opportunites WHERE statut NOT IN ('Gagnée','Perdue')")->fetchColumn();
    $pipelinePond  = $db->query("SELECT COALESCE(SUM(valeur*(probabilite/100)),0) FROM crm_opportunites WHERE statut NOT IN ('Gagnée','Perdue')")->fetchColumn();
    $gagnees       = $db->query("SELECT COUNT(*) FROM crm_opportunites WHERE statut='Gagnée'")->fetchColumn();
    $perdues       = $db->query("SELECT COUNT(*) FROM crm_opportunites WHERE statut='Perdue'")->fetchColumn();
    $tauxConversion= ($gagnees + $perdues) > 0 ? round($gagnees / ($gagnees + $perdues) * 100) : 0;
    echo json_encode(compact('totalClients','totalProspects','totalInter','suivisAuj','pipelineTotal','pipelinePond','gagnees','perdues','tauxConversion')); exit;
}

echo json_encode(['error'=>'Action inconnue']);
