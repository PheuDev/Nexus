# Déployer NEXUS sur InfinityFree

NEXUS utilise PHP et MySQL/MariaDB. Ce guide vise une installation personnelle de démonstration. Évite d'y stocker des données financières réelles.

## 1. Créer le site et la base

1. Crée un compte InfinityFree et un site avec leur sous-domaine gratuit.
2. Dans le panneau de contrôle, crée une base MySQL et note son hôte, son nom, son utilisateur et son mot de passe.
3. Dans phpMyAdmin, sélectionne cette base et importe `database/schema.infinityfree.sql`.
4. N'importe pas `database/seed.sql` : il ajoute des comptes de démonstration avec des mots de passe connus.

## 2. Configurer l'application

1. Copie `config/config.example.php` vers `config/config.php`.
2. Dans `config/config.php`, remplace les valeurs `DB_HOST`, `DB_NAME`, `DB_USER` et `DB_PASS` par celles fournies par InfinityFree.
3. Remplace `APP_URL` par l'adresse HTTPS complète du site, sans `/public` et sans barre oblique finale.
4. Mets `APP_ENV` à `prod` avant toute utilisation publique.

`config/config.php` est ignoré par Git. Ne le pousse pas sur GitHub et ne partage pas ses identifiants.

## 3. Envoyer les fichiers

1. Connecte-toi au FTP depuis le panneau InfinityFree.
2. Envoie le projet dans `htdocs`, en conservant sa structure : `config/`, `controllers/`, `core/`, `database/`, `lang/`, `models/`, `public/` et `views/`.
3. N'envoie pas le dossier `.git` ni `config/config.php` avant de l'avoir rempli avec les identifiants de ta base.
4. Le `.htaccess` à la racine sert `public/index.php` à la racine du domaine et bloque l'accès HTTP aux sources et à la configuration.

Le fichier de configuration doit exister sur l'hébergement avant la première visite du site.

## Remarques

- Le formulaire de réinitialisation envoie les courriels avec `mail()` PHP. Vérifie la réception sur l'hébergement avant de compter sur cette fonction.
- L'application utilise une API externe pour les taux de change. Les restrictions réseau du plan gratuit peuvent limiter les appels sortants.
- Fais une sauvegarde locale périodique de la base depuis phpMyAdmin.
