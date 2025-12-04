<?php
session_start();

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/users.php';
require_once __DIR__ . '/../models/quiz.php';

class AdminController
{
    private static function requireAdmin()
    {
        if (!isAdmin()) {
            redirect('../views/auth/login.php');
            exit;
        }
    }

    private static function sanitizeId($id)
    {
        // Vérifie que c’est un entier strictement
        return filter_var($id, FILTER_VALIDATE_INT);
    }

    public static function dashboard()
    {
        self::requireAdmin();

        $users = User::getAll();
        $quizzes = Quiz::getAll();

        include __DIR__ . '/../views/admin/dashboard.php';
    }

    public static function deleteUser($id)
    {
        self::requireAdmin();

        $id = self::sanitizeId($id);
        if (!$id) {
            redirect('../views/admin/dashboard.php?error=invalid_id');
            exit;
        }

        User::delete($id);
        redirect('../views/admin/dashboard.php?success=user_deleted');
        exit;
    }

    public static function deleteQuiz($id)
    {
        self::requireAdmin();

        $id = self::sanitizeId($id);
        if (!$id) {
            redirect('../views/admin/dashboard.php?error=invalid_id');
            exit;
        }

        Quiz::delete($id);
        redirect('../views/admin/dashboard.php?success=quiz_deleted');
        exit;
    }

    public static function setUserActive($id)
    {
        self::requireAdmin();

        $id = self::sanitizeId($id);
        if (!$id) {
            redirect('../views/admin/dashboard.php?error=invalid_id');
            exit;
        }

        $active = isset($_GET['active']) ? intval($_GET['active']) : null;
        if ($active === null || ($active !== 0 && $active !== 1)) {
            redirect('../views/admin/dashboard.php?error=invalid_active');
            exit;
        }

        global $conn;
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$active, $id]);

        redirect('../views/admin/dashboard.php?success=user_status_updated');
        exit;
    }

    public static function setQuizStatus($id)
    {
        self::requireAdmin();

        $id = self::sanitizeId($id);
        if (!$id) {
            redirect('../views/admin/dashboard.php?error=invalid_id');
            exit;
        }

        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $allowed = ['active', 'disabled'];
        if (!in_array($status, $allowed, true)) {
            redirect('../views/admin/dashboard.php?error=invalid_status');
            exit;
        }

        global $conn;
        $stmt = $conn->prepare("UPDATE quizzes SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        redirect('../views/admin/dashboard.php?success=quiz_status_updated');
        exit;
    }
}


//   ROUTER SÉCURISÉ


if (isset($_GET['action'])) {

    // Sécurise l'action
    $action = sanitize($_GET['action']);

    // Liste blanche (actions autorisées)
    $allowedActions = ['deleteUser', 'deleteQuiz', 'setUserActive', 'setQuizStatus'];

    if (!in_array($action, $allowedActions)) {
        redirect('../views/admin/dashboard.php?error=invalid_action');
        exit;
    }

    // Vérifie que l’ID existe
    if (!isset($_GET['id'])) {
        redirect('../views/admin/dashboard.php?error=missing_id');
        exit;
    }

    $id = $_GET['id'];

    if ($action === 'deleteUser') {
        AdminController::deleteUser($id);
    }

    if ($action === 'deleteQuiz') {
        AdminController::deleteQuiz($id);
    }

    if ($action === 'setUserActive') {
        AdminController::setUserActive($id);
    }

    if ($action === 'setQuizStatus') {
        AdminController::setQuizStatus($id);
    }

    // Si on arrive ici sans correspondance, rediriger vers le dashboard
    redirect('../views/admin/dashboard.php?error=unhandled_action');
    exit;
}