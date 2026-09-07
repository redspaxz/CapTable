# CapTable — T&Tech Consulting Group

Système de gestion de capital (cap table) et des actions pour **T&Tech Consulting Group**, société opérant au Cameroun sous le droit **OHADA** (Acte uniforme relatif au droit des sociétés commerciales et du GIE — AUSCGIE).

Architecture : **monolithe modulaire** en PHP pur (MVC maison), MySQL/MariaDB, vues serveur Bootstrap 5, Chart.js.

## Modules

| Module | Fonction |
|---|---|
| Security | Connexion, rôles (admin / finance / viewer), CSRF |
| Dashboard | Indicateurs : capital social, titres, actionnaires, mouvements |
| Shareholders | Actionnaires (personnes physiques et morales, CNI/RC) |
| Shares | Catégories d'actions, émissions (apport en numéraire/nature), cessions |
| CapTable | Répartition du capital, %, graphique, export CSV, registre des mouvements de titres (art. 716 AUSCGIE) |
| Documents | Certificats d'actions, actes de cession, PV d'assemblée générale |

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

Comptes de démonstration (mot de passe `password`) : `admin@ttechgroup.cm` (admin), `finance@ttechgroup.cm` (finance), `viewer@ttechgroup.cm` (lecture seule).

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
| T9 Sécurité | CSRF (419), redirection anonyme, API protégée, diagnostic `/health` |

**Dernière exécution : 2026-09-07 — 31/31 réussis** (PHP 8.2, SQLite). Le script sort avec un code d'erreur non nul si une assertion échoue : intégrable dans une CI. Note : les données de test sont en ASCII pur car la console Windows peut altérer les accents transmis à curl.

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

## Roadmap (non inclus en v1)

ESOP/options, tours de table et modélisation de dilution, instruments convertibles.
