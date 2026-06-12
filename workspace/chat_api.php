<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/assistant.php';
startSecureSession();
header('Content-Type: application/json');
requireAuth();
$user   = currentUser();
$db     = getDB();
$action = $_REQUEST['action'] ?? '';

if ($action === 'envoyer') {
    $contenu = trim($_POST['contenu'] ?? '');
    $dest_id = $_POST['destinataire_id'] ?: null;
    if (!$contenu) { echo json_encode(['error'=>'Message vide']); exit; }
    $db->prepare("INSERT INTO chat_messages (expediteur_id,destinataire_id,contenu) VALUES (?,?,?)")
       ->execute([$user['id'],$dest_id,htmlspecialchars($contenu,ENT_QUOTES,'UTF-8')]);
    echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]); exit;
}

if ($action === 'historique') {
    $dest_id = $_GET['destinataire_id'] ?? null;
    if ($dest_id) {
        $stmt = $db->prepare("SELECT m.*,CONCAT(u.prenom,' ',u.nom) as expediteur_nom,u.role as expediteur_role FROM chat_messages m LEFT JOIN users u ON u.id=m.expediteur_id WHERE (m.expediteur_id=? AND m.destinataire_id=?) OR (m.expediteur_id=? AND m.destinataire_id=?) ORDER BY m.created_at DESC LIMIT 60");
        $stmt->execute([$user['id'],$dest_id,$dest_id,$user['id']]);
    } else {
        $stmt = $db->prepare("SELECT m.*,CONCAT(u.prenom,' ',u.nom) as expediteur_nom,u.role as expediteur_role FROM chat_messages m LEFT JOIN users u ON u.id=m.expediteur_id WHERE m.destinataire_id IS NULL ORDER BY m.created_at DESC LIMIT 60");
        $stmt->execute();
    }
    echo json_encode(array_reverse($stmt->fetchAll())); exit;
}

if ($action === 'messages') {
    $since   = (int)($_GET['since'] ?? 0);
    $dest_id = $_GET['destinataire_id'] ?? null;
    if ($dest_id) {
        $stmt = $db->prepare("SELECT m.*,CONCAT(u.prenom,' ',u.nom) as expediteur_nom,u.role as expediteur_role FROM chat_messages m LEFT JOIN users u ON u.id=m.expediteur_id WHERE ((m.expediteur_id=? AND m.destinataire_id=?) OR (m.expediteur_id=? AND m.destinataire_id=?)) AND m.id>? ORDER BY m.created_at ASC LIMIT 50");
        $stmt->execute([$user['id'],$dest_id,$dest_id,$user['id'],$since]);
    } else {
        $stmt = $db->prepare("SELECT m.*,CONCAT(u.prenom,' ',u.nom) as expediteur_nom,u.role as expediteur_role FROM chat_messages m LEFT JOIN users u ON u.id=m.expediteur_id WHERE m.destinataire_id IS NULL AND m.id>? ORDER BY m.created_at ASC LIMIT 50");
        $stmt->execute([$since]);
    }
    echo json_encode($stmt->fetchAll()); exit;
}

if ($action === 'contacts') {
    $stmt = $db->prepare("SELECT id,CONCAT(prenom,' ',nom) as nom_complet,role FROM users WHERE actif=1 AND id!=? ORDER BY prenom ASC");
    $stmt->execute([$user['id']]);
    echo json_encode($stmt->fetchAll()); exit;
}

