<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

startSecureSession();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ---- AUTH ----
if ($action === 'login') {
    $result = login($_POST['email'] ?? '', $_POST['password'] ?? '');
    echo json_encode($result); exit;
}
if ($action === 'logout') { logout(); }

requireAuth();
$user = currentUser();
$db   = getDB();

function safeQ(PDO $db, string $sql, array $p=[]): mixed {
    try { $s=$db->prepare($sql);$s->execute($p);return $s->fetchColumn(); } catch(Exception $e){return 0;}
}
function safeAll(PDO $db, string $sql, array $p=[]): array {
    try { $s=$db->prepare($sql);$s->execute($p);return $s->fetchAll(); } catch(Exception $e){return [];}
}

// ============ STATS ============
if ($action === 'stats') {
    $uid = $user['id'];
    $s = [];
    $s['total_clients']    = safeQ($db,"SELECT COUNT(*) FROM clients WHERE statut='Client actif'");
    $s['total_prospects']  = safeQ($db,"SELECT COUNT(*) FROM clients WHERE statut='Prospect'");
    $s['projets_en_cours'] = safeQ($db,"SELECT COUNT(*) FROM projets WHERE statut='En cours'");
    $s['contrats_signes']  = safeQ($db,"SELECT COUNT(*) FROM projets WHERE statut IN ('Signé','En cours','En test','Livré','Clôturé')");
    $s['projets_livres']   = safeQ($db,"SELECT COUNT(*) FROM projets WHERE statut='Livré'");
    $s['kanban']           = safeAll($db,"SELECT statut, COUNT(*) as nb FROM projets GROUP BY statut");
    $s['ca_total']         = safeQ($db,"SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé'");
    $s['depenses']         = safeQ($db,"SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Sortie' AND statut='Réalisé'");
    $s['ca_mois']          = safeQ($db,"SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND MONTH(date_operation)=MONTH(NOW()) AND YEAR(date_operation)=YEAR(NOW())");
    $s['depenses_mois']    = safeQ($db,"SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Sortie' AND statut='Réalisé' AND MONTH(date_operation)=MONTH(NOW()) AND YEAR(date_operation)=YEAR(NOW())");
    $s['solde']            = $s['ca_total'] - $s['depenses'];
    $s['evolution']        = 0;
    $s['factures_attente'] = safeQ($db,"SELECT COUNT(*) FROM factures WHERE statut='Envoyé'");
    if (isManager()) {
        $s['taches_total']     = safeQ($db,"SELECT COUNT(*) FROM taches WHERE statut!='Terminé'");
        $s['taches_en_cours']  = safeQ($db,"SELECT COUNT(*) FROM taches WHERE statut='En cours'");
        $s['taches_bloquees']  = safeQ($db,"SELECT COUNT(*) FROM taches WHERE statut='Bloqué'");
        $s['taches_terminees'] = safeQ($db,"SELECT COUNT(*) FROM taches WHERE statut='Terminé'");
    } else {
        $s['taches_total']     = safeQ($db,"SELECT COUNT(*) FROM taches WHERE assigne_a=? AND statut!='Terminé'",[$uid]);
        $s['taches_en_cours']  = safeQ($db,"SELECT COUNT(*) FROM taches WHERE assigne_a=? AND statut='En cours'",[$uid]);
        $s['taches_bloquees']  = safeQ($db,"SELECT COUNT(*) FROM taches WHERE assigne_a=? AND statut='Bloqué'",[$uid]);
        $s['taches_terminees'] = safeQ($db,"SELECT COUNT(*) FROM taches WHERE assigne_a=? AND statut='Terminé'",[$uid]);
    }
    $s['equipe'] = safeQ($db,"SELECT COUNT(*) FROM users WHERE actif=1");
    echo json_encode($s); exit;
}

// ============ PIPELINE ============
if ($action === 'pipeline') {
    $rows = safeAll($db,"SELECT statut, COUNT(*) as nb FROM projets GROUP BY statut ORDER BY FIELD(statut,'Prospection','Devis envoyé','Signé','En cours','En test','Livré','Clôturé')");
    echo json_encode($rows); exit;
}

// ============ TÂCHES ============
if ($action === 'mes_taches') {
    $uid   = $user['id'];
    $where = isManager() ? "WHERE t.assigne_a=$uid" : "WHERE t.assigne_a=$uid";
    $sql   = "SELECT t.*, CONCAT(u.prenom,' ',u.nom) as assigne_nom, p.nom as projet_nom
              FROM taches t
              LEFT JOIN users u ON u.id=t.assigne_a
              LEFT JOIN projets p ON p.id=t.projet_id
              WHERE t.assigne_a=$uid
              ORDER BY FIELD(t.priorite,'Haute','Moyenne','Basse'), t.date_echeance ASC";
    echo json_encode(safeAll($db,$sql)); exit;
}

