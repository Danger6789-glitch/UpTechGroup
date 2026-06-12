<?php
// ============================================
// UP TECH GROUP — Assistant IA local (sans API)
// Compréhension du langage naturel + données DB
// ============================================

class UpTechAssistant {

    private PDO $db;
    private array $user;

    public function __construct(PDO $db, array $user) {
        $this->db   = $db;
        $this->user = $user;
    }

    public function respond(string $message): string {
        $msg = mb_strtolower(trim($message), 'UTF-8');

        // Ordre de priorité des intentions
        if ($this->match($msg, ['bonjour','salut','hello','bonsoir','hey','coucou']))
            return $this->repondreBonjour();

        if ($this->match($msg, ['qui es-tu','qui êtes','présente','présentation','tu es quoi','c\'est quoi']))
            return $this->repondrePresentation();

        if ($this->match($msg, ['projet','projets','en cours','kanban','livraison','deadline','statut projet']))
            return $this->reponseProjets($msg);

        if ($this->match($msg, ['tâche','taches','tâches','assigné','todo','à faire','en retard','deadline tâche']))
            return $this->reponseTaches($msg);

        if ($this->match($msg, ['client','clients','prospect','prospects','contact','partenaire']))
            return $this->reponseClients($msg);

        if ($this->match($msg, ['finance','finances','chiffre','argent','revenu','dépense','trésorerie','budget','fcfa','bilan','résultat','ca ']))
            return $this->reponseFinances($msg);

        if ($this->match($msg, ['équipe','collaborateur','membre','employé','qui travaille','effectif','staff']))
            return $this->reponseEquipe($msg);

        if ($this->match($msg, ['résumé','résumé','bilan','tableau de bord','dashboard','vue globale','aperçu','état général','comment va']))
            return $this->reponseResume();

        if ($this->match($msg, ['aide','help','que peux-tu','que sais-tu','commande','question','fonctionnalité']))
            return $this->reponseAide();

        if ($this->match($msg, ['merci','super','parfait','excellent','bravo','bien','génial']))
            return $this->reponseMerci();

        if ($this->match($msg, ['calendrier','agenda','événement','réunion','rendez-vous','planning']))
            return $this->reponseCalendrier($msg);

        if ($this->match($msg, ['conseil','recommande','suggère','comment faire','comment améliorer','astuce']))
            return $this->reponseConseils($msg);

        // Réponse par défaut intelligente
        return $this->reponseParDefaut($msg);
    }

    // ===== MATCHERS =====
    private function match(string $msg, array $keywords): bool {
        foreach ($keywords as $kw) {
            if (mb_strpos($msg, $kw, 0, 'UTF-8') !== false) return true;
        }
        return false;
    }

    private function fmt(float $n): string {
        return number_format($n, 0, ',', ' ');
    }

    // ===== BONJOUR =====
    private function repondreBonjour(): string {
        $heure = (int)date('H');
        $moment = $heure < 12 ? 'matin' : ($heure < 18 ? 'après-midi' : 'soir');
        $prenom = explode(' ', $this->user['nom'])[0];
        return "Bonjour {$prenom} ! Bon {$moment}. Je suis l'assistant d'UP TECH GROUP. Je peux vous informer sur vos **projets**, **tâches**, **clients**, **finances** et l'**équipe**. Que souhaitez-vous savoir ?";
    }

    // ===== PRÉSENTATION =====
    private function repondrePresentation(): string {
        return "Je suis l'**assistant IA local d'UP TECH GROUP**.\n\nJe suis connecté directement à votre base de données et je peux répondre à vos questions sur :\n\n— **Projets** : statuts, deadlines, pipeline\n— **Tâches** : assignées, en retard, en cours\n— **Clients** : actifs, prospects, suivi\n— **Finances** : CA, dépenses, trésorerie\n— **Équipe** : membres et rôles\n\nToutes mes réponses sont basées sur vos données réelles en temps réel. Posez votre question !";
    }

