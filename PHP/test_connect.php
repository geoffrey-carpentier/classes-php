<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/User.php';

$user = new User($mysqli);

// Test connexion correcte
$resOk = $user->connect('testuser', 'Secret123');
echo "Connexion correcte :\n";
var_dump($resOk);

// Test mauvaise correspondance login/password
$resBad = $user->connect('testuser', 'MauvaisMDP');
echo "Le login ne correspond pas au mot de passe. :\n";
var_dump($resBad);

// Fermeture de la connexion 
$mysqli->close();
echo "\nFin des tests.\n";