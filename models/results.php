<?php
// model/Result.php : Cette page permet de calculer et de stocker le score final des participants pour les quiz.
class Result {
    protected $conn;
//Connexion à la base de données
    public function __construct($conn) {
        $this->conn = $conn;
    }

// Calculer le score d'un utilisateur pour un quiz
    public function computeScore(int $userId, int $quizId): int {
        $stmt = $this->conn->prepare("
            SELECT SUM(score) as total_score
            FROM answers_quiz
            WHERE user_id = ? AND quiz_id = ?
        ");
        $stmt->execute([$userId, $quizId]);
        $row = $stmt->fetch();
        return (int)$row['total_score'];
    }

 // Enregistrer le score si le resultat n'existe pas encore ou le mettre à jour sinon
    public function saveResult(int $userId, int $quizId, int $score) {
        $stmt = $this->conn->prepare("
            SELECT id FROM results WHERE user_id = ? AND quiz_id = ?
        ");
        $stmt->execute([$userId, $quizId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $upd = $this->conn->prepare("
                UPDATE results SET score = ? WHERE user_id = ? AND quiz_id = ?
            ");
            $upd->execute([$score, $userId, $quizId]);
        } else {
            $ins = $this->conn->prepare("
                INSERT INTO results (user_id, quiz_id, score) VALUES (?, ?, ?)
            ");
            $ins->execute([$userId, $quizId, $score]);
        }
    }

// Récupérer tous les résultats pour un quiz avec les noms des participants du plus grand au plus petit score
    public function getResultsByQuiz(int $quizId): array {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.first_name, u.last_name
            FROM results r
            JOIN users u ON u.id = r.user_id
            WHERE r.quiz_id = ?
            ORDER BY r.score DESC
        ");
        $stmt->execute([$quizId]);
        return $stmt->fetchAll();
    }
}
