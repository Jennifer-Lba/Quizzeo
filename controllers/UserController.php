<?php
//cette
session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__. '/../config/conf.php';

requireLogin();
$user = currentUser();
$role = $user['role'] ?? 'utilisateur';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// CSRF check
$csrf = post('csrf_token');
if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
    $_SESSION['error'] = "Formulaire invalide (CSRF).";
    redirect('../views/user/profile.php');
}

// Récupération des données
$firstName = trim(post('first_name'));
$lastName  = trim(post('last_name'));
$email     = trim(post('email'));
$password  = post('password');

// Validation obligatoire
if (!$firstName || !$lastName || !$email) {
    $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires.";
    redirect('../views/user/profile.php');
}

// Validation email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Adresse email invalide.";
    redirect('../views/user/profile.php');
}

// Vérification unicité email
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $user['id']]);
if ($stmt->rowCount() > 0) {
    $_SESSION['error'] = "Cet email est déjà utilisé.";
    redirect('../views/user/profile.php');
}

// Validation mot de passe si fourni
if (!empty($password) && strlen($password) < 8) {
    $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
    redirect('../views/user/profile.php');
}

// Gestion du rôle (seul admin peut le modifier)
$newRole = $role;
if ($role === 'admin' && isset($_POST['role'])) {
    $validRoles = ['admin','école','entreprise','utilisateur'];
    if (in_array($_POST['role'], $validRoles)) {
        $newRole = $_POST['role'];
    }
}

try {
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE users
            SET first_name = ?, last_name = ?, email = ?, password = ?, role = ?
            WHERE id = ?
        ");
        $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $newRole, $user['id']]);
        session_regenerate_id(true); // Sécurité après changement de mot de passe
    } else {
        $stmt = $conn->prepare("
            UPDATE users
            SET first_name = ?, last_name = ?, email = ?, role = ?
            WHERE id = ?
        ");
        $stmt->execute([$firstName, $lastName, $email, $newRole, $user['id']]);
    }

    // Mise à jour session
    $_SESSION['user']['first_name'] = $firstName;
    $_SESSION['user']['last_name']  = $lastName;
    $_SESSION['user']['email']      = $email;
    $_SESSION['user']['role']       = $newRole;

    $_SESSION['success'] = "Profil mis à jour avec succès.";
    redirect('../views/user/profile.php');

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
    redirect(url: '../views/user/profile.php');
}
