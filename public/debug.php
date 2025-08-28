<?php
// public/debug.php - Temporary debug page to diagnose the redirect loop

require_once '../config/database.php';
require_once '../classes/AuthManager.php';

echo "<h1>Debug Information</h1>";

// Test database connection
echo "<h2>Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test AuthManager
echo "<h2>Session Information</h2>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

try {
    $auth = new AuthManager($db);
    echo "✅ AuthManager created successfully<br>";
    
    echo "Is logged in: " . ($auth->isLoggedIn() ? 'YES' : 'NO') . "<br>";
    
    if ($auth->isLoggedIn()) {
        $user = $auth->getCurrentUser();
        echo "Current user: <pre>" . print_r($user, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "❌ AuthManager error: " . $e->getMessage() . "<br>";
}

// Test user table
echo "<h2>User Table Test</h2>";
try {
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Users in database: " . $result['count'] . "<br>";
    
    $query = "SELECT id, username, full_name, is_active FROM users LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sample users: <pre>" . print_r($users, true) . "</pre>";
} catch (Exception $e) {
    echo "❌ User table error: " . $e->getMessage() . "<br>";
}

echo "<h2>Server Information</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] ?? 'Unknown' . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] ?? 'Unknown' . "<br>";

echo "<hr>";
echo "<a href='login.php'>Go to Login</a> | ";
echo "<a href='index.php'>Go to Index</a> | ";
echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='clear_session'>";
echo "<button type='submit'>Clear Session</button>";
echo "</form>";

// Clear session if requested
if (isset($_POST['action']) && $_POST['action'] === 'clear_session') {
    session_destroy();
    echo "<br>✅ Session cleared! <a href='debug.php'>Refresh</a>";
}
?>