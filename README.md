# NEXUS — Personal Control Center

> **Sophistication dans le rendu. Simplicité dans le code.**

NEXUS est un Personal OS web — une application PHP/MySQL conçue comme un véritable centre de contrôle personnel : finances, objectifs, planning, analyse, budgets, historique d'activité, prévisions financières et score de santé financière. Interface dark/cybernétique premium, PWA installable, i18n FR/EN/ES.

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-4.4-FF6384?style=flat&logo=chartdotjs&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green?style=flat)

---

## Aperçu

- **Dashboard** — Score financier, KPIs, prévisions mois suivant, graphique évolution multi-période (1M/3M/6M/12M), objectifs, tâches du jour, transactions récentes
- **Finances** — CRUD transactions paginées, récurrences automatiques, notes, export CSV, gestion catégories complète
- **Budgets** — Budget mensuel par catégorie avec suivi visuel (barres de progression, alertes dépassement)
- **Objectifs** — Épargne progressive avec dépôts, barres animées, passage automatique en "atteint"
- **Planning** — Tâches avec priorités, filtres, badge compteur dans la nav
- **Analyse** — Graphiques barres + doughnut, comparaison mois courant vs précédent, variations
- **Historique** — Journal complet de toutes les actions (créations, modifications, suppressions, connexions)
- **Paramètres** — Thème dark/light (switch topbar), devise avec conversion de taux réels, langue (FR/EN/ES)
- **Auth** — Inscription/connexion AJAX, anti brute-force, reset mot de passe par email, sessions sécurisées CSRF

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Serveur | PHP 8.1+ |
| Base de données | MySQL 8.0+ / MariaDB |
| Frontend | HTML5, CSS3 vanilla, JavaScript vanilla |
| Graphiques | Chart.js 4.4 (local, pas de CDN) |
| Architecture | MVC pragmatique, front controller centralisé |
| PWA | Service Worker + Web App Manifest |
| i18n | FR / EN / ES — détection automatique navigateur |

Pas de framework, pas de dépendances npm, pas de build step. PHP pur.

---

## Installation

### Prérequis

- PHP 8.1+ avec extensions `pdo_mysql` et `session`
- MySQL 8.0+ ou MariaDB
- Serveur local : WAMP, MAMP, Laragon, ou `php -S`

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/votre-username/nexus.git
cd nexus

# 2. Configurer la base de données
cp config/config.example.php config/config.php
# Éditer config/config.php avec vos identifiants MySQL

# 3. Créer les tables
mysql -u root -p < database/schema.sql

# 4. (Optionnel) Charger les données de démonstration
mysql -u root -p < database/seed.sql

# 5. Lancer
# Avec WAMP/MAMP : placer le dossier dans www/ ou htdocs/
# Avec PHP built-in :
php -S localhost:8000 -t public
```

Ouvrir : `http://localhost/nexus/public/` (ou `http://localhost:8000/`)

---

## Comptes de démonstration

Après avoir chargé `seed.sql` :

| Email | Mot de passe | Devise | Langue |
|-------|-------------|--------|--------|
| alex@demo.nexus | demo1234 | EUR | Français |
| sam@demo.nexus  | demo1234 | USD | English |

> Ces comptes sont destinés aux tests. Supprimez-les ou changez les mots de passe avant toute mise en ligne réelle.

---

## Structure du projet

