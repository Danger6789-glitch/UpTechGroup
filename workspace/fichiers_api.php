<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
header('Content-Type: application/json');

$user = currentUser();
$db   = getDB();

define('UPLOAD_DIR', dirname(__DIR__) . '/uploads_uptechgroup/');
define('UPLOAD_MAX', 256 * 1024 * 1024); // 256 MB

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// Dossier Ressources communes (projet_id = 0)
$RESSOURCES_ID = 0;

function getCategorie(string $mime): string {
    if (str_starts_with($mime, 'image/')) return 'image';
    if (str_starts_with($mime, 'video/')) return 'video';
    if ($mime === 'application/pdf') return 'document';
    if (in_array($mime, ['application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','text/plain','text/csv'])) return 'document';
    if (in_array($mime, ['application/zip','application/x-rar-compressed','application/x-7z-compressed','application/gzip','application/x-tar'])) return 'archive';
    return 'autre';
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============ LISTE DES PROJETS ============
if ($action === 'projets') {
    $isManager = isManager();
    if ($isManager) {
        $projets = $db->query("SELECT p.id, p.nom, p.statut, COUNT(f.id) as nb_fichiers FROM projets p LEFT JOIN fichiers f ON f.projet_id=p.id GROUP BY p.id ORDER BY p.nom ASC")->fetchAll();
    } else {
        $stmt = $db->prepare("SELECT DISTINCT p.id, p.nom, p.statut, COUNT(f.id) as nb_fichiers FROM projets p LEFT JOIN taches t ON t.projet_id=p.id LEFT JOIN fichiers f ON f.projet_id=p.id WHERE t.assigne_a=? GROUP BY p.id ORDER BY p.nom ASC");
        $stmt->execute([$user['id']]);
        $projets = $stmt->fetchAll();
    }
    // Compter les fichiers ressources communes
    $nbRessources = (int)$db->query("SELECT COUNT(*) FROM fichiers WHERE projet_id IS NULL OR projet_id=0")->fetchColumn();
    // Ajouter Ressources communes en premier
    array_unshift($projets, ['id'=>0,'nom'=>'Ressources communes','statut'=>'actif','nb_fichiers'=>$nbRessources]);
    echo json_encode($projets); exit;
}

// ============ LISTE DES FICHIERS D'UN PROJET ============
if ($action === 'fichiers') {
    $projetId = (int)($_GET['projet_id'] ?? -1);
    $cat      = $_GET['categorie'] ?? '';
    $q        = $_GET['q'] ?? '';

    if ($projetId === 0) {
        $sql   = "SELECT f.*, CONCAT(u.prenom,' ',u.nom) as uploade_par_nom FROM fichiers f LEFT JOIN users u ON u.id=f.uploade_par WHERE (f.projet_id IS NULL OR f.projet_id=0)";
    } else {
        $sql   = "SELECT f.*, CONCAT(u.prenom,' ',u.nom) as uploade_par_nom FROM fichiers f LEFT JOIN users u ON u.id=f.uploade_par WHERE f.projet_id=$projetId";
    }
    if ($cat)  $sql .= " AND f.categorie='$cat'";
    if ($q)    $sql .= " AND (f.nom_affiche LIKE '%$q%' OR f.description LIKE '%$q%')";
    $sql .= " ORDER BY f.created_at DESC";

    try {
        $fichiers = $db->query($sql)->fetchAll();
        echo json_encode($fichiers);
    } catch(Exception $e) {
        echo json_encode([]);
    }
    exit;
}

// ============ UPLOAD MULTIPLE ============
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $projetId = isset($_POST['projet_id']) ? (int)$_POST['projet_id'] : null;
    $desc     = trim($_POST['description'] ?? '');
    $results  = [];

    $files = $_FILES['fichiers'] ?? $_FILES['fichier'] ?? null;
    if (!$files) { echo json_encode(['error'=>'Aucun fichier reçu']); exit; }

    // Normaliser en tableau (upload multiple ou unique)
    if (!is_array($files['name'])) {
        $files = array_map(fn($v) => [$v], $files);
    }
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $results[] = ['error' => "Erreur upload: " . $files['name'][$i]];
            continue;
        }
        $taille = $files['size'][$i];
        if ($taille > UPLOAD_MAX) {
            $results[] = ['error' => $files['name'][$i] . " trop grand (max 256 MB)"];
            continue;
        }
        $nomOriginal = $files['name'][$i];
        $mime        = mime_content_type($files['tmp_name'][$i]) ?: $files['type'][$i];
        $categorie   = getCategorie($mime);
        $ext         = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
        $nomStockage = uniqid('f_') . '.' . $ext;
        $dest        = UPLOAD_DIR . $nomStockage;

        if (!move_uploaded_file($files['tmp_name'][$i], $dest)) {
            $results[] = ['error' => "Impossible de déplacer: $nomOriginal"];
            continue;
        }

        try {
            $db->prepare("INSERT INTO fichiers (projet_id,nom_original,nom_stockage,nom_affiche,type_mime,taille,categorie,description,uploade_par) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$projetId ?: null, $nomOriginal, $nomStockage, $nomOriginal, $mime, $taille, $categorie, $desc, $user['id']]);
            $newId = $db->lastInsertId();

            // Extraction ZIP automatique
            if ($ext === 'zip' && !empty($_POST['extraire'])) {
                $zip = new ZipArchive();
                if ($zip->open($dest) === true) {
                    $extracted = 0;
                    for ($j = 0; $j < $zip->numFiles; $j++) {
                        $entry    = $zip->getNameIndex($j);
                        if (str_ends_with($entry, '/')) continue; // dossier
                        $entryExt = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                        $entryTmp = $zip->getStream($entry);
                        if (!$entryTmp) continue;
                        $entryContent = stream_get_contents($entryTmp);
                        fclose($entryTmp);
                        $entryStockage = uniqid('fz_') . '.' . $entryExt;
                        file_put_contents(UPLOAD_DIR . $entryStockage, $entryContent);
                        $entryMime = mime_content_type(UPLOAD_DIR . $entryStockage) ?: 'application/octet-stream';
                        $db->prepare("INSERT INTO fichiers (projet_id,nom_original,nom_stockage,nom_affiche,type_mime,taille,categorie,description,uploade_par) VALUES (?,?,?,?,?,?,?,?,?)")
                           ->execute([$projetId ?: null, basename($entry), $entryStockage, basename($entry), $entryMime, strlen($entryContent), getCategorie($entryMime), "Extrait de $nomOriginal", $user['id']]);
                        $extracted++;
                    }
                    $zip->close();
                    $results[] = ['success'=>true,'nom'=>$nomOriginal,'id'=>$newId,'extraits'=>$extracted];
                    continue;
                }
            }
            $results[] = ['success'=>true,'nom'=>$nomOriginal,'id'=>$newId];
        } catch(Exception $e) {
            $results[] = ['error'=>$e->getMessage()];
        }
    }
    echo json_encode(['success'=>true,'results'=>$results,'count'=>count(array_filter($results,fn($r)=>isset($r['success'])))]);
    exit;
}

