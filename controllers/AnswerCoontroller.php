<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/conf.php';

requireLogin(); // accès uniquement aux utilisateurs connectés

// Traitement POST : enregistrement des réponses
require_once __DIR__ . '/../models/answer.php';
require_once __DIR__ . '/../models/result.php';
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
            // vérifier dans choices si le choix correspond à une réponse correcte
            $cstmt = $conn->prepare("SELECT is_correct FROM choices WHERE question_id = ? AND choice_text = ? LIMIT 1");
            $cstmt->execute([$qid, $answerText]);
            $crow = $cstmt->fetch();
            if ($crow && !empty($crow['is_correct'])) {
                $scoreForThis = (int)$points;
            }
        } else {
            // type texte : comparer au correct_answer si renseigné
            $correct = trim(strtolower($qrow['correct_answer'] ?? ''));
            if ($correct !== '' && trim(strtolower($answerText)) === $correct) {
                $scoreForThis = (int)$points;
            }
        }

        $answerModel->addAnswer($userId, $quizId, $qid, $answerText, $scoreForThis);
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
$stmt = $conn->prepare("
    SELECT q.*, c.id AS choice_id, c.choice_text
    FROM questions q
    LEFT JOIN choices c ON q.id = c.question_id
    WHERE q.quiz_id = ?
    ORDER BY q.id
");
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
        $questions[$qId]['choices'][] = $row['choice_text'];
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
                        <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= htmlspecialchars($choice) ?>">
                        <?= htmlspecialchars($choice) ?>
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