```
NEXUS/
├── config/
│   ├── config.example.php  ← Template à copier en config.php
│   └── config.php          ← Ignoré par git (.gitignore)
├── core/
│   ├── core.php            ← Session, CSRF, auth, sanitisation, render
│   ├── currency.php        ← Taux de change (API open.er-api.com + fallback)
│   └── i18n.php            ← Internationalisation (t(), th(), détection navigateur)
├── controllers/            ← Un controller par module
├── models/                 ← Un model par entité
├── views/
│   ├── layout.php          ← Shell commun (sidebar, topbar, recherche)
│   ├── auth/               ← Login, register, forgot, reset
│   └── *.php               ← dashboard, finances, goals, planning, analytics,
│                              budgets, history, settings
├── lang/
│   ├── fr.php              ← Français (défaut)
│   ├── en.php              ← English
│   └── es.php              ← Español
├── public/
│   ├── index.php           ← Front controller + table de routes
│   ├── manifest.json       ← PWA manifest
│   ├── sw.js               ← Service Worker (cache assets statiques)
│   └── assets/
│       ├── css/style.css   ← Design system complet (variables CSS, thèmes, responsive)
│       └── js/
│           ├── app.js      ← NEXUS JS (AJAX, toasts, recherche, i18n toggle, thème)
│           └── chart.umd.min.js  ← Chart.js 4.4 (local)
└── database/
    ├── schema.sql          ← Structure complète (à exécuter en premier)
    └── seed.sql            ← Données de démo (optionnel)
```

---

## Fonctionnalités détaillées

### Finances
- Transactions avec récurrence (quotidienne, hebdomadaire, mensuelle, annuelle) — générées automatiquement à la connexion
- Notes contextuelles sur chaque transaction
- Export CSV (UTF-8 BOM, compatible Excel) avec filtres actifs
- Gestion complète des catégories (CRUD + couleur)
- Pagination 20 transactions/page

### Budgets
- Définir un budget par catégorie et par mois
- Suivi visuel : dépensé vs budget, barre de progression, alerte dépassement
- Widget résumé sur le dashboard

### Score financier
- Score 0–100 calculé à partir du taux d'épargne (40%), de l'équilibre revenus/dépenses (30%) et de la progression des objectifs (30%)

### Prévisions
- Moyenne des 3 derniers mois projetée sur le mois suivant
- Affiché dans le dashboard et les insights automatiques

### Recherche globale
- Barre de recherche dans la topbar
- Résultats multi-tables : transactions, objectifs, tâches
- Debounce 280ms, résultats en dropdown sans rechargement

### i18n
- Détection automatique depuis `HTTP_ACCEPT_LANGUAGE` du navigateur (aucune permission requise)
- Priorité : préférence DB > session > navigateur > français
- Changement dans Paramètres → rechargement immédiat
- Noms des mois des graphiques traduits dynamiquement

### PWA
- Installable sur mobile et desktop
- Cache des assets statiques (CSS, JS, Chart.js) via Service Worker
- Pages PHP toujours servies en network-first (données toujours fraîches)

---

## Sécurité

- Requêtes préparées PDO partout (zéro concaténation SQL)
- `password_hash()` / `password_verify()` avec bcrypt
- Sessions sécurisées (`httponly`, `SameSite: Lax`, régénération d'ID à la connexion)
- Protection CSRF sur toutes les actions POST (token `hash_equals`)
- Anti brute-force : 5 tentatives / 10 min, stocké en DB
- Isolation stricte par `user_id` sur toutes les requêtes
- `htmlspecialchars()` sur toutes les sorties
- Token de reset mot de passe : SHA-256, expiration 60 min, usage unique

---

## Direction artistique

Thème **dark / futuriste / cybernétique / premium** :
- Palette : noir profond (`#04060c`), graphite, bleu électrique, cyan (`#00d9ff`), touches violet
- Grille de fond CSS subtile, lueurs glow controlées, progress bars animées
- Thème light disponible (switch dans la topbar)
- Responsive 4 breakpoints : 1200px, 1100px, 768px, 480px
- Animations d'entrée des panels via `@keyframes` (ne bloque pas Chart.js)

---

## Ajouter un module

1. Créer `controllers/MonModuleController.php`
2. Créer `models/MonModel.php` si nécessaire
3. Créer `views/mon_module.php`
4. Ajouter les routes dans `public/index.php`
5. Ajouter l'entrée nav dans `views/layout.php`
6. Ajouter les clés de traduction dans `lang/fr.php`, `lang/en.php`, `lang/es.php`

Aucune re-architecture nécessaire.

---

## Licence

MIT — Libre d'utilisation, modification et distribution.
