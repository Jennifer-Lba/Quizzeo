<?php
// controllers/AuthController.php
require_once __DIR__ . '/../config/conf.php';
require_once __DIR__ . '/../helpers/functions.php';

class AuthController
{
    private $conn;
    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    // INSCRIPTION
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /views/auth/register.php");
            exit;
        }

        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? '');
        // normalize role values coming from the form (support 'admin' -> 'administrateur')
        if ($role === 'admin') {
            $role = 'administrateur';
        }

        if (!$first || !$last || !$email || !$password || !$role) {
            $_SESSION['error'] = "Veuillez remplir tous les champs.";
            header("Location: /views/auth/register.php");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            header("Location: /views/auth/register.php");
            exit;
        }

        // Autoriser désormais la création d'un compte administrateur depuis le formulaire
        // (attention : cela ouvre la possibilité de créer des admins depuis l'interface publique).
        $allowedRoles = ['utilisateur', 'école', 'entreprise', 'administrateur'];
        if (!in_array($role, $allowedRoles)) {
            $_SESSION['error'] = "Rôle invalide.";
            header("Location: /views/auth/register.php");
            exit;
        }

        // vérifier si email déjà présent
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Cet email existe déjà. Connectez-vous.";
            header("Location: /views/auth/login.php");
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (first_name,last_name,email,password,role) VALUES (?,?,?,?,?)");
        $stmt->execute([$first, $last, $email, $hashed, $role]);

        $_SESSION['success'] = "Inscription réussie ! Vous pouvez vous connecter.";
        header("Location: /views/auth/login.php");
        exit;
    }

    // CONNEXION
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /views/auth/login.php");
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $_SESSION['error'] = "Veuillez remplir tous les champs.";
            header("Location: /views/auth/login.php");
            exit;
        }

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['error'] = "Cet email n'existe pas. Veuillez vous inscrire.";
            header("Location: /views/auth/register.php");
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            $_SESSION['error'] = "Mot de passe incorrect.";
            header("Location: /views/auth/login.php");
            exit;
        }

        // succès
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role']
        ];

        // Redirection selon rôle (routes propres gérées par .htaccess)
        switch ($user['role']) {
            case 'admin':
            case 'administrateur':
                header("Location: /views/admin/dashboard.php");
                break;
            case 'école':
                header("Location: /views/quiz/dashboard_school.php");
                break;
            case 'entreprise':
                header("Location: /views/quiz/dashboard_company.php");
                break;
            default:
                header("Location: /views/user/dashboard.php");
                break;
        }
        exit;
    }

    // DECONNEXION
    public function logout()
    {
        session_unset();
        session_destroy();
        header("Location: /index.php");
        exit;
    }
}

// Router
$auth = new AuthController();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'register') $auth->register();
if ($action === 'login') $auth->login();
if ($action === 'logout') $auth->logout();
