<?php
// auth_debug.php - Comprehensive Authentication Debug Tool
// Place this in your project root directory

echo "<h1>WhatsApp Notification System - Authentication Debug</h1>";
echo "<hr>";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Database connection: <strong>SUCCESS</strong><br>";
        
        // Check database name
        $result = $db->query("SELECT DATABASE() as db_name");
        $dbName = $result->fetch(PDO::FETCH_ASSOC);
        echo "📊 Current database: <strong>" . $dbName['db_name'] . "</strong><br>";
        
    } else {
        echo "❌ Database connection: <strong>FAILED</strong><br>";
        die("Cannot continue without database connection");
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    die("Cannot continue without database connection");
}

// Test 2: Check Required Tables
echo "<h2>2. Database Tables Check</h2>";
$requiredTables = ['users', 'roles', 'user_sessions', 'activity_logs'];
foreach ($requiredTables as $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "✅ Table '$table': <strong>EXISTS</strong><br>";
            
            // Check table structure for users table
            if ($table === 'users') {
                $result = $db->query("DESCRIBE users");
                $columns = $result->fetchAll(PDO::FETCH_ASSOC);
                $columnNames = array_column($columns, 'Field');
                echo "&nbsp;&nbsp;📋 Columns: " . implode(', ', $columnNames) . "<br>";
            }
        } else {
            echo "❌ Table '$table': <strong>MISSING</strong><br>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
    }
}