    // ===== PROJETS =====
    private function reponseProjets(string $msg): string {
        $projets = $this->db->query("SELECT p.*, c.raison_sociale as client_nom FROM projets p LEFT JOIN clients c ON c.id=p.client_id ORDER BY p.updated_at DESC")->fetchAll();

        if (empty($projets))
            return "Aucun projet n'est encore enregistré dans le workspace. Créez votre premier projet depuis la section **Projets** du dashboard.";

        // En cours spécifiquement
        if ($this->match($msg, ['en cours'])) {
            $enCours = array_filter($projets, fn($p) => $p['statut'] === 'En cours');
            if (empty($enCours)) return "Aucun projet n'est actuellement en cours de développement.";
            $lines = array_map(fn($p) => "— **{$p['nom']}** ({$p['client_nom']}) · Livraison : " . ($p['date_livraison'] ?: 'non définie'), array_values($enCours));
            return "**" . count($enCours) . " projet(s) en cours :**\n\n" . implode("\n", $lines);
        }

        // Retard/deadline
        if ($this->match($msg, ['retard','deadline','livraison','urgent'])) {
            $today = date('Y-m-d');
            $retard = array_filter($projets, fn($p) => !empty($p['date_livraison']) && $p['date_livraison'] < $today && !in_array($p['statut'], ['Livré','Clôturé']));
            if (empty($retard)) return "Bonne nouvelle : aucun projet n'est en retard sur sa date de livraison.";
            $lines = array_map(fn($p) => "— **{$p['nom']}** · Deadline dépassée : {$p['date_livraison']}", array_values($retard));
            return "**Attention — " . count($retard) . " projet(s) en retard :**\n\n" . implode("\n", $lines) . "\n\nConsultez le tableau de bord pour mettre à jour ces projets.";
        }

        // Vue globale pipeline
        $byStatut = [];
        foreach ($projets as $p) {
            $byStatut[$p['statut']] = ($byStatut[$p['statut']] ?? 0) + 1;
        }
        $statuts = ['Prospection','Devis envoyé','Signé','En cours','En test','Livré','Clôturé'];
        $lines = [];
        foreach ($statuts as $s) {
            if (!empty($byStatut[$s])) $lines[] = "— {$s} : **{$byStatut[$s]}** projet(s)";
        }
        $budgetTotal = array_sum(array_column($projets, 'budget'));
        return "**Pipeline commercial — " . count($projets) . " projets au total :**\n\n" . implode("\n", $lines) . "\n\n**Valeur totale du pipeline :** " . $this->fmt($budgetTotal) . " FCFA";
    }

