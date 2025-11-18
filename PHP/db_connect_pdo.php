<?php
declare(strict_types=1);                                                               // Force un typage strict.

// ---------- Paramètres DSN & identifiants ----------
$dsn = 'mysql:host=127.0.0.1;dbname=classes;charset=utf8mb4';                          // Chaîne DSN PDO.
$dbUser = 'root';                                                                      // Identifiant MySQL.
$dbPass = '';                                                                          // Mot de passe MySQL.

// ---------- Création de l’instance PDO protégée par try/catch ----------
try {
    $pdo = new PDO(                                                                    // Instancie PDO.
        $dsn,
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,                               // Remonte les erreurs.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,                          // Retourne des tableaux associatifs.
        ]
    );
} catch (PDOException $exception) {
    die('Erreur PDO : ' . $exception->getMessage());                                   // Stoppe si la connexion échoue.
}