// Test 3: Check Default Users
echo "<h2>3. Default Users Check</h2>";
try {
    $result = $db->query("SELECT u.id, u.username, u.email, u.full_name, u.is_active, u.password, r.name as role_name 
                          FROM users u 
                          LEFT JOIN roles r ON u.role_id = r.id 
                          ORDER BY u.id");
    $users = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ No users found in database<br>";
        echo "<strong>Solution:</strong> Run the database setup script to create default users<br>";
    } else {
        echo "✅ Found " . count($users) . " users:<br>";
        foreach ($users as $user) {
            $status = $user['is_active'] ? '🟢 Active' : '🔴 Inactive';
            echo "&nbsp;&nbsp;👤 <strong>{$user['username']}</strong> ({$user['email']}) - {$user['role_name']} - $status<br>";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;Password hash: " . substr($user['password'], 0, 20) . "...<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking users: " . $e->getMessage() . "<br>";
}

// Test 4: Password Hash Test
echo "<h2>4. Password Hash Verification Test</h2>";
$testPassword = 'admin123';
$hash = password_hash($testPassword, PASSWORD_DEFAULT);
$verify = password_verify($testPassword, $hash);

echo "🔐 Test password: <strong>$testPassword</strong><br>";
echo "🔑 Generated hash: <code>$hash</code><br>";
echo "✓ Verification result: <strong>" . ($verify ? 'SUCCESS' : 'FAILED') . "</strong><br>";

// Test against database passwords
if (!empty($users)) {
    echo "<br><strong>Testing against database users:</strong><br>";
    foreach ($users as $user) {
        if ($user['username'] === 'admin') {
            $dbVerify = password_verify($testPassword, $user['password']);
            echo "&nbsp;&nbsp;👤 {$user['username']}: " . ($dbVerify ? '✅ PASSWORD CORRECT' : '❌ PASSWORD INCORRECT') . "<br>";
            
            if (!$dbVerify) {
                // Try to fix the password
                echo "&nbsp;&nbsp;&nbsp;&nbsp;🔧 Attempting to fix admin password...<br>";
                try {
                    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
                    $stmt->execute([$newHash]);
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ Admin password updated successfully<br>";
                } catch (Exception $e) {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ Failed to update password: " . $e->getMessage() . "<br>";
                }
            }
        }
    }
}

// Test 5: Authentication Manager Test
echo "<h2>5. Authentication Manager Test</h2>";
try {
    require_once 'classes/AuthManager.php';
    $auth = new AuthManager($db);
    echo "✅ AuthManager created successfully<br>";
    
    // Test login
    echo "<br><strong>Testing login process:</strong><br>";
    $loginResult = $auth->login('admin', 'admin123');
    echo "🔐 Login attempt result: " . ($loginResult['success'] ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
    
    if (!$loginResult['success']) {
        echo "&nbsp;&nbsp;❌ Error: " . $loginResult['error'] . "<br>";
    } else {
        echo "&nbsp;&nbsp;✅ User data: " . print_r($loginResult['user'], true) . "<br>";
        
        // Test session
        echo "<br><strong>Testing session:</strong><br>";
        $isLoggedIn = $auth->isLoggedIn();
        echo "📋 Is logged in: " . ($isLoggedIn ? '✅ YES' : '❌ NO') . "<br>";
        
        if ($isLoggedIn) {
            $currentUser = $auth->getCurrentUser();
            echo "👤 Current user: " . print_r($currentUser, true) . "<br>";
        }
        
        // Logout
        $auth->logout();
        echo "🚪 Logout completed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ AuthManager error: " . $e->getMessage() . "<br>";
}

// Test 6: Session Configuration
echo "<h2>6. Session Configuration</h2>";
echo "📋 Session save path: <strong>" . session_save_path() . "</strong><br>";
echo "📋 Session save path writable: <strong>" . (is_writable(session_save_path()) ? '✅ YES' : '❌ NO') . "</strong><br>";
echo "📋 Session module name: <strong>" . session_module_name() . "</strong><br>";
echo "📋 Session cookie lifetime: <strong>" . ini_get('session.cookie_lifetime') . " seconds</strong><br>";

// Test 7: PHP Extensions
echo "<h2>7. Required PHP Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'session', 'hash'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? '✅' : '❌') . " $ext: <strong>" . ($loaded ? 'LOADED' : 'MISSING') . "</strong><br>";
}

// Test 8: File Permissions
echo "<h2>8. File Permissions</h2>";
$files = [
    'config/database.php' => 'config/database.php',
    'classes/AuthManager.php' => 'classes/AuthManager.php',
    'public/login.php' => 'public/login.php',
    'public/index.php' => 'public/index.php'
];

foreach ($files as $path => $description) {
    $readable = file_exists($path) && is_readable($path);
    echo ($readable ? '✅' : '❌') . " $description: <strong>" . ($readable ? 'READABLE' : 'NOT FOUND/READABLE') . "</strong><br>";
}

// Test 9: Quick Fix Recommendations
echo "<h2>9. Quick Fix Recommendations</h2>";

// Check if we can run a quick login test
try {
    $quickTest = $auth->login('admin', 'admin123');
    if ($quickTest['success']) {
        echo "🎉 <strong style='color: green;'>AUTHENTICATION IS WORKING!</strong><br>";
        echo "✅ You can now access: <a href='public/login.php'>Login Page</a><br>";
        $auth->logout(); // Clean up
    } else {
        echo "❌ <strong style='color: red;'>AUTHENTICATION STILL NOT WORKING</strong><br>";
        echo "<strong>Try these fixes:</strong><br>";
        echo "1. Run database setup: <code>php setup_database.php</code><br>";
        echo "2. Check database credentials in config/database.php<br>";
        echo "3. Verify MySQL is running<br>";
        echo "4. Check file permissions<br>";
    }
} catch (Exception $e) {
    echo "❌ Quick test failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If all tests pass, try: <a href='public/login.php'>Login Page</a></li>";
echo "<li>Use credentials: <strong>admin / admin123</strong></li>";
echo "<li>If login fails, check the error logs</li>";
echo "<li>Enable debug mode by adding <code>?debug=1</code> to login URL</li>";
echo "</ol>";

echo "<h3>Development Server:</h3>";
echo "<code>php -S localhost:8005 -t public</code><br>";
echo "Then visit: <a href='http://localhost:8005/login.php'>http://localhost:8005/login.php</a>";
?>