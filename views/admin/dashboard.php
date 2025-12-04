<?php
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../config/conf.php';


// Afficher les erreurs (évite la page blanche)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sécurité
requireLogin();
// Supporter les deux valeurs possibles du rôle administrateur
requireRole(['admin', 'administrateur']);

$user = currentUser();

// Utiliser la connexion PDO fournie dans config (nommée $conn)
$stmt = $conn->query("SELECT id, first_name, last_name, email, role, is_active FROM users");
$users = $stmt->fetchAll();

// Tous les quizzes
 $stmt = $conn->query("
    SELECT q.id, q.title, q.status, u.first_name, u.last_name
    FROM quizzes q
    JOIN users u ON q.creator_id = u.id
");
$quizzes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrateur</title>
</head>
<body>

<h1>Bonjour <?= htmlspecialchars($user['first_name']) ?>, Dashboard Admin</h1>

<h2>Liste des utilisateurs</h2>
<table>
<thead>
<tr>
<th>Nom</th>
<th>Prénom</th>
<th>Email</th>
<th>Rôle</th>
<th>Actif</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
<td><?= htmlspecialchars($u['last_name']) ?></td>
<td><?= htmlspecialchars($u['first_name']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>
<td><?= htmlspecialchars($u['role']) ?></td>
<td><?= $u['is_active'] ? "Oui" : "Non" ?></td>
<td>
    <?php if ($u['is_active']): ?>
        <a href="/controllers/AdminController.php?action=setUserActive&id=<?= $u['id'] ?>&active=0" onclick="return confirm('Désactiver cet utilisateur ?')">Désactiver</a>
    <?php else: ?>
        <a href="/controllers/AdminController.php?action=setUserActive&id=<?= $u['id'] ?>&active=1" onclick="return confirm('Activer cet utilisateur ?')">Activer</a>
    <?php endif; ?>
    |
    <a href="/controllers/AdminController.php?action=deleteUser&id=<?= $u['id'] ?>" onclick="return confirm('Supprimer cet utilisateur ? Cela est irréversible.')">Supprimer</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h2>Liste des quiz</h2>
<table>
<thead>
<tr>
<th>Titre</th>
<th>Créateur</th>
<th>Statut</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($quizzes as $q): ?>
<tr>
<td><?= htmlspecialchars($q['title']) ?></td>
<td><?= htmlspecialchars($q['first_name'] . ' ' . $q['last_name']) ?></td>
<td><?= htmlspecialchars($q['status']) ?></td>
<td>
    <?php if ($q['status'] === 'active'): ?>
        <a href="/controllers/AdminController.php?action=setQuizStatus&id=<?= $q['id'] ?>&status=disabled" onclick="return confirm('Désactiver ce quiz ?')">Désactiver</a>
    <?php else: ?>
        <a href="/controllers/AdminController.php?action=setQuizStatus&id=<?= $q['id'] ?>&status=active" onclick="return confirm('Activer ce quiz ?')">Activer</a>
    <?php endif; ?>
    |
    <a href="/controllers/AdminController.php?action=deleteQuiz&id=<?= $q['id'] ?>" onclick="return confirm('Supprimer ce quiz ?')">Supprimer</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<a href="/controllers/logout.php">Se déconnecter</a>

</body>
</html>