EcoRide — Installation locale et parcours de réservation

Liens utiles
- Dépôt GitHub : https://github.com/Atif-BUX/Studi
- Démo (GitHub Pages) : https://atif-bux.github.io/Studi/
- Démo alternative : https://ecoridebyatif.onrender.com/

Présentation
- EcoRide est une application PHP + MariaDB (XAMPP) de covoiturage écoresponsable. Les utilisateurs peuvent rechercher des trajets, réserver et utiliser un système de crédits avec gains conducteur.

Installation locale (Windows + XAMPP)
- Installer XAMPP puis démarrer Apache + MySQL depuis le panneau XAMPP.
- Chemin du client MySQL utilisé ci‑dessous : `C:\xampp\mysql\bin\mysql.exe`
- Ouvrir VS Code → Terminal (PowerShell 7 convient — `PSVersionTable.PSVersion`).

Configuration de l’application (src/Database.php)
- `DB_HOST = 'localhost'`
- `DB_NAME = 'ecoride_db'`
- `DB_USER = 'root'`
- `DB_PASS = ''` (par défaut avec XAMPP) — remplacez par votre mot de passe (`password321` dans l’environnement d’évaluation)
- `DB_PORT = 3306`
- `DB_SOCKET = ''` (Windows n’utilise pas de socket)

Environnement de développement et IDE
- IDE principal : Visual Studio Code (extensions conseillées : PHP Intelephense, ESLint, Prettier, GitLens).
- Gestion de version : Git (GitHub). Stratégie : `main` + `develop` + branches fonctionnelles.
- Terminal : PowerShell 7 et invite `cmd` (XAMPP).
- Serveur local : XAMPP 8.2 (Apache 2.4 + MariaDB 10.4 + PHP 8.2).
- Outillage complémentaire : phpMyAdmin (stockage configuré), Notion/Trello pour le kanban, Chrome/Edge pour les tests, MongoDB (optionnel) pour le logger NoSQL.

Import de la base de données
- Chemin du dump complet (ex.) : `C:/xampp/htdocs/EcoRide/ecoride_db.sql`
- Recréation de la base (ajouter `-p` si votre root a un mot de passe) :
  - `& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "DROP DATABASE IF EXISTS ecoride_db; CREATE DATABASE ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`
- Import du dump via `SOURCE` :
  - `& 'C:\xampp\mysql\bin\mysql.exe' -u root ecoride_db -e "SOURCE C:/xampp/htdocs/EcoRide/ecoride_db.sql"`
- Scripts d’upgrade (optionnels si nécessaires) :
  - `& 'C:\xampp\mysql\bin\mysql.exe' -u root ecoride_db -e "SOURCE C:/xampp/htdocs/EcoRide/database/20241104_schema_upgrade.sql"`
  - `& 'C:\xampp\mysql\bin\mysql.exe' -u root ecoride_db -e "SOURCE C:/xampp/htdocs/EcoRide/database/20241107_credit_upgrade.sql"`
  - `& 'C:\xampp\mysql\bin\mysql.exe' -u root ecoride_db -e "SOURCE C:/xampp/htdocs/EcoRide/database/sample/seed_minimal.sql"`

Correctif des collations MySQL 8.0
- Si votre dump vient d’un MySQL 8.0, remplacez les collations spécifiques 8.0 :
  - `Copy-Item 'C:\xampp\htdocs\EcoRide\ecoride_db.sql' 'C:\xampp\htdocs\EcoRide\ecoride_db.sql.bak' -Force`
  - `$raw = Get-Content -Raw 'C:\xampp\htdocs\EcoRide\ecoride_db.sql'`
  - `$fixed = [regex]::Replace($raw,'utf8mb4_0900_[A-Za-z_]+','utf8mb4_unicode_ci')`
  - `[System.IO.File]::WriteAllText('C:\xampp\htdocs\EcoRide\ecoride_db.sql',$fixed,[System.Text.UTF8Encoding]::new($false))`
  - `Select-String -Path 'C:\xampp\htdocs\EcoRide\ecoride_db.sql' -Pattern 'utf8mb4_0900' || Write-Host 'OK: no 8.0 collations left'`

