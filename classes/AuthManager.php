<?php
// classes/AuthManager.php
class AuthManager {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Login user
    public function login($username, $password, $remember = false) {
        try {
            $query = "SELECT u.*, r.name as role_name, r.permissions 
                      FROM users u 
                      JOIN roles r ON u.role_id = r.id 
                      WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Create session
                $this->createSession($user, $remember);
                
                // Log activity
                $this->logActivity($user['id'], 'login', 'User logged in');
                
                return [
                    'success' => true,
                    'user' => $this->sanitizeUser($user)
                ];
            } else {
                $this->logActivity(null, 'login_failed', 'Failed login attempt for: ' . $username);
                return ['success' => false, 'error' => 'Invalid credentials'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Register new user
    public function register($userData) {
        try {
            // Check if username or email already exists
            $query = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userData['username'], $userData['email']]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'error' => 'Username or email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Generate email verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Insert user
            $query = "INSERT INTO users (username, email, password, full_name, role_id, email_verification_token) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $userData['username'],
                $userData['email'],
                $hashedPassword,
                $userData['full_name'],
                $userData['role_id'] ?? 3, // Default to User role
                $verificationToken
            ]);
            
            if ($result) {
                $userId = $this->conn->lastInsertId();
                $this->logActivity($userId, 'register', 'User registered');
                
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'verification_token' => $verificationToken
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to create user'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Create user session
    private function createSession($user, $remember = false) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true);
        $_SESSION['logged_in'] = true;
        
        // Create session token for database tracking
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $sessionToken;
        
        $expiresAt = $remember ? 
            date('Y-m-d H:i:s', strtotime('+30 days')) : 
            date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store session in database
        $query = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $user['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        // Set remember me cookie if requested
        if ($remember) {
            setcookie('remember_token', $sessionToken, strtotime('+30 days'), '/', '', true, true);
        }
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        // Debug: Clear any corrupted session data
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }
        
        return true;
    }
    
    // Get current user
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role_name' => $_SESSION['role_name'],
            'permissions' => $_SESSION['permissions']
        ];
    }
    
    // Check if user has permission
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array($permission, $permissions);
    }
    
    // Logout user
    public function logout() {
        if (isset($_SESSION['session_token'])) {
            // Remove session from database
            $query = "DELETE FROM user_sessions WHERE session_token = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$_SESSION['session_token']]);
        }
        
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Clear remember me cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Update last login
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
    }
    
    // Sanitize user data (remove sensitive info)
    private function sanitizeUser($user) {
        unset($user['password']);
        unset($user['password_reset_token']);
        unset($user['email_verification_token']);
        return $user;
    }
    
    // Log user activity
    public function logActivity($userId, $action, $description = null) {
        $query = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    // Get all users (admin only)
    public function getAllUsers() {
        if (!$this->hasPermission('user.read')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }
        
        $query = "SELECT u.id, u.username, u.email, u.full_name, u.is_active, u.last_login, 
                         u.created_at, r.name as role_name 
                  FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all roles
    public function getAllRoles() {
        $query = "SELECT * FROM roles ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create new user (admin only)
    public function createUser($userData) {
        if (!$this->hasPermission('user.create')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }
        
        return $this->register($userData);
    }
    
    // Update user (admin only or own profile)
    public function updateUser($userId, $userData) {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->hasPermission('user.update') && $currentUser['id'] != $userId) {
            return ['success' => false, 'error' => 'Permission denied'];
        }
        
        try {
            $fields = [];
            $values = [];
            
            if (!empty($userData['email'])) {
                $fields[] = 'email = ?';
                $values[] = $userData['email'];
            }
            
            if (!empty($userData['full_name'])) {
                $fields[] = 'full_name = ?';
                $values[] = $userData['full_name'];
            }
            
            if (!empty($userData['password'])) {
                $fields[] = 'password = ?';
                $values[] = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            
            if (isset($userData['is_active']) && $this->hasPermission('user.update')) {
                $fields[] = 'is_active = ?';
                $values[] = $userData['is_active'];
            }
            
            if (isset($userData['role_id']) && $this->hasPermission('user.update')) {
                $fields[] = 'role_id = ?';
                $values[] = $userData['role_id'];
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'No fields to update'];
            }
            
            $values[] = $userId;
            
            $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($values);
            
            if ($result) {
                $this->logActivity($currentUser['id'], 'user_update', 'Updated user ID: ' . $userId);
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to update user'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Delete user (admin only)
    public function deleteUser($userId) {
        if (!$this->hasPermission('user.delete')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }
        
        $currentUser = $this->getCurrentUser();
        if ($currentUser['id'] == $userId) {
            return ['success' => false, 'error' => 'Cannot delete your own account'];
        }
        
        try {
            // Soft delete - just deactivate
            $query = "UPDATE users SET is_active = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$userId]);
            
            if ($result) {
                $this->logActivity($currentUser['id'], 'user_delete', 'Deleted user ID: ' . $userId);
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to delete user'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Clean expired sessions
    public function cleanExpiredSessions() {
        $query = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
    
    // Get user activity logs
    public function getUserActivityLogs($userId = null, $limit = 100) {
        if (!$this->hasPermission('log.read')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }
        
        if ($userId) {
            $query = "SELECT al.*, u.username, u.full_name 
                      FROM activity_logs al 
                      LEFT JOIN users u ON al.user_id = u.id 
                      WHERE al.user_id = ? 
                      ORDER BY al.created_at DESC 
                      LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId, $limit]);
        } else {
            $query = "SELECT al.*, u.username, u.full_name 
                      FROM activity_logs al 
                      LEFT JOIN users u ON al.user_id = u.id 
                      ORDER BY al.created_at DESC 
                      LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$limit]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Password reset request
    public function requestPasswordReset($email) {
        try {
            $query = "SELECT id FROM users WHERE email = ? AND is_active = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $query = "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$token, $expires, $user['id']]);
                
                return ['success' => true, 'token' => $token];
            } else {
                return ['success' => false, 'error' => 'Email not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Reset password
    public function resetPassword($token, $newPassword) {
        try {
            $query = "SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $query = "UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([$hashedPassword, $user['id']]);
                
                if ($result) {
                    $this->logActivity($user['id'], 'password_reset', 'Password reset completed');
                    return ['success' => true];
                }
            }
            
            return ['success' => false, 'error' => 'Invalid or expired token'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>