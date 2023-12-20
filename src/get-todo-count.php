<?php

include ('./models/TodoModel.php');

session_start();

$user_id = $_SESSION['user_id'];

$todo = new TodoModel();
$pendingCount = $todo->getCountTodos($user_id);

header('Content-Type: application/json');
echo json_encode(['pendingCount' => $pendingCount['count']]);
?>