if ($action === 'toutes_taches' && isManager()) {
    $sql = "SELECT t.*, CONCAT(u.prenom,' ',u.nom) as assigne_nom, p.nom as projet_nom
            FROM taches t
            LEFT JOIN users u ON u.id=t.assigne_a
            LEFT JOIN projets p ON p.id=t.projet_id
            ORDER BY FIELD(t.priorite,'Haute','Moyenne','Basse'), t.date_echeance ASC";
    echo json_encode(safeAll($db,$sql)); exit;
}

if ($action === 'update_tache_statut') {
    $id=$_POST['id']??0;$statut=$_POST['statut']??'';$prog=(int)($_POST['progression']??0);
    if(!in_array($statut,['À faire','En cours','Bloqué','Terminé'])){echo json_encode(['error'=>'Statut invalide']);exit;}
    $db->prepare("UPDATE taches SET statut=?,progression=?,updated_at=NOW() WHERE id=?")->execute([$statut,$prog,(int)$id]);
    logActivity($user['id'],'tache_update',"Tâche #$id → $statut");
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'create_tache' && isManager()) {
    $db->prepare("INSERT INTO taches (titre,description,projet_id,assigne_a,cree_par,priorite,statut,date_debut,date_echeance,estimation_heures) VALUES (?,?,?,?,?,?,?,?,?,?)")
       ->execute([$_POST['titre'],$_POST['description']??'',$_POST['projet_id']?:null,$_POST['assigne_a']?:null,$user['id'],$_POST['priorite']??'Moyenne','À faire',$_POST['date_debut']?:null,$_POST['date_echeance']?:null,(float)($_POST['estimation_heures']??0)?:null]);
    $newId=$db->lastInsertId();

    if (!empty($_POST['assigne_a'])) {
        $assigneId = (int)$_POST['assigne_a'];
        addNotification($assigneId, "Nouvelle tâche assignée : " . $_POST['titre'] . " — Priorité " . ($_POST['priorite']??'Moyenne') . ($echeance?" · Échéance ".$echeance:'.'), "dashboard.php");

        // Email au collaborateur assigné
        try {
            $stmt = $db->prepare("SELECT prenom, nom, email FROM users WHERE id=?");
            $stmt->execute([$assigneId]);
            $assignee = $stmt->fetch();
            if ($assignee && !empty($assignee['email'])) {
                $titre    = htmlspecialchars($_POST['titre']);
                $priorite = $_POST['priorite'] ?? 'Moyenne';
                $echeance = !empty($_POST['date_echeance']) ? date('d/m/Y', strtotime($_POST['date_echeance'])) : 'Non définie';
                $desc     = !empty($_POST['description']) ? htmlspecialchars($_POST['description']) : '';
                $appUrl   = defined('APP_URL') ? APP_URL : 'https://uptech-group.com/workspace';
                $prCols   = ['Haute'=>'#e05252','Moyenne'=>'#f0a500','Basse'=>'#36A9E1'];
                $prCol    = $prCols[$priorite] ?? '#36A9E1';

                $html = "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif'>
<div style='max-width:520px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 20px rgba(0,0,0,.08)'>
  <div style='background:linear-gradient(135deg,#29235C,#36A9E1);padding:24px 32px;'>
    <div style='font-size:20px;font-weight:900;color:#fff'>UP TECH GROUP</div>
    <div style='font-size:12px;color:rgba(255,255,255,.8);margin-top:2px'>Workspace — Nouvelle tâche assignée</div>
  </div>
  <div style='padding:28px 32px'>
    <p style='font-size:14px;color:#333;margin-bottom:20px'>Bonjour <strong>{$assignee['prenom']}</strong>,</p>
    <p style='font-size:14px;color:#333;margin-bottom:20px'>Une nouvelle tâche vous a été assignée par <strong>{$user['nom']}</strong>.</p>
    <div style='background:#f8f9ff;border:1px solid #e8e8f0;border-radius:12px;padding:20px;margin-bottom:24px'>
      <div style='font-size:16px;font-weight:800;color:#29235C;margin-bottom:14px'>{$titre}</div>
      <table style='width:100%;font-size:13px;border-collapse:collapse'>
        <tr><td style='color:#7a78a0;padding:5px 0;font-weight:600'>Priorité</td><td style='text-align:right'><span style='background:{$prCol}22;color:{$prCol};padding:2px 10px;border-radius:99px;font-size:12px;font-weight:700'>{$priorite}</span></td></tr>
        <tr><td style='color:#7a78a0;padding:5px 0;font-weight:600;border-top:1px solid #f0f0f0'>Échéance</td><td style='text-align:right;color:#1a1a2e;font-weight:600;border-top:1px solid #f0f0f0'>{$echeance}</td></tr>
        ".($desc?"<tr><td style='color:#7a78a0;padding:5px 0;font-weight:600;border-top:1px solid #f0f0f0;vertical-align:top'>Description</td><td style='text-align:right;color:#555;border-top:1px solid #f0f0f0'>{$desc}</td></tr>":'')."
      </table>
    </div>
    <div style='text-align:center;margin-bottom:20px'>
      <a href='{$appUrl}/dashboard.php' style='display:inline-block;background:linear-gradient(135deg,#29235C,#36A9E1);color:#fff;text-decoration:none;padding:12px 30px;border-radius:10px;font-weight:700;font-size:14px'>Voir mes tâches</a>
    </div>
  </div>
  <div style='background:#f8f9ff;padding:16px;text-align:center;font-size:11px;color:#7a78a0;border-top:1px solid #e8e8f0'>
    UP TECH GROUP SARL U &middot; Lomé, Togo &middot; uptech-group.com
  </div>
</div>
</body></html>";

                $sujet   = '=?UTF-8?B?' . base64_encode("Nouvelle tâche : {$_POST['titre']} — UP TECH GROUP") . '?=';
                $headers = implode("\r\n", [
                    "From: UP TECH GROUP Workspace <workspace@uptech-group.com>",
                    "Reply-To: ariel@uptech-group.com",
                    "Content-Type: text/html; charset=UTF-8",
                    "MIME-Version: 1.0",
                ]);
                mail($assignee['email'], $sujet, $html, $headers);
            }
        } catch(Exception $e) {}
    }
    logActivity($user['id'],'tache_create',$_POST['titre']);
    echo json_encode(['success'=>true,'id'=>$newId]); exit;
}

