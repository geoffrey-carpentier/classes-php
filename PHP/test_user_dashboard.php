<?php

declare(strict_types=1);

// Charge les deux connexions et classes.
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/db_connect_pdo.php';
require_once __DIR__ . '/UserPdo.php';

// Détermine le pilote choisi via le formulaire (mysqli par défaut).
$driver = $_POST['driver'] ?? 'mysqli';

// Construit l’objet adapté.
$user = $driver === 'pdo' ? new UserPdo($pdo) : new User($mysqli);

// Récupère l’action demandée.
$action = $_POST['action'] ?? null;

// Prépare un tableau pour stocker le résultat (affiché dans la page).
$resultat = null;

// Récupère les champs du formulaire (avec valeurs par défaut).
$login = $_POST['login'] ?? 'demo_login';
$password = $_POST['password'] ?? 'demo_pass';
$email = $_POST['email'] ?? 'demo@example.com';
$firstname = $_POST['firstname'] ?? 'Demo';
$lastname = $_POST['lastname'] ?? 'User';

// Exécute l’action choisie si elle existe.
if ($action) {
    switch ($action) {
        case 'register':
            $resultat = $user->register($login, $password, $email, $firstname, $lastname);
            break;
        case 'connect':
            $resultat = $user->connect($login, $password);
            break;
        case 'update':
            $resultat = $user->update($login, $password, $email, $firstname, $lastname);
            break;
        case 'delete':
            $resultat = $user->delete();
            break;
        case 'disconnect':
            $user->disconnect();
            $resultat = 'Utilisateur déconnecté.';
            break;
        case 'infos':
            $resultat = $user->getAllInfos();
            break;
        case 'status':
            $resultat = $user->isConnected();
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Tests User (mysqli / PDO)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
            background: #f4f6f8;
        }

        form {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            max-width: 600px;
        }

        label {
            display: block;
            margin-top: .8rem;
        }

        input,
        select {
            width: 100%;
            padding: .5rem;
            margin-top: .2rem;
        }

        button {
            margin-top: 1rem;
            padding: .7rem 1.2rem;
            cursor: pointer;
        }

        pre {
            background: #111;
            color: #0f0;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1.5rem;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <h1>Test interactif User / UserPdo</h1>

    <form method="post">
        <label>Pilote
            <select name="driver">
                <option value="mysqli" <?= $driver === 'mysqli' ? 'selected' : '' ?>>mysqli</option>
                <option value="pdo" <?= $driver === 'pdo' ? 'selected' : '' ?>>PDO</option>
            </select>
        </label>

        <label>Login <input type="text" name="login" value="<?= htmlspecialchars($login, ENT_QUOTES) ?>"></label>
        <label>Mot de passe <input type="text" name="password" value="<?= htmlspecialchars($password, ENT_QUOTES) ?>"></label>
        <label>Email <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES) ?>"></label>
        <label>Prénom <input type="text" name="firstname" value="<?= htmlspecialchars($firstname, ENT_QUOTES) ?>"></label>
        <label>Nom <input type="text" name="lastname" value="<?= htmlspecialchars($lastname, ENT_QUOTES) ?>"></label>

        <label>Action
            <select name="action">
                <option value="">-- Choisir une action --</option>
                <option value="register">register()</option>
                <option value="connect">connect()</option>
                <option value="update">update()</option>
                <option value="delete">delete()</option>
                <option value="disconnect">disconnect()</option>
                <option value="infos">getAllInfos()</option>
                <option value="status">isConnected()</option>
            </select>
        </label>

        <button type="submit">Executer</button>
    </form>

    <?php if ($action): ?>
        <pre><?php var_export($resultat); ?></pre>
    <?php endif; ?>
</body>

</html>