<?php
// model/Answer.php : Cette page permet de gérer les réponses des utilisateurs aux quiz.
class Answer {
    protected $conn;
//Connexion à la base de données
    public function __construct($conn) {
        $this->conn = $conn;
    }

// Enregistrer une réponse pour une question dans la table answers_quiz
    public function addAnswer(int $userId, int $quizId, int $questionId, string $answerText, int $score = 0) {
        $stmt = $this->conn->prepare("
            INSERT INTO answers_quiz (user_id, quiz_id, question_id, answer_text, score)
            VALUES (:user_id, :quiz_id, :question_id, :answer_text, :score)
        ");
                $stmt->execute([
                    'user_id' => $userId,
                    'quiz_id' => $quizId,
                    'question_id' => $questionId,
                    'answer_text' => $answerText,
                    'score' => $score
                ]);
    }

// Récupérer toutes les réponses d'un quiz avec les noms des utilisateurs et le texte des questions
    public function getAnswersByQuiz(int $quizId): array {
        $stmt = $this->conn->prepare("
            SELECT a.*, u.first_name, u.last_name, q.question_text
            FROM answers_quiz a
            JOIN users u ON u.id = a.user_id
            JOIN questions q ON q.id = a.question_id
            WHERE a.quiz_id = ?
            ORDER BY a.user_id, a.question_id
        ");
        $stmt->execute([$quizId]);
        return $stmt->fetchAll();
    }

// Récupérer les réponses d'un utilisateur donné pour un quiz
    public function getAnswersByUser(int $quizId, int $userId): array {
        $stmt = $this->conn->prepare("
            SELECT * FROM answers_quiz
            WHERE quiz_id = ? AND user_id = ?
        ");
        $stmt->execute([$quizId, $userId]);
        return $stmt->fetchAll();
    }
}