if ($action === 'update_tache' && isManager()) {
    $id=(int)$_POST['id'];
    $db->prepare("UPDATE taches SET titre=?,description=?,assigne_a=?,projet_id=?,priorite=?,statut=?,date_debut=?,date_echeance=?,estimation_heures=?,updated_at=NOW() WHERE id=?")
       ->execute([$_POST['titre'],$_POST['description']??'',$_POST['assigne_a']?:null,$_POST['projet_id']?:null,$_POST['priorite']??'Moyenne',$_POST['statut']??'À faire',$_POST['date_debut']?:null,$_POST['date_echeance']?:null,(float)($_POST['estimation_heures']??0)?:null,$id]);
    logActivity($user['id'],'tache_update',"Modification tâche #$id");
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'delete_tache' && isManager()) {
    $id=(int)$_POST['id'];
    $db->prepare("DELETE FROM taches WHERE id=?")->execute([$id]);
    logActivity($user['id'],'tache_delete',"Suppression tâche #$id");
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'get_tache') {
    $id=(int)($_GET['id']??0);
    $stmt=$db->prepare("SELECT t.*,CONCAT(u.prenom,' ',u.nom) as assigne_nom,p.nom as projet_nom FROM taches t LEFT JOIN users u ON u.id=t.assigne_a LEFT JOIN projets p ON p.id=t.projet_id WHERE t.id=?");
    $stmt->execute([$id]); echo json_encode($stmt->fetch()); exit;
}

// Commentaires tâches
if ($action === 'tache_commentaires') {
    $id=(int)($_GET['tache_id']??0);
    $rows=safeAll($db,"SELECT c.*,CONCAT(u.prenom,' ',u.nom) as auteur_nom FROM tache_commentaires c LEFT JOIN users u ON u.id=c.user_id WHERE c.tache_id=? ORDER BY c.created_at ASC",[$id]);
    echo json_encode($rows); exit;
}
if ($action === 'add_commentaire') {
    $tid=(int)$_POST['tache_id']; $contenu=trim($_POST['contenu']??'');
    if(!$contenu){echo json_encode(['error'=>'Contenu vide']);exit;}
    try{
        $db->prepare("CREATE TABLE IF NOT EXISTS tache_commentaires (id INT AUTO_INCREMENT PRIMARY KEY,tache_id INT NOT NULL,user_id INT NOT NULL,contenu TEXT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(tache_id) REFERENCES taches(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")->execute();
        $db->prepare("INSERT INTO tache_commentaires (tache_id,user_id,contenu) VALUES (?,?,?)")->execute([$tid,$user['id'],$contenu]);
        $newId=$db->lastInsertId();
        // Notifier l'assigné si différent
        $t=$db->prepare("SELECT assigne_a,titre FROM taches WHERE id=?");$t->execute([$tid]);$tache=$t->fetch();
        if($tache&&$tache['assigne_a']&&$tache['assigne_a']!=$user['id']) addNotification($tache['assigne_a'],"Commentaire sur : ".$tache['titre'],"dashboard.php");
        echo json_encode(['success'=>true,'id'=>$newId]);
    }catch(Exception $e){echo json_encode(['error'=>$e->getMessage()]);}
    exit;
}
if ($action === 'delete_commentaire' && isManager()) {
    $db->prepare("DELETE FROM tache_commentaires WHERE id=?")->execute([(int)$_POST['id']]);
    echo json_encode(['success'=>true]); exit;
}

