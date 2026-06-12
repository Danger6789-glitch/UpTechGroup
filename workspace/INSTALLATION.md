# UP TECH GROUP — Workspace collaboratif
## Guide d'installation sur cPanel

---

## ✅ PRÉREQUIS
- Hébergement cPanel avec PHP 7.4+ et MySQL 5.7+
- Accès au File Manager et phpMyAdmin

---

## 📦 ÉTAPE 1 — Créer la base de données

1. Dans cPanel → **MySQL Databases**
2. Créer une base : `uptechgroup_ws`
3. Créer un utilisateur MySQL (ex: `uptechgroup_user`) avec un mot de passe fort
4. Associer l'utilisateur à la base avec **tous les droits**
5. Ouvrir **phpMyAdmin** → sélectionner `uptechgroup_ws`
6. Onglet **Importer** → choisir `database.sql` → cliquer **Exécuter**

---

## ⚙️ ÉTAPE 2 — Configurer l'application

Ouvrir `includes/config.php` et modifier :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'uptechgroup_ws');   // nom de la base créée
define('DB_USER', 'uptechgroup_user'); // utilisateur MySQL
define('DB_PASS', 'VOTRE_MOT_DE_PASSE');
define('APP_URL', 'https://workspace.uptech-group.com');
```

---

## 🚀 ÉTAPE 3 — Uploader les fichiers

### Option A — Sous-domaine recommandé (workspace.uptech-group.com)
1. cPanel → **Subdomains** → créer `workspace.uptech-group.com`
2. Pointer vers un dossier ex: `public_html/workspace`
3. Uploader TOUS les fichiers dans ce dossier via File Manager

### Option B — Dossier dans le domaine principal
- Uploader dans `public_html/workspace/`
- Accès via : `https://uptech-group.com/workspace/`

---

## 🔐 ÉTAPE 4 — Première connexion

URL : `https://workspace.uptech-group.com` (ou votre URL choisie)

**Identifiants par défaut :**
- Email : `ariel@uptech-group.com`
- Mot de passe : `UpTech2026!`

> ⚠️ **CHANGER LE MOT DE PASSE IMMÉDIATEMENT** après la première connexion
> (Section Équipe → votre profil ou via phpMyAdmin)

---

## 👥 RÔLES & DROITS

| Rôle | Droits |
|---|---|
| **Admin** | Tout + gestion équipe + création comptes |
| **Manager** | Projets, clients, finances, tâches, stats |
| **Collaborateur** | Ses tâches + statistiques globales |

---

## ➕ Ajouter un collaborateur

1. Se connecter en **Admin**
2. Menu **Équipe & Accès** → bouton **+ Collaborateur**
3. Remplir nom, email, mot de passe, rôle
4. Partager les identifiants avec le collaborateur

---

## 🔒 SÉCURITÉ — À FAIRE APRÈS INSTALLATION

1. Changer le mot de passe admin par défaut
2. S'assurer que le dossier `includes/` n'est pas accessible publiquement
   → Créer un fichier `.htaccess` dans `includes/` avec :
   ```
   Deny from all
   ```
3. Activer HTTPS (SSL) via cPanel → Let's Encrypt

---

## 📁 STRUCTURE DES FICHIERS

```
/
├── index.php          → Page de connexion
├── dashboard.php      → Application principale
├── api.php            → Backend (toutes les actions AJAX)
├── database.sql       → Schéma + données initiales
├── includes/
│   ├── config.php     → ⚠️ À CONFIGURER (DB, URL)
│   └── auth.php       → Gestion sessions & rôles
└── INSTALLATION.md    → Ce guide
```

---

*UP TECH GROUP SARL U — NIF 1002104545 — RCCM TG-LFW-01-2026-B13-01453*
