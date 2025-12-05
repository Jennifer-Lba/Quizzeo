<?php
require_once __DIR__ . '/../config/conf.php';
 
class Question
{
    private static function getConnection(): PDO
    {
        global $conn;
        return $conn;
    }
 
    public static function getAllByQuiz(int $quiz_id): array
    {
        $stmt = self::getConnection()->prepare("SELECT * FROM questions WHERE quiz_id = :quiz_id");
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    public static function create(int $quiz_id, string $question_text, string $type = 'qcm', int $points = 1, array $choices = []): bool
    {
        $conn = self::getConnection();
 
        // Pour compatibilité on met correct_answer NULL par défaut
        $correct_answer = null;
 
        // Insertion de la question
        $stmt = $conn->prepare("
        INSERT INTO questions (quiz_id, question_text, type, points, correct_answer)
        VALUES (:quiz_id, :question_text, :type, :points, :correct_answer)
    ");
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->bindParam(':question_text', $question_text);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':points', $points, PDO::PARAM_INT);
        $stmt->bindParam(':correct_answer', $correct_answer);
 
        $ok = $stmt->execute();
        if (!$ok) return false;
 
        $questionId = (int)$conn->lastInsertId();
 
        // Si QCM et choix fournis, insérer les choix
        if ($type === 'qcm' && !empty($choices)) {
            $ins = $conn->prepare("
            INSERT INTO choices (question_id, choice_text, is_correct)
            VALUES (:question_id, :choice_text, :is_correct)
        ");
            foreach ($choices as $c) {
                $choice_text = $c['choice_text'] ?? '';
                $is_correct = !empty($c['is_correct']) ? 1 : 0;
                $ins->execute([
                    ':question_id' => $questionId,
                    ':choice_text' => $choice_text,
                    ':is_correct' => $is_correct
                ]);
            }
        }
 
        return true;
    }
 
 
    public static function delete(int $id): bool
    {
        $conn = self::getConnection();
 
        // Supprime les choix (sécurité si pas de FK cascade)
        $delChoices = $conn->prepare("DELETE FROM choices WHERE question_id = :id");
        $delChoices->bindParam(':id', $id, PDO::PARAM_INT);
        $delChoices->execute();
 
        // Supprime la question
        $delQ = $conn->prepare("DELETE FROM questions WHERE id = :id");
        $delQ->bindParam(':id', $id, PDO::PARAM_INT);
        return $delQ->execute();
    }
}