<?php
require_once __DIR__ . '/../config/conf.php';

class QuizController
{
    private PDO $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM quizzes ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM quizzes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(string $title, string $description): bool
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) return false;
        $status = 'active';
        $stmt = $this->conn->prepare("INSERT INTO quizzes (title, description, creator_id, status) VALUES (:title, :description, :creator_id, :status)");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':creator_id', $user['id'], PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }

    public function update(int $id, string $title, string $description): bool
    {
        $stmt = $this->conn->prepare("UPDATE quizzes SET title = :title, description = :description WHERE id = :id");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM quizzes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
