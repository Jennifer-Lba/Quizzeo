<?php
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
 
function redirect($url)
{
    header("Location: $url");
    exit;
}
 
function sanitize($value)
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}
 
 
function post($key)
{
    return isset($_POST[$key]) ? sanitize($_POST[$key]) : null;
}
 
 
function get($key)
{
    return isset($_GET[$key]) ? sanitize($_GET[$key]) : null;
}
 
 
function isLogged()
{
    return isset($_SESSION['user']);
}
 
 
function isAdmin()
{
    // Supporter deux variantes possibles du rôle administrateur
    $role = $_SESSION['user']['role'] ?? '';
    $n = normalizeRole($role);
    return isLogged() && in_array($n, ['admin', 'administrateur', 'administrateur'], true);
}
 
// Bloque l'accès si l'utilisateur n'est pas connecté
function requireLogin() {
    if (!isLogged()) {
        redirect('/views/auth/login.php');
    }
}
 
// Bloque l'accès selon le rôle
function normalizeRole(string $role): string {
    // Lowercase and remove common accents for comparison
    $r = mb_strtolower($role, 'UTF-8');
    $map = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a',
        'á' => 'a', 'ã' => 'a', 'å' => 'a',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ß' => 'ss'
    ];
    $r = strtr($r, $map);
    $r = preg_replace('/[^a-z0-9_\-]/', '', $r);
    return $r;
}
 
function requireRole($roles = []) {
    if (!isLogged()) {
        // If not logged, redirect to login
        redirect('/views/auth/login.php');
    }
 
    $current = $_SESSION['user']['role'] ?? '';
    $nCurrent = normalizeRole($current);
 
    // Normalize allowed roles for comparison
    $normalizedAllowed = array_map('normalizeRole', $roles);
 
    if (!in_array($nCurrent, $normalizedAllowed, true) && !isAdmin()) {
        $allowedList = implode(', ', $roles);
        $safeRole = htmlspecialchars($current ?: 'aucun');
        die("Accès refusé : rôle actuel={$safeRole}. Rôles autorisés : {$allowedList}.");
    }
}
 
// Récupère les informations de l'utilisateur connecté
function currentUser() {
    return $_SESSION['user'] ?? null;
}
 
// Génère un token CSRF
function generateCSRFToken() {
    // Toujours générer un nouveau token pour cette session
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
 
// Retourne un champ input caché avec le token CSRF
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
 
// Vérifie le token CSRF
function verifyCSRFToken($token = null) {
    if ($token === null) {
        $token = trim($_POST['csrf_token'] ?? '');
    }
 
    $session_token = isset($_SESSION['csrf_token']) ? trim($_SESSION['csrf_token']) : '';
 
    if (empty($session_token) || empty($token) || $token !== $session_token) {
        die("Erreur de sécurité : token invalide.");
    }
 
    return true;
}