<?php

//! Fichier de configuration / connexion mysqli.


# Informations de connexion à la base de données MySQL
$dbHost = '127.0.0.1';  
$dbUser = 'root';        
$dbPass = '';            
$dbName = 'classes';     


# Création de l'objet mysqli et tentative de connexion au serveur MySQL
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

# Vérification de la connexion : si erreur stop et affiche un message clair.

if ($mysqli->connect_error) {
    die("Erreur de connexion MySQL ({$mysqli->connect_errno}) : {$mysqli->connect_error}");
} // -> $mysqli->connect_error contient le message d'erreur éventuel.

# Définir le jeu de caractères pour éviter les problèmes d'encodage (accents, emojis, etc.)
$mysqli->set_charset('utf8mb4');

// La connexion $mysqli est active/disponible via require_once 'db_connect.php' dans d'autres fichiers.
// Exemple d'utilisation : $user = new User($mysqli);