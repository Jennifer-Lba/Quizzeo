<?php
require_once __DIR__ . '/../config/conf.php';

class User
{
    private static function db(): PDO
    {
        global $conn;
        return $conn;
    }

    // Récupérer tous les utilisateurs
    public static function getAll(): array
    {
        $stmt = self::db()->query("SELECT * FROM users ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un utilisateur par ID
    public static function getById(int $id): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    // Créer un utilisateur
    public static function create(string $firstname, string $lastname, string $email, string $password, string $role): bool
    {
        $stmt = self::db()->prepare("
            INSERT INTO users (firstname, lastname, email, password, role)
            VALUES (:firstname, :lastname, :email, :password, :role)
        ");

         $hashed = password_hash($password, PASSWORD_BCRYPT);

        return $stmt->execute([
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':email' => $email,
            ':password' => $hashed,
            ':role' => $role
        ]);
    }

    // Supprimer un utilisateur
    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
