<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
requireAuth();
if (!isManager()) { http_response_code(403); die('Accès non autorisé'); }
$db   = getDB();
$type = $_GET['type'] ?? '';
$date = date('Y-m-d');

function csvLine(array $row): string {
    return implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"', $row)) . "\n";
}
function sendCSV(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    echo "\xEF\xBB\xBF";
    echo csvLine($headers);
    foreach ($rows as $r) echo csvLine(array_values($r));
    exit;
}

switch ($type) {
    case 'clients':
        $rows = $db->query("SELECT raison_sociale,type,statut,secteur,contact_nom,email,telephone,pays,created_at FROM clients ORDER BY raison_sociale")->fetchAll();
        sendCSV("clients_uptech_$date.csv",['Raison sociale','Type','Statut','Secteur','Contact','Email','Téléphone','Pays','Date ajout'],$rows);
    case 'projets':
        $rows = $db->query("SELECT p.nom,p.type_prestation,p.statut,p.priorite,c.raison_sociale,p.budget,p.date_debut,p.date_livraison,CONCAT(u.prenom,' ',u.nom) FROM projets p LEFT JOIN clients c ON c.id=p.client_id LEFT JOIN users u ON u.id=p.manager_id ORDER BY p.created_at DESC")->fetchAll();
        sendCSV("projets_uptech_$date.csv",['Projet','Type','Statut','Priorité','Client','Budget FCFA','Début','Livraison','Manager'],$rows);
    case 'taches':
        $rows = $db->query("SELECT t.titre,t.statut,t.priorite,p.nom,CONCAT(u.prenom,' ',u.nom),t.date_echeance,t.progression FROM taches t LEFT JOIN projets p ON p.id=t.projet_id LEFT JOIN users u ON u.id=t.assigne_a ORDER BY t.created_at DESC")->fetchAll();
        sendCSV("taches_uptech_$date.csv",['Tâche','Statut','Priorité','Projet','Assigné à','Échéance','Progression %'],$rows);
    case 'finances':
        $rows = $db->query("SELECT type,categorie,montant,date_operation,moyen_paiement,statut,description FROM tresorerie ORDER BY date_operation DESC")->fetchAll();
        sendCSV("finances_uptech_$date.csv",['Type','Catégorie','Montant FCFA','Date','Moyen paiement','Statut','Description'],$rows);
    case 'factures':
        $rows = $db->query("SELECT f.numero,f.type,f.statut,c.raison_sociale,f.devise,f.montant_ht,f.montant_tva,f.montant_ttc,f.date_emission,f.date_echeance,f.objet FROM factures f LEFT JOIN clients c ON c.id=f.client_id ORDER BY f.created_at DESC")->fetchAll();
        sendCSV("factures_uptech_$date.csv",['Numéro','Type','Statut','Client','Devise','HT','TVA','TTC','Émission','Échéance','Objet'],$rows);
    case 'temps':
        $rows = $db->query("SELECT CONCAT(u.prenom,' ',u.nom),t.titre,p.nom,e.description,e.debut,e.fin,ROUND(e.duree/3600,2),e.facturable FROM time_entries e LEFT JOIN users u ON u.id=e.user_id LEFT JOIN taches t ON t.id=e.tache_id LEFT JOIN projets p ON p.id=e.projet_id ORDER BY e.debut DESC")->fetchAll();
        sendCSV("temps_uptech_$date.csv",['Collaborateur','Tâche','Projet','Description','Début','Fin','Heures','Facturable'],$rows);
    case 'crm':
        $rows = $db->query("SELECT c.raison_sociale,i.type_interaction,i.sujet,i.contenu,CONCAT(u.prenom,' ',u.nom),i.date_interaction,i.prochain_suivi FROM crm_interactions i LEFT JOIN clients c ON c.id=i.client_id LEFT JOIN users u ON u.id=i.user_id ORDER BY i.date_interaction DESC")->fetchAll();
        sendCSV("crm_uptech_$date.csv",['Client','Type','Sujet','Contenu','Par','Date','Prochain suivi'],$rows);
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Export CSV — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:#0f0e1a;color:#e8e6f0;min-height:100vh;padding:24px;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 0% 0%,rgba(41,35,92,0.5) 0%,transparent 60%);pointer-events:none;}
.page{max-width:720px;margin:0 auto;position:relative;z-index:1;padding-top:8px;}
.topbar{display:flex;align-items:center;gap:12px;margin-bottom:28px;}
.back-btn{display:flex;align-items:center;gap:6px;color:#7a78a0;text-decoration:none;font-size:13px;font-weight:500;padding:6px 12px;border-radius:8px;transition:color .2s;}
.back-btn:hover{color:#36A9E1;}
.back-btn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
h1{font-size:20px;font-weight:800;color:#fff;}
.sub{font-size:13px;color:#7a78a0;margin-top:4px;margin-bottom:24px;}
.info-box{background:rgba(54,169,225,.06);border:1px solid rgba(54,169,225,.15);border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:12px;color:#7a78a0;line-height:1.7;}
.info-box strong{color:#36A9E1;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px;}
.export-card{background:#1a1930;border:1px solid rgba(54,169,225,.15);border-radius:14px;padding:20px;text-decoration:none;display:flex;flex-direction:column;gap:10px;transition:all .2s;}
.export-card:hover{border-color:rgba(54,169,225,.4);transform:translateY(-2px);}
.eicon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;}
.eicon svg{width:18px;height:18px;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.ename{font-size:13px;font-weight:700;color:#fff;}
.edesc{font-size:11px;color:#7a78a0;line-height:1.5;}
.ebadge{display:inline-block;background:rgba(46,204,135,.12);border:1px solid rgba(46,204,135,.2);color:#2ecc87;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;margin-top:auto;width:fit-content;}
</style>
</head>
<body>
<div class="page">
  <div class="topbar">
    <a class="back-btn" href="dashboard.php"><svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg> Workspace</a>
    <div><h1>Export CSV</h1><div class="sub">Exportez vos données pour Excel, Google Sheets ou votre comptabilité</div></div>
  </div>
  <div class="info-box"><strong>Format Excel-compatible</strong> — Séparateur <strong>;</strong> et encodage <strong>UTF-8 BOM</strong> — s'ouvre directement dans Excel sans configuration.</div>
  <div class="grid">
    <?php
    $exports = [
      ['clients','Clients & Prospects','Nom, type, statut, contact, email, téléphone, pays','#2ecc87','rgba(46,204,135,.15)','<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
      ['projets','Projets','Nom, statut, client, budget, dates, manager','#36A9E1','rgba(54,169,225,.15)','<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>'],
      ['taches','Tâches','Titre, statut, priorité, projet, assigné, échéance','#f0a500','rgba(240,165,0,.15)','<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
      ['finances','Finances','Toutes les entrées/sorties de trésorerie','#2ecc87','rgba(46,204,135,.15)','<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
      ['factures','Factures','Devis, factures et avoirs avec montants','#9b8fff','rgba(155,143,255,.15)','<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
      ['temps','Suivi du temps','Heures par collaborateur, tâche et projet','#36A9E1','rgba(54,169,225,.15)','<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
      ['crm','Interactions CRM','Historique de tous les contacts clients','#e05252','rgba(224,82,82,.15)','<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 5.5 5.5l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>'],
    ];
    foreach ($exports as [$key,$name,$desc,$color,$bg,$icon]):
    ?>
    <a class="export-card" href="export_csv.php?type=<?=$key?>">
      <div class="eicon" style="background:<?=$bg?>"><svg viewBox="0 0 24 24" style="stroke:<?=$color?>"><?=$icon?></svg></div>
      <div class="ename"><?=$name?></div>
      <div class="edesc"><?=$desc?></div>
      <span class="ebadge">Télécharger .CSV</span>
    </a>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