// ============ PROJETS ============
if ($action === 'projets') {
    $filtre = $_GET['statut'] ?? '';
    $where  = $filtre ? "WHERE p.statut=".($db->quote($filtre)) : '';
    $sql    = "SELECT p.*,c.raison_sociale as client_nom,CONCAT(u.prenom,' ',u.nom) as manager_nom FROM projets p LEFT JOIN clients c ON c.id=p.client_id LEFT JOIN users u ON u.id=p.manager_id $where ORDER BY p.updated_at DESC";
    echo json_encode(safeAll($db,$sql)); exit;
}

if ($action === 'get_projet') {
    $id=(int)($_GET['id']??0);
    $stmt=$db->prepare("SELECT p.*,c.raison_sociale as client_nom FROM projets p LEFT JOIN clients c ON c.id=p.client_id WHERE p.id=?");
    $stmt->execute([$id]); echo json_encode($stmt->fetch()); exit;
}

if ($action === 'create_projet' && isManager()) {
    $db->prepare("INSERT INTO projets (nom,client_id,type_prestation,statut,priorite,description,lien_drive,date_debut,date_livraison,budget,manager_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$_POST['nom'],$_POST['client_id']?:null,$_POST['type_prestation']??'Développement',$_POST['statut']??'Prospection',$_POST['priorite']??'Moyenne',$_POST['description']??'',$_POST['lien_drive']??'',$_POST['date_debut']?:null,$_POST['date_livraison']?:null,(float)($_POST['budget']??0),$_POST['manager_id']?:null,$user['id']]);
    $newId=$db->lastInsertId();
    logActivity($user['id'],'projet_create',$_POST['nom']);
    echo json_encode(['success'=>true,'id'=>$newId]); exit;
}

if ($action === 'update_projet' && isManager()) {
    $id=(int)$_POST['id'];
    $db->prepare("UPDATE projets SET nom=?,client_id=?,type_prestation=?,statut=?,priorite=?,description=?,lien_drive=?,date_debut=?,date_livraison=?,budget=?,manager_id=?,updated_at=NOW() WHERE id=?")
       ->execute([$_POST['nom'],$_POST['client_id']?:null,$_POST['type_prestation']??'Développement',$_POST['statut']??'Prospection',$_POST['priorite']??'Moyenne',$_POST['description']??'',$_POST['lien_drive']??'',$_POST['date_debut']?:null,$_POST['date_livraison']?:null,(float)($_POST['budget']??0),$_POST['manager_id']?:null,$id]);
    logActivity($user['id'],'projet_update',"Modification projet #$id");
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'update_projet_statut' && isManager()) {
    $db->prepare("UPDATE projets SET statut=?,updated_at=NOW() WHERE id=?")->execute([$_POST['statut'],(int)$_POST['id']]);
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'delete_projet' && isManager()) {
    $id=(int)$_POST['id'];
    // Soft: désassocier les tâches et factures plutôt que cascader
    $db->prepare("UPDATE taches SET projet_id=NULL WHERE projet_id=?")->execute([$id]);
    try{$db->prepare("UPDATE factures SET projet_id=NULL WHERE projet_id=?")->execute([$id]);}catch(Exception $e){}
    $db->prepare("DELETE FROM projets WHERE id=?")->execute([$id]);
    logActivity($user['id'],'projet_delete',"Suppression projet #$id");
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'dupliquer_projet' && isManager()) {
    $id=(int)$_POST['id'];
    $stmt=$db->prepare("SELECT * FROM projets WHERE id=?");$stmt->execute([$id]);$p=$stmt->fetch();
    if(!$p){echo json_encode(['error'=>'Projet introuvable']);exit;}
    $db->prepare("INSERT INTO projets (nom,client_id,type_prestation,statut,priorite,description,lien_drive,date_debut,date_livraison,budget,manager_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute(["Copie de ".$p['nom'],$p['client_id'],$p['type_prestation'],'Prospection',$p['priorite'],$p['description'],$p['lien_drive'],$p['date_debut'],$p['date_livraison'],$p['budget'],$p['manager_id'],$user['id']]);
    echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]); exit;
}

// ============ CLIENTS ============
if ($action === 'clients') {
    echo json_encode(safeAll($db,"SELECT * FROM clients ORDER BY raison_sociale ASC")); exit;
}

if ($action === 'get_client') {
    $id=(int)($_GET['id']??0);
    $stmt=$db->prepare("SELECT * FROM clients WHERE id=?");$stmt->execute([$id]);
    echo json_encode($stmt->fetch()); exit;
}

