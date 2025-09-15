<?php
// public/auth_check.php - FIXED VERSION
session_start(); // Start session first

require_once '../config/database.php';
require_once '../classes/AuthManager.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("Database connection failed. Please check your configuration.");
    }
    
    $auth = new AuthManager($db);

    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        // If it's an AJAX request, return JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        
        // Otherwise redirect to login
        header('Location: login.php');
        exit;
    }

    // Get current user data
    $currentUser = $auth->getCurrentUser();
    if (!$currentUser) {
        // If it's an AJAX request, return JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Invalid session']);
            exit;
        }
        
        // Clear invalid session and redirect
        $auth->logout();
        header('Location: login.php');
        exit;
    }

    // Helper function to check permissions
    function hasPermission($permission) {
        global $currentUser;
        if (!$currentUser || !isset($currentUser['permissions'])) {
            return false;
        }
        return in_array($permission, $currentUser['permissions']);
    }

    // Helper function to require permission
    function requirePermission($permission, $message = 'Access denied') {
        if (!hasPermission($permission)) {
            // If it's an AJAX request, return JSON error
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => $message]);
                exit;
            }
            
            // Otherwise show error page or redirect
            die('<h1>403 Forbidden</h1><p>' . htmlspecialchars($message) . '</p>');
        }
    }

    // Make sure all variables are available
    global $db, $auth, $currentUser;

} catch (Exception $e) {
    error_log('Authentication error: ' . $e->getMessage());
    die('Authentication system error. Please try again.');
}
?>