// ============ LIEN VIDÉO EXTERNE ============
if ($action === 'lien_video' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $projetId = isset($_POST['projet_id']) ? (int)$_POST['projet_id'] : null;
    $lien     = trim($_POST['lien'] ?? '');
    $titre    = trim($_POST['titre'] ?? 'Vidéo externe');
    $desc     = trim($_POST['description'] ?? '');

    if (!$lien || !filter_var($lien, FILTER_VALIDATE_URL)) {
        echo json_encode(['error'=>'URL invalide']); exit;
    }
    // Détecter la plateforme
    $plateforme = 'lien';
    if (str_contains($lien,'youtube.com') || str_contains($lien,'youtu.be')) $plateforme='youtube';
    elseif (str_contains($lien,'drive.google.com')) $plateforme='drive';
    elseif (str_contains($lien,'vimeo.com')) $plateforme='vimeo';

    try {
        $db->prepare("INSERT INTO fichiers (projet_id,nom_original,nom_stockage,nom_affiche,type_mime,taille,categorie,description,uploade_par) VALUES (?,?,?,?,?,?,?,?,?)")
           ->execute([$projetId ?: null, $lien, 'lien:'.$lien, $titre, 'video/'.$plateforme, 0, 'video', $desc, $user['id']]);
        echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
    } catch(Exception $e) {
        echo json_encode(['error'=>$e->getMessage()]);
    }
    exit;
}

// ============ TÉLÉCHARGER UN FICHIER ============
if ($action === 'download') {
    header('Content-Type: text/html'); // reset
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM fichiers WHERE id=?");
    $stmt->execute([$id]);
    $f = $stmt->fetch();
    if (!$f) { http_response_code(404); echo "Fichier introuvable"; exit; }

    // Lien externe → redirection
    if (str_starts_with($f['nom_stockage'], 'lien:')) {
        header('Location: ' . substr($f['nom_stockage'], 5)); exit;
    }

    $path = UPLOAD_DIR . $f['nom_stockage'];
    if (!file_exists($path)) { http_response_code(404); echo "Fichier manquant"; exit; }

    header('Content-Type: ' . $f['type_mime']);
    header('Content-Disposition: attachment; filename="' . $f['nom_original'] . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// ============ SUPPRIMER UN FICHIER ============
if ($action === 'delete' && isManager()) {
    $id   = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM fichiers WHERE id=?");
    $stmt->execute([$id]);
    $f = $stmt->fetch();
    if ($f && !str_starts_with($f['nom_stockage'],'lien:')) {
        @unlink(UPLOAD_DIR . $f['nom_stockage']);
    }
    $db->prepare("DELETE FROM fichiers WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
}

// ============ RENOMMER ============
if ($action === 'renommer' && isManager()) {
    $id  = (int)($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    if (!$nom) { echo json_encode(['error'=>'Nom vide']); exit; }
    $db->prepare("UPDATE fichiers SET nom_affiche=? WHERE id=?")->execute([$nom, $id]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['error'=>'Action inconnue']);
