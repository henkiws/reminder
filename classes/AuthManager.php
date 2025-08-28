<?php
// classes/AuthManager.php - FIXED VERSION
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
    
    // Login user - FIXED VERSION
    public function login($username, $password, $remember = false) {
        try {
            // Debug: Log the login attempt
            error_log("Login attempt for: " . $username);
            
            $query = "SELECT u.*, r.name as role_name, r.permissions 
                      FROM users u 
                      JOIN roles r ON u.role_id = r.id 
                      WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug: Check if user was found
            if (!$user) {
                error_log("User not found: " . $username);
                return ['success' => false, 'error' => 'User not found or inactive'];
            }
            
            error_log("User found: " . $user['username'] . ", checking password...");
            error_log("Stored hash: " . $user['password']);
            error_log("Input password: " . $password);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                error_log("Password verified successfully");
                
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
                error_log("Password verification failed");
                $this->logActivity(null, 'login_failed', 'Failed login attempt for: ' . $username);
                return ['success' => false, 'error' => 'Invalid credentials'];
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Register new user - FIXED VERSION
    public function register($userData) {
        try {
            // Check if username or email already exists
            $query = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userData['username'], $userData['email']]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'error' => 'Username or email already exists'];
            }
            
            // Hash password properly
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            error_log("Registering user with hash: " . $hashedPassword);
            
            // Generate email verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Insert user
            $query = "INSERT INTO users (username, email, password, full_name, role_id, email_verification_token, email_verified) 
                      VALUES (?, ?, ?, ?, ?, ?, 1)"; // Set email_verified = 1 for demo
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $userData['username'],
                $userData['email'],
                $hashedPassword,
                $userData['full_name'],
                $userData['role_id'] ?? 4, // Default to User role (id=4)
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
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Create user session - FIXED VERSION
    private function createSession($user, $remember = false) {
        // Clear any existing session data
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true) ?? [];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        error_log("Session created for user: " . $user['username']);
        error_log("Session data: " . print_r($_SESSION, true));
        
        // Create session token for database tracking
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $sessionToken;
        
        $expiresAt = $remember ? 
            date('Y-m-d H:i:s', strtotime('+30 days')) : 
            date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store session in database
        try {
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
        } catch (Exception $e) {
            error_log("Failed to store session in database: " . $e->getMessage());
        }
        
        // Set remember me cookie if requested
        if ($remember) {
            setcookie('remember_token', $sessionToken, strtotime('+30 days'), '/', '', false, true);
        }
    }
    
    // Check if user is logged in - FIXED VERSION
    public function isLoggedIn() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            $this->startSession();
        }
        
        // Debug current session
        error_log("Checking login status. Session data: " . print_r($_SESSION, true));
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            error_log("No logged_in flag in session");
            return false;
        }
        
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            error_log("No user_id in session");
            return false;
        }
        
        // Check if session has expired (24 hour default)
        if (isset($_SESSION['login_time'])) {
            $sessionAge = time() - $_SESSION['login_time'];
            if ($sessionAge > (24 * 60 * 60)) { // 24 hours
                error_log("Session expired");
                $this->logout();
                return false;
            }
        }
        
        // Verify session is still valid in database (optional but recommended)
        if (isset($_SESSION['session_token'])) {
            try {
                $query = "SELECT id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$_SESSION['session_token']]);
                if ($stmt->rowCount() == 0) {
                    error_log("Session token not found in database or expired");
                    $this->logout();
                    return false;
                }
            } catch (Exception $e) {
                error_log("Error checking session in database: " . $e->getMessage());
                // Don't logout on database errors, just continue
            }
        }
        
        return true;
    }
    
    // Get current user - FIXED VERSION
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role_name' => $_SESSION['role_name'],
            'permissions' => $_SESSION['permissions'] ?? []
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
    
    // Logout user - FIXED VERSION
    public function logout() {
        if (isset($_SESSION['session_token'])) {
            // Remove session from database
            try {
                $query = "DELETE FROM user_sessions WHERE session_token = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$_SESSION['session_token']]);
            } catch (Exception $e) {
                error_log("Error deleting session from database: " . $e->getMessage());
            }
        }
        
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Clear session
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        // Clear remember me cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Update last login
    private function updateLastLogin($userId) {
        try {
            $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
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
        try {
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
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
    
    // Get all users (admin only)
    public function getAllUsers() {
        if (!$this->hasPermission('user.read')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }
        
        try {
            $query = "SELECT u.id, u.username, u.email, u.full_name, u.is_active, u.last_login, 
                             u.created_at, r.name as role_name 
                      FROM users u 
                      JOIN roles r ON u.role_id = r.id 
                      ORDER BY u.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting users: " . $e->getMessage());
            return [];
        }
    }
    
    // Get all roles
    public function getAllRoles() {
        try {
            $query = "SELECT * FROM roles ORDER BY id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting roles: " . $e->getMessage());
            return [];
        }
    }
    
    // Test method to verify password hashing
    public function testPasswordHash($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $verify = password_verify($password, $hash);
        
        return [
            'password' => $password,
            'hash' => $hash,
            'verify' => $verify
        ];
    }
}
?>