    // ===== TÂCHES =====
    private function reponseTaches(string $msg): string {
        $uid = $this->user['id'];
        $role = $this->user['role'];

        if ($role === 'collaborateur') {
            $stmt = $this->db->prepare("SELECT t.*, p.nom as projet_nom FROM taches t LEFT JOIN projets p ON p.id=t.projet_id WHERE t.assigne_a=? ORDER BY FIELD(t.priorite,'Haute','Moyenne','Basse'), t.date_echeance ASC");
            $stmt->execute([$uid]);
        } else {
            $stmt = $this->db->query("SELECT t.*, p.nom as projet_nom, CONCAT(u.prenom,' ',u.nom) as assigne_nom FROM taches t LEFT JOIN projets p ON p.id=t.projet_id LEFT JOIN users u ON u.id=t.assigne_a ORDER BY FIELD(t.priorite,'Haute','Moyenne','Basse'), t.date_echeance ASC");
        }
        $taches = $stmt->fetchAll();

        if (empty($taches)) return "Aucune tâche trouvée. Créez des tâches depuis le dashboard et assignez-les à votre équipe.";

        $total    = count($taches);
        $aFaire   = count(array_filter($taches, fn($t) => $t['statut'] === 'À faire'));
        $enCours  = count(array_filter($taches, fn($t) => $t['statut'] === 'En cours'));
        $bloquees = count(array_filter($taches, fn($t) => $t['statut'] === 'Bloqué'));
        $termines = count(array_filter($taches, fn($t) => $t['statut'] === 'Terminé'));

        // Tâches en retard
        $today  = date('Y-m-d');
        $retard = array_filter($taches, fn($t) => !empty($t['date_echeance']) && $t['date_echeance'] < $today && $t['statut'] !== 'Terminé');

        if ($this->match($msg, ['retard','urgent','dépassé','en retard'])) {
            if (empty($retard)) return "Bonne nouvelle : aucune tâche n'est en retard sur sa deadline.";
            $lines = array_map(fn($t) => "— **{$t['titre']}** · Deadline : {$t['date_echeance']}" . (isset($t['assigne_nom']) ? " · Assigné à : {$t['assigne_nom']}" : ''), array_values($retard));
            return "**" . count($retard) . " tâche(s) en retard :**\n\n" . implode("\n", $lines);
        }

        if ($this->match($msg, ['bloqué','bloquée','problème'])) {
            $bl = array_filter($taches, fn($t) => $t['statut'] === 'Bloqué');
            if (empty($bl)) return "Aucune tâche n'est actuellement bloquée.";
            $lines = array_map(fn($t) => "— **{$t['titre']}**" . (isset($t['assigne_nom']) ? " ({$t['assigne_nom']})" : ''), array_values($bl));
            return "**Tâches bloquées :**\n\n" . implode("\n", $lines) . "\n\nCes tâches nécessitent une attention immédiate.";
        }

        // Haute priorité
        $haute = array_filter($taches, fn($t) => $t['priorite'] === 'Haute' && $t['statut'] !== 'Terminé');

        $response = "**Résumé des tâches" . ($role === 'collaborateur' ? ' (vos tâches)' : '') . " :**\n\n";
        $response .= "— À faire : **{$aFaire}**\n";
        $response .= "— En cours : **{$enCours}**\n";
        $response .= "— Bloquées : **{$bloquees}**\n";
        $response .= "— Terminées : **{$termines}**\n";
        if (!empty($retard)) $response .= "\n⚠️ **{$count($retard)} tâche(s) en retard** — attention requise.";
        if (!empty($haute)) $response .= "\n🔴 **" . count($haute) . " tâche(s) haute priorité** en attente.";
        return $response;
    }

    // ===== CLIENTS =====
    private function reponseClients(string $msg): string {
        $clients = $this->db->query("SELECT * FROM clients ORDER BY created_at DESC")->fetchAll();

        if (empty($clients)) return "Aucun client enregistré. Ajoutez vos premiers clients et prospects depuis la section **Clients** du dashboard.";

        $actifs    = array_filter($clients, fn($c) => $c['statut'] === 'Client actif');
        $prospects = array_filter($clients, fn($c) => $c['statut'] === 'Prospect');
        $inactifs  = array_filter($clients, fn($c) => $c['statut'] === 'Client inactif');

        if ($this->match($msg, ['prospect','prospects'])) {
            if (empty($prospects)) return "Aucun prospect en base actuellement.";
            $lines = array_map(fn($c) => "— **{$c['raison_sociale']}** ({$c['secteur']}) · {$c['pays']}", array_values($prospects));
            return "**" . count($prospects) . " prospect(s) :**\n\n" . implode("\n", $lines);
        }

        if ($this->match($msg, ['actif','actifs'])) {
            if (empty($actifs)) return "Aucun client actif en ce moment.";
            $lines = array_map(fn($c) => "— **{$c['raison_sociale']}** · {$c['contact_nom']}", array_values($actifs));
            return "**" . count($actifs) . " client(s) actif(s) :**\n\n" . implode("\n", $lines);
        }

        $response  = "**Portefeuille clients :**\n\n";
        $response .= "— Clients actifs : **" . count($actifs) . "**\n";
        $response .= "— Prospects : **" . count($prospects) . "**\n";
        $response .= "— Clients inactifs : **" . count($inactifs) . "**\n";
        $response .= "— Total : **" . count($clients) . "**";

        if (!empty($actifs)) {
            $response .= "\n\n**Clients actifs :**\n";
            foreach (array_slice(array_values($actifs), 0, 5) as $c) {
                $response .= "— {$c['raison_sociale']}\n";
            }
        }
        return $response;
    }

