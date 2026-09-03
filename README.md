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

Comptes de démonstration (mot de passe `password`) : `admin@ttechgroup.cm` (admin), `finance@ttechgroup.cm` (finance).

## Notes OHADA

- Registre des mouvements de titres conformément à l'art. 716 AUSCGIE.
- Interface en français ; documents générés (certificat d'actions, acte de cession, PV) suivent les usages OHADA.
- Prix de cession et mentions manuscrites à compléter sur les originaux signés.

## Roadmap (non inclus en v1)

ESOP/options, tours de table et modélisation de dilution, instruments convertibles.
