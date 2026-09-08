# Classes PHP

**Note** : Ce projet a été réalisé dans le cadre de ma formation de Développeur Web et Web Mobile (DWWM) au sein de La Plateforme.

Ce dépôt regroupe les exercices de découverte et de pratique de la Programmation Orientée Objet (POO) en PHP, associés à la manipulation d'une base de données MySQL.

## Contenu du dépôt

- **`User.php`** : Classe de gestion d'un utilisateur utilisant **MySQLi**. Elle implémente des méthodes pour l'inscription (`register`), la connexion (`connect`), la déconnexion (`disconnect`), la suppression (`delete`), et la mise à jour des informations (`update`).
- **`UserPdo.php`** : Équivalent de la classe utilisateur, réécrite pour utiliser l'interface **PDO** (recommandée pour la sécurité et la flexibilité).
- **Scripts de test** (`test_*.php`) : Ensemble de scripts permettant de valider le bon fonctionnement de chaque méthode.
- **`database/`** : Contient le script SQL d'export permettant de recréer la structure de la base de données.

## Installation

1. Importez le fichier SQL présent dans le dossier `database/` dans votre SGBD (ex: phpMyAdmin, DBeaver) pour créer la base et la table `utilisateurs`.
2. Assurez-vous que vos identifiants de connexion MySQL locaux correspondent à ceux définis dans le code PHP (par défaut: `root` sans mot de passe, ou `root`/`root`).
3. Placez ce projet sur votre serveur web local (WAMP, XAMPP, MAMP) et lancez les fichiers de test dans votre navigateur.