    // ===== FINANCES =====
    private function reponseFinances(string $msg): string {
        $caTotal   = (float)$this->db->query("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé'")->fetchColumn();
        $depTotal  = (float)$this->db->query("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Sortie' AND statut='Réalisé'")->fetchColumn();
        $caAnnee   = (float)$this->db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=?");
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=?");
        $stmt->execute([date('Y')]); $caAnnee = (float)$stmt->fetchColumn();
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=? AND MONTH(date_operation)=?");
        $stmt->execute([date('Y'), date('n')]); $caMois = (float)$stmt->fetchColumn();
        $net  = $caTotal - $depTotal;
        $marge = $caTotal > 0 ? round(($net / $caTotal) * 100, 1) : 0;

        if ($this->match($msg, ['mois','mensuel','ce mois']))
            return "**CA ce mois (" . date('F Y') . ") :** " . $this->fmt($caMois) . " FCFA";

        if ($this->match($msg, ['dépense','charges','sortie']))
            return "**Dépenses totales :** " . $this->fmt($depTotal) . " FCFA\n**Résultat net :** " . $this->fmt($net) . " FCFA\n**Marge :** {$marge}%";

        return "**Résumé financier UP TECH GROUP :**\n\n— CA total réalisé : **" . $this->fmt($caTotal) . " FCFA**\n— CA " . date('Y') . " : **" . $this->fmt($caAnnee) . " FCFA**\n— CA ce mois : **" . $this->fmt($caMois) . " FCFA**\n— Dépenses totales : **" . $this->fmt($depTotal) . " FCFA**\n— Résultat net : **" . $this->fmt($net) . " FCFA**\n— Marge nette : **{$marge}%**";
    }

    // ===== EQUIPE =====
    private function reponseEquipe(string $msg): string {
        $users = $this->db->query("SELECT nom, prenom, role, last_login FROM users WHERE actif=1 ORDER BY role, prenom")->fetchAll();
        if (empty($users)) return "Aucun membre d'équipe trouvé.";
        $lines = array_map(fn($u) => "— **{$u['prenom']} {$u['nom']}** · " . ucfirst($u['role']) . ($u['last_login'] ? " · Dernière connexion : " . date('d/m/Y', strtotime($u['last_login'])) : ''), $users);
        return "**Équipe UP TECH GROUP — " . count($users) . " membre(s) :**\n\n" . implode("\n", $lines);
    }

    // ===== RÉSUMÉ GLOBAL =====
    private function reponseResume(): string {
        $projets  = (int)$this->db->query("SELECT COUNT(*) FROM projets WHERE statut='En cours'")->fetchColumn();
        $taches   = (int)$this->db->query("SELECT COUNT(*) FROM taches WHERE statut!='Terminé'")->fetchColumn();
        $clients  = (int)$this->db->query("SELECT COUNT(*) FROM clients WHERE statut='Client actif'")->fetchColumn();
        $prospects= (int)$this->db->query("SELECT COUNT(*) FROM clients WHERE statut='Prospect'")->fetchColumn();
        $ca       = (float)$this->db->query("SELECT COALESCE(SUM(montant),0) FROM tresorerie WHERE type='Entrée' AND statut='Réalisé' AND YEAR(date_operation)=YEAR(NOW())")->fetchColumn();
        $bloquees = (int)$this->db->query("SELECT COUNT(*) FROM taches WHERE statut='Bloqué'")->fetchColumn();
        $retard   = (int)$this->db->query("SELECT COUNT(*) FROM projets WHERE date_livraison < CURDATE() AND statut NOT IN ('Livré','Clôturé')")->fetchColumn();

        $date = date('d/m/Y à H:i');
        $response  = "**Tableau de bord UP TECH GROUP — {$date}**\n\n";
        $response .= "**Activité commerciale**\n";
        $response .= "— Projets en cours : **{$projets}**" . ($retard > 0 ? " ⚠️ ({$retard} en retard)" : "") . "\n";
        $response .= "— Clients actifs : **{$clients}** · Prospects : **{$prospects}**\n\n";
        $response .= "**Opérations**\n";
        $response .= "— Tâches ouvertes : **{$taches}**" . ($bloquees > 0 ? " ⚠️ ({$bloquees} bloquées)" : "") . "\n\n";
        $response .= "**Finances " . date('Y') . "**\n";
        $response .= "— CA réalisé : **" . $this->fmt($ca) . " FCFA**";
        return $response;
    }

