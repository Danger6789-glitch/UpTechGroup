<?php
// ============================================
// UP TECH GROUP — Cron job : rappels deadlines
// ============================================
// À exécuter toutes les heures via cPanel Cron Jobs :
// 0 * * * * php /home/c2761235c/public_html/workspace/cron_rappels.php
// ============================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/mailer.php';

$db = getDB();

echo "[" . date('Y-m-d H:i:s') . "] Cron rappels démarré\n";

// Tâches dont la deadline est dans les prochaines 24h
$demain = date('Y-m-d', strtotime('+1 day'));
$today  = date('Y-m-d');

$sql = "SELECT t.*, 
               u.email as user_email, 
               CONCAT(u.prenom,' ',u.nom) as user_nom,
               p.nom as projet_nom
        FROM taches t
        LEFT JOIN users u ON u.id = t.assigne_a
        LEFT JOIN projets p ON p.id = t.projet_id
        WHERE t.date_echeance = ?
          AND t.statut NOT IN ('Terminé')
          AND u.email IS NOT NULL
          AND u.actif = 1";

$stmt = $db->prepare($sql);
$stmt->execute([$demain]);
$taches = $stmt->fetchAll();

$sent = 0;
foreach ($taches as $tache) {
    $result = UpTechMailer::deadlineProche(
        $tache['user_email'],
        $tache['user_nom'],
        $tache
    );
    if ($result) {
        $sent++;
        echo "  Rappel envoyé à {$tache['user_email']} pour : {$tache['titre']}\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] {$sent} rappel(s) envoyé(s)\n";
echo "Fin du cron.\n";
