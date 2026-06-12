<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

startSecureSession();
header('Content-Type: application/json');
requireAuth();

if (!isManager()) {
    echo json_encode(['error' => 'Accès non autorisé']); exit;
}

$db     = getDB();
$action = $_REQUEST['action'] ?? '';
$year   = (int)($_REQUEST['year'] ?? date('Y'));

// ============ REVENUS & DÉPENSES PAR MOIS ============
if ($action === 'revenus_depenses_mois') {
    $data = [];
    for ($m = 1; $m <= 12; $m++) {
        $revenus  = $db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=? AND MONTH(date_operation)=?");
        $revenus->execute([$year, $m]);
        $depenses = $db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Sortie' AND statut='Réalisé' AND YEAR(date_operation)=? AND MONTH(date_operation)=?");
        $depenses->execute([$year, $m]);
        $prevu    = $db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Prévu' AND YEAR(date_operation)=? AND MONTH(date_operation)=?");
        $prevu->execute([$year, $m]);
        $data[] = [
            'mois'     => $m,
            'revenus'  => (float)$revenus->fetchColumn(),
            'depenses' => (float)$depenses->fetchColumn(),
            'prevu'    => (float)$prevu->fetchColumn(),
        ];
    }
    echo json_encode($data); exit;
}

// ============ RÉSUMÉ FINANCIER ============
if ($action === 'resume') {
    $ca_total    = $db->query("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé'")->fetchColumn();
    $ca_annee    = $db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=?");
    $ca_annee->execute([$year]); $ca_annee = $ca_annee->fetchColumn();
    $ca_mois     = $db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=? AND MONTH(date_operation)=?");
    $ca_mois->execute([$year, date('n')]); $ca_mois = $ca_mois->fetchColumn();
    $depenses    = $db->query("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Sortie' AND statut='Réalisé'")->fetchColumn();
    $dep_annee   = $db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Sortie' AND statut='Réalisé' AND YEAR(date_operation)=?");
    $dep_annee->execute([$year]); $dep_annee = $dep_annee->fetchColumn();
    $prevu_total = $db->query("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Prévu'")->fetchColumn();

    // Mois précédent pour comparaison
    $lastMonth = date('n') == 1 ? 12 : date('n') - 1;
    $lastYear  = date('n') == 1 ? $year - 1 : $year;
    $ca_last   = $db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=? AND MONTH(date_operation)=?");
    $ca_last->execute([$lastYear, $lastMonth]); $ca_last = $ca_last->fetchColumn();

    $evolution = $ca_last > 0 ? round((($ca_mois - $ca_last) / $ca_last) * 100, 1) : 0;

    echo json_encode([
        'ca_total'    => (float)$ca_total,
        'ca_annee'    => (float)$ca_annee,
        'ca_mois'     => (float)$ca_mois,
        'depenses'    => (float)$depenses,
        'dep_annee'   => (float)$dep_annee,
        'net_annee'   => (float)$ca_annee - (float)$dep_annee,
        'net_total'   => (float)$ca_total - (float)$depenses,
        'prevu_total' => (float)$prevu_total,
        'evolution'   => $evolution,
        'marge'       => $ca_annee > 0 ? round((($ca_annee - $dep_annee) / $ca_annee) * 100, 1) : 0,
    ]);
    exit;
}

// ============ RÉPARTITION PAR CATÉGORIE ============
if ($action === 'categories') {
    $entrees = $db->prepare("SELECT categorie, COALESCE(SUM(montant),0) as total FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=? GROUP BY categorie ORDER BY total DESC");
    $entrees->execute([$year]);
    $sorties = $db->prepare("SELECT categorie, COALESCE(SUM(montant),0) as total FROM tresorerie WHERE type='Sortie' AND statut='Réalisé' AND YEAR(date_operation)=? GROUP BY categorie ORDER BY total DESC");
    $sorties->execute([$year]);
    echo json_encode(['entrees' => $entrees->fetchAll(), 'sorties' => $sorties->fetchAll()]);
    exit;
}

// ============ PIPELINE PROJETS ============
if ($action === 'pipeline') {
    $statuts = ['Prospection','Devis envoyé','Signé','En cours','En test','Livré','Clôturé'];
    $data = [];
    foreach ($statuts as $s) {
        $stmt = $db->prepare("SELECT COUNT(*) as nb, COALESCE(SUM(budget),0) as valeur FROM projets WHERE statut=?");
        $stmt->execute([$s]);
        $row = $stmt->fetch();
        $data[] = ['statut' => $s, 'nb' => (int)$row['nb'], 'valeur' => (float)$row['valeur']];
    }
    echo json_encode($data); exit;
}

// ============ TOP CLIENTS PAR CA ============
if ($action === 'top_clients') {
    $sql = "SELECT c.raison_sociale, 
                   COUNT(p.id) as nb_projets,
                   COALESCE(SUM(p.montant_encaisse),0) as ca
            FROM clients c
            LEFT JOIN projets p ON p.client_id = c.id
            GROUP BY c.id, c.raison_sociale
            HAVING ca > 0
            ORDER BY ca DESC
            LIMIT 8";
    echo json_encode($db->query($sql)->fetchAll()); exit;
}

// ============ TRÉSORERIE CUMULÉE ============
if ($action === 'tresorerie_cumul') {
    $sql = "SELECT DATE_FORMAT(date_operation,'%Y-%m') as mois,
                   SUM(CASE WHEN type='Entrée' THEN montant ELSE -montant END) as flux
            FROM tresorerie
            WHERE statut='Réalisé' AND YEAR(date_operation)=?
            GROUP BY mois
            ORDER BY mois ASC";
    $stmt = $db->prepare($sql); $stmt->execute([$year]);
    $rows = $stmt->fetchAll();
    $cumul = 0; $data = [];
    foreach ($rows as $r) {
        $cumul += $r['flux'];
        $data[] = ['mois' => $r['mois'], 'cumul' => $cumul];
    }
    echo json_encode($data); exit;
}

// ============ FACTURATION ============
if ($action === 'facturation') {
    $stats = [
        'total'   => $db->query("SELECT COUNT(*) FROM factures")->fetchColumn(),
        'payees'  => $db->query("SELECT COUNT(*) FROM factures WHERE statut='Payé'")->fetchColumn(),
        'attente' => $db->query("SELECT COUNT(*) FROM factures WHERE statut='Envoyé'")->fetchColumn(),
        'annulees'=> $db->query("SELECT COUNT(*) FROM factures WHERE statut='Annulé'")->fetchColumn(),
        'montant_total' => $db->query("SELECT COALESCE(SUM(montant_ht),0) FROM factures WHERE statut='Payé'")->fetchColumn(),
        'montant_attente'=> $db->query("SELECT COALESCE(SUM(montant_ht),0) FROM factures WHERE statut='Envoyé'")->fetchColumn(),
    ];
    echo json_encode($stats); exit;
}

// ============ ANNÉES DISPONIBLES ============
if ($action === 'annees') {
    $stmt = $db->query("SELECT DISTINCT YEAR(date_operation) as annee FROM tresorerie ORDER BY annee DESC");
    $annees = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array(date('Y'), $annees)) array_unshift($annees, (int)date('Y'));
    echo json_encode($annees); exit;
}

echo json_encode(['error' => 'Action inconnue']);
