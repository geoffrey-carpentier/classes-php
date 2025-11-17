<?php
// Test pour User::update() et User::delete()
// Assure-toi d'utiliser le php.exe de Laragon comme indiqué dans la doc précédente.

// Inclure la connexion et la classe
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/User.php';

$user = new User($mysqli);

// 1) Créer un utilisateur de test (login unique)
$create = $user->register('tmp_update_user', 'Password123', 'tmp@example.com', 'Tmp', 'User');
echo "Create result:\n";
var_dump($create);

// 2) Se connecter avec ce user (vérifier que connect() fonctionne)
$connect = $user->connect('tmp_update_user', 'Password123');
echo "\nConnect result:\n";
var_dump($connect);

// 3) Tester update : changer login/email/prénom/nom (et mot de passe)
$updateOk = false;
if ($connect['success'] ?? false) {
    // Tentative de mise à jour vers un nouveau login
    $updateOk = $user->update('tmp_updated_user', 'NewPass456', 'updated@example.com', 'Updated', 'User');
    echo "\nUpdate result (should be true):\n";
    var_export($updateOk);
    echo "\nObject after update:\n";
    var_dump($user->getAllInfos());
} else {
    echo "\nImpossible d'exécuter update : connexion échouée.\n";
}

// 4) Tester doublon login (si tu as un autre utilisateur 'testuser' existant)
// Essaie de mettre le login à 'testuser' (devrait échouer si 'testuser' existe)
if ($updateOk) {
    $conflict = $user->update('testuser', 'NewPass456', 'conflict@example.com', 'C', 'O');
    echo "\nUpdate with existing login (should fail or return false):\n";
    var_dump($conflict);
}

// 5) Tester delete()
$delOk = $user->delete();
echo "\nDelete result (should be true):\n";
var_export($delOk);

// 6) Vérifier que l'objet est déconnecté après delete()
echo "\nisConnected after delete (should be false): ";
var_export($user->isConnected());
echo "\n";

// Fermeture de la connexion
$mysqli->close();
echo "\nFin des tests.\n";
