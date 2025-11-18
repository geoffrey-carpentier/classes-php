<?php

declare(strict_types=1);                     // Active un typage strict pour limiter les erreurs.

// ---------- Paramètres de connexion ----------
$dbHost = '127.0.0.1';                       // Adresse du serveur MySQL.
$dbUser = 'root';                            // Identifiant.
$dbPass = '';                                // Mot de passe.
$dbName = 'classes';                         // Nom de la base de données ciblée.

// ---------- Ouverture de la connexion mysqli ----------
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName); // Instancie mysqli et tente la connexion.

// ---------- Vérification d’erreur de connexion ----------
if ($mysqli->connect_error) {                // connect_error contient un message si la connexion échoue.
    die("Erreur de connexion MySQL : " . $mysqli->connect_error); // Stoppe l’exécution avec un message clair.
}

// ---------- Configuration du jeu de caractères ----------
$mysqli->set_charset('utf8mb4');             // Force l’UTF-8 pour éviter les soucis d’accents/emoji.

//? ---------- Remarque d’utilisation ----------
//? La variable $mysqli est dorénavant disponible via un simple 'require'.
//? Exemple : $user = new User($mysqli);
//??????????????????????????????????????????????