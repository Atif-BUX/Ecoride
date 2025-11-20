# Compléments pour le rapport EcoRide

Ce document rassemble les éléments demandés par l’école à intégrer dans la copie ECF. Chaque section peut être copiée/collée dans votre rapport Word aux emplacements correspondants (Partie 2 – Spécifications techniques et Partie 3 – Recherche / Mise en œuvre).

## 1. Environnement Docker (CPT1)
Afin de documenter l’installation et la configuration de l’environnement de travail, j’ai ajouté une stack Docker Compose (`docker-compose.yml` + `docker/php/Dockerfile`). Elle reproduit l’infrastructure que j’utilise en local :

- **Service `web`** : image `php:8.2-apache` enrichie de l’extension `pdo_mysql` et du module `mod_rewrite`. Le code du projet est monté dans `/var/www/html`, ce qui permet un hot-reload identique à XAMPP.
- **Service `db`** : image `mariadb:10.6` initialisée avec la base `ecoride_db`. Les identifiants par défaut sont `ecoride` / `ecoride` et le port hôte est exposé sur `3307`.
- **Procédure** : `cp .env.example .env.local`, remplacer `DB_HOST` par `db`, `DB_USER`/`DB_PASS` par `ecoride`, puis lancer `docker compose up --build`. L’application est accessible sur `http://localhost:8080` et la base peut être alimentée via `docker compose exec db mysql -uecoride -pecoride ecoride_db < ecoride_db_v0.9.0.sql`.

Ce passage décrit précisément l’installation attendue au critère CPT1 : environnement reproductible, isolé et documenté (cf. README.md, section « Installation rapide (Docker Compose) »).

## 2. Interaction front-end dynamique (CPT3)
Pour répondre à la demande d’utiliser davantage de JavaScript moderne (Fetch/AJAX), j’ai implémenté une recherche asynchrone :

- **API** : `api/search_travels.php` expose les trajets au format JSON (filtres sur ville, date, prix, notation, etc.). Les données sont sécurisées via `PDO` et les photos conducteurs sont encodées en base64 pour rester compatibles avec le front.
- **Fetch côté client** : dans `script.js`, la fonction `runAsyncTravelSearch` intercepte le formulaire (`#travelSearchForm`), valide les champs puis envoie la requête avec `fetch`. Les résultats sont rendus dynamiquement (cartes Bootstrap identiques au rendu PHP) et un fallback affiche un formulaire « Exprimer votre besoin » sans recharger la page.
- **Progressive enhancement** : si JavaScript est désactivé, la page `covoiturages.php` continue d’utiliser le rendu PHP d’origine. Cette approche respecte les bonnes pratiques d’accessibilité tout en apportant l’interactivité demandée.

Ce paragraphe peut être intégré dans la partie « Spécifications techniques – mécanismes de sécurité / front dynamique » pour prouver la maîtrise des appels AJAX (critère CPT3).

## 3. Composants métier et POO (activité 2)
Le back-end repose sur plusieurs classes métiers injectées via PDO :

- `TravelManager` (fichier `src/TravelManager.php`) centralise les règles sur les trajets : prévention des doublons conducteur/date, filtrage multi-critères, gestion des crédits lors des confirmations.
- `CreditManager`, `SystemParameterManager` et `UserManager` encapsulent respectivement la logique de portefeuille, les paramètres systèmes (`booking_auto_confirm`) et le cycle de vie des utilisateurs (hash, soft delete, rôles).
- Chaque méthode est couverte par des transactions (`beginTransaction` / `commit` / `rollBack`) et du logging applicatif.

Vous pouvez insérer ce texte dans la section « Composants métier côté serveur » pour rappeler explicitement l’usage de la POO et répondre à la remarque « incorporer la POO ».

## 4. Veille ciblée (sécurité & performances)
En complément de la veille déjà mentionnée, voici un paragraphe prêt à l’emploi pour justifier la surveillance sécurité :

> « Avant de publier l’API AJAX, j’ai relu les recommandations OWASP (sections Injection et Sensitive Data Exposure) et les notes de version PHP 8.2. J’utilise `Csrf::validateRequest` pour les POST critiques, `password_hash/password_verify` pour l’authentification, GitGuardian pour surveiller les secrets dans GitHub et GTmetrix + EcoIndex pour valider l’impact environnemental. Cette veille continue m’a permis de corriger le validateur CSRF pour accepter les ports personnalisés (localhost:8080) et de sécuriser l’API JSON en renvoyant uniquement les champs strictement nécessaires. »

Copiez-collez ces compléments (ou adaptez-les) dans votre document Word pour répondre point par point aux retours du livret d’évaluation.