if ($action === 'create_client' && isManager()) {
    // Vérifier doublon
    $check=$db->prepare("SELECT id FROM clients WHERE raison_sociale=?");$check->execute([$_POST['raison_sociale']]);
    if($check->fetch()){echo json_encode(['error'=>'Un client avec ce nom existe déjà.']);exit;}
    $db->prepare("INSERT INTO clients (raison_sociale,type,statut,secteur,contact_nom,email,telephone,adresse,site_web,pays,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$_POST['raison_sociale'],$_POST['type']??'Entreprise',$_POST['statut']??'Prospect',$_POST['secteur']??'',$_POST['contact_nom']??'',$_POST['email']??'',$_POST['telephone']??'',$_POST['adresse']??'',$_POST['site_web']??'',$_POST['pays']??'Togo',$_POST['notes']??'',$user['id']]);
    logActivity($user['id'],'client_create',$_POST['raison_sociale']);
    echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]); exit;
}

if ($action === 'update_client' && isManager()) {
    $id=(int)$_POST['id'];
    $db->prepare("UPDATE clients SET raison_sociale=?,type=?,statut=?,secteur=?,contact_nom=?,email=?,telephone=?,adresse=?,site_web=?,pays=?,notes=? WHERE id=?")
       ->execute([$_POST['raison_sociale'],$_POST['type']??'Entreprise',$_POST['statut']??'Prospect',$_POST['secteur']??'',$_POST['contact_nom']??'',$_POST['email']??'',$_POST['telephone']??'',$_POST['adresse']??'',$_POST['site_web']??'',$_POST['pays']??'Togo',$_POST['notes']??'',$id]);
    logActivity($user['id'],'client_update',"Modification client #$id");
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'delete_client' && isManager()) {
    $id=(int)$_POST['id'];
    // Vérifier projets liés
    $nb=safeQ($db,"SELECT COUNT(*) FROM projets WHERE client_id=?",[$id]);
    if($nb>0 && empty($_POST['force'])){echo json_encode(['error'=>"Ce client a $nb projet(s) lié(s).",'needs_confirm'=>true]);exit;}
    $db->prepare("UPDATE projets SET client_id=NULL WHERE client_id=?")->execute([$id]);
    $db->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
    logActivity($user['id'],'client_delete',"Suppression client #$id");
    echo json_encode(['success'=>true]); exit;
}

// ============ FINANCES ============
if ($action === 'tresorerie' && isManager()) {
    $sql="SELECT t.*,CONCAT(u.prenom,' ',u.nom) as createur,p.nom as projet_nom FROM tresorerie t LEFT JOIN users u ON u.id=t.created_by LEFT JOIN projets p ON p.id=t.projet_id ORDER BY t.date_operation DESC LIMIT 100";
    echo json_encode(safeAll($db,$sql)); exit;
}

