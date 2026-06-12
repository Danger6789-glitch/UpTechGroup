<?php
// ============================================
// UP TECH GROUP — Système d'emails automatiques
// ============================================
// Utilise PHPMailer via include manuel (sans Composer)
// Compatible cPanel LWS

class UpTechMailer {

    // ⚠️ CONFIGURER ICI après création de l'adresse email dans cPanel
    private static $config = [
        'smtp_host'     => 'mail.uptech-group.com',   // Serveur SMTP LWS
        'smtp_port'     => 587,                         // TLS
        'smtp_user'     => 'workspace@uptech-group.com', // Email créé dans cPanel
        'smtp_pass'     => '~vNrFn{qx.5BA9g,',  // 
        'from_email'    => 'workspace@uptech-group.com',
        'from_name'     => 'UP TECH GROUP Workspace',
        'reply_to'      => 'contact@uptech-group.com',
        'app_url'       => 'https://uptech-group.com/workspace',
    ];

    // ============ ENVOI PRINCIPAL ============
    public static function send(string $to, string $toName, string $subject, string $htmlBody): bool {
        $c = self::$config;

        // Utilisation de mail() natif PHP comme fallback si SMTP non configuré
        // Pour SMTP, on utilise les headers manuellement
        $boundary = md5(uniqid());
        $headers  = implode("\r\n", [
            "From: {$c['from_name']} <{$c['from_email']}>",
            "Reply-To: {$c['reply_to']}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "X-Mailer: UpTechGroupMailer/1.0",
            "X-Priority: 1",
        ]);

        try {
            $result = mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, $headers);
            self::log($to, $subject, $result ? 'sent' : 'failed');
            return $result;
        } catch (\Exception $e) {
            self::log($to, $subject, 'error: ' . $e->getMessage());
            return false;
        }
    }

    // ============ LOG ============
    private static function log(string $to, string $subject, string $status): void {
        $logFile = __DIR__ . '/logs/emails.log';
        if (!is_dir(__DIR__ . '/logs')) mkdir(__DIR__ . '/logs', 0755, true);
        $line = date('Y-m-d H:i:s') . " | $status | To: $to | Subject: $subject\n";
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    // ============ TEMPLATE DE BASE ============
    private static function template(string $title, string $content, string $cta_text = '', string $cta_url = ''): string {
        $url     = self::$config['app_url'];
        $cta_btn = $cta_text ? "<div style='text-align:center;margin:28px 0'><a href='{$cta_url}' style='display:inline-block;background:linear-gradient(135deg,#29235C,#36A9E1);color:#fff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:700;font-size:15px;letter-spacing:0.3px'>{$cta_text}</a></div>" : '';

        return "<!DOCTYPE html>
<html lang='fr'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width,initial-scale=1'>
<title>{$title}</title>
</head>
<body style='margin:0;padding:0;background:#0f0e1a;font-family:Poppins,Arial,sans-serif;'>
<div style='max-width:580px;margin:0 auto;padding:32px 16px;'>

  <!-- HEADER -->
  <div style='background:linear-gradient(160deg,#29235C 0%,#1a1845 60%,#0d0c22 100%);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;'>
    <div style='font-size:28px;font-weight:900;color:#fff;letter-spacing:-1px;margin-bottom:4px;'>UP TECH GROUP</div>
    <div style='font-size:12px;color:rgba(255,255,255,0.4);letter-spacing:2px;text-transform:uppercase;'>Workspace collaboratif</div>
  </div>

  <!-- BODY -->
  <div style='background:#1a1930;border:1px solid rgba(54,169,225,0.15);border-top:none;border-radius:0 0 16px 16px;padding:36px;'>
    <h2 style='font-size:20px;font-weight:700;color:#fff;margin:0 0 16px;line-height:1.3;'>{$title}</h2>
    <div style='color:#b8b6cc;font-size:14px;line-height:1.8;'>{$content}</div>
    {$cta_btn}
    <hr style='border:none;border-top:1px solid rgba(54,169,225,0.15);margin:24px 0;'>
    <p style='font-size:11px;color:#7a78a0;margin:0;text-align:center;line-height:1.8;'>
      Cet email a été envoyé automatiquement par le Workspace UP TECH GROUP.<br>
      <a href='{$url}' style='color:#36A9E1;text-decoration:none;'>Accéder au workspace</a>
    </p>
  </div>

  <!-- FOOTER -->
  <p style='text-align:center;font-size:11px;color:#3a3860;margin-top:20px;'>
    UP TECH GROUP SARL U &mdash; Lomé, Togo &mdash; NIF 1002104545
  </p>
</div>
</body>
</html>";
    }

    // ============ EMAIL : TÂCHE ASSIGNÉE ============
    public static function tacheAssignee(string $to, string $toName, array $tache, array $assigner): bool {
        $url     = self::$config['app_url'];
        $prio    = ['Haute' => '#e05252', 'Moyenne' => '#f0a500', 'Basse' => '#36A9E1'][$tache['priorite']] ?? '#36A9E1';
        $echeance = !empty($tache['date_echeance']) ? date('d/m/Y', strtotime($tache['date_echeance'])) : 'Non définie';

        $content = "
        <p>Bonjour <strong style='color:#fff'>{$toName}</strong>,</p>
        <p>Une nouvelle tâche vient de vous être assignée par <strong style='color:#36A9E1'>{$assigner['nom']}</strong>.</p>

        <div style='background:#13122a;border:1px solid rgba(54,169,225,0.15);border-left:4px solid {$prio};border-radius:10px;padding:18px 20px;margin:20px 0;'>
          <div style='font-size:16px;font-weight:700;color:#fff;margin-bottom:10px;'>{$tache['titre']}</div>
          " . (!empty($tache['description']) ? "<div style='font-size:13px;color:#b8b6cc;margin-bottom:12px;'>{$tache['description']}</div>" : "") . "
          <table style='width:100%;font-size:12px;'>
            <tr>
              <td style='color:#7a78a0;padding:4px 0;width:120px;'>Priorité</td>
              <td><span style='background:{$prio}22;color:{$prio};padding:2px 10px;border-radius:99px;font-weight:700;font-size:11px;'>{$tache['priorite']}</span></td>
            </tr>
            <tr>
              <td style='color:#7a78a0;padding:4px 0;'>Date limite</td>
              <td style='color:#f0a500;font-weight:600;'>{$echeance}</td>
            </tr>
            " . (!empty($tache['projet_nom']) ? "<tr><td style='color:#7a78a0;padding:4px 0;'>Projet</td><td style='color:#fff;'>{$tache['projet_nom']}</td></tr>" : "") . "
          </table>
        </div>
        <p>Connectez-vous au workspace pour voir les détails et commencer à travailler.</p>";

        return self::send($to, $toName,
            "Nouvelle tâche : {$tache['titre']}",
            self::template("Nouvelle tâche assignée", $content, "Voir la tâche", "{$url}/dashboard.php#taches")
        );
    }

    // ============ EMAIL : DEADLINE DANS 24H ============
    public static function deadlineProche(string $to, string $toName, array $tache): bool {
        $url      = self::$config['app_url'];
        $echeance = date('d/m/Y', strtotime($tache['date_echeance']));

        $content = "
        <p>Bonjour <strong style='color:#fff'>{$toName}</strong>,</p>
        <p>La date limite d'une de vos tâches est dans <strong style='color:#f0a500'>moins de 24 heures</strong>.</p>

        <div style='background:#13122a;border:1px solid rgba(240,165,0,0.3);border-left:4px solid #f0a500;border-radius:10px;padding:18px 20px;margin:20px 0;'>
          <div style='font-size:16px;font-weight:700;color:#fff;margin-bottom:8px;'>{$tache['titre']}</div>
          <div style='font-size:13px;color:#f0a500;font-weight:600;'>Deadline : {$echeance}</div>
          " . (!empty($tache['projet_nom']) ? "<div style='font-size:12px;color:#7a78a0;margin-top:6px;'>Projet : {$tache['projet_nom']}</div>" : "") . "
          <div style='margin-top:10px;'>
            <span style='background:rgba(240,165,0,0.15);color:#f0a500;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;'>Statut : {$tache['statut']}</span>
          </div>
        </div>
        <p>Pensez à mettre à jour le statut de cette tâche dès qu'elle est terminée.</p>";

        return self::send($to, $toName,
            "Rappel : \"{$tache['titre']}\" — Deadline demain",
            self::template("Deadline dans 24 heures", $content, "Mettre à jour la tâche", "{$url}/dashboard.php#taches")
        );
    }

    // ============ EMAIL : NOUVEAU PROJET ============
    public static function nouveauProjet(string $to, string $toName, array $projet, array $createur): bool {
        $url      = self::$config['app_url'];
        $livraison = !empty($projet['date_livraison']) ? date('d/m/Y', strtotime($projet['date_livraison'])) : 'Non définie';
        $budget    = !empty($projet['budget']) ? number_format($projet['budget'], 0, ',', ' ') . ' FCFA' : 'Non défini';

        $content = "
        <p>Bonjour <strong style='color:#fff'>{$toName}</strong>,</p>
        <p>Un nouveau projet a été créé par <strong style='color:#36A9E1'>{$createur['nom']}</strong>.</p>

        <div style='background:#13122a;border:1px solid rgba(54,169,225,0.15);border-left:4px solid #36A9E1;border-radius:10px;padding:18px 20px;margin:20px 0;'>
          <div style='font-size:18px;font-weight:700;color:#fff;margin-bottom:12px;'>{$projet['nom']}</div>
          <table style='width:100%;font-size:12px;'>
            <tr><td style='color:#7a78a0;padding:4px 0;width:130px;'>Type</td><td style='color:#fff;'>{$projet['type_prestation']}</td></tr>
            <tr><td style='color:#7a78a0;padding:4px 0;'>Statut</td><td style='color:#2ecc87;font-weight:600;'>{$projet['statut']}</td></tr>
            <tr><td style='color:#7a78a0;padding:4px 0;'>Date de livraison</td><td style='color:#f0a500;font-weight:600;'>{$livraison}</td></tr>
            <tr><td style='color:#7a78a0;padding:4px 0;'>Budget</td><td style='color:#fff;'>{$budget}</td></tr>
          </table>
          " . (!empty($projet['description']) ? "<div style='margin-top:12px;font-size:13px;color:#b8b6cc;border-top:1px solid rgba(54,169,225,0.1);padding-top:12px;'>{$projet['description']}</div>" : "") . "
        </div>";

        return self::send($to, $toName,
            "Nouveau projet : {$projet['nom']}",
            self::template("Nouveau projet créé", $content, "Voir le projet", "{$url}/dashboard.php#projets")
        );
    }

    // ============ EMAIL : CHANGEMENT STATUT TÂCHE ============
    public static function statutTacheChange(string $to, string $toName, array $tache, string $ancienStatut, string $modifieur): bool {
        $url    = self::$config['app_url'];
        $colors = ['À faire' => '#7a78a0', 'En cours' => '#36A9E1', 'Bloqué' => '#e05252', 'Terminé' => '#2ecc87'];
        $cOld   = $colors[$ancienStatut] ?? '#7a78a0';
        $cNew   = $colors[$tache['statut']] ?? '#7a78a0';

        $content = "
        <p>Bonjour <strong style='color:#fff'>{$toName}</strong>,</p>
        <p>Le statut d'une tâche que vous suivez a été modifié par <strong style='color:#36A9E1'>{$modifieur}</strong>.</p>

        <div style='background:#13122a;border:1px solid rgba(54,169,225,0.15);border-radius:10px;padding:18px 20px;margin:20px 0;'>
          <div style='font-size:16px;font-weight:700;color:#fff;margin-bottom:14px;'>{$tache['titre']}</div>
          <div style='display:flex;align-items:center;gap:12px;flex-wrap:wrap;'>
            <span style='background:{$cOld}22;color:{$cOld};padding:4px 14px;border-radius:99px;font-size:12px;font-weight:700;'>{$ancienStatut}</span>
            <span style='color:#7a78a0;font-size:18px;'>→</span>
            <span style='background:{$cNew}22;color:{$cNew};padding:4px 14px;border-radius:99px;font-size:12px;font-weight:700;'>{$tache['statut']}</span>
          </div>
        </div>";

        return self::send($to, $toName,
            "Tâche mise à jour : {$tache['titre']}",
            self::template("Statut de tâche modifié", $content, "Voir la tâche", "{$url}/dashboard.php#taches")
        );
    }

    // ============ EMAIL : INVITATION / NOUVEAU COMPTE ============
    public static function invitation(string $to, string $toName, string $role, string $password, string $inviteur): bool {
        $url      = self::$config['app_url'];
        $roleLabels = ['admin' => 'Administrateur', 'manager' => 'Manager', 'collaborateur' => 'Collaborateur'];
        $roleLabel  = $roleLabels[$role] ?? $role;
        $roleColors = ['admin' => '#e05252', 'manager' => '#36A9E1', 'collaborateur' => '#2ecc87'];
        $roleColor  = $roleColors[$role] ?? '#36A9E1';

        $content = "
        <p>Bonjour <strong style='color:#fff'>{$toName}</strong>,</p>
        <p><strong style='color:#36A9E1'>{$inviteur}</strong> vous invite à rejoindre le workspace collaboratif d'<strong style='color:#fff'>UP TECH GROUP</strong>.</p>

        <div style='background:#13122a;border:1px solid rgba(54,169,225,0.2);border-radius:10px;padding:24px;margin:20px 0;text-align:center;'>
          <div style='font-size:13px;color:#7a78a0;margin-bottom:6px;'>Votre rôle</div>
          <span style='background:{$roleColor}22;color:{$roleColor};padding:6px 20px;border-radius:99px;font-size:14px;font-weight:700;'>{$roleLabel}</span>
        </div>

        <div style='background:#13122a;border:1px solid rgba(54,169,225,0.15);border-radius:10px;padding:20px;margin:20px 0;'>
          <div style='font-size:13px;color:#7a78a0;margin-bottom:14px;font-weight:600;text-transform:uppercase;letter-spacing:1px;'>Vos identifiants de connexion</div>
          <table style='width:100%;font-size:13px;'>
            <tr>
              <td style='color:#7a78a0;padding:6px 0;width:130px;'>Adresse email</td>
              <td style='color:#fff;font-weight:600;font-family:monospace;'>{$to}</td>
            </tr>
            <tr>
              <td style='color:#7a78a0;padding:6px 0;'>Mot de passe</td>
              <td style='color:#36A9E1;font-weight:700;font-family:monospace;font-size:15px;'>{$password}</td>
            </tr>
          </table>
        </div>

        <div style='background:rgba(240,165,0,0.08);border:1px solid rgba(240,165,0,0.2);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:12px;color:#f0a500;'>
          Changez votre mot de passe dès votre première connexion via Mon Profil.
        </div>

        <p style='font-size:13px;color:#b8b6cc;'>Cliquez sur le bouton ci-dessous pour accéder au workspace :</p>";

        return self::send($to, $toName,
            "Invitation — Workspace UP TECH GROUP",
            self::template("Bienvenue sur le Workspace UP TECH GROUP", $content, "Accéder au workspace", $url)
        );
    }
}
