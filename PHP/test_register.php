<?php
// Test rapide de User::register()
// 1) inclut la connexion ($mysqli) et la classe User
// 2) teste un enregistrement valide
// 3) reteste le même login pour vérifier le code 'login_exists'
// 4) teste une erreur de validation (email invalide)

// adapter les chemins si besoin
require_once __DIR__ . '/db_connect.php'; // initialise $mysqli
require_once __DIR__ . '/User.php';       // définit la classe User

// Instancie la classe User en lui passant la connexion mysqli
$user = new User($mysqli);

// 1) Test enregistrement valide
echo "Test 1 — enregistrement valide\n";
$result1 = $user->register('testuser', 'Secret123', 'testuser@example.com', 'Jean', 'Dupont');
var_dump($result1);

// 2) Test doublon : même login -> doit renvoyer error_code 'login_exists'
echo "\nTest 2 — doublon login (même login)\n";
$result2 = $user->register('testuser', 'Secret123', 'autre@example.com', 'Paul', 'Martin');
var_dump($result2);

// 3) Test validation : email invalide
echo "\nTest 3 — email invalide\n";
$result3 = $user->register('anotheruser', 'Secret123', 'not-an-email', 'Ana', 'Lopez');
var_dump($result3);

// Fermeture de la connexion
$mysqli->close();
echo "\nFin des tests.\n";
