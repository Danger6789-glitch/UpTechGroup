<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
requireAuth();
if (!isManager()) { http_response_code(403); die('Accès non autorisé'); }

$db   = getDB();
$mois = (int)($_GET['mois'] ?? date('n'));
$an   = (int)($_GET['annee'] ?? date('Y'));
$label = date('F Y', mktime(0, 0, 0, $mois, 1, $an));

function fmt(float $n): string { return number_format($n, 0, ',', ' '); }
function fmtDur(int $s): string { $h=floor($s/3600);$m=floor(($s%3600)/60);return $h>0?$h.'h '.$m.'m':$m.'m'; }

function safeQuery(PDO $db, string $sql, array $params = []): mixed {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (Exception $e) { return 0; }
}
function safeAll(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) { return []; }
}

// Sélecteur de période
if (($_GET['select'] ?? '') === '1') { ?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Choisir la période</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Poppins',sans-serif;background:#0f0e1a;color:#e8e6f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}.card{background:#1a1930;border:1px solid rgba(54,169,225,.15);border-radius:16px;padding:32px;width:100%;max-width:400px;}h2{font-size:18px;font-weight:700;color:#fff;margin-bottom:20px;}label{font-size:11px;font-weight:600;color:#7a78a0;text-transform:uppercase;letter-spacing:.8px;display:block;margin-bottom:6px;margin-top:14px;}select{width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(54,169,225,.15);border-radius:8px;padding:10px 13px;color:#e8e6f0;font-family:'Poppins',sans-serif;font-size:13px;outline:none;-webkit-appearance:none;}select option{background:#13122a;}.btn{width:100%;background:linear-gradient(135deg,#29235C,#36A9E1);border:none;border-radius:10px;padding:12px;color:#fff;font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;cursor:pointer;margin-top:20px;}.back{display:block;text-align:center;margin-top:12px;font-size:12px;color:#7a78a0;text-decoration:none;}.back:hover{color:#36A9E1;}</style></head>
<body><div class="card">
  <h2>Générer un rapport PDF</h2>
  <form action="rapport_pdf.php" method="GET">
    <label>Mois</label>
    <select name="mois"><?php for($m=1;$m<=12;$m++):?><option value="<?=$m?>"<?=$m==date('n')?' selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option><?php endfor;?></select>
    <label>Année</label>
    <select name="annee"><?php for($y=date('Y');$y>=2024;$y--):?><option value="<?=$y?>"><?=$y?></option><?php endfor;?></select>
    <button type="submit" class="btn">Générer le rapport</button>
  </form>
  <a class="back" href="dashboard.php">Retour au workspace</a>
</div></body></html>
<?php exit; }

