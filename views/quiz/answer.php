<?php
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../config/conf.php';

requireLogin(); // accès uniquement aux utilisateurs connectés

$quizId = get('quiz_id');
if (!$quizId) die("Quiz introuvable.");

// Vérifier le statut   quiz
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ? AND status='active'");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();
if (!$quiz) die("Quiz introuvable ou désactivé.");

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
    <form action="/controllers/AnswerController.php?quiz_id=<?= $quiz['id'] ?>"  method="POST">
    <?= csrfField() ?>
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