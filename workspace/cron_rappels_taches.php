<?php
/**
 * UP TECH GROUP — Rappels tâches J-1
 * Cron: 0 7 * * * php /home/c2761235c/public_html/workspace/cron_rappels_taches.php
 * Envoie un email à chaque collaborateur dont une tâche arrive à échéance demain
 */
require_once __DIR__ . '/includes/config.php';

$db     = getDB();
$demain = date('Y-m-d', strtotime('+1 day'));
$demainFr = date('d/m/Y', strtotime('+1 day'));

// Récupérer toutes les tâches qui arrivent à échéance demain et non terminées
try {
    $stmt = $db->prepare("
        SELECT
            t.id, t.titre, t.priorite, t.description,
            t.date_echeance, t.statut,
            p.nom AS projet_nom,
            CONCAT(u.prenom, ' ', u.nom) AS assignee_nom,
            u.email AS assignee_email,
            u.prenom AS assignee_prenom
        FROM taches t
        LEFT JOIN users u ON u.id = t.assigne_a
        LEFT JOIN projets p ON p.id = t.projet_id
        WHERE t.date_echeance = ?
          AND t.statut NOT IN ('Terminé')
          AND t.assigne_a IS NOT NULL
          AND u.email IS NOT NULL
          AND u.actif = 1
        ORDER BY FIELD(t.priorite,'Haute','Moyenne','Basse')
    ");
    $stmt->execute([$demain]);
    $taches = $stmt->fetchAll();
} catch(Exception $e) {
    echo "Erreur DB: " . $e->getMessage() . "\n";
    exit;
}

if (empty($taches)) {
    echo date('Y-m-d H:i') . " — Aucune tâche à échéance demain.\n";
    exit;
}

// Grouper les tâches par utilisateur
$parUser = [];
foreach ($taches as $t) {
    $email = $t['assignee_email'];
    if (!isset($parUser[$email])) {
        $parUser[$email] = [
            'prenom' => $t['assignee_prenom'],
            'nom'    => $t['assignee_nom'],
            'email'  => $email,
            'taches' => [],
        ];
    }
    $parUser[$email]['taches'][] = $t;
}

$appUrl   = defined('APP_URL') ? APP_URL : 'https://uptech-group.com/workspace';
$prCols   = ['Haute'=>'#e05252','Moyenne'=>'#f0a500','Basse'=>'#36A9E1'];
$envoyes  = 0;
$erreurs  = 0;

foreach ($parUser as $userData) {
    $prenom  = $userData['prenom'];
    $nbTaches = count($userData['taches']);
    $mot      = $nbTaches > 1 ? 'tâches arrivent' : 'tâche arrive';

    // Construire les lignes de tâches dans l'email
    $lignesTaches = '';
    foreach ($userData['taches'] as $t) {
        $col     = $prCols[$t['priorite']] ?? '#36A9E1';
        $projet  = $t['projet_nom'] ? htmlspecialchars($t['projet_nom']) : 'Sans projet';
        $titre   = htmlspecialchars($t['titre']);
        $desc    = $t['description'] ? '<div style="font-size:12px;color:#666;margin-top:4px">'.htmlspecialchars($t['description']).'</div>' : '';
        $lignesTaches .= "
        <div style='background:#f8f9ff;border:1px solid #e8e8f0;border-radius:10px;padding:14px 16px;margin-bottom:10px'>
            <div style='display:flex;align-items:center;justify-content:space-between;margin-bottom:6px'>
                <span style='font-size:14px;font-weight:700;color:#1a1a2e'>{$titre}</span>
                <span style='background:{$col}22;color:{$col};font-size:11px;font-weight:700;padding:2px 10px;border-radius:99px'>{$t['priorite']}</span>
            </div>
            <div style='font-size:12px;color:#7a78a0'>Projet : {$projet}</div>
            {$desc}
        </div>";
    }

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f2f8;font-family:Arial,sans-serif'>
<div style='max-width:540px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 24px rgba(0,0,0,.08)'>
    <!-- Header -->
    <div style='background:linear-gradient(135deg,#29235C,#36A9E1);padding:24px 32px'>
        <div style='font-size:20px;font-weight:900;color:#fff;letter-spacing:-0.5px'>UP TECH GROUP</div>
        <div style='font-size:12px;color:rgba(255,255,255,.75);margin-top:2px'>Rappel échéance — Workspace</div>
    </div>
    <!-- Body -->
    <div style='padding:28px 32px'>
        <p style='font-size:15px;color:#1a1a2e;margin-bottom:6px'>Bonjour <strong>{$prenom}</strong>,</p>
        <p style='font-size:14px;color:#555;margin-bottom:20px'>
            Tu as <strong>{$nbTaches} {$mot}</strong> à échéance <strong>demain le {$demainFr}</strong>.
        </p>
        {$lignesTaches}
        <div style='text-align:center;margin-top:24px'>
            <a href='{$appUrl}/dashboard.php' style='display:inline-block;background:linear-gradient(135deg,#29235C,#36A9E1);color:#fff;text-decoration:none;padding:13px 32px;border-radius:10px;font-weight:700;font-size:14px'>
                Voir mes tâches
            </a>
        </div>
    </div>
    <!-- Footer -->
    <div style='background:#f8f9ff;padding:16px 32px;text-align:center;font-size:11px;color:#7a78a0;border-top:1px solid #e8e8f0'>
        UP TECH GROUP SARL U &middot; Lomé, Togo &middot; uptech-group.com<br>
        Ce rappel est automatique — ne pas répondre à cet email.
    </div>
</div>
</body></html>";

    $sujet   = '=?UTF-8?B?' . base64_encode("Rappel : {$nbTaches} tâche(s) à rendre demain — UP TECH GROUP") . '?=';
    $headers = implode("\r\n", [
        "From: UP TECH GROUP Workspace <workspace@uptech-group.com>",
        "Reply-To: ariel@uptech-group.com",
        "Content-Type: text/html; charset=UTF-8",
        "MIME-Version: 1.0",
        "X-Mailer: PHP/" . PHP_VERSION,
    ]);

    $ok = mail($userData['email'], $sujet, $html, $headers);
    if ($ok) {
        $envoyes++;
        echo date('Y-m-d H:i') . " — Email envoyé à {$userData['email']} ({$nbTaches} tâche(s))\n";
    } else {
        $erreurs++;
        echo date('Y-m-d H:i') . " — ERREUR envoi à {$userData['email']}\n";
    }
}

echo date('Y-m-d H:i') . " — Terminé : {$envoyes} email(s) envoyé(s), {$erreurs} erreur(s)\n";
