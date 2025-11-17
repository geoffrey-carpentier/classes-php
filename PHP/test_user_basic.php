<?php
/**
 * Script de test minimal pour vérifier :
 * - si la connexion mysqli est fonctionnelle,
 * - l'instanciation de la classe User avec la connexion mysqli.
 *
 * Pour exécuter le script depuis le terminal intégré :
 *?   cd d:\TOOLS\LARAGON\www\classes-php\PHP
 *?   php test_user_basic.php
 */

# Inclure la connexion et la classe
require_once __DIR__ . '/db_connect.php'; // initialise $mysqli
require_once __DIR__ . '/User.php';       // définit la classe User

# Vérification rapide de la connexion (affiche les infos de base du serveur MySQL).
echo "Test de la connexion MySQL ... Récupération des infos :\n";
echo "- Host info : " . $mysqli->host_info . PHP_EOL;
echo "- Client info : " . mysqli_get_client_info() . PHP_EOL;

# Instanciation de la classe User en lui passant la connexion mysqli
$user = new User($mysqli);

# Affichage de l'objet (état initial)
echo "\nÉtat initial de l'objet User (avant toute opération) :\n";
var_dump($user->getAllInfos());

# Vérification du statut de l'utilisateur (Résultat de 'isConnected()' )
echo "\nStatut de l'utilisateur 'isConnected()?' : ";
var_export($user->isConnected());
echo PHP_EOL;

# Fermeture de la connexion à la fin du script
$mysqli->close();
echo "\nConnexion MySQL terminée.\n";