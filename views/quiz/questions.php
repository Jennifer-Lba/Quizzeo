<?php
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../config/conf.php';
require_once __DIR__ . '/../../models/question.php';
 
requireLogin();
// Autoriser uniquement : administrateur (admin/administrateur), école ou entreprise (créateurs de quiz)
requireRole(['admin', 'administrateur', 'école', 'entreprise']);
 
// Récupère l'ID du quiz
$quiz_id = get('quiz_id') ?? $_POST['quiz_id'] ?? null;
if (!$quiz_id) {
    redirect('/views/quiz/dashboard_school.php');
}
 
// Récupérer le quiz
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();
 
if (!$quiz) {
    redirect('/views/quiz/dashboard_school.php');
}
 
// Vérifier que l'utilisateur est le créateur du quiz (comparer en int) ou un admin
if ((int)$quiz['creator_id'] !== (int)($_SESSION['user']['id'] ?? 0) && !isAdmin()) {
    die("Accès refusé.");
}
 
$message = '';
$pending_delete_question = null;
 
// Traiter l'ajout de question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $question_text = post('question_text');
    $type = post('type') ?? 'qcm';
    $points = intval(post('points') ?? 1);
 
    if ($question_text) {
        $choices = [];
        if ($type === 'qcm') {
            $choices_textarea = post('choices_textarea') ?? '';
            $lines = array_values(array_filter(array_map('trim', explode("\n", $choices_textarea))));
            $correct_choice = intval(post('correct_choice') ?? 0) - 1; // 1-based input
            foreach ($lines as $i => $line) {
                $choices[] = [
                    'choice_text' => $line,
                    'is_correct' => ($i === $correct_choice) ? 1 : 0
                ];
            }
        }
 
        if (Question::create($quiz_id, $question_text, $type, $points, $choices)) {
            $message = "Question ajoutée avec succès !";
        } else {
            $message = "Erreur lors de l'ajout de la question.";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
 
// Traiter la suppression de question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    $question_id = $_POST['question_id'] ?? null;
    $confirm_delete_answers = isset($_POST['confirm_delete_answers']) && $_POST['confirm_delete_answers'] == '1';
    if ($question_id) {
        // Vérifier s'il y a des réponses liées dans answers_quiz
        $chk = $conn->prepare("SELECT COUNT(*) as c FROM answers_quiz WHERE question_id = ?");
        $chk->execute([$question_id]);
        $cnt = (int)($chk->fetchColumn() ?? 0);

        if ($cnt > 0 && !$confirm_delete_answers) {
            // Ne pas tenter la suppression brute qui déclenche une erreur FK
            $message = "Impossible de supprimer la question : " . $cnt . " réponse(s) enregistrée(s). " .
                       "Pour supprimer la question, confirmez la suppression de toutes les réponses associées.";
            // store a small flag to show a confirm form for this question
            $pending_delete_question = $question_id;
        } else {
            // soit pas de réponses, soit confirmation fournie -> supprimer les réponses puis la question
            if ($cnt > 0) {
                $delAnswers = $conn->prepare("DELETE FROM answers_quiz WHERE question_id = ?");
                $delAnswers->execute([$question_id]);
            }
            $stmt = $conn->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
            $stmt->execute([$question_id, $quiz_id]);
            $message = "Question supprimée !";
        }
    }
}
 
// Récupérer les questions existantes
$stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();
?>
 
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les questions - <?= htmlspecialchars($quiz['title']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
 
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
 
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
 
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
 
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
 
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
 
        .form-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
            border-left: 4px solid #4CAF50;
        }
 
        .form-group {
            margin-bottom: 20px;
        }
 
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
 
        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
 
        input[type="text"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
 
        textarea {
            resize: vertical;
            min-height: 100px;
        }
 
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
 
        button[type="submit"]:hover {
            background-color: #45a049;
        }
 
        .questions-list {
            margin-top: 30px;
        }
 
        .question-card {
            background-color: #f9f9f9;
            border-left: 4px solid #2196F3;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
 
        .question-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
 
        .question-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
 
        .question-card .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
 
        .question-card .actions button {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            background-color: #f44336;
            color: white;
        }
 
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #666;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
 
        .back-link:hover {
            background-color: #555;
        }
 
        .no-questions {
            text-align: center;
            color: #999;
            padding: 40px 20px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gérer les questions : <?= htmlspecialchars($quiz['title']) ?></h1>
 
        <?php if ($message) : ?>
            <div class="message <?= strpos($message, 'Erreur') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php if (!empty($pending_delete_question)) : ?>
                <div style="text-align:center;margin-bottom:16px;">
                    <form method="POST" onsubmit="return confirm('Confirmer la suppression de la question et de toutes les réponses associées ?');">
                        <input type="hidden" name="action" value="delete_question">
                        <input type="hidden" name="question_id" value="<?= (int)$pending_delete_question ?>">
                        <input type="hidden" name="confirm_delete_answers" value="1">
                        <button type="submit" style="background:#c62828;color:#fff;padding:10px 16px;border-radius:6px;border:none;cursor:pointer;">Supprimer la question et toutes les réponses</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
 
        <div class="form-section">
            <h2>Ajouter une nouvelle question</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_question">
                <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
 
                <div class="form-group">
                    <label for="question_text">Question :</label>
                    <textarea id="question_text" name="question_text" placeholder="Écrivez votre question..." required></textarea>
                </div>
 
                <div class="form-group">
                    <label for="type">Type de question :</label>
                    <select id="type" name="type">
                        <option value="qcm">QCM</option>
                        <option value="text">Texte</option>
                    </select>
                </div>
 
                <div class="form-group">
                    <label for="points">Points (valeur) :</label>
                    <input type="number" id="points" name="points" value="1" min="1">
                </div>
 
                <div class="form-group">
                    <label for="choices_textarea">Choix (pour QCM) — un choix par ligne :</label>
                    <textarea id="choices_textarea" name="choices_textarea" placeholder="Ex :\nChoix A\nChoix B\nChoix C\n...\n(Laisser vide pour type 'text')"></textarea>
                </div>
 
                <div class="form-group">
                    <label for="correct_choice">Numéro de la bonne réponse (1 = première ligne) :</label>
                    <input type="number" id="correct_choice" name="correct_choice" min="1" placeholder="1">
                    <small>Ignorer pour les questions de type texte.</small>
                </div>
 
                <button type="submit">Ajouter la question</button>
            </form>
        </div>
 
        <div class="questions-list">
            <h2>Questions existantes (<?= count($questions) ?>)</h2>
 
            <?php if (count($questions) > 0) : ?>
                <?php foreach ($questions as $index => $q) : ?>
                    <div class="question-card">
                        <h3><?= ($index + 1) ?>. <?= htmlspecialchars($q['question_text']) ?></h3>
                        <div class="actions">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                <input type="hidden" name="action" value="delete_question">
                                <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                <button type="submit">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="no-questions">
                    <p>Aucune question ajoutée pour ce quiz. Commencez par en ajouter une !</p>
                </div>
            <?php endif; ?>
        </div>
 
        <div>
            <a href="/views/quiz/dashboard_school.php" class="back-link">Retour au dashboard</a>
        </div>
    </div>
</body>
</html>