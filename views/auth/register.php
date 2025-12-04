<?php
session_start();
require_once "../../helpers/functions.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
</head>

<body>
    <h1>INSCRIPTION</h1>
    <?php if (!empty($_SESSION['error'])): ?>
        <p style="color:red;"><?= htmlspecialchars($_SESSION['error']); ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <p style="color:green;"><?= htmlspecialchars($_SESSION['success']); ?></p>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form action="/controllers/AuthController.php" method="POST">
        <!--
        //permet denvoyer linfo au controleur donc le controller saura quoi faire -->
        <input type="hidden" name="action" value="register">
        <div>
            <label for="first_name">Prénom</label>
            <input type="text" name="first_name" id="first_name">
            <br>
        </div>

        <div>
            <br>
            <label for="last_name">Nom</label>
            <input type="text" name="last_name" id="last_name">
        </div>
        <div>
            <br>
            <label for="email">Email</label>
            <input type="email" name="email" id="email">
        </div>
        <div>
            <br>
            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password">
        </div>
        <div>
            <br>
            <label for="role">Sélectionner votre Rôle</label>
            <select name="role" id="role" required>
                <option value="administrateur">Administrateur</option>
                <option value="école">école</option>
                <option value="entreprise">entreprise</option>
                <option value="utilisateur">utilisateur</option>
            </select>
        </div>
        <p>Vous avez déjà un compte ? </p>
        <a href="login.php">Se connecter </a> <br>
        <br>
        <button type="submit"> S'inscrire </button>



    </form>
</body>

</html>