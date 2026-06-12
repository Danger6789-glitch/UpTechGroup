#!/usr/bin/env php
<?php
// ============================================
// UP TECH GROUP — Relances factures automatiques
// CRON : 0 8 * * * php /home/c2761235c/public_html/workspace/cron_relances.php
// ============================================

require_once __DIR__ . '/includes/config.php';

$db = getDB();

function sendRelance(array $facture, string $type, int $joursRetard): void {
    $sujet = '=?UTF-8?B?' . base64_encode('Rappel de paiement — ' . $facture['numero'] . ' — UP TECH GROUP') . '?=';

    $messages = [
        'relance1' => "Nous vous rappelons que la facture <strong>{$facture['numero']}</strong> d'un montant de <strong>" . number_format($facture['montant_ttc'], 0, ',', ' ') . " {$facture['devise']}</strong> était due le <strong>" . date('d/m/Y', strtotime($facture['date_echeance'])) . "</strong>.<br><br>Si ce règlement a déjà été effectué, merci d'ignorer ce message.",
        'relance2' => "Malgré notre premier rappel, nous n'avons pas encore reçu le règlement de la facture <strong>{$facture['numero']}</strong> d'un montant de <strong>" . number_format($facture['montant_ttc'], 0, ',', ' ') . " {$facture['devise']}</strong>.<br><br>Ce règlement était attendu depuis <strong>{$joursRetard} jours</strong>. Nous vous demandons de procéder au paiement dans les plus brefs délais.",
        'relance3' => "Il s'agit de notre dernier rappel amiable concernant la facture <strong>{$facture['numero']}</strong> d'un montant de <strong>" . number_format($facture['montant_ttc'], 0, ',', ' ') . " {$facture['devise']}</strong>, impayée depuis <strong>{$joursRetard} jours</strong>.<br><br>Sans règlement sous 72h, nous nous verrons dans l'obligation de prendre les mesures nécessaires pour le recouvrement de cette créance.",
    ];

    $titres = [
        'relance1' => 'Rappel de paiement',
        'relance2' => '2ème rappel — Paiement en retard',
        'relance3' => 'URGENT — Dernier rappel avant procédure',
    ];

    $colors = ['relance1'=>'#36A9E1','relance2'=>'#f0a500','relance3'=>'#e05252'];

    $html = "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#f5f5f5;padding:32px'>
    <div style='max-width:560px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 20px rgba(0,0,0,.08)'>
      <div style='background:{$colors[$type]};padding:24px 32px'>
        <div style='font-size:20px;font-weight:800;color:#fff'>UP TECH GROUP</div>
        <div style='font-size:13px;color:rgba(255,255,255,.8);margin-top:4px'>{$titres[$type]}</div>
      </div>
      <div style='padding:28px 32px'>
        <p style='font-size:14px;color:#333;margin-bottom:16px'>Bonjour <strong>{$facture['client_nom']}</strong>,</p>
        <p style='font-size:14px;color:#333;line-height:1.7;margin-bottom:20px'>{$messages[$type]}</p>
        <div style='background:#f8f9ff;border:1px solid #e8e8f0;border-radius:12px;padding:16px;margin-bottom:24px'>
          <table style='width:100%;font-size:13px'>
            <tr><td style='color:#666;padding:4px 0'>Facture</td><td style='text-align:right;font-weight:700;color:#29235C'>{$facture['numero']}</td></tr>
            <tr><td style='color:#666;padding:4px 0'>Date d'échéance</td><td style='text-align:right;color:#e05252;font-weight:600'>" . date('d/m/Y', strtotime($facture['date_echeance'])) . "</td></tr>
            <tr><td style='color:#666;padding:4px 0'>Jours de retard</td><td style='text-align:right;color:#e05252;font-weight:600'>{$joursRetard} jours</td></tr>
            <tr><td style='color:#666;padding:4px 0;font-weight:700'>Montant dû</td><td style='text-align:right;font-size:18px;font-weight:800;color:#29235C'>" . number_format($facture['montant_ttc'], 0, ',', ' ') . " {$facture['devise']}</td></tr>
          </table>
        </div>
        <hr style='border:none;border-top:1px solid #e8e8f0;margin:20px 0'>
        <p style='font-size:11px;color:#999;text-align:center'>UP TECH GROUP SARL U · NIF 1002104545 · Lomé, Togo<br>workspace@uptech-group.com · +228 96 24 25 04</p>
      </div>
    </div>
    </body></html>";

    $headers = implode("\r\n", [
        "From: UP TECH GROUP <workspace@uptech-group.com>",
        "Content-Type: text/html; charset=UTF-8",
        "MIME-Version: 1.0",
    ]);

    mail($facture['client_email'], $sujet, $html, $headers);
}

// Récupérer les factures impayées avec échéance dépassée
$stmt = $db->query("
    SELECT f.*, c.raison_sociale as client_nom, c.email as client_email
    FROM factures f
    LEFT JOIN clients c ON c.id = f.client_id
    WHERE f.type = 'Facture'
    AND f.statut IN ('Envoyé', 'Accepté')
    AND f.date_echeance < CURDATE()
    AND f.montant_ttc > 0
    AND c.email IS NOT NULL
    AND c.email != ''
    ORDER BY f.date_echeance ASC
");
$factures = $stmt->fetchAll();

$today    = new DateTime();
$relances = 0;

foreach ($factures as $f) {
    $echeance     = new DateTime($f['date_echeance']);
    $joursRetard  = (int)$today->diff($echeance)->days;
    $typeRelance  = null;

    // Logique de relance :
    // J+7  → 1ère relance douce
    // J+15 → 2ème relance ferme
    // J+30 → 3ème relance urgente
    if ($joursRetard === 7)  $typeRelance = 'relance1';
    if ($joursRetard === 15) $typeRelance = 'relance2';
    if ($joursRetard === 30) $typeRelance = 'relance3';

    if ($typeRelance) {
        sendRelance($f, $typeRelance, $joursRetard);

        // Logger la relance dans les notifications workspace
        $db->prepare("INSERT INTO notifications (user_id, message, lien) SELECT id, ?, ? FROM users WHERE role='admin' LIMIT 1")
           ->execute([
               "Relance envoyée pour la facture {$f['numero']} — {$f['client_nom']} ({$joursRetard}j de retard)",
               "facturation.php"
           ]);

        $relances++;
        echo date('Y-m-d H:i:s') . " | Relance ($typeRelance) envoyée : {$f['numero']} → {$f['client_email']} ({$joursRetard}j retard)\n";
    }
}

echo date('Y-m-d H:i:s') . " | Cron relances terminé — $relances relance(s) envoyée(s)\n";