if ($action === 'ai_chat') {
    $message = trim($_POST['message'] ?? '');
    if (!$message) { echo json_encode(['error'=>'Message vide']); exit; }

    // Pas de clé API → assistant local
    if (!defined('OPENROUTER_API_KEY') || !OPENROUTER_API_KEY) {
        $assistant = new UpTechAssistant($db, $user);
        echo json_encode(['success'=>true,'reply'=>$assistant->respond($message)]);
        exit;
    }

    // Données entreprise temps réel
    $stats = [
        'clients'   => $db->query("SELECT COUNT(*) FROM clients WHERE statut='Client actif'")->fetchColumn(),
        'prospects' => $db->query("SELECT COUNT(*) FROM clients WHERE statut='Prospect'")->fetchColumn(),
        'projets'   => $db->query("SELECT COUNT(*) FROM projets WHERE statut='En cours'")->fetchColumn(),
        'taches'    => $db->query("SELECT COUNT(*) FROM taches WHERE statut!='Terminé'")->fetchColumn(),
        'ca_total'  => $db->query("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé'")->fetchColumn(),
        'equipe'    => $db->query("SELECT COUNT(*) FROM users WHERE actif=1")->fetchColumn(),
    ];
    $projStr = implode(', ', array_column($db->query("SELECT nom FROM projets WHERE statut='En cours' LIMIT 5")->fetchAll(),'nom'));

    $systemPrompt = "Tu es l'assistant IA officiel d'UP TECH GROUP, entreprise de solutions numeriques basee a Lome, Togo (SARL U, NIF 1002104545). Slogan : Parce que le numerique n'est pas un luxe, mais une necessite. Activites : developpement logiciels, transformation digitale, conseil IT, formation numerique, applications web et mobiles. Site : uptech-group.com. Donnees temps reel : Clients actifs={$stats['clients']}, Prospects={$stats['prospects']}, Projets en cours={$stats['projets']}".($projStr?" ($projStr)":"").", Taches ouvertes={$stats['taches']}, CA total=".number_format($stats['ca_total'],0,',',' ')." FCFA, Equipe={$stats['equipe']} membre(s). REGLES : Reponds UNIQUEMENT en francais. Sois direct et concis. N'affiche JAMAIS ta reflexion interne. Va directement a la reponse finale.";

    // Historique
    $histStmt = $db->prepare("SELECT role,contenu FROM chat_ai_history WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
    $histStmt->execute([$user['id']]);
    $history = array_reverse($histStmt->fetchAll());

    $messages = [['role'=>'system','content'=>$systemPrompt]];
    foreach ($history as $h) {
        $messages[] = ['role'=>$h['role']==='assistant'?'assistant':'user','content'=>$h['contenu']];
    }
    $messages[] = ['role'=>'user','content'=>$message];

    $payload = [
        'model'       => 'openrouter/free',
        'messages'    => $messages,
        'max_tokens'  => 1000,
        'temperature' => 0.7,
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>json_encode($payload),
        CURLOPT_HTTPHEADER=>[
            'Content-Type: application/json',
            'Authorization: Bearer '.OPENROUTER_API_KEY,
            'HTTP-Referer: https://uptech-group.com/workspace',
            'X-Title: UP TECH GROUP Workspace',
        ],
        CURLOPT_TIMEOUT=>30,
        CURLOPT_SSL_VERIFYPEER=>true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    // Erreur → fallback local
    if ($curlErr || $httpCode !== 200) {
        $assistant = new UpTechAssistant($db, $user);
        echo json_encode(['success'=>true,'reply'=>$assistant->respond($message)]);
        exit;
    }

    $data    = json_decode($response, true);
    $aiReply = $data['choices'][0]['message']['content'] ?? null;

    if (!$aiReply) {
        $assistant = new UpTechAssistant($db, $user);
        echo json_encode(['success'=>true,'reply'=>$assistant->respond($message)]);
        exit;
    }

    // Sauvegarder historique
    $db->prepare("INSERT INTO chat_ai_history (user_id,role,contenu) VALUES (?,?,?)")->execute([$user['id'],'user',$message]);
    $db->prepare("INSERT INTO chat_ai_history (user_id,role,contenu) VALUES (?,?,?)")->execute([$user['id'],'assistant',$aiReply]);
    $db->prepare("DELETE FROM chat_ai_history WHERE user_id=? AND id NOT IN (SELECT id FROM (SELECT id FROM chat_ai_history WHERE user_id=? ORDER BY created_at DESC LIMIT 40) t)")->execute([$user['id'],$user['id']]);

    echo json_encode(['success'=>true,'reply'=>$aiReply]);
    exit;
}

if ($action === 'clear_ai') {
    $db->prepare("DELETE FROM chat_ai_history WHERE user_id=?")->execute([$user['id']]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['error'=>'Action inconnue']);