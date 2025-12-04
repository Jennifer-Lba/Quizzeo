<?php
session_start();
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../config/conf.php';

requireLogin();
$user = currentUser();

// Si un utilisateur entreprise arrive ici, le rediriger vers son dashboard
if (($user['role'] ?? '') === 'entreprise') {
    redirect('/views/quiz/dashboard_company.php');
}

// Autoriser uniquement : école ou administrateur
requireRole(['école', 'admin', 'administrateur']);

// Récupérer les quiz de l'école
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
    <h1>Dashboard École</h1>
    <a class="button" href="/views/quiz/create.php">Créer un nouveau quiz</a>

    <?php if (count($quizzes) > 0): ?>
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
                // Compter le nombre de réponses
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
    <?php else: ?>
    <p>Aucun quiz créé. <a href="/views/quiz/create.php">Créer un quiz</a></p>
    <?php endif; ?>

    <a class="button" href="/controllers/logout.php">Se déconnecter</a>
</body>
</html>
