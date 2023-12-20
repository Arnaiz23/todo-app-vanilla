<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (isset($_COOKIE['token'])) {
    header('Location: user-info.php');
    die ();
} else {
    header('Location: index.html');
    die ();
}
?>
