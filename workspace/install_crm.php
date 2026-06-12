<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
if (!isAdmin()) die('Admin uniquement.');
$db=$db=getDB();$msgs=[];
try{$db->exec("CREATE TABLE IF NOT EXISTS crm_interactions (id INT AUTO_INCREMENT PRIMARY KEY,client_id INT NOT NULL,user_id INT NOT NULL,type_interaction ENUM('Appel','Email','Réunion','WhatsApp','Visite','Autre') DEFAULT 'Appel',sujet VARCHAR(255) NOT NULL,contenu TEXT,date_interaction DATETIME NOT NULL,duree_min INT DEFAULT NULL,prochain_suivi DATE DEFAULT NULL,note_suivi VARCHAR(255) DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");$msgs[]=['ok','Table crm_interactions créée'];}catch(Exception $e){$msgs[]=['err',$e->getMessage()];}
try{$db->exec("CREATE TABLE IF NOT EXISTS crm_opportunites (id INT AUTO_INCREMENT PRIMARY KEY,client_id INT NOT NULL,titre VARCHAR(255) NOT NULL,valeur DECIMAL(15,2) DEFAULT 0,devise VARCHAR(10) DEFAULT 'FCFA',probabilite TINYINT DEFAULT 50,statut ENUM('Identifiée','Qualifiée','Proposition','Négociation','Gagnée','Perdue') DEFAULT 'Identifiée',date_cloture DATE DEFAULT NULL,notes TEXT,user_id INT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");$msgs[]=['ok','Table crm_opportunites créée'];}catch(Exception $e){$msgs[]=['err',$e->getMessage()];}
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Installation CRM</title>
<style>body{font-family:sans-serif;background:#0f0e1a;color:#e8e6f0;padding:24px;max-width:600px;margin:0 auto;}h1{font-size:20px;font-weight:700;color:#fff;margin-bottom:20px;}.item{padding:12px 16px;border-radius:10px;margin-bottom:8px;font-size:13px;}.ok{background:rgba(46,204,135,.12);border:1px solid rgba(46,204,135,.3);color:#2ecc87;}.err{background:rgba(224,82,82,.12);border:1px solid rgba(224,82,82,.3);color:#f08080;}a{color:#36A9E1;}p{font-size:13px;color:#7a78a0;margin-top:16px;}</style></head><body>
<h1>Installation CRM</h1>
<?php foreach($msgs as[$t,$m]):?><div class="item <?=$t?>"><?=$t==='ok'?'✓':'✗'?> <?=htmlspecialchars($m)?></div><?php endforeach;?>
<p>Installation terminée. <a href="crm.php">Accéder au CRM</a></p>
<p style="color:#e05252">Supprime ce fichier immédiatement.</p>
</body></html>
