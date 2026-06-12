<?php
// ============================================
// UP TECH GROUP — Génération PDF factures/devis
// ============================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
requireAuth();
if (!isManager()) { die('Accès non autorisé'); }

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('ID manquant');

$db   = getDB();
$stmt = $db->prepare("SELECT f.*, c.raison_sociale as client_nom, c.email as client_email,
                              c.telephone as client_tel, c.pays as client_pays,
                              c.contact_nom as client_contact, c.secteur as client_secteur,
                              p.nom as projet_nom,
                              CONCAT(u.prenom,' ',u.nom) as createur_nom
                       FROM factures f
                       LEFT JOIN clients c ON c.id=f.client_id
                       LEFT JOIN projets p ON p.id=f.projet_id
                       LEFT JOIN users u ON u.id=f.cree_par
                       WHERE f.id=?");
$stmt->execute([$id]);
$f = $stmt->fetch();
if (!$f) die('Document introuvable');

$lignesStmt = $db->prepare("SELECT * FROM facture_lignes WHERE facture_id=? ORDER BY ordre");
$lignesStmt->execute([$id]);
$lignes = $lignesStmt->fetchAll();

function fmt(float $n, string $devise='FCFA'): string {
    return number_format($n, 0, ',', ' ') . ' ' . $devise;
}

$typeLabel  = ['Devis'=>'DEVIS','Facture'=>'FACTURE','Avoir'=>'AVOIR'][$f['type']] ?? $f['type'];
$statutColors = [
    'Brouillon'=>'#7a78a0','Envoyé'=>'#36A9E1','Accepté'=>'#9b8fff',
    'Payé'=>'#2ecc87','Annulé'=>'#e05252','Refusé'=>'#e05252'
];
$statutColor = $statutColors[$f['statut']] ?? '#7a78a0';

// Générer HTML → PDF via imprimante navigateur
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= $typeLabel ?> <?= htmlspecialchars($f['numero']) ?> — UP TECH GROUP</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:#fff;color:#1a1a2e;font-size:13px;line-height:1.5;}
.page{max-width:800px;margin:0 auto;padding:40px;}

/* HEADER */
.doc-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:40px;padding-bottom:28px;border-bottom:2px solid #29235C;}
.brand{display:flex;align-items:flex-start;gap:14px;}
.brand-logo{width:52px;height:52px;object-fit:contain;}
.brand-name{font-size:20px;font-weight:900;color:#29235C;letter-spacing:-0.5px;}
.brand-slogan{font-size:10px;color:#7a78a0;font-style:italic;margin-top:2px;}
.brand-info{font-size:10px;color:#555;margin-top:8px;line-height:1.7;}
.doc-meta{text-align:right;}
.doc-type{font-size:32px;font-weight:900;color:#29235C;letter-spacing:-1px;line-height:1;}
.doc-num{font-size:14px;color:#36A9E1;font-weight:700;font-family:'Space Mono',monospace;margin-top:4px;}
.doc-date{font-size:11px;color:#7a78a0;margin-top:6px;}
.statut-badge{display:inline-block;padding:4px 14px;border-radius:99px;font-size:11px;font-weight:700;margin-top:8px;color:#fff;background:<?= $statutColor ?>;}

/* PARTIES */
.parties{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;}
.partie-card{background:#f8f9ff;border:1px solid #e8e8f0;border-radius:12px;padding:16px;}
.partie-label{font-size:9px;font-weight:700;color:#29235C;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;opacity:.7;}
.partie-name{font-size:15px;font-weight:700;color:#1a1a2e;margin-bottom:4px;}
.partie-detail{font-size:11px;color:#555;line-height:1.7;}
.partie-highlight{background:linear-gradient(135deg,#29235C,#36A9E1);color:#fff;border:none;}
.partie-highlight .partie-label{color:rgba(255,255,255,.7);}
.partie-highlight .partie-name{color:#fff;}
.partie-highlight .partie-detail{color:rgba(255,255,255,.8);}

/* OBJET */
.objet-section{background:#f0f7ff;border-left:4px solid #36A9E1;border-radius:0 8px 8px 0;padding:12px 16px;margin-bottom:28px;}
.objet-label{font-size:9px;font-weight:700;color:#36A9E1;text-transform:uppercase;letter-spacing:2px;margin-bottom:4px;}
.objet-text{font-size:13px;font-weight:600;color:#1a1a2e;}
<?php if(!empty($f['projet_nom'])): ?>
.projet-ref{font-size:11px;color:#7a78a0;margin-top:3px;}
<?php endif; ?>

/* TABLEAU LIGNES */
.lignes-table{width:100%;border-collapse:collapse;margin-bottom:24px;}
.lignes-table thead tr{background:#29235C;color:#fff;}
.lignes-table th{padding:10px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;}
.lignes-table th:last-child{text-align:right;}
.lignes-table td{padding:11px 12px;border-bottom:1px solid #f0f0f0;font-size:12px;vertical-align:top;}
.lignes-table td:last-child{text-align:right;font-weight:600;white-space:nowrap;}
.lignes-table tr:nth-child(even) td{background:#fafafa;}
.lignes-table tr:last-child td{border-bottom:none;}
.desc-main{font-weight:500;color:#1a1a2e;}
.mono{font-family:'Space Mono',monospace;font-size:11px;}

/* TOTAUX */
.totaux-section{display:flex;justify-content:flex-end;margin-bottom:28px;}
.totaux-box{width:300px;}
.totaux-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #f0f0f0;font-size:12px;}
.totaux-row:last-child{border:none;}
.totaux-row.total-ht{color:#555;}
.totaux-row.remise{color:#e05252;}
.totaux-row.tva{color:#555;}
.totaux-row.ttc{background:#29235C;color:#fff;padding:12px 14px;border-radius:10px;font-size:15px;font-weight:800;border:none;margin-top:8px;}
.totaux-label{font-weight:500;}
.totaux-val{font-family:'Space Mono',monospace;font-weight:700;}

/* ECHEANCE */
.echeance-box{background:#fff8e1;border:1px solid #f0a500;border-radius:10px;padding:12px 16px;margin-bottom:24px;display:flex;align-items:center;gap:10px;font-size:12px;}
.echeance-label{font-weight:700;color:#f0a500;}

/* NOTES & CONDITIONS */
.notes-section{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px;}
.notes-box{background:#f8f9ff;border:1px solid #e8e8f0;border-radius:10px;padding:14px;}
.notes-title{font-size:10px;font-weight:700;color:#29235C;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px;}
.notes-text{font-size:11px;color:#555;line-height:1.7;}

/* SIGNATURE */
.signature-section{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;}
.sig-box{border:1px solid #e8e8f0;border-radius:10px;padding:16px;text-align:center;}
.sig-label{font-size:10px;font-weight:700;color:#29235C;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:32px;}
.sig-line{border-top:1px solid #ccc;padding-top:8px;font-size:10px;color:#7a78a0;}

/* FOOTER */
.doc-footer{border-top:1px solid #e8e8f0;padding-top:16px;text-align:center;font-size:10px;color:#7a78a0;line-height:1.8;}

/* PRINT */
@media print {
  body{font-size:12px;}
  .page{padding:20px;}
  .no-print{display:none !important;}
  @page{margin:1cm;size:A4;}
}

/* BOUTONS */
.print-bar{position:fixed;bottom:24px;right:24px;display:flex;gap:10px;z-index:100;}
.print-btn{background:#29235C;color:#fff;border:none;border-radius:10px;padding:12px 22px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 20px rgba(41,35,92,.4);}
.print-btn.accent{background:#36A9E1;}
.print-btn svg{width:16px;height:16px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;}
</style>
</head>
<body>

<!-- BOUTONS IMPRESSION -->
<div class="print-bar no-print">
  <button class="print-btn" onclick="history.back()">
    <svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>
    Retour
  </button>
  <button class="print-btn accent" onclick="window.print()">
    <svg viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Imprimer / PDF
  </button>
</div>

<div class="page">

  <!-- HEADER -->
  <div class="doc-header">
    <div class="brand">
      <img src="assets/logo.png" alt="UP TECH GROUP" class="brand-logo">
      <div>
        <div class="brand-name">UP TECH GROUP</div>
        <div class="brand-slogan">Parce que le numérique n'est pas un luxe, mais une nécessité</div>
        <div class="brand-info">
          SARL U · NIF 1002104545<br>
          RCCM TG-LFW-01-2026-B13-01453<br>
          Lomé, Quartier Baguida, Togo<br>
          +228 96 24 25 04 · workspace@uptech-group.com<br>
          uptech-group.com
        </div>
      </div>
    </div>
    <div class="doc-meta">
      <div class="doc-type"><?= $typeLabel ?></div>
      <div class="doc-num"><?= htmlspecialchars($f['numero']) ?></div>
      <div class="doc-date">
        Émis le : <strong><?= date('d/m/Y', strtotime($f['date_emission'])) ?></strong>
        <?php if($f['date_echeance']): ?>
        <br>Échéance : <strong><?= date('d/m/Y', strtotime($f['date_echeance'])) ?></strong>
        <?php endif; ?>
      </div>
      <div><span class="statut-badge"><?= htmlspecialchars($f['statut']) ?></span></div>
    </div>
  </div>

  <!-- PARTIES -->
  <div class="parties">
    <div class="partie-card partie-highlight">
      <div class="partie-label">Émetteur</div>
      <div class="partie-name">UP TECH GROUP SARL U</div>
      <div class="partie-detail">
        Lomé, Quartier Baguida, Togo<br>
        NIF : 1002104545<br>
        +228 96 24 25 04<br>
        workspace@uptech-group.com
      </div>
    </div>
    <div class="partie-card">
      <div class="partie-label">Client / Destinataire</div>
      <div class="partie-name"><?= htmlspecialchars($f['client_nom'] ?? 'Client non défini') ?></div>
      <div class="partie-detail">
        <?php if($f['client_contact']): ?><?= htmlspecialchars($f['client_contact']) ?><br><?php endif; ?>
        <?php if($f['client_email']): ?><?= htmlspecialchars($f['client_email']) ?><br><?php endif; ?>
        <?php if($f['client_tel']): ?><?= htmlspecialchars($f['client_tel']) ?><br><?php endif; ?>
        <?php if($f['client_pays']): ?><?= htmlspecialchars($f['client_pays']) ?><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- OBJET -->
  <?php if($f['objet']): ?>
  <div class="objet-section">
    <div class="objet-label">Objet</div>
    <div class="objet-text"><?= htmlspecialchars($f['objet']) ?></div>
    <?php if($f['projet_nom']): ?><div class="projet-ref">Projet : <?= htmlspecialchars($f['projet_nom']) ?></div><?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- LIGNES -->
  <table class="lignes-table">
    <thead>
      <tr>
        <th style="width:45%">Description</th>
        <th style="width:10%;text-align:center">Qté</th>
        <th style="width:10%">Unité</th>
        <th style="width:15%;text-align:right">Prix unit.</th>
        <?php if(array_sum(array_column($lignes,'remise_pct'))>0): ?><th style="width:8%;text-align:right">Remise</th><?php endif; ?>
        <th style="width:15%;text-align:right">Total HT</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($lignes as $l): ?>
      <tr>
        <td><div class="desc-main"><?= htmlspecialchars($l['description']) ?></div></td>
        <td style="text-align:center" class="mono"><?= number_format($l['quantite'],2,',',' ') ?></td>
        <td><?= htmlspecialchars($l['unite']) ?></td>
        <td class="mono"><?= fmt($l['prix_unit'], $f['devise']) ?></td>
        <?php if(array_sum(array_column($lignes,'remise_pct'))>0): ?><td class="mono"><?= $l['remise_pct']>0?$l['remise_pct'].'%':'—' ?></td><?php endif; ?>
        <td class="mono"><?= fmt($l['total_ht'], $f['devise']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- TOTAUX -->
  <div class="totaux-section">
    <div class="totaux-box">
      <div class="totaux-row total-ht">
        <span class="totaux-label">Total HT</span>
        <span class="totaux-val"><?= fmt($f['montant_ht'], $f['devise']) ?></span>
      </div>
      <?php if($f['remise_pct']>0): ?>
      <div class="totaux-row remise">
        <span class="totaux-label">Remise (<?= $f['remise_pct'] ?>%)</span>
        <span class="totaux-val">- <?= fmt($f['montant_remise'], $f['devise']) ?></span>
      </div>
      <div class="totaux-row total-ht">
        <span class="totaux-label">HT après remise</span>
        <span class="totaux-val"><?= fmt($f['montant_ht']-$f['montant_remise'], $f['devise']) ?></span>
      </div>
      <?php endif; ?>
      <?php if($f['tva_pct']>0): ?>
      <div class="totaux-row tva">
        <span class="totaux-label">TVA (<?= $f['tva_pct'] ?>%)</span>
        <span class="totaux-val"><?= fmt($f['montant_tva'], $f['devise']) ?></span>
      </div>
      <?php endif; ?>
      <div class="totaux-row ttc">
        <span class="totaux-label">TOTAL <?= $f['tva_pct']>0?'TTC':'HT' ?></span>
        <span class="totaux-val"><?= fmt($f['montant_ttc'], $f['devise']) ?></span>
      </div>
    </div>
  </div>

  <!-- ÉCHÉANCE -->
  <?php if($f['date_echeance'] && $f['type']==='Facture'): ?>
  <div class="echeance-box">
    <span style="font-size:18px">⏰</span>
    <div>
      <span class="echeance-label">Date de paiement : </span>
      <?= date('d/m/Y', strtotime($f['date_echeance'])) ?>
      — Montant à régler : <strong><?= fmt($f['montant_ttc'], $f['devise']) ?></strong>
    </div>
  </div>
  <?php endif; ?>

  <!-- NOTES & CONDITIONS -->
  <div class="notes-section">
    <?php if($f['notes']): ?>
    <div class="notes-box">
      <div class="notes-title">Notes</div>
      <div class="notes-text"><?= nl2br(htmlspecialchars($f['notes'])) ?></div>
    </div>
    <?php endif; ?>
    <?php if($f['conditions']): ?>
    <div class="notes-box">
      <div class="notes-title">Conditions de paiement</div>
      <div class="notes-text"><?= nl2br(htmlspecialchars($f['conditions'])) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- SIGNATURES -->
  <?php if($f['type']==='Devis'): ?>
  <div class="signature-section">
    <div class="sig-box">
      <div class="sig-label">Signature UP TECH GROUP</div>
      <div class="sig-line">Nom, date et signature</div>
    </div>
    <div class="sig-box">
      <div class="sig-label">Bon pour accord — Client</div>
      <div class="sig-line">Nom, date et signature</div>
    </div>
  </div>
  <?php endif; ?>

  <!-- FOOTER -->
  <div class="doc-footer">
    UP TECH GROUP SARL U · RCCM TG-LFW-01-2026-B13-01453 · NIF 1002104545 · Lomé, Quartier Baguida, Togo<br>
    Tél : +228 96 24 25 04 · Email : workspace@uptech-group.com · Web : uptech-group.com
  </div>

</div>
</body>
</html>
