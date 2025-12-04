<?php
require_once __DIR__ . '/config/conf.php';
require_once __DIR__ . '/helpers/functions.php';


// Si l'utilisateur est connecté, rediriger vers son dashboard selon le rôle
if (isLogged()) {
    $user = currentUser();
    switch ($user['role']) {
        case 'admin':
            header("Location: /views/admin/dashboard.php");
            exit;
        case 'école':
            header("Location: /views/quiz/dashboard_school.php");
            exit;
        case 'entreprise':
            header("Location: /views/quiz/dashboard_company.php");
            exit;
        default:
            header("Location: /views/user/dashboard.php");
            exit;
    }
}

// Utilisateur non connecté : afficher page d'accueil avec login / inscription
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <img src="/assets/images/quizzeo.logo.jpg" alt="Quizzeo Logo" style="height:50px;">
        <h1>Bienvenue sur Quizzeo</h1>
        <p>Créez vos quiz et évaluez vos utilisateurs facilement !</p>

</head>
<body>
<section>
        <h2>Se connecter</h2>
        <?php if (!empty($_SESSION['error'])): ?>
            <p style="color:red;"><?= $_SESSION['error']; ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <form action="/controllers/AuthController.php" method="POST">
            <input type="hidden" name="action" value="login">
            <label>Email: <input type="email" name="email" required></label><br>
            <label>Mot de passe: <input type="password" name="password" required></label><br>
            <button type="submit">Se connecter</button>
        </form>
        <p>Pas encore de compte ? <a href="/views/auth/register.php">Créer un compte</a></p>
    </section>


    <section>
        <h2>Fonctionnalités</h2>
        <ul>
            <li>Créer des quiz (Écoles & Entreprises)</li>
            <li>Répondre à des quiz (Utilisateurs)</li>
            <li>Suivre les résultats en temps réel</li>
            <li>Gestion des comptes pour l'admin</li>
        </ul>
    </section>




</body>
</html>
