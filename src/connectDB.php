<?php
$host = 'db';
$dbname = 'todo';
$user = 'user';
$passwordDB = 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $passwordDB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
