<?php

require __DIR__ . '/../vendor/autoload.php';
include 'connectDB.php';

use Firebase\JWT\JWT;

if (empty($_POST['username']) || empty($_POST['password'])) {
    header('Location: loginPage.php?data=missing');
    exit;
}

$username = htmlspecialchars($_POST['username']);
$password = htmlspecialchars($_POST['password']);

try {
    $pdo = include 'connectDB.php';

    $select = $pdo->prepare('SELECT id,username, password FROM `user` WHERE username = :username');
    $select->execute([':username' => $username]);

    if ($select->rowCount() === 0) {
        header('Location: loginPage.php?error=credentials');
        exit ();
    }

    $user = $select->fetch();

    if (!password_verify($password, $user['password'])) {
        header('Location: loginPage.php?error=credentials');
        exit ();
    }

    $secret_key = 'secret';
    $payload = [
        'user_id' => $user['id'],
        'exp' => time() + 3600,
    ];

    $jwt = JWT::encode($payload, $secret_key, 'HS256');

    setcookie('token', $jwt, time() + 3600, '/', null, true, true);

    $_SESSION['username'] = $user['username'];

    header('Location: user-info.php');
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}

?>
