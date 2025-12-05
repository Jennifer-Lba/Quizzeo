<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/conf.php';

requireLogin(); // accès uniquement aux utilisateurs connectés

// Traitement POST : enregistrement des réponses
require_once __DIR__ . '/../models/answer.php';
require_once __DIR__ . '/../models/results.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF si présent
    if (isset($_POST['csrf_token'])) verifyCSRFToken();

    $postQuizId = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : null;
    $quizId = $postQuizId ?: $quizId;

    $answers = $_POST['answers'] ?? [];
    $user = currentUser();
    $userId = $user['id'] ?? null;
    if (!$userId) die("Utilisateur non authentifié.");

    $answerModel = new Answer($conn);
    // pour chaque réponse, déterminer si elle est correcte et attribuer les points
    foreach ($answers as $questionId => $value) {
        $qid = intval($questionId);
        $answerText = is_array($value) ? implode(', ', $value) : (string)$value;

        // récupérer la question pour connaître le type et les points
        $qstmt = $conn->prepare("SELECT type, points, correct_answer FROM questions WHERE id = ? LIMIT 1");
        $qstmt->execute([$qid]);
        $qrow = $qstmt->fetch();
        $points = $qrow['points'] ?? 0;
        $scoreForThis = 0;

        if ($qrow && $qrow['type'] === 'qcm') {
            // $answerText contient maintenant l'id du choix sélectionné (valeur du formulaire)
            $choiceId = intval($answerText);
            $cstmt = $conn->prepare("SELECT is_correct, choice_text FROM choices WHERE id = ? LIMIT 1");
            $cstmt->execute([$choiceId]);
            $crow = $cstmt->fetch();
            // remplacer answerText par le texte du choix pour stockage lisible
            if ($crow) {
                $answerText = $crow['choice_text'];
                if (!empty($crow['is_correct'])) {
                    $scoreForThis = (int)$points;
                }
                // Debug log: choix trouvé
                error_log(sprintf("[AnswerController] user=%s quiz=%s question=%s choice_id=%s is_correct=%s points=%s score_for_this=%s",
                    $userId, $quizId, $qid, $choiceId, $crow['is_correct'] ?? 'NULL', $points, $scoreForThis
                ));
            } else {
                // Debug: choix introuvable
                error_log(sprintf("[AnswerController] user=%s quiz=%s question=%s choice_id=%s NOT FOUND",
                    $userId, $quizId, $qid, $choiceId
                ));
            }
        } else {
            // type texte : comparer au correct_answer si renseigné
            $correct = trim(strtolower($qrow['correct_answer'] ?? ''));
            if ($correct !== '' && trim(strtolower($answerText)) === $correct) {
                $scoreForThis = (int)$points;
            }
        }

        // Debug avant insertion
        error_log(sprintf("[AnswerController] INSERT answer user=%s quiz=%s question=%s answer_text=%s score=%s",
            $userId, $quizId, $qid, $answerText, $scoreForThis
        ));

        $answerModel->addAnswer($userId, $quizId, $qid, $answerText, $scoreForThis);

        // Vérifier insertion (quick read)
        try {
            $vstmt = $conn->prepare("SELECT * FROM answers_quiz WHERE user_id = ? AND quiz_id = ? AND question_id = ? ORDER BY id DESC LIMIT 1");
            $vstmt->execute([$userId, $quizId, $qid]);
            $vrow = $vstmt->fetch();
            if ($vrow) {
                error_log(sprintf("[AnswerController] inserted_row id=%s score=%s answer_text_db=%s", $vrow['id'], $vrow['score'], $vrow['answer_text']));
            } else {
                error_log(sprintf("[AnswerController] insert failed or row not found for user=%s quiz=%s q=%s", $userId, $quizId, $qid));
            }
        } catch (Exception $e) {
            error_log('[AnswerController] verification read failed: ' . $e->getMessage());
        }
    }

    // calculer le score total et enregistrer le résultat
    $resultModel = new Result($conn);
    $total = $resultModel->computeScore($userId, $quizId);
    $resultModel->saveResult($userId, $quizId, $total);

    redirect('/views/quiz/results.php?quiz_id=' . $quizId);
}

// Accepter quiz_id depuis GET ou POST (POST via formulaire caché)
$quizId = get('quiz_id') ?? (isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : null);
if (!$quizId) die("Quiz introuvable. (paramètre quiz_id manquant)");

// Récupérer le quiz (ne pas filtrer sur le statut pour le debug)
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ? LIMIT 1");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();
if (!$quiz) die("Quiz introuvable dans la base de données (vérifiez l'ID et la configuration DB).");

// Récupérer toutes les questions et choix d’un coup
$stmt = $conn->prepare("\n    SELECT q.*, c.id AS choice_id, c.choice_text\n    FROM questions q\n    LEFT JOIN choices c ON q.id = c.question_id\n    WHERE q.quiz_id = ?\n    ORDER BY q.id\n");
$stmt->execute([$quizId]);
$rows = $stmt->fetchAll();

// Organiser les questions
$questions = [];
foreach ($rows as $row) {
    $qId = $row['id'];
    if (!isset($questions[$qId])) {
        $questions[$qId] = [
            'id' => $qId,
            'question_text' => $row['question_text'],
            'type' => $row['type'],
            'choices' => []
        ];
    }
    if ($row['choice_id']) {
        $questions[$qId]['choices'][] = [
            'id' => $row['choice_id'],
            'text' => $row['choice_text']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="/controllers/AnswerController.php"  method="POST">
    <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">

    <?php foreach ($questions as $q): ?>
        <div class="question">
            <p><strong><?= htmlspecialchars($q['question_text']) ?></strong></p>

            <?php if ($q['type'] === 'qcm'): ?>
                <?php foreach ($q['choices'] as $choice): ?>
                    <label>
                        <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= (int)$choice['id'] ?>">
                        <?= htmlspecialchars($choice['text']) ?>
                    </label><br>
                <?php endforeach; ?>
            <?php else: ?>
                <textarea name="answers[<?= $q['id'] ?>]" rows="3" cols="50"></textarea>
            <?php endif; ?>
        </div>
        <hr>
    <?php endforeach; ?>

    <button type="submit">Envoyer mes réponses</button>
</form>
</body>
</html>