if ($action === 'add_operation' && isManager()) {
    // Valider montant positif
    $montant=(float)($_POST['montant']??0);
    if($montant<=0){echo json_encode(['error'=>'Le montant doit être positif.']);exit;}
    $db->prepare("INSERT INTO tresorerie (description,type,categorie,montant,date_operation,projet_id,moyen_paiement,statut,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
       ->execute([$_POST['description'],$_POST['type'],$_POST['categorie']??'Autre',$montant,$_POST['date_operation'],$_POST['projet_id']?:null,$_POST['moyen_paiement']??'Mobile Money',$_POST['statut']??'Réalisé',$user['id']]);
    logActivity($user['id'],'finance_add',$_POST['type'].': '.$montant.' FCFA - '.$_POST['description']);
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'delete_operation' && isManager()) {
    $db->prepare("DELETE FROM tresorerie WHERE id=?")->execute([(int)$_POST['id']]);
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'budget_projet' && isManager()) {
    $id=(int)($_GET['projet_id']??0);
    $budget=safeQ($db,"SELECT budget FROM projets WHERE id=?",[$id]);
    $depenses=safeQ($db,"SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE projet_id=? AND type='Sortie' AND statut='Réalisé'",[$id]);
    $revenus=safeQ($db,"SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE projet_id=? AND type='Entrée' AND statut='Réalisé'",[$id]);
    echo json_encode(['budget'=>$budget,'depenses'=>$depenses,'revenus'=>$revenus,'restant'=>$budget-$depenses]); exit;
}

// ============ UTILISATEURS ============
if ($action === 'users' && isAdmin()) {
    // Inclure heure précise de last_login
    echo json_encode(safeAll($db,"SELECT id,nom,prenom,email,role,actif,DATE_FORMAT(last_login,'%d/%m/%Y à %H:%i') as last_login,poste,created_at FROM users ORDER BY created_at DESC")); exit;
}

if ($action === 'create_user' && isAdmin()) {
    $prenom=trim($_POST['prenom']??'');$nom=trim($_POST['nom']??'');$email=trim($_POST['email']??'');$pass=$_POST['password']??'';$role=$_POST['role']??'collaborateur';
    if(!$prenom||!$nom||!$email||!$pass){echo json_encode(['error'=>'Tous les champs sont obligatoires.']);exit;}
    if(strlen($pass)<8){echo json_encode(['error'=>'Mot de passe trop court (min. 8 caractères).']);exit;}
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){echo json_encode(['error'=>'Email invalide.']);exit;}
    $check=$db->prepare("SELECT id FROM users WHERE email=?");$check->execute([$email]);
    if($check->fetch()){echo json_encode(['error'=>'Cet email est déjà utilisé.']);exit;}
    $hash=password_hash($pass,PASSWORD_BCRYPT,['cost'=>12]);
    try{
        $db->prepare("INSERT INTO users (prenom,nom,email,password,role,actif) VALUES (?,?,?,?,?,1)")->execute([$prenom,$nom,$email,$hash,$role]);
        $newId=$db->lastInsertId();
    }catch(Exception $e){echo json_encode(['error'=>'Erreur lors de la création.']);exit;}
    // Email de bienvenue
    $appUrl=defined('APP_URL')?APP_URL:'https://uptech-group.com/workspace';
    $html="<!DOCTYPE html><html><body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif'><div style='max-width:560px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden'><div style='background:linear-gradient(135deg,#29235C,#36A9E1);padding:32px;text-align:center'><div style='font-size:24px;font-weight:900;color:#fff'>UP TECH GROUP</div><div style='font-size:13px;color:rgba(255,255,255,.8);margin-top:4px'>Workspace collaboratif</div></div><div style='padding:32px'><h2 style='font-size:20px;font-weight:700;color:#1a1a2e;margin-bottom:8px'>Bienvenue, {$prenom} !</h2><p style='font-size:14px;color:#555;line-height:1.7;margin-bottom:24px'>Votre compte a ete cree sur le workspace d'UP TECH GROUP.</p><div style='background:#f8f9ff;border:1px solid #e8e8f0;border-radius:12px;padding:20px;margin-bottom:24px'><table style='width:100%;font-size:13px;border-collapse:collapse'><tr><td style='color:#7a78a0;padding:8px 0;font-weight:600'>Email</td><td style='color:#1a1a2e;font-weight:700;text-align:right'>{$email}</td></tr><tr><td style='color:#7a78a0;padding:8px 0;font-weight:600;border-top:1px solid #f0f0f0'>Mot de passe</td><td style='color:#29235C;font-weight:800;text-align:right;font-family:monospace;font-size:16px;border-top:1px solid #f0f0f0'>{$pass}</td></tr><tr><td style='color:#7a78a0;padding:8px 0;font-weight:600;border-top:1px solid #f0f0f0'>Role</td><td style='text-align:right;border-top:1px solid #f0f0f0'><span style='background:rgba(54,169,225,.15);color:#36A9E1;padding:3px 12px;border-radius:99px;font-size:12px;font-weight:700'>".ucfirst($role)."</span></td></tr></table></div><div style='text-align:center;margin-bottom:24px'><a href='{$appUrl}' style='display:inline-block;background:linear-gradient(135deg,#29235C,#36A9E1);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px'>Acceder au workspace</a></div><div style='background:#fff8e1;border:1px solid #f0a500;border-radius:10px;padding:14px 16px;font-size:12px;color:#854f0b;line-height:1.6'><strong>Important :</strong> Changez votre mot de passe apres votre premiere connexion dans Mon Profil &gt; Securite.</div></div><div style='background:#f8f9ff;padding:20px;text-align:center;font-size:11px;color:#7a78a0;border-top:1px solid #e8e8f0'>UP TECH GROUP SARL U &middot; NIF 1002104545 &middot; Lome, Togo</div></div></body></html>";
    $sujet='=?UTF-8?B?'.base64_encode('Bienvenue sur le workspace — UP TECH GROUP').'?=';
    $headers=implode("\r\n",["From: UP TECH GROUP Workspace <workspace@uptech-group.com>","Reply-To: ariel@uptech-group.com","Content-Type: text/html; charset=UTF-8","MIME-Version: 1.0"]);
    $sent=mail($email,$sujet,$html,$headers);
    addNotification($user['id'],"Nouveau collaborateur : {$prenom} {$nom} ({$role})".($sent?" — Email envoye":" — Email non envoye"),'dashboard.php');
    logActivity($user['id'],'user_create',"Création compte {$prenom} {$nom} ({$role})");
    echo json_encode(['success'=>true,'id'=>$newId,'email_envoye'=>$sent,'message'=>$sent?"Compte créé. Email envoyé à {$email}.":"Compte créé. Email non envoyé — transmettez les identifiants manuellement."]); exit;
}

