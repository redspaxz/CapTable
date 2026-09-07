# CapTable — T&Tech Consulting Group

Système de gestion de capital (cap table) et des actions pour **T&Tech Consulting Group**, société opérant au Cameroun sous le droit **OHADA** (Acte uniforme relatif au droit des sociétés commerciales et du GIE — AUSCGIE).

Architecture : **monolithe modulaire** en PHP pur (MVC maison), MySQL/MariaDB, vues serveur Bootstrap 5, Chart.js.

## Modules

| Module | Fonction |
|---|---|
| Security | Connexion, rôles (admin / finance / viewer / **auditeur CAC**), CSRF, anti-force brute |
| Dashboard | Indicateurs : capital social, titres, actionnaires, mouvements |
| Shareholders | Actionnaires (personnes physiques et morales, CNI/RC) |
| Shares | Catégories OHADA (ordinaire / préférence / **ADPSDV sans droit de vote**, poids de vote ×0/×1/×2), préférence de liquidation, **clauses d'agrément et d'inaliénabilité (10 ans max, art. 2-1)**, émissions, cessions avec **workflow d'approbation et préemption 30 j** |
| CapTable | Répartition du capital, %, graphique, export CSV, registre des mouvements (art. 716) **avec références notariées/RCCM**, historique à toute date, waterfall de liquidation, **double devise XAF/EUR/USD** |
| Options (ESOP) | Vesting temps réel + **jalons**, exercice automatisé (émission + registre), **note d'impact fiscal CGI indicative** |
| Convertibles | **OCA / BSA / SAFE** : modélisation de conversion (cap + remise) et cap table pro-forma dilué |
| Compliance | **Conventions réglementées ≥ 10 % (art. 440, alerte CAC)**, **registre des bénéficiaires effectifs (COBAC/DGI, seuil 25 %)**, cessions en attente d'agrément |
| Governance | **Moteur de droits de vote et quorum/majorité AGE/AGO/AGC**, modèles de résolutions (augmentation de capital, rachat, conversion, agrément) |
| Portals | Portail des parties prenantes (fondateur, investisseur, administrateur, employé, **auditeur**) : titres, options, valeur acquise |
| Documents | Certificats d'actions, actes de cession, PV d'assemblée |

## Structure

```
public/            Racine web (front controller, assets)
app/Core/          Micro-framework : Router, Database (PDO), View, Auth, Session, CSRF, Validator
app/Modules/       Un dossier par module fonctionnel (controllers + services)
app/Views/         Gabarits PHP
app/routes/        Définition des routes
config/            Configuration (variables d'environnement)
database/          Schémas MySQL / SQLite + seed de démonstration
```

Toutes les opérations sur les titres (émissions, cessions) sont inscrites dans un **registre des mouvements de titres** en ajout seul (append-only) — la détention actuelle est toujours recalculée à partir de ce registre. Montants en XAF stockés en entiers.

## Prérequis

- PHP ≥ 8.1 (pdo_mysql ou pdo_sqlite)
- MySQL/MariaDB (ou SQLite pour un essai local)

## Installation

```bash
cp .env.example .env          # puis renseigner les accès DB

# MySQL
mysql -u root -p captable < database/schema.mysql.sql

# SQLite (démo rapide)
DB_DRIVER=sqlite sqlite3 database/captable.sqlite < database/schema.sqlite.sql

# Données de démonstration (T&Tech : 3 actionnaires, 10 000 actions, une cession)
php database/seed.php

php -S localhost:8080 -t public public/router.php
```

Comptes de démonstration (mot de passe `password`) : `admin@ttechgroup.cm` (admin/fondateur), `finance@ttechgroup.cm` (finance), `viewer@ttechgroup.cm` (lecture seule), `employee@ttechgroup.cm` (employé, portail avec options), `auditor@ttechgroup.cm` (commissaire aux comptes, lecture seule étendue).

> **Mise à jour d'une installation v1/v2** : ré-importer `database/schema.<driver>.sql` (les `CREATE TABLE IF NOT EXISTS` sont ignorés, les `ALTER` ajoutent les colonnes et tables suivantes : options, préférences, workflow de cession, bénéficiaires effectifs, convertibles, double devise).
>
> **Mise à jour d'une installation antérieure à la projection `share_holdings`** : lancer une fois `php database/migrate_holdings.php` (idempotent — crée la table et la backfill depuis le registre, MySQL et SQLite). Tant que la table est absente, l'application continue de fonctionner en repliant le registre à chaque lecture (plus lent, jamais en erreur) ; la migration rétablit le mode rapide. En cPanel le script est tenté automatiquement à chaque déploiement.

## Recette UAT

La suite de recette `scripts/uat.sh` reconstruit une base de démonstration vierge, démarre l'application et déroule l'ensemble des workflows métier avec des assertions PASS/FAIL (aucune dépendance outre `php`, `curl` et `bash`) :

```bash
bash scripts/uat.sh          # port personnalisé : UAT_PORT=9090 bash scripts/uat.sh
```