Parcours de réservation (Pending → Confirm)
- À la réservation : `reserveSeats` crée une réservation `status='pending'` (aucun débit/crédit immédiat).
- Confirmation : `confirmReservation` revérifie disponibilité et crédits, passe à `status='confirmed'`, renseigne `confirmed_at`, débite le passager, crédite le conducteur, décrémente les places et incrémente `travels.earnings`.
- Annulation : si `pending` → simple `status='cancelled'` ; si `confirmed` → restitution des places et des crédits.
- UI : la page trajet affiche « Réservation en attente » avant confirmation ; un bouton « Confirmer » apparaît pour les réservations en attente. Le profil affiche des libellés clairs.

Paramètre d’auto‑confirmation
- Nom : `booking_auto_confirm` (tables `configurations` / `parameters`)
- Valeurs :
  - `'1'` ou `true` : confirmation immédiate après insertion
  - `'0'` : laisse la réservation en attente jusqu’à confirmation
- Recommandé (évaluation) : OFF
  - `& 'C:\xampp\mysql\bin\mysql.exe' -u root ecoride_db -e "INSERT IGNORE INTO configurations (label) VALUES ('default'); INSERT IGNORE INTO parameters (property, default_value) VALUES ('booking_auto_confirm','1'); INSERT INTO configuration_parameters (configuration_id, parameter_id, value) SELECT c.id, p.id, '0' FROM configurations c, parameters p WHERE c.label='default' AND p.property='booking_auto_confirm' ON DUPLICATE KEY UPDATE value=VALUES(value);"`
- Valeur par défaut (optionnel) :
  - `& 'C:\xampp\mysql\bin\mysql.exe' -u root ecoride_db -e "ALTER TABLE reservations ALTER COLUMN status SET DEFAULT 'pending';"`

Comptes démo (ajustés pour la DEMO)
- Conducteur : `jean.dupont@test.fr` / `password321`
- Passager : `john.wick@gmail.com` / `password321`
- Réinitialisation dans « Paramètres » (admin_params.php).

Dépannage
- « Access denied » : ajouter `-p` aux commandes `mysql.exe` puis saisir le mot de passe.
- Redirection PowerShell `<` : utiliser `-e "SOURCE chemin.sql"` plutôt que la redirection.
- `IF/THEN` phpMyAdmin : exécuter via `mysql.exe` ou privilégier des scripts idempotents.
- Incompatibilités de clés étrangères : aligner les types/unsigned entre PK/FK.

Sauvegarder une base fonctionnelle
- `& 'C:\xampp\mysql\bin\mysqldump.exe' -u root ecoride_db > 'C:\xampp\htdocs\EcoRide\backup_ecoride_db_working.sql'`

Pile technique (rapide)
- Front‑end : HTML5, CSS3, JS (+ Bootstrap)
- Back‑end : PHP 8 + PDO
- Base relationnelle : MariaDB/MySQL (XAMPP)
- Dev : VS Code, Git/GitHub

Versions et livraisons
- Version actuelle : 0.9.0 (2025‑11‑09)
- Changelog : voir `CHANGELOG.md`
- Partage de la base avec les évaluateurs :
  - Recommandé : créer une Release GitHub (tag `v0.9.0`) et y joindre un dump zippé `ecoride_db_v0.9.0.sql.zip`. Ajouter le lien ici.
  - Alternative : committer un petit jeu d’essai dans `database/sample/seed_minimal.sql` et mettre le dump complet en pièce jointe de la Release.
  - Éviter de committer de gros `.sql` sur `main` (limite GitHub 100 Mo par fichier) ; préférer les Releases ou Git LFS.

Schéma MCD (Base de données)
- Diagramme minimal (entités/relations) : `graphics/mcd_ecoride.svg`
- Export PDF (automatique) :
  - `pwsh -File .\\scripts\\export_mcd_pdf.ps1`
  - Produit : `graphics/mcd_ecoride.pdf`
- Export manuel PNG/PDF : ouvrez le SVG dans votre navigateur et « Imprimer » → « Enregistrer en PDF », ou exportez via Inkscape.

