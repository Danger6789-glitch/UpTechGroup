<?php
// ============================================
// UP TECH GROUP — Gestion des permissions
// Inclure dans includes/permissions.php
// ============================================

/**
 * Liste de tous les modules disponibles avec leur label et icône
 */
function getAllModules(): array {
    return [
        // Communication & Collaboration
        'dashboard'    => ['label'=>'Tableau de bord',   'icon'=>'grid',          'group'=>'Principal',     'desc'=>'Vue générale, KPIs, alertes'],
        'taches'       => ['label'=>'Mes tâches',         'icon'=>'check',         'group'=>'Principal',     'desc'=>'Voir et gérer ses propres tâches'],
        'calendrier'   => ['label'=>'Calendrier',         'icon'=>'calendar',      'group'=>'Principal',     'desc'=>'Événements et planification'],
        'chat'         => ['label'=>'Messages',           'icon'=>'message',       'group'=>'Principal',     'desc'=>'Chat interne équipe'],
        'fichiers'     => ['label'=>'Fichiers',           'icon'=>'folder',        'group'=>'Principal',     'desc'=>'Accès aux fichiers projet'],
        'temps'        => ['label'=>'Suivi du temps',     'icon'=>'clock',         'group'=>'Principal',     'desc'=>'Timer et saisie des heures'],
        'assistant'    => ['label'=>'Assistant IA',       'icon'=>'ai',            'group'=>'Principal',     'desc'=>'Accès à l\'assistant intelligent'],

        // Gestion
        'projets'      => ['label'=>'Projets',            'icon'=>'folder2',       'group'=>'Gestion',       'desc'=>'Voir et gérer les projets'],
        'clients'      => ['label'=>'Clients',            'icon'=>'users',         'group'=>'Gestion',       'desc'=>'Voir et gérer les clients & prospects'],
        'crm'          => ['label'=>'CRM',                'icon'=>'phone',         'group'=>'Gestion',       'desc'=>'Interactions et opportunités clients'],
        'finances'     => ['label'=>'Finances',           'icon'=>'dollar',        'group'=>'Gestion',       'desc'=>'Trésorerie et opérations financières'],
        'facturation'  => ['label'=>'Facturation',        'icon'=>'credit',        'group'=>'Gestion',       'desc'=>'Devis, factures et avoirs'],
        'charge'       => ['label'=>'Charge de travail',  'icon'=>'users2',        'group'=>'Gestion',       'desc'=>'Vue charge équipe'],

        // Rapports
        'rapports'     => ['label'=>'Rapport PDF',        'icon'=>'file',          'group'=>'Rapports',      'desc'=>'Générer les rapports mensuels'],
        'export'       => ['label'=>'Export CSV',         'icon'=>'download',      'group'=>'Rapports',      'desc'=>'Exporter les données'],
        'stats'        => ['label'=>'Statistiques',       'icon'=>'bar',           'group'=>'Rapports',      'desc'=>'Graphiques et indicateurs'],
    ];
}

/**
 * Récupérer les permissions d'un utilisateur
 * Admin = tout. Manager = défaut large. Collaborateur = via table.
 */
function getUserPermissions(int $userId, string $role): array {
    // Admin a tout
    if ($role === 'admin') {
        return array_keys(getAllModules());
    }

    // Manager a tout sauf admin
    if ($role === 'manager') {
        return array_keys(getAllModules());
    }

    // Collaborateur : lecture depuis la table
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT module FROM user_permissions WHERE user_id=? AND peut_voir=1");
        $stmt->execute([$userId]);
        $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Si aucune permission définie → accès minimal par défaut
        if (empty($perms)) {
            return ['dashboard', 'taches', 'calendrier', 'chat', 'temps'];
        }

        return $perms;
    } catch (Exception $e) {
        return ['dashboard', 'taches', 'calendrier', 'chat', 'temps'];
    }
}

/**
 * Vérifier si l'utilisateur courant peut accéder à un module
 */
function canAccess(string $module): bool {
    $user = currentUser();
    if ($user['role'] === 'admin' || $user['role'] === 'manager') return true;

    $perms = getUserPermissions($user['id'], $user['role']);
    return in_array($module, $perms);
}

/**
 * Sauvegarder les permissions d'un utilisateur
 */
function saveUserPermissions(int $userId, array $modules): bool {
    $db = getDB();
    try {
        // Supprimer les anciennes
        $db->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$userId]);

        // Insérer les nouvelles
        if (!empty($modules)) {
            $stmt = $db->prepare("INSERT INTO user_permissions (user_id, module, peut_voir) VALUES (?,?,1)");
            foreach ($modules as $module) {
                if (array_key_exists($module, getAllModules())) {
                    $stmt->execute([$userId, $module]);
                }
            }
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Récupérer les permissions sauvegardées pour affichage dans le formulaire
 */
function getSavedPermissions(int $userId, string $role): array {
    if (in_array($role, ['admin', 'manager'])) {
        return array_keys(getAllModules());
    }
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT module FROM user_permissions WHERE user_id=? AND peut_voir=1");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return ['dashboard', 'taches', 'calendrier', 'chat', 'temps'];
    }
}
