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

// ============ NUMÉRO AUTO ============
function genNumero(PDO $db, string $type): string {
    $prefix = ['Devis'=>'DEV','Facture'=>'FAC','Avoir'=>'AVO'][$type] ?? 'DOC';
    $year   = date('Y');
    $stmt   = $db->prepare("SELECT COUNT(*) FROM factures WHERE type=? AND YEAR(date_emission)=?");
    $stmt->execute([$type, $year]);
    $count  = (int)$stmt->fetchColumn() + 1;
    return $prefix . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ============ RECALCULER LES TOTAUX ============
function recalculer(PDO $db, int $factureId): void {
    $lignes = $db->prepare("SELECT * FROM facture_lignes WHERE facture_id=? ORDER BY ordre");
    $lignes->execute([$factureId]);
    $rows = $lignes->fetchAll();

    $totalHT = 0;
    foreach ($rows as $l) {
        $lineTotal = $l['quantite'] * $l['prix_unit'] * (1 - $l['remise_pct'] / 100);
        $totalHT  += $lineTotal;
        $db->prepare("UPDATE facture_lignes SET total_ht=? WHERE id=?")->execute([round($lineTotal,2), $l['id']]);
    }

    $fStmt = $db->prepare("SELECT remise_pct, tva_pct FROM factures WHERE id=?");
    $fStmt->execute([$factureId]);
    $f = $fStmt->fetch();

    $remise  = $totalHT * ($f['remise_pct'] / 100);
    $htApres = $totalHT - $remise;
    $tva     = $htApres * ($f['tva_pct'] / 100);
    $ttc     = $htApres + $tva;

    $db->prepare("UPDATE factures SET montant_ht=?,montant_remise=?,montant_tva=?,montant_ttc=?,updated_at=NOW() WHERE id=?")
       ->execute([round($totalHT,2), round($remise,2), round($tva,2), round($ttc,2), $factureId]);
}

// ============ CRÉER ============
if ($action === 'creer') {
    $type      = $_POST['type'] ?? 'Facture';
    $numero    = genNumero($db, $type);
    $clientId  = $_POST['client_id'] ?: null;
    $projetId  = $_POST['projet_id'] ?: null;
    $devise    = $_POST['devise'] ?? 'FCFA';
    $dateEmis  = $_POST['date_emission'] ?: date('Y-m-d');
    $dateEch   = $_POST['date_echeance'] ?: null;
    $objet     = trim($_POST['objet'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');
    $conditions= trim($_POST['conditions'] ?? 'Paiement à réception. Tout retard de paiement entraînera des pénalités.');
    $remisePct = (float)($_POST['remise_pct'] ?? 0);
    $tvaPct    = (float)($_POST['tva_pct'] ?? 0);

    $db->prepare("INSERT INTO factures (numero,type,statut,client_id,projet_id,devise,date_emission,date_echeance,objet,notes,conditions,remise_pct,tva_pct,cree_par)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$numero,$type,'Brouillon',$clientId,$projetId,$devise,$dateEmis,$dateEch,$objet,$notes,$conditions,$remisePct,$tvaPct,$user['id']]);

    $id = $db->lastInsertId();
    echo json_encode(['success'=>true,'id'=>$id,'numero'=>$numero]); exit;
}

// ============ LISTE ============
if ($action === 'liste') {
    $type   = $_GET['type'] ?? '';
    $statut = $_GET['statut'] ?? '';
    $where  = 'WHERE 1=1';
    $params = [];
    if ($type)   { $where .= ' AND f.type=?';   $params[] = $type; }
    if ($statut) { $where .= ' AND f.statut=?';  $params[] = $statut; }

    $sql = "SELECT f.*, c.raison_sociale as client_nom, p.nom as projet_nom,
                   CONCAT(u.prenom,' ',u.nom) as createur
            FROM factures f
            LEFT JOIN clients c ON c.id=f.client_id
            LEFT JOIN projets p ON p.id=f.projet_id
            LEFT JOIN users u ON u.id=f.cree_par
            $where ORDER BY f.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll()); exit;
}

// ============ DÉTAIL ============
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT f.*, c.raison_sociale as client_nom, c.email as client_email,
                                  c.telephone as client_tel, c.pays as client_pays,
                                  c.contact_nom as client_contact,
                                  p.nom as projet_nom,
                                  CONCAT(u.prenom,' ',u.nom) as createur
                           FROM factures f
                           LEFT JOIN clients c ON c.id=f.client_id
                           LEFT JOIN projets p ON p.id=f.projet_id
                           LEFT JOIN users u ON u.id=f.cree_par
                           WHERE f.id=?");
    $stmt->execute([$id]);
    $facture = $stmt->fetch();
    if (!$facture) { echo json_encode(['error'=>'Introuvable']); exit; }

    $lignes = $db->prepare("SELECT * FROM facture_lignes WHERE facture_id=? ORDER BY ordre");
    $lignes->execute([$id]);
    $facture['lignes'] = $lignes->fetchAll();
    echo json_encode($facture); exit;
}

// ============ MODIFIER INFOS ============
if ($action === 'modifier') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("UPDATE factures SET client_id=?,projet_id=?,devise=?,date_emission=?,date_echeance=?,objet=?,notes=?,conditions=?,remise_pct=?,tva_pct=?,statut=?,updated_at=NOW() WHERE id=?")
       ->execute([
           $_POST['client_id']?:null, $_POST['projet_id']?:null,
           $_POST['devise']??'FCFA', $_POST['date_emission'], $_POST['date_echeance']?:null,
           $_POST['objet'], $_POST['notes'], $_POST['conditions'],
           (float)($_POST['remise_pct']??0), (float)($_POST['tva_pct']??0),
           $_POST['statut']??'Brouillon', $id
       ]);
    recalculer($db, $id);
    echo json_encode(['success'=>true]); exit;
}

// ============ LIGNES ============
if ($action === 'save_lignes') {
    $factureId = (int)($_POST['facture_id'] ?? 0);
    $lignes    = json_decode($_POST['lignes'] ?? '[]', true);

    $db->prepare("DELETE FROM facture_lignes WHERE facture_id=?")->execute([$factureId]);
    foreach ($lignes as $i => $l) {
        $lineTotal = ($l['quantite'] ?? 1) * ($l['prix_unit'] ?? 0) * (1 - (($l['remise_pct'] ?? 0) / 100));
        $db->prepare("INSERT INTO facture_lignes (facture_id,ordre,description,quantite,unite,prix_unit,remise_pct,total_ht) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$factureId, $i, $l['description']??'', $l['quantite']??1, $l['unite']??'', $l['prix_unit']??0, $l['remise_pct']??0, round($lineTotal,2)]);
    }
    recalculer($db, $factureId);
    echo json_encode(['success'=>true]); exit;
}

// ============ CHANGER STATUT ============
if ($action === 'statut') {
    $id     = (int)($_POST['id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    $valid  = ['Brouillon','Envoyé','Accepté','Payé','Annulé','Refusé'];
    if (!in_array($statut, $valid)) { echo json_encode(['error'=>'Statut invalide']); exit; }
    $db->prepare("UPDATE factures SET statut=?,updated_at=NOW() WHERE id=?")->execute([$statut,$id]);
    echo json_encode(['success'=>true]); exit;
}

// ============ SUPPRIMER ============
if ($action === 'supprimer') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("DELETE FROM facture_lignes WHERE facture_id=?")->execute([$id]);
    $db->prepare("DELETE FROM factures WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
}

// ============ DUPLIQUER ============
if ($action === 'dupliquer') {
    $id    = (int)($_POST['id'] ?? 0);
    $type  = $_POST['type'] ?? null;
    $stmt  = $db->prepare("SELECT * FROM factures WHERE id=?");
    $stmt->execute([$id]); $orig = $stmt->fetch();
    if (!$orig) { echo json_encode(['error'=>'Introuvable']); exit; }

    $newType   = $type ?: $orig['type'];
    $numero    = genNumero($db, $newType);
    $db->prepare("INSERT INTO factures (numero,type,statut,client_id,projet_id,devise,date_emission,date_echeance,objet,notes,conditions,remise_pct,tva_pct,montant_ht,montant_remise,montant_tva,montant_ttc,facture_liee,cree_par)
                  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$numero,$newType,'Brouillon',$orig['client_id'],$orig['projet_id'],$orig['devise'],date('Y-m-d'),$orig['date_echeance'],$orig['objet'],$orig['notes'],$orig['conditions'],$orig['remise_pct'],$orig['tva_pct'],$orig['montant_ht'],$orig['montant_remise'],$orig['montant_tva'],$orig['montant_ttc'],($type==='Avoir'?$id:null),$user['id']]);

    $newId = $db->lastInsertId();
    $lignesOrig = $db->prepare("SELECT * FROM facture_lignes WHERE facture_id=? ORDER BY ordre");
    $lignesOrig->execute([$id]);
    foreach ($lignesOrig->fetchAll() as $l) {
        $db->prepare("INSERT INTO facture_lignes (facture_id,ordre,description,quantite,unite,prix_unit,remise_pct,total_ht) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$newId,$l['ordre'],$l['description'],$l['quantite'],$l['unite'],$l['prix_unit'],$l['remise_pct'],$l['total_ht']]);
    }
    echo json_encode(['success'=>true,'id'=>$newId,'numero'=>$numero]); exit;
}

// ============ STATS ============
if ($action === 'stats') {
    $total    = $db->query("SELECT COUNT(*) FROM factures")->fetchColumn();
    $payees   = $db->query("SELECT COUNT(*) FROM factures WHERE statut='Payé'")->fetchColumn();
    $attente  = $db->query("SELECT COUNT(*) FROM factures WHERE statut IN ('Envoyé','Accepté')")->fetchColumn();
    $ca_fact  = $db->query("SELECT COALESCE(SUM(montant_ttc),0) FROM factures WHERE statut='Payé' AND type='Facture'")->fetchColumn();
    $ca_att   = $db->query("SELECT COALESCE(SUM(montant_ttc),0) FROM factures WHERE statut IN ('Envoyé','Accepté') AND type='Facture'")->fetchColumn();
    echo json_encode(compact('total','payees','attente','ca_fact','ca_att')); exit;
}

// ============ CLIENTS & PROJETS pour selects ============
if ($action === 'clients') { echo json_encode($db->query("SELECT id,raison_sociale FROM clients ORDER BY raison_sociale")->fetchAll()); exit; }
if ($action === 'projets') { echo json_encode($db->query("SELECT id,nom FROM projets WHERE statut NOT IN ('Clôturé') ORDER BY nom")->fetchAll()); exit; }

echo json_encode(['error'=>'Action inconnue']);