Réinitialisation rapide (données démo)
- Script PowerShell : `scripts/reset_demo.ps1`
  - Exemple (sans mot de passe root) :
    - `pwsh -File .\\scripts\\reset_demo.ps1 -Yes`
  - Exemple (avec mot de passe root) :
    - `pwsh -File .\\scripts\\reset_demo.ps1 -RootPassword "votre_mot_de_passe" -Yes`
  - Paramètres :
    - `-MysqlPath` (par défaut `C:\\xampp\\mysql\\bin\\mysql.exe`)
    - `-RootUser` (par défaut `root`)
    - `-RootPassword` (optionnel)
    - `-DbName` (par défaut `ecoride_db`)
    - `-Yes` pour éviter la confirmation interactive

Réinitialisation du mot de passe (Reset)
- Parcours utilisateur
  - Page « Mot de passe oublié » : `mot_de_passe_oublie.php`
  - Saisissez votre email : un lien de réinitialisation est généré (sans envoi réel d’email en démo) et consigné dans `logs/mail.log`.
  - Une bannière d’information s’affiche ensuite sur `connexion.php` (« Si un compte correspond, un lien a été envoyé. »).
  - Page de reset : `reinitialiser_mot_de_passe.php?token=...` pour saisir un nouveau mot de passe.
- Sécurité & hygiène
  - Jeton valable une durée configurable (TTL), usage unique, invalidé après emploi.
  - Purge automatique des tokens expirés au moment de la connexion.
- Paramétrage TTL (admin)
  - `admin_params.php` → section « Sécurité — Réinitialisation mot de passe »
  - Paramètre `password_reset_ttl_minutes` (recommandé : 30–60, plage : 5–240)

Guide (Quickstart)
- URLs
  - Accueil : `http://localhost/EcoRide/`
  - Covoiturages (recherche) : `http://localhost/EcoRide/covoiturages.php`
  - Détail d’un trajet : `http://localhost/EcoRide/detail_trajet.php?id=1` (changer l’id)
  - Profil : `http://localhost/EcoRide/profil.php`
  - Paramètres admin : `http://localhost/EcoRide/admin_params.php`
  - Besoins (NoSQL) : `http://localhost/EcoRide/admin_needs.php`
  - Espace employé (avis) : `http://localhost/EcoRide/employe_reviews.php`
  - Dashboard admin : `http://localhost/EcoRide/admin_dashboard.php`

- Comptes démo
  - Conducteur (ADMIN) : `jean.dupont@test.fr` / `password321`
  - Passager : `john.wick@gmail.com` / `password321`
  - Réinitialisation possible dans « Paramètres ».

- Parcours à tester
  - US3/4 : recherche (filtres minimaux) → résultats (trajets futurs, places disponibles).
  - US5/6 : détail → réserver → confirmer (débit crédits, décrément des places). Réservation d’un trajet passé bloquée.
  - US8/9 : devenir conducteur → proposer un trajet (sélection de véhicule) → carte récap.
  - US10/11 : démarrer/terminer un trajet (statuts). Emails simulés dans `logs/mail.log`.
  - US12 : valider/refuser des avis (`employe_reviews.php`).
  - US13 : suspendre/réactiver un utilisateur ; graphiques (trajets/jour, crédits/jour) dans `admin_dashboard.php`.
  - NoSQL : sans résultat de recherche, utiliser le formulaire « Vous êtes passager ? » → vue admin `admin_needs.php` (MongoDB si actif, sinon fichier `logs/nosql.log`).

- (Optionnel) Activer MongoDB
  - Installer MongoDB Community Server (Windows service par défaut sur 27017).
  - Installer l’extension PHP `mongodb` et l’activer dans `php.ini` (`extension=mongodb`), puis redémarrer Apache.
  - Optionnel : définir `MONGO_URI` (sinon `mongodb://127.0.0.1:27017`).
  - Le logger NoSQL écrit dans `ecoride.needs` ; la page admin lit MongoDB si disponible.

Produire un dump « propre » (Windows/XAMPP)
- Depuis le terminal VS Code (PowerShell) :
  - `& 'C:\\xampp\\mysql\\bin\\mysqldump.exe' -u root --routines --triggers --single-transaction --default-character-set=utf8mb4 ecoride_db > 'C:\\xampp\\htdocs\\EcoRide\\ecoride_db_v0.9.0.sql'`
  - Zipper : clic droit sur le `.sql` → « Envoyer vers » → Dossier compressé.
  - Joindre le `.zip` à une Release GitHub et coller le lien ici.
