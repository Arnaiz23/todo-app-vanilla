<?php

include ('./models/TodoModel.php');

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['todoId']) && isset($data['completed'])) {
        $todoId = $data['todoId'];
        $completed = $data['completed'];

        $todo = new TodoModel();
        $todo->updateCompleted($todoId, $completed);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Todo status updated']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);

?>
