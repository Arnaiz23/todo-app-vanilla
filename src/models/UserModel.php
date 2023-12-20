<?php
class UserModel
{
    private $db;

    public function __construct()
    {
        $this->db = include __DIR__ . '/../connectDB.php';
    }

    public function login($user_information)
    {
        $pdo = $this->db;
        $select = $pdo->prepare('SELECT username FROM `user` WHERE id = :id');
        $user_id = $user_information->user_id;
        $select->execute([':id' => $user_id]);

        $row = $select->fetch();

        return $row;
    }

    public function register($user_information)
    {
        try {
            $pdo = $this->db;
            $create = $pdo->prepare('INSERT INTO user (username, password) VALUES (:username, :password)');
            $create->execute([':username' => $user_information['username'], ':password' => $user_information['password']]);

            $last_id = $pdo->lastInsertId();

            return ['message' => 'The user has been created successfully!!', 'last_id' => $last_id];
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 1);
        }
    }
}
?>
