<?php
// view/quiz/dashboard_company.php
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../config/conf.php';

requireLogin();
// Autoriser uniquement : entreprise ou administrateur
requireRole(['entreprise', 'admin', 'administrateur']);
$user = currentUser();

// Récupérer les quiz de l'entreprise
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE creator_id = ? ORDER BY id DESC");
$stmt->execute([$user['id']]);
$quizzes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    h1>Dashboard Entreprise</h1>
    <a class="button" href="/views/quiz/create.php">Créer un nouveau quiz</a>
    <table>
        <thead>
            <tr>
                <th>Titre du quiz</th>
                <th>Statut</th>
                <th>Nombre de réponses</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quizzes as $q): ?>
                <?php
                $stmt = $conn->prepare("SELECT COUNT(*) FROM results WHERE quiz_id = ?");
                $stmt->execute([$q['id']]);
                $count = $stmt->fetchColumn();
                ?>
                <tr>
                    <td><?= htmlspecialchars($q['title']) ?></td>
                    <td><?= htmlspecialchars($q['status']) ?></td>
                    <td><?= $count ?></td>
                    <td>
                        <a class="button" href="/views/quiz/edit.php?quiz_id=<?= $q['id'] ?>">Éditer</a>
                        <a class="button" href="/views/quiz/questions.php?quiz_id=<?= $q['id'] ?>">Questions</a>
                        <a class="button" href="/views/quiz/results.php?quiz_id=<?= $q['id'] ?>">Résultats</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a class="button" href="/controllers/logout.php">Se déconnecter</a>
</body>
</html>
