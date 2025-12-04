<?php
session_start();
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../config/conf.php';

requireLogin();
$user = currentUser();
$role = $user['role'] ?? 'utilisateur';

// Génération CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupération des messages flash
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
</head>
<body>
 <h1>Mon profil</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/controllers/UserController.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div>
            <label>Prénom *</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
        </div>

        <div>
            <label>Nom *</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
        </div>

        <div>
            <label>Email *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
<div>
            <label>Nouveau mot de passe</label>
            <input type="password" name="password" placeholder="Laissez vide pour ne pas changer">
        </div>

        <?php if ($role === 'admin'): ?>
            <div>
                <label>Rôle</label>
                <select name="role">
                    <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Administrateur</option>
                    <option value="école" <?= $user['role']=='école'?'selected':'' ?>>École</option>
                    <option value="entreprise" <?= $user['role']=='entreprise'?'selected':'' ?>>Entreprise</option>
                    <option value="utilisateur" <?= $user['role']=='utilisateur'?'selected':'' ?>>Utilisateur</option>
                </select>
            </div>
        <?php endif; ?>

        <button type="submit">Modifier</button>
    </form>

</body>
</html>
