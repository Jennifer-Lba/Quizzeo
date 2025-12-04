<?php
// view/quiz/results.php
//cette page affiche les résultats de un quiz spécifique.
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../config/conf.php';
requireLogin(); //  accès uniquement aux utilisateurs connectés

$quizId = get('quiz_id');
if (!$quizId) die("Quiz introuvable.");
// Vérifier que le quiz existe et est actif
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();
if (!$quiz) die("Quiz introuvable.");


// Vérifier le rôle de l'utilisateur
$user = currentUser();
// Si c'est le créateur ou un admin, afficher tous les résultats
if ($quiz['creator_id'] == $user['id'] || isAdmin()) {
    $stmt = $conn->prepare("
        SELECT r.*, u.first_name, u.last_name
        FROM results r
        JOIN users u ON u.id = r.user_id
        WHERE r.quiz_id = ?
        ORDER BY r.score DESC
    ");
    $stmt->execute([$quizId]);
    $results = $stmt->fetchAll();
    $show_all = true;
} else {
    // Pour les utilisateurs ordinaires, afficher uniquement leur propre résultat (s'il existe)
    $stmt = $conn->prepare("
        SELECT r.*, u.first_name, u.last_name
        FROM results r
        JOIN users u ON u.id = r.user_id
        WHERE r.quiz_id = ? AND r.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$quizId, $user['id']]);
    $userResult = $stmt->fetch();
    $show_all = false;
}

// Déterminer le lien de retour selon le rôle (utiliser isAdmin() pour normaliser admin)
$dashboardLink = '/index.php';
if ($user['role'] === 'école') {
    $dashboardLink = '/views/quiz/dashboard_school.php';
} elseif ($user['role'] === 'entreprise') {
    $dashboardLink = '/views/quiz/dashboard_company.php';
} elseif (isAdmin()) {
    $dashboardLink = '/views/admin/dashboard.php';
} elseif ($user['role'] === 'utilisateur') {
    $dashboardLink = '/views/user/dashboard.php';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats du quiz</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <h1>Résultats du quiz: <?=htmlspecialchars($quiz["title"]) ?></h1>
    <?php if (!empty($show_all) && $show_all): ?>
        <p>Nombre de participants: <?= count($results) ?></p>

        <?php if (empty($results)): ?>
            <p>Aucun résultat pour ce quiz.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['last_name']) ?></td>
                            <td><?= htmlspecialchars($r['first_name']) ?></td>
                            <td><?= htmlspecialchars($r['score']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <?php if (!empty($userResult)): ?>
            <p>Votre résultat :</p>
            <ul>
                <li>Nom : <?= htmlspecialchars($userResult['last_name']) ?></li>
                <li>Prénom : <?= htmlspecialchars($userResult['first_name']) ?></li>
                <li>Score : <?= htmlspecialchars($userResult['score']) ?></li>
            </ul>
        <?php else: ?>
            <p>Vous n'avez pas de résultat pour ce quiz (peut-être que vous n'avez pas encore répondu).</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="/index.php">Retour à l’accueil</a>
</body>
</html>
