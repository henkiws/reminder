<?php
// public/auth.php - Authentication check only
require_once '../config/database.php';
require_once '../classes/AuthManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthManager($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get current user data
$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// Helper function to check permissions
function hasPermission($permission) {
    global $currentUser;
    return in_array($permission, $currentUser['permissions'] ?? []);
}

// Helper function to require permission
function requirePermission($permission, $message = 'Access denied') {
    if (!hasPermission($permission)) {
        http_response_code(403);
        die(json_encode(['error' => $message]));
    }
}
?>