// Données — toutes protégées contre les tables vides ou inexistantes
$ca        = (float)safeQuery($db,"SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND MONTH(date_operation)=? AND YEAR(date_operation)=?",[$mois,$an]);
$depenses  = (float)safeQuery($db,"SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Sortie' AND statut='Réalisé' AND MONTH(date_operation)=? AND YEAR(date_operation)=?",[$mois,$an]);
$resultat  = $ca - $depenses;
$projetsEC = (int)safeQuery($db,"SELECT COUNT(*) FROM projets WHERE statut='En cours'");
$projetsL  = (int)safeQuery($db,"SELECT COUNT(*) FROM projets WHERE statut='Livré' AND MONTH(updated_at)=? AND YEAR(updated_at)=?",[$mois,$an]);
$tachesT   = (int)safeQuery($db,"SELECT COUNT(*) FROM taches WHERE statut='Terminé' AND MONTH(updated_at)=? AND YEAR(updated_at)=?",[$mois,$an]);
$nouveauxC = (int)safeQuery($db,"SELECT COUNT(*) FROM clients WHERE MONTH(created_at)=? AND YEAR(created_at)=?",[$mois,$an]);
$factEmises= (int)safeQuery($db,"SELECT COUNT(*) FROM factures WHERE type='Facture' AND MONTH(date_emission)=? AND YEAR(date_emission)=?",[$mois,$an]);
$factPayees= (float)safeQuery($db,"SELECT COALESCE(SUM(montant_ttc),0) FROM factures WHERE type='Facture' AND statut='Payé' AND MONTH(date_emission)=? AND YEAR(date_emission)=?",[$mois,$an]);
$tempsTotal= (int)safeQuery($db,"SELECT COALESCE(SUM(duree),0) FROM time_entries WHERE MONTH(debut)=? AND YEAR(debut)=?",[$mois,$an]);
$equipe    = (int)safeQuery($db,"SELECT COUNT(*) FROM users WHERE actif=1");
$projets   = safeAll($db,"SELECT p.nom,p.statut,p.type_prestation,c.raison_sociale,p.budget FROM projets p LEFT JOIN clients c ON c.id=p.client_id WHERE p.statut NOT IN ('Clôturé') ORDER BY p.statut,p.nom LIMIT 10");
$factures  = safeAll($db,"SELECT f.numero,f.type,f.statut,c.raison_sociale,f.montant_ttc,f.devise FROM factures f LEFT JOIN clients c ON c.id=f.client_id WHERE MONTH(f.date_emission)=? AND YEAR(f.date_emission)=? ORDER BY f.date_emission DESC LIMIT 10",[$mois,$an]);
$tempsProjets = safeAll($db,"SELECT p.nom,COALESCE(SUM(e.duree),0) as total FROM time_entries e LEFT JOIN projets p ON p.id=e.projet_id WHERE MONTH(e.debut)=? AND YEAR(e.debut)=? GROUP BY e.projet_id,p.nom ORDER BY total DESC LIMIT 5",[$mois,$an]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rapport <?= ucfirst($label) ?> — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:#fff;color:#1a1a2e;font-size:13px;line-height:1.5;}
.page{max-width:800px;margin:0 auto;padding:40px;}
.doc-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px;padding-bottom:24px;border-bottom:3px solid #29235C;}
.brand-logo{width:48px;height:48px;object-fit:contain;margin-bottom:8px;display:block;}
.brand-name{font-size:18px;font-weight:900;color:#29235C;}
.brand-slogan{font-size:10px;color:#7a78a0;font-style:italic;}
.brand-info{font-size:10px;color:#555;margin-top:8px;line-height:1.7;}
.report-meta{text-align:right;}
.report-type{font-size:28px;font-weight:900;color:#29235C;letter-spacing:-1px;}
.report-period{font-size:13px;color:#36A9E1;font-weight:700;margin-top:4px;font-family:'Space Mono',monospace;}
.report-date{font-size:11px;color:#7a78a0;margin-top:4px;}
.section{margin-bottom:28px;}
.section-title{font-size:11px;font-weight:700;color:#29235C;text-transform:uppercase;letter-spacing:2px;margin-bottom:14px;display:flex;align-items:center;gap:10px;}
.section-title::after{content:'';flex:1;height:1px;background:#e8e8f0;}
.resultat-box{display:flex;gap:12px;margin-bottom:20px;}
.res-item{flex:1;border-radius:12px;padding:16px;text-align:center;}
.res-val{font-size:20px;font-weight:800;font-family:'Space Mono',monospace;}
.res-lbl{font-size:11px;margin-top:3px;opacity:.8;}
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-box{background:#f8f9ff;border:1px solid #e8e8f0;border-radius:12px;padding:14px;text-align:center;}
.kpi-val{font-size:18px;font-weight:800;color:#29235C;font-family:'Space Mono',monospace;}
.kpi-lbl{font-size:10px;color:#7a78a0;margin-top:3px;text-transform:uppercase;letter-spacing:.5px;}
.kpi-box.highlight{background:linear-gradient(135deg,#29235C,#36A9E1);border:none;}
.kpi-box.highlight .kpi-val,.kpi-box.highlight .kpi-lbl{color:#fff;}
.kpi-box.success .kpi-val{color:#1d9e75;}
.tbl{width:100%;border-collapse:collapse;}
.tbl th{font-size:10px;font-weight:700;color:#29235C;text-transform:uppercase;letter-spacing:1px;padding:8px 10px;text-align:left;border-bottom:2px solid #29235C;}
.tbl td{font-size:12px;padding:9px 10px;border-bottom:1px solid #f0f0f0;vertical-align:middle;}
.tbl tr:last-child td{border:none;}
.tbl tr:nth-child(even) td{background:#fafafa;}
.badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;}
.b-green{background:#e8faf3;color:#0f6e56;}
.b-blue{background:#e6f1fb;color:#185fa5;}
.b-orange{background:#fef3e2;color:#854f0b;}
.b-red{background:#fcebeb;color:#a32d2d;}
.b-purple{background:#eeedfe;color:#534ab7;}
.mono{font-family:'Space Mono',monospace;font-size:11px;}
.bar-chart{display:flex;flex-direction:column;gap:8px;}
.bar-row{display:flex;align-items:center;gap:10px;}
.bar-lbl{font-size:11px;color:#555;width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex-shrink:0;}
.bar-wrap{flex:1;height:8px;background:#f0f0f0;border-radius:99px;overflow:hidden;}
.bar-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#29235C,#36A9E1);}
.bar-val{font-size:10px;font-family:'Space Mono',monospace;color:#29235C;white-space:nowrap;min-width:50px;text-align:right;}
.empty-note{font-size:12px;color:#7a78a0;font-style:italic;padding:12px 0;}
.doc-footer{border-top:1px solid #e8e8f0;padding-top:14px;text-align:center;font-size:10px;color:#7a78a0;margin-top:28px;line-height:1.8;}
@media print{body{font-size:11px;}.page{padding:20px;max-width:100%;}.no-print{display:none !important;}@page{margin:1cm;size:A4;}}
.print-bar{position:fixed;bottom:24px;right:24px;display:flex;gap:10px;z-index:100;}
.print-btn{background:#29235C;color:#fff;border:none;border-radius:10px;padding:11px 20px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(41,35,92,.3);}
.print-btn.accent{background:#36A9E1;}
.print-btn svg{width:15px;height:15px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;}
</style>
</head>
<body>

<div class="print-bar no-print">
  <button class="print-btn" onclick="history.back()"><svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg> Retour</button>
  <button class="print-btn" onclick="window.location='rapport_pdf.php?select=1'" style="background:#1e1d35"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Changer période</button>
  <button class="print-btn accent" onclick="window.print()"><svg viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg> Imprimer / PDF</button>
</div>

<div class="page">

  <div class="doc-header">
    <div>
      <img src="assets/logo.png" alt="UP TECH GROUP" class="brand-logo">
      <div class="brand-name">UP TECH GROUP</div>
      <div class="brand-slogan">Parce que le numérique n'est pas un luxe, mais une nécessité</div>
      <div class="brand-info">SARL U · NIF 1002104545 · RCCM TG-LFW-01-2026-B13-01453<br>Lomé, Quartier Baguida, Togo · +228 96 24 25 04</div>
    </div>
    <div class="report-meta">
      <div class="report-type">RAPPORT MENSUEL</div>
      <div class="report-period"><?= strtoupper($label) ?></div>
      <div class="report-date">Généré le <?= date('d/m/Y à H:i') ?></div>
      <div style="margin-top:8px;font-size:11px;color:#7a78a0">Équipe : <strong style="color:#29235C"><?= $equipe ?> membre(s)</strong></div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Résultats financiers</div>
    <?php if($ca == 0 && $depenses == 0): ?>
      <div class="empty-note">Aucune transaction enregistrée pour cette période.</div>
    <?php else: ?>
    <div class="resultat-box">
      <div class="res-item" style="background:#e8faf3;border:1px solid #9fe1cb"><div class="res-val" style="color:#0f6e56"><?= fmt($ca) ?> FCFA</div><div class="res-lbl" style="color:#0f6e56">Chiffre d'affaires</div></div>
      <div class="res-item" style="background:#fcebeb;border:1px solid #f7c1c1"><div class="res-val" style="color:#a32d2d"><?= fmt($depenses) ?> FCFA</div><div class="res-lbl" style="color:#a32d2d">Dépenses</div></div>
      <div class="res-item" style="background:<?= $resultat>=0?'#e8faf3':'#fcebeb' ?>;border:1px solid <?= $resultat>=0?'#9fe1cb':'#f7c1c1' ?>"><div class="res-val" style="color:<?= $resultat>=0?'#0f6e56':'#a32d2d' ?>"><?= fmt($resultat) ?> FCFA</div><div class="res-lbl" style="color:<?= $resultat>=0?'#0f6e56':'#a32d2d' ?>">Résultat net</div></div>
    </div>
    <?php endif; ?>
  </div>

  <div class="section">
    <div class="section-title">Activité opérationnelle</div>
    <div class="kpi-grid">
      <div class="kpi-box"><div class="kpi-val"><?= $projetsEC ?></div><div class="kpi-lbl">Projets en cours</div></div>
      <div class="kpi-box<?= $projetsL>0?' success':'' ?>"><div class="kpi-val"><?= $projetsL ?></div><div class="kpi-lbl">Projets livrés</div></div>
      <div class="kpi-box"><div class="kpi-val"><?= $tachesT ?></div><div class="kpi-lbl">Tâches terminées</div></div>
      <div class="kpi-box<?= $nouveauxC>0?' success':'' ?>"><div class="kpi-val"><?= $nouveauxC ?></div><div class="kpi-lbl">Nouveaux clients</div></div>
      <div class="kpi-box"><div class="kpi-val"><?= $factEmises ?></div><div class="kpi-lbl">Factures émises</div></div>
      <div class="kpi-box<?= $factPayees>0?' success':'' ?>"><div class="kpi-val" style="font-size:13px"><?= fmt($factPayees) ?></div><div class="kpi-lbl">Encaissé FCFA</div></div>
      <div class="kpi-box"><div class="kpi-val"><?= $tempsTotal>0?fmtDur($tempsTotal):'—' ?></div><div class="kpi-lbl">Temps logué</div></div>
      <div class="kpi-box highlight"><div class="kpi-val"><?= $equipe ?></div><div class="kpi-lbl">Équipe active</div></div>
    </div>
  </div>

  <?php if($projets): ?>
  <div class="section">
    <div class="section-title">Portefeuille projets actifs</div>
    <table class="tbl">
      <thead><tr><th>Projet</th><th>Type</th><th>Client</th><th>Statut</th><th style="text-align:right">Budget</th></tr></thead>
      <tbody>
        <?php $sC=['En cours'=>'b-blue','En test'=>'b-blue','Livré'=>'b-green','Signé'=>'b-purple','Prospection'=>'b-orange','Devis envoyé'=>'b-orange'];
        foreach($projets as $p): ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($p['nom']) ?></td>
          <td style="color:#555"><?= htmlspecialchars($p['type_prestation']) ?></td>
          <td style="color:#555"><?= htmlspecialchars($p['raison_sociale']??'—') ?></td>
          <td><span class="badge <?= $sC[$p['statut']]??'b-blue' ?>"><?= htmlspecialchars($p['statut']) ?></span></td>
          <td class="mono" style="text-align:right"><?= $p['budget']>0?fmt($p['budget']).' FCFA':'—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if($tempsProjets): ?>
  <div class="section">
    <div class="section-title">Temps passé par projet</div>
    <?php $maxT=max(array_column($tempsProjets,'total')?:[1]); ?>
    <div class="bar-chart">
      <?php foreach($tempsProjets as $t): ?>
      <div class="bar-row">
        <div class="bar-lbl"><?= htmlspecialchars($t['nom']??'Sans projet') ?></div>
        <div class="bar-wrap"><div class="bar-fill" style="width:<?= $maxT>0?round($t['total']/$maxT*100):0 ?>%"></div></div>
        <div class="bar-val"><?= fmtDur((int)$t['total']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if($factures): ?>
  <div class="section">
    <div class="section-title">Facturation du mois</div>
    <table class="tbl">
      <thead><tr><th>Numéro</th><th>Type</th><th>Client</th><th>Statut</th><th style="text-align:right">Montant TTC</th></tr></thead>
      <tbody>
        <?php $fC=['Payé'=>'b-green','Envoyé'=>'b-blue','Accepté'=>'b-purple','Brouillon'=>'b-orange','Annulé'=>'b-red'];
        $tC=['Facture'=>'b-blue','Devis'=>'b-purple','Avoir'=>'b-orange'];
        foreach($factures as $f): ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($f['numero']) ?></td>
          <td><span class="badge <?= $tC[$f['type']]??'b-blue' ?>"><?= $f['type'] ?></span></td>
          <td style="color:#555"><?= htmlspecialchars($f['raison_sociale']??'—') ?></td>
          <td><span class="badge <?= $fC[$f['statut']]??'b-blue' ?>"><?= $f['statut'] ?></span></td>
          <td class="mono" style="text-align:right"><?= fmt($f['montant_ttc']) ?> <?= $f['devise'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if(!$projets && !$factures && $ca==0): ?>
  <div style="text-align:center;padding:40px;color:#7a78a0;">
    <div style="font-size:16px;font-weight:700;color:#29235C;margin-bottom:8px">Aucune donnée pour <?= ucfirst($label) ?></div>
    <div style="font-size:12px">Commencez à enregistrer des projets, clients et transactions dans le workspace.</div>
  </div>
  <?php endif; ?>

  <div class="doc-footer">
    UP TECH GROUP SARL U · RCCM TG-LFW-01-2026-B13-01453 · NIF 1002104545 · Lomé, Quartier Baguida, Togo<br>
    uptech-group.com · workspace@uptech-group.com · +228 96 24 25 04<br>
    <strong>Document confidentiel — Usage interne uniquement</strong>
  </div>

</div>
</body>
</html>