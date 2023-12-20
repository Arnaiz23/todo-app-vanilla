<?php

require __DIR__ . '/../vendor/autoload.php';
include './models/UserModel.php';

use Firebase\JWT\JWT;

if (empty($_POST['username']) || empty($_POST['password'])) {
    header('Location: registerPage.php?data=missing');
    exit;
}

$username = trim(htmlspecialchars($_POST['username']));
$password = trim(htmlspecialchars($_POST['password']));
$encrypted_password = password_hash($password, PASSWORD_BCRYPT);

$user_information = ['username' => $username, 'password' => $encrypted_password];

try {
    $user = new UserModel();
    $user_data = $user->register($user_information);
} catch (\Throwable $th) {
    header('Location: registerPage.php?error=invalid');
    exit;
}

$payload = [
    'user_id' => $user_data['last_id'],
    'exp' => time() + 3600,
];

$secret_key = 'secret';

$jwt = JWT::encode($payload, $secret_key, 'HS256');

setcookie('token', $jwt, time() + 3600, '/', null, true, true);

$_SESSION['username'] = $username;

header('Location: user-info.php');

?>