    // ===== CALENDRIER =====
    private function reponseCalendrier(string $msg): string {
        try {
            $today    = date('Y-m-d');
            $stmt     = $this->db->prepare("SELECT titre, debut, type FROM evenements WHERE debut >= ? ORDER BY debut ASC LIMIT 5");
            $stmt->execute([$today . ' 00:00:00']);
            $events   = $stmt->fetchAll();
            if (empty($events)) return "Aucun événement prévu prochainement. Ajoutez des événements depuis le **Calendrier** du workspace.";
            $lines = array_map(fn($e) => "— **{$e['titre']}** · " . date('d/m/Y H:i', strtotime($e['debut'])), $events);
            return "**Prochains événements :**\n\n" . implode("\n", $lines);
        } catch (\Exception $e) {
            return "Accédez au **Calendrier** depuis le menu pour voir vos événements.";
        }
    }

    // ===== CONSEILS =====
    private function reponseConseils(string $msg): string {
        $projets  = (int)$this->db->query("SELECT COUNT(*) FROM projets WHERE statut='En cours'")->fetchColumn();
        $prospects= (int)$this->db->query("SELECT COUNT(*) FROM clients WHERE statut='Prospect'")->fetchColumn();
        $bloquees = (int)$this->db->query("SELECT COUNT(*) FROM taches WHERE statut='Bloqué'")->fetchColumn();
        $retard   = (int)$this->db->query("SELECT COUNT(*) FROM projets WHERE date_livraison < CURDATE() AND statut NOT IN ('Livré','Clôturé')")->fetchColumn();

        $conseils = [];
        if ($bloquees > 0) $conseils[] = "Résolvez les **{$bloquees} tâche(s) bloquée(s)** — elles ralentissent la production.";
        if ($retard > 0)   $conseils[] = "**{$retard} projet(s) en retard** — contactez les clients concernés et mettez à jour les deadlines.";
        if ($prospects > 0) $conseils[] = "Vous avez **{$prospects} prospect(s)** — relancez-les pour convertir en clients actifs.";
        if ($projets > 5)  $conseils[] = "Vous gérez **{$projets} projets** simultanément — assurez-vous que l'équipe n'est pas surchargée.";

        if (empty($conseils)) return "Tout semble bien organisé ! Continuez à maintenir vos projets à jour et à relancer vos prospects régulièrement.";
        return "**Recommandations basées sur vos données :**\n\n" . implode("\n\n", $conseils);
    }

    // ===== AIDE =====
    private function reponseAide(): string {
        return "**Je peux répondre à ces types de questions :**\n\n— *\"Montre-moi les projets en cours\"*\n— *\"Quelles tâches sont bloquées ?\"*\n— *\"Combien de clients actifs ?\"*\n— *\"Quel est notre CA ce mois ?\"*\n— *\"Résumé général de l'entreprise\"*\n— *\"Qui est dans l'équipe ?\"*\n— *\"Quels projets sont en retard ?\"*\n— *\"Donne-moi des conseils\"*\n\nToutes les réponses sont basées sur vos données réelles en temps réel.";
    }

    // ===== MERCI =====
    private function reponseMerci(): string {
        $reponses = [
            "Avec plaisir ! N'hésitez pas si vous avez d'autres questions.",
            "De rien ! Je suis là pour vous aider à piloter UP TECH GROUP.",
            "Tout le plaisir est pour moi. Bon travail !",
        ];
        return $reponses[array_rand($reponses)];
    }

    // ===== PAR DÉFAUT =====
    private function reponseParDefaut(string $msg): string {
        $motsCles = ['projet','tâche','client','finance','équipe','calendrier','résumé'];
        $suggestions = [];
        foreach ($motsCles as $mc) {
            $suggestions[] = "\"" . ucfirst($mc) . "s\"";
        }
        return "Je n'ai pas compris votre demande. Essayez l'une de ces questions :\n\n— *\"État des projets\"*\n— *\"Mes tâches du jour\"*\n— *\"Résumé financier\"*\n— *\"Liste de l'équipe\"*\n\nOu tapez **aide** pour voir toutes les commandes disponibles.";
    }
}
