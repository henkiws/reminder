<?php
// fix_passwords.php - Fix default user passwords
echo "Fixing default user passwords...\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("❌ Database connection failed\n");
    }
    
    echo "✅ Connected to database\n";
    
    // Define default users with correct passwords
    $defaultUsers = [
        [
            'username' => 'admin',
            'password' => 'admin123',
            'email' => 'admin@example.com',
            'full_name' => 'System Administrator',
            'role_id' => 1
        ],
        [
            'username' => 'manager',
            'password' => 'admin123',
            'email' => 'manager@example.com',
            'full_name' => 'Manager User',
            'role_id' => 3
        ],
        [
            'username' => 'user1',
            'password' => 'admin123',
            'email' => 'user1@example.com',
            'full_name' => 'Regular User',
            'role_id' => 4
        ]
    ];
    
    foreach ($defaultUsers as $user) {
        echo "\nProcessing user: {$user['username']}\n";
        
        // Check if user exists
        $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$user['username']]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $newHash = password_hash($user['password'], PASSWORD_DEFAULT);
        
        if ($existingUser) {
            // User exists, update password
            echo "  👤 User exists, updating password...\n";
            
            // Verify current password
            $currentVerify = password_verify($user['password'], $existingUser['password']);
            echo "  🔐 Current password verify: " . ($currentVerify ? 'CORRECT' : 'INCORRECT') . "\n";
            
            if (!$currentVerify) {
                echo "  🔧 Updating password hash...\n";
                $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
                $updateStmt->execute([$newHash, $user['username']]);
                echo "  ✅ Password updated successfully\n";
            } else {
                echo "  ✅ Password already correct\n";
            }
        } else {
            // User doesn't exist, create it
            echo "  🆕 User not found, creating...\n";
            
            $insertStmt = $db->prepare("
                INSERT INTO users (username, email, password, full_name, role_id, is_active, email_verified) 
                VALUES (?, ?, ?, ?, ?, 1, 1)
            ");
            $insertStmt->execute([
                $user['username'],
                $user['email'],
                $newHash,
                $user['full_name'],
                $user['role_id']
            ]);
            echo "  ✅ User created successfully\n";
        }
        
        // Verify the fix worked
        $verifyStmt = $db->prepare("SELECT password FROM users WHERE username = ?");
        $verifyStmt->execute([$user['username']]);
        $updatedUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($updatedUser) {
            $finalVerify = password_verify($user['password'], $updatedUser['password']);
            echo "  🧪 Final verification: " . ($finalVerify ? '✅ SUCCESS' : '❌ FAILED') . "\n";
        }
    }
    
    echo "\n🎉 Password fix completed!\n";
    echo "\nYou can now login with:\n";
    echo "• admin / admin123\n";
    echo "• manager / admin123\n";
    echo "• user1 / admin123\n";
    
    echo "\nNext steps:\n";
    echo "1. Run: php -S localhost:8005 -t public\n";
    echo "2. Visit: http://localhost:8005/login.php\n";
    echo "3. Login with admin / admin123\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>