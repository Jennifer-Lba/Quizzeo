<?php
session_start();
require_once "../../helpers/functions.php"; // fonctions utiles si besoin
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
</head>

<body>
    <h1>Connexion</h1>

    <?php if (!empty($_SESSION['error'])): ?>
        <p style="color:red;"><?= htmlspecialchars($_SESSION['error']); ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <p style="color:green;"><?= htmlspecialchars($_SESSION['success']); ?></p>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form action="/controllers/AuthController.php" method="POST">
        <input type="hidden" name="action" value="login">

        <div>
            <label for="email">Email :</label>
            <input type="email" name="email" id="email" required>
        </div>

        <div>
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>
        </div>

        <button type="submit"> Se connecter </button>
    </form>

    <p>Pas encore de compte ? <a href="register.php">S’inscrire</a></p>

</body>

</html>