<?php
include './models/TodoModel.php';

session_start();

if (isset($_POST['todo']) && !empty($_POST['todo'])) {
    $todo_title = $_POST['todo'];
    $user_id = $_SESSION['user_id'];

    $Todo = new TodoModel();
    $Todo->createTodo($todo_title, $user_id);

    header('Location: user-info.php');
    die ();
}
?>
