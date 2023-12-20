<?php

class TodoModel
{
    private $db;

    public function __construct()
    {
        $this->db = include __DIR__ . '/../connectDB.php';
    }

    public function getUserTodos($user_id)
    {
        $pdo = $this->db;
        $select = $pdo->prepare('SELECT id, title, completed FROM `todo` WHERE user_id = :user_id');
        $select->execute([':user_id' => $user_id]);

        $rows = $select->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function getCountTodos($user_id)
    {
        $pdo = $this->db;
        $select = $pdo->prepare('SELECT count(id) as count FROM `todo` WHERE user_id = :user_id AND completed = 0');
        $select->execute([':user_id' => $user_id]);

        $rows = $select->fetch(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function createTodo($title, $user_id)
    {
        try {
            $pdo = $this->db;
            $insert = $pdo->prepare('INSERT INTO `todo` (title, completed, user_id) VALUES (:title, 0, :user_id)');
            $insert->execute([':title' => $title, ':user_id' => $user_id]);

            // Return something. The execute doesn't return anything.
            return 'The todo is successfully created';
        } catch (PDOException $e) {
            print $e->getMessage();
        }
    }

    public function updateCompleted($id, $new_value)
    {
        try {
            $pdo = $this->db;
            $query = 'UPDATE `todo` SET completed = :completed WHERE id = :id';
            $update = $pdo->prepare($query);
            $update->execute([':completed' => $new_value, ':id' => $id]);

            return 'The completed field updated correctly';
        } catch (PDOException $e) {
            print $e->getMessage();
        }
    }
}

?>
