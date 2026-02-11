<?php
require_once('config.php');
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
$newHash = password_hash('IagoNougat29@', PASSWORD_BCRYPT);
$pdo->prepare("UPDATE mod321_users SET user_pass = ? WHERE user_login = ?")->execute([$newHash, 'votre_login']);
echo "Mot de passe mis à jour !";