| Domaine | Couverture |
|---|---|
| T1 Authentification | page de connexion, rejet d'un mot de passe erroné, connexion admin |
| T2 Actionnaires | listing des fondateurs, création (personne morale), modification |
| T3 Catégories d'actions | création d'une catégorie PREF |
| T4 Émissions | émission tracée au registre, blocage du dépassement de quota autorisé |
| T5 Cessions | cession enregistrée, blocage au-delà des disponibilités du cédant |
| T6 Cap table & registre | pourcentages exacts multi-catégories, capital recalculé, export CSV, registre art. 716 |
| T7 Documents | certificat d'actions émis/imprimable + garde-fou, acte de cession, PV d'assemblée avec présence exacte |
| T8 Contrôle d'accès | rôle viewer : lecture seule, écriture rejetée (403) |
| T9 Sécurité | CSRF (419), redirection anonyme, API protégée, diagnostic `/health`, HEAD servi par la route GET, pas de cookie de session sur 404 |
| T10 Options & vesting | attribution, acquises au cliff, exercice automatisé (émission EX- + registre), garde-fou d'exercice |
| T11 Waterfall | préférence de liquidation puis reliquat au prorata — montants exacts |
| T12 Portails & historique | portail employé (options, valeur acquise), portail fondateur (titres), capital reconstitué à date |
| T13 Restrictions | lock-up refusé, clause d'agrément → cession en attente → approbation → registre + référence notariée |
| T14 Conformité | conventions réglementées ≥ 10 % + alerte CAC, déclaration UBO, moteur de vote AGE |
| T15 Convertibles & devise | OCA modélisée, pro-forma dilué, équivalent EUR (taux 655,957) |
| T16 Cohérence projection | `share_holdings` identique au repli du registre après chaque écriture (agrément, exercice, émission) |
| T17 Migration install v1 | table supprimée → l'app replie le registre (200), migration recrée + backfill, projection re-cohérente |

**Dernière exécution : 2026-09-07 — 59/59 réussis** (PHP 8.2, SQLite). Les lectures temps réel passent par la projection `share_holdings` maintenue transactionnellement (registres volumineux : < 1 ms / 2 MiB contre ~120 ms / 90 MiB par repli PHP) ; l'historique à date reste reconstitué en SQL. Si la table est absente (install pré-projection), l'app replie le registre en attendant la migration `database/migrate_holdings.php` (idempotente, lancée automatiquement au déploiement cPanel). Le script sort avec un code d'erreur non nul si une assertion échoue : intégrable dans une CI. Note : les données de test sont en ASCII pur car la console Windows peut altérer les accents transmis à curl.

## Déploiement cPanel (hébergement mutualisé)

Le dépôt peut être déployé tel quel dans un sous-répertoire du web root (ex. `public_html/ctms`) via le **Git Versioning** de cPanel :

1. Créer le dépôt Git dans cPanel (URL du dépôt GitHub, branche `main`).
2. Le fichier `.cpanel.yml` copie `app/`, `config/`, `database/`, `public/` et le `.htaccess` racine vers `public_html/ctms`.
3. Le `.htaccess` racine interdit l'accès web à `app/`, `config/`, `database/`, `.git` et `.env`, et route toutes les requêtes vers `public/`.
4. Créer la base MySQL dans cPanel, importer `database/schema.mysql.sql` (phpMyAdmin), puis créer le fichier `.env` **sur le serveur** à côté de `.htaccess` (il n'est pas versionné) :

   ```
   DB_HOST=localhost
   DB_NAME=<votre_base>
   DB_USER=<votre_utilisateur>
   DB_PASS=<votre_mot_de_passe>
   ```

5. L'application détecte automatiquement son sous-chemin (`/ctms`) — aucune configuration d'URL n'est nécessaire. Forcer `APP_BASE_URL` dans `.env` reste possible.

Après le premier déploiement, changer le mot de passe des comptes de démonstration ou les supprimer.

## Sécurité (référentiel OWASP)

Contrôles en place :

- **Injection SQL** : 100 % des requêtes passent par des requêtes préparées PDO paramétrées.
- **XSS** : échappement systématique (`htmlspecialchars` ENT_QUOTES) ; JSON embarqué encodé avec `JSON_HEX_*` ; en-tête CSP.
- **CSRF** : jeton par session vérifié (`hash_equals`) sur tous les formulaires POST, y compris la déconnexion.
- **Contrôle d'accès** : rôles admin / finance / viewer appliqués par middleware sur chaque route ; écritures réservées admin+finance.
- **Authentification** : bcrypt (`password_hash`), régénération de l'ID de session à la connexion, limitation anti-force brute (5 essais / 10 min / adresse IP).
- **Sessions** : cookies `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS, expiration après 30 min d'inactivité.
- **En-têtes de sécurité** : `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP, HSTS (en HTTPS).
- **Divulgation d'informations** : `/health` masque le détail des erreurs hors mode debug ; `.env`, `.git`, `app/`, `config/`, `database/` interdits par `.htaccess`.
- **Injection CSV** : cellules préfixées quand elles commencent par `= + - @ \` avant export.

Recommandations production : changer les mots de passe de démo, restreindre ou désactiver `/health`, envisager SRI sur les CDN et un journal d'audit des actions utilisateurs.

## Notes OHADA

- Registre des mouvements de titres conformément à l'art. 716 AUSCGIE.
- Interface en français ; documents générés (certificat d'actions, acte de cession, PV) suivent les usages OHADA.
- Prix de cession et mentions manuscrites à compléter sur les originaux signés.

## Roadmap (non inclus)

Tours de table et modélisation de dilution, instruments convertibles (BSA, SAFE), interface d'administration des comptes portail, journal d'audit des actions utilisateurs.
