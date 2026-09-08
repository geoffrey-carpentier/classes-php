<?php

//! Script de test pour vérifier les méthodes CRUD de la classe User

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/User.php';

$user = new User($mysqli);

echo "Register:\n";
var_dump($user->register('UserTest', 'password', 'UserTest@mail.com', 'User', 'NAME'));

echo "Connect:\n";
var_dump($user->connect('UserTest', 'password'));

echo "Is connected?\n";
var_dump($user->isConnected());

echo "Update:\n";
var_dump($user->update('TsetResu', 'drowssap', 'TsetResu@email.com', 'Resu', 'EMAN'));

echo "Check infos:\n";
var_dump($user->getAllInfos());

echo "Test de Delete:\n";
var_dump($user->delete());

