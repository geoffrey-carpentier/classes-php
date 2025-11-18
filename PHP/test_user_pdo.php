<?php
declare(strict_types=1);

// Charge la connexion PDO et la classe correspondante.
require_once __DIR__ . '/db_connect_pdo.php';
require_once __DIR__ . '/UserPdo.php';

// Crée un nouvel objet UserPdo.
$user = new UserPdo($pdo);

// Fonction utilitaire pour afficher proprement les résultats.
function afficher(string $titre, mixed $donnees): void
{
    echo "\n==== {$titre} ====\n";
    var_export($donnees);
    echo "\n";
}

// Données de test simples.
$login = 'pdo_test';
$password = 'azerty';
$email = 'pdo_test@mail.com';
$firstname = 'Tom';
$lastname = 'Dupont';

// Enchaîne les appels CRUD pour vérifier le bon fonctionnement.
afficher('REGISTER', $user->register($login, $password, $email, $firstname, $lastname));
afficher('CONNECT', $user->connect($login, $password));
afficher('UPDATE', $user->update('pdo_test2', 'azerty2', 'pdo_test2@example.com', 'Thomas', 'Durand'));
afficher('INFOS', $user->getAllInfos());
afficher('DELETE', $user->delete());
afficher('IS CONNECTED ?', $user->isConnected());