if ($action === 'toggle_user' && isAdmin()) {
    $id=(int)$_POST['id'];
    if($id===$user['id']){echo json_encode(['error'=>'Impossible de modifier votre propre statut.']);exit;}
    $db->prepare("UPDATE users SET actif=1-actif WHERE id=? AND id!=1")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'delete_user' && isAdmin()) {
    $id=(int)$_POST['id'];
    if($id===1||$id===$user['id']){echo json_encode(['error'=>'Impossible de supprimer ce compte.']);exit;}
    $db->prepare("UPDATE taches SET assigne_a=NULL WHERE assigne_a=?")->execute([$id]);
    try{$db->prepare("DELETE FROM notifications WHERE user_id=?")->execute([$id]);}catch(Exception $e){}
    $db->prepare("DELETE FROM users WHERE id=? AND id!=1")->execute([$id]);
    logActivity($user['id'],'user_delete',"Suppression compte user #$id");
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'reset_password_user' && isAdmin()) {
    $id=(int)$_POST['id'];$newPass=trim($_POST['password']??'');
    if(strlen($newPass)<8){echo json_encode(['error'=>'Mot de passe trop court.']);exit;}
    $stmt=$db->prepare("SELECT prenom,nom,email FROM users WHERE id=?");$stmt->execute([$id]);$u=$stmt->fetch();
    if(!$u){echo json_encode(['error'=>'Utilisateur introuvable.']);exit;}
    $hash=password_hash($newPass,PASSWORD_BCRYPT,['cost'=>12]);
    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$id]);
    // Email
    $html="<div style='font-family:sans-serif;max-width:480px;margin:auto;background:#fff;border-radius:12px;overflow:hidden'><div style='background:linear-gradient(135deg,#29235C,#36A9E1);padding:24px;text-align:center;color:#fff;font-size:18px;font-weight:700'>UP TECH GROUP</div><div style='padding:24px'><p>Bonjour <strong>{$u['prenom']}</strong>,</p><p style='margin:12px 0'>Votre mot de passe a ete reinitialise par l'administrateur.</p><div style='background:#f8f9ff;border:1px solid #e0e0f0;border-radius:8px;padding:16px;margin:16px 0;text-align:center'><div style='font-size:11px;color:#7a78a0;margin-bottom:4px'>Nouveau mot de passe</div><div style='font-size:22px;font-weight:800;font-family:monospace;color:#29235C;letter-spacing:2px'>{$newPass}</div></div><div style='text-align:center'><a href='https://uptech-group.com/workspace/' style='background:linear-gradient(135deg,#29235C,#36A9E1);color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;display:inline-block'>Se connecter</a></div></div></div>";
    $sujet='=?UTF-8?B?'.base64_encode('Nouveau mot de passe — UP TECH GROUP').'?=';
    $headers="From: UP TECH GROUP <workspace@uptech-group.com>\r\nContent-Type: text/html; charset=UTF-8\r\nMIME-Version: 1.0";
    $sent=mail($u['email'],$sujet,$html,$headers);
    logActivity($user['id'],'password_reset',"Reset mdp user #{$id}");
    echo json_encode(['success'=>true,'email_envoye'=>$sent]); exit;
}

// ============ NOTIFICATIONS ============
if ($action === 'notifs') {
    try {
        $stmt = $db->prepare("
            SELECT id, message, lien, lu,
                   DATE_FORMAT(created_at, '%d/%m à %H:%i') as created_at
            FROM notifications
            WHERE user_id=? AND lu=0
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$user['id']]);
        echo json_encode($stmt->fetchAll());
    } catch(Exception $e) { echo json_encode([]); }
    exit;
}
if ($action === 'read_notifs') {
    markNotificationsRead($user['id']); echo json_encode(['success'=>true]); exit;
}
if ($action === 'marquer_notif_lue') {
    $id=(int)$_POST['id'];
    try{$db->prepare("UPDATE notifications SET lu=1 WHERE id=? AND user_id=?")->execute([$id,$user['id']]);}catch(Exception $e){}
    echo json_encode(['success'=>true]); exit;
}
if ($action === 'notif_count') {
    $n=safeQ($db,"SELECT COUNT(*) FROM notifications WHERE user_id=? AND lu=0",[$user['id']]);
    echo json_encode(['count'=>(int)$n]); exit;
}

// ============ LISTE USERS ============
if ($action === 'liste_users') {
    echo json_encode(safeAll($db,"SELECT id,CONCAT(prenom,' ',nom) as nom_complet,role,poste FROM users WHERE actif=1 ORDER BY prenom")); exit;
}

// ============ CHARGE DE TRAVAIL ============
if ($action === 'charge_travail' && isManager()) {
    $rows=safeAll($db,"SELECT u.id,CONCAT(u.prenom,' ',u.nom) as nom,u.poste,COUNT(t.id) as nb_taches,SUM(CASE WHEN t.priorite='Haute' THEN 1 ELSE 0 END) as taches_haute FROM users u LEFT JOIN taches t ON t.assigne_a=u.id AND t.statut NOT IN ('Terminé') WHERE u.actif=1 GROUP BY u.id ORDER BY nb_taches DESC");
    echo json_encode($rows); exit;
}

// ============ ACTIVITÉ LOG ============
if ($action === 'activite_user') {
    $uid=(int)($_GET['user_id']??$user['id']);
    $rows=safeAll($db,"SELECT * FROM activity_log WHERE user_id=? ORDER BY created_at DESC LIMIT 20",[$uid]);
    echo json_encode($rows); exit;
}

