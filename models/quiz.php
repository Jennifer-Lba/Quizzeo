<?php
require_once __DIR__ . '/../config/conf.php';

class Quiz
{
    private static function getConnection(): PDO
    {
        global $conn;
        return $conn;
    }

    public static function getAll(): array
    {
        $stmt = self::getConnection()->prepare("SELECT * FROM quizzes ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::getConnection()->prepare("DELETE FROM quizzes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
