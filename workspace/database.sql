-- ============================================
-- UP TECH GROUP — Base de données workspace
-- Compatible MySQL 5.7+ / MariaDB 10.3+
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Utilisateurs & rôles
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','manager','collaborateur') DEFAULT 'collaborateur',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `actif` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clients
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `raison_sociale` VARCHAR(200) NOT NULL,
  `type` ENUM('Entreprise','ONG','Institution','Particulier') DEFAULT 'Entreprise',
  `statut` ENUM('Prospect','Client actif','Client inactif') DEFAULT 'Prospect',
  `secteur` VARCHAR(100),
  `contact_nom` VARCHAR(150),
  `email` VARCHAR(150),
  `telephone` VARCHAR(30),
  `pays` VARCHAR(80) DEFAULT 'Togo',
  `notes` TEXT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Projets
CREATE TABLE IF NOT EXISTS `projets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(200) NOT NULL,
  `client_id` INT,
  `type_prestation` ENUM('Développement','Conseil','Formation','Maintenance','Autre') DEFAULT 'Développement',
  `statut` ENUM('Prospection','Devis envoyé','Signé','En cours','En test','Livré','Clôturé') DEFAULT 'Prospection',
  `priorite` ENUM('Haute','Moyenne','Basse') DEFAULT 'Moyenne',
  `description` TEXT,
  `date_debut` DATE,
  `date_livraison` DATE,
  `budget` DECIMAL(15,2) DEFAULT 0,
  `montant_facture` DECIMAL(15,2) DEFAULT 0,
  `montant_encaisse` DECIMAL(15,2) DEFAULT 0,
  `manager_id` INT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tâches
CREATE TABLE IF NOT EXISTS `taches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titre` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `projet_id` INT,
  `assigne_a` INT,
  `cree_par` INT,
  `priorite` ENUM('Haute','Moyenne','Basse') DEFAULT 'Moyenne',
  `statut` ENUM('À faire','En cours','Bloqué','Terminé') DEFAULT 'À faire',
  `date_echeance` DATE,
  `progression` TINYINT(3) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`projet_id`) REFERENCES `projets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigne_a`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cree_par`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Finances - Trésorerie
CREATE TABLE IF NOT EXISTS `tresorerie` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `description` VARCHAR(255) NOT NULL,
  `type` ENUM('Entrée','Sortie') NOT NULL,
  `categorie` ENUM('Prestation','Salaire','Charges','Taxes','Autre') DEFAULT 'Autre',
  `montant` DECIMAL(15,2) NOT NULL,
  `date_operation` DATE NOT NULL,
  `projet_id` INT,
  `moyen_paiement` ENUM('Mobile Money','Virement','Espèces','Chèque') DEFAULT 'Mobile Money',
  `statut` ENUM('Prévu','Réalisé') DEFAULT 'Réalisé',
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`projet_id`) REFERENCES `projets`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Factures & Devis
CREATE TABLE IF NOT EXISTS `factures` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero` VARCHAR(50) UNIQUE NOT NULL,
  `type` ENUM('Devis','Facture','Avoir') DEFAULT 'Facture',
  `client_id` INT,
  `projet_id` INT,
  `date_emission` DATE NOT NULL,
  `date_echeance` DATE,
  `montant_ht` DECIMAL(15,2) DEFAULT 0,
  `statut` ENUM('Brouillon','Envoyé','Accepté','Payé','Annulé') DEFAULT 'Brouillon',
  `notes` TEXT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`projet_id`) REFERENCES `projets`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Commentaires sur tâches
CREATE TABLE IF NOT EXISTS `commentaires` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tache_id` INT NOT NULL,
  `user_id` INT,
  `contenu` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tache_id`) REFERENCES `taches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `lien` VARCHAR(255),
  `lu` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Données initiales
-- ============================================

-- Admin par défaut : ariel@uptechgroup.com / UpTech2026!
INSERT INTO `users` (`nom`, `prenom`, `email`, `password`, `role`) VALUES
('SEHOUBO', 'Akakpo Ariel', 'ariel@uptech-group.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'admin');
-- ⚠️ MOT DE PASSE PAR DÉFAUT : UpTech2026! — À CHANGER IMMÉDIATEMENT

-- Capital initial en trésorerie
INSERT INTO `tresorerie` (`description`, `type`, `categorie`, `montant`, `date_operation`, `statut`, `created_by`) VALUES
('Capital social initial — Apport associé unique', 'Entrée', 'Autre', 100000.00, '2026-01-30', 'Réalisé', 1);