// ============ PROFIL ============
if ($action === 'update_profil') {
    $prenom=trim($_POST['prenom']??'');$nom=trim($_POST['nom']??'');
    if(!$prenom||!$nom){echo json_encode(['error'=>'Prénom et nom obligatoires.']);exit;}
    $db->prepare("UPDATE users SET prenom=?,nom=?,telephone=?,poste=?,bio=? WHERE id=?")->execute([$prenom,$nom,trim($_POST['telephone']??''),trim($_POST['poste']??''),trim($_POST['bio']??''),$user['id']]);
    $_SESSION['user_nom']=$prenom.' '.$nom;
    logActivity($user['id'],'profil_update','Modification du profil');
    echo json_encode(['success'=>true]); exit;
}

// ============ MOT DE PASSE ============
if ($action === 'change_password') {
    $current=$_POST['current']??'';$new=$_POST['new']??'';
    if(strlen($new)<8){echo json_encode(['error'=>'Mot de passe trop court.']);exit;}
    $stmt=$db->prepare("SELECT password FROM users WHERE id=?");$stmt->execute([$user['id']]);$row=$stmt->fetch();
    if(!$row||!password_verify($current,$row['password'])){echo json_encode(['error'=>'Mot de passe actuel incorrect.']);exit;}
    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT,['cost'=>12]),$user['id']]);
    logActivity($user['id'],'password_change','Changement de mot de passe');
    echo json_encode(['success'=>true]); exit;
}

// ============ 2FA ============
if ($action === 'toggle_2fa') {
    $stmt=$db->prepare("SELECT deux_fa_actif FROM users WHERE id=?");$stmt->execute([$user['id']]);
    $nouveau=(int)$stmt->fetchColumn()?0:1;
    $db->prepare("UPDATE users SET deux_fa_actif=? WHERE id=?")->execute([$nouveau,$user['id']]);
    echo json_encode(['success'=>true,'actif'=>$nouveau]); exit;
}
if ($action === 'statut_2fa') {
    $stmt=$db->prepare("SELECT deux_fa_actif FROM users WHERE id=?");$stmt->execute([$user['id']]);
    echo json_encode(['actif'=>(int)$stmt->fetchColumn()]); exit;
}

// ===== TERMINER UNE TÂCHE (avec notifications équipe) =====
if ($action === 'terminer_tache') {
    $id    = (int)($_POST['id'] ?? 0);
    $titre = trim($_POST['titre'] ?? '');

    // Récupérer infos tâche
    $stmt = $db->prepare("SELECT t.*, p.nom as projet_nom, CONCAT(u.prenom,' ',u.nom) as assignee_nom
                          FROM taches t
                          LEFT JOIN projets p ON p.id=t.projet_id
                          LEFT JOIN users u ON u.id=t.assigne_a
                          WHERE t.id=?");
    $stmt->execute([$id]);
    $tache = $stmt->fetch();
    if (!$tache) { echo json_encode(['error'=>'Tâche introuvable']); exit; }

    // Marquer comme terminée
    $db->prepare("UPDATE taches SET statut='Terminé', progression=100, updated_at=NOW() WHERE id=?")
       ->execute([$id]);

    // Notifier TOUS les membres actifs sauf la personne qui vient de terminer
    $auteur = $user['nom'];
    $projet = $tache['projet_nom'] ? " (projet : {$tache['projet_nom']})" : '';
    $msg    = "{$auteur} a terminé la tâche : {$tache['titre']}{$projet}";

    try {
        $users = $db->query("SELECT id FROM users WHERE actif=1 AND id != {$user['id']}")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($users as $uid) {
            addNotification((int)$uid, $msg, 'dashboard.php');
        }
    } catch(Exception $e) {}

    try { logActivity($user['id'], 'tache_terminee', $tache['titre']); } catch(Exception $e) {}
    echo json_encode(['success'=>true]); exit;
}


if ($action === 'update_user_infos' && isAdmin()) {
    $id    = (int)($_POST['id'] ?? 0);
    $prenom= trim($_POST['prenom'] ?? '');
    $nom   = trim($_POST['nom'] ?? '');
    $poste = trim($_POST['poste'] ?? '');
    $role  = $_POST['role'] ?? 'collaborateur';
    if (!$prenom || !$nom) { echo json_encode(['error'=>'Prénom et nom obligatoires']); exit; }
    if (!in_array($role, ['collaborateur','manager','admin'])) { echo json_encode(['error'=>'Rôle invalide']); exit; }
    $db->prepare("UPDATE users SET prenom=?,nom=?,poste=?,role=? WHERE id=?")->execute([$prenom,$nom,$poste,$role,$id]);
    try { logActivity($user['id'],'user_update',"Modification compte #{$id}"); } catch(Exception $e) {}
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['error'=>'Action inconnue: '.$action]);
