<?php
require_once __DIR__ . '/../../controllers/QuizController.php';
require_once __DIR__ . '/../../helpers/functions.php';


// Autoriser les rôles suivants : administrateur (deux variantes), école, entreprise
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'administrateur', 'école', 'entreprise'], true)) {
    redirect('/views/auth/login.php');
}

$quizCtrl = new QuizController();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post('title');
    $description = post('description');    if ($title && $description) {
        if ($quizCtrl->create($title, $description)) {
            $message = "Quiz créé avec succès !";
        } else {
            $message = "Erreur lors de la création du quiz.";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un quiz</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }

        textarea {
            resize: vertical;
            min-height: 200px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            }

        button[type="submit"],
        a.button {
            flex: 1;
            padding: 12px 20px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
        }

        button[type="submit"]:hover {
            background-color: #45a049;
        }

        a.button {
            background-color: #f44336;
            color: white;
        }

        a.button:hover {
            background-color: #da190b;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Créer un quiz</h1>

        <?php if ($message) : ?>
            <div class="message <?= strpos($message, 'Erreur') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="title">Titre du quiz :</label>
                <input type="text" id="title" name="title" placeholder="Ex: Quiz de Mathématiques" required>
            </div>

            <div class="form-group">
                <label for="description">Description :</label>
                <textarea id="description" name="description" placeholder="Décrivez votre quiz..." required></textarea>
            </div>

            <div class="button-group">
                <button type="submit">Créer le quiz</button>
                <a class="button" href="/views/quiz/dashboard_school.php">Retour au dashboard</a>
            </div>
</body>
</html>




        


    
