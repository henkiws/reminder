<?php
// Enhanced Login Page - public/login.php
session_start();

require_once '../config/database.php';
require_once '../classes/AuthManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthManager($db);

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            handleLogin($_POST, $auth);
        } elseif ($_POST['action'] === 'register') {
            handleRegister($_POST, $auth);
        }
    }
}

function handleLogin($data, $auth) {
    global $error;
    
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $remember = isset($data['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
        return;
    }
    
    $result = $auth->login($username, $password, $remember);
    
    if ($result['success']) {
        header('Location: index.php');
        exit();
    } else {
        $error = $result['error'];
    }
}

function handleRegister($data, $auth) {
    global $error, $success;
    
    $requiredFields = ['full_name', 'username', 'email', 'password', 'confirm_password'];
    foreach ($requiredFields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $error = "Field $field harus diisi";
            return;
        }
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        $error = 'Password dan konfirmasi password tidak sama';
        return;
    }
    
    if (strlen($data['password']) < 6) {
        $error = 'Password minimal 6 karakter';
        return;
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
        return;
    }
    
    $userData = [
        'full_name' => trim($data['full_name']),
        'username' => trim($data['username']),
        'email' => trim($data['email']),
        'password' => $data['password'],
        'role_id' => 4 // Default to User role
    ];
    
    $result = $auth->register($userData);
    
    if ($result['success']) {
        $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
    } else {
        $error = $result['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WA Notification System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white bg-opacity-20 rounded-full mb-4">
                <i class="fab fa-whatsapp text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">WA Notifikasi</h1>
            <p class="text-white text-opacity-80">Sistem Pemberitahuan WhatsApp Otomatis</p>
        </div>

        <!-- Login/Register Form Container -->
        <div class="glass-effect rounded-2xl shadow-xl overflow-hidden">
            <!-- Tab Navigation -->
            <div class="flex bg-white bg-opacity-10">
                <button id="login-tab" class="flex-1 py-4 text-center text-white font-medium border-b-2 border-white transition-colors" onclick="switchTab('login')">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
                <button id="register-tab" class="flex-1 py-4 text-center text-white text-opacity-60 font-medium border-b-2 border-transparent hover:text-white transition-colors" onclick="switchTab('register')">
                    <i class="fas fa-user-plus mr-2"></i>Daftar
                </button>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error): ?>
            <div class="p-4 bg-red-500 bg-opacity-20 border-l-4 border-red-500 text-white">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="p-4 bg-green-500 bg-opacity-20 border-l-4 border-green-500 text-white">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="login-form" class="p-8">
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="space-y-6">
                        <div>
                            <label for="username" class="block text-sm font-medium text-white mb-2">
                                <i class="fas fa-user mr-2"></i>Username atau Email
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition-colors"
                                   placeholder="Masukkan username atau email"
                                   required
                                   autocomplete="username">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-white mb-2">
                                <i class="fas fa-lock mr-2"></i>Password
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition-colors pr-12"
                                       placeholder="Masukkan password"
                                       required
                                       autocomplete="current-password">
                                <button type="button" 
                                        onclick="togglePassword('password')"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-white text-opacity-60 hover:text-white transition-colors">
                                    <i id="password-icon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center text-white text-sm">
                                <input type="checkbox" 
                                       name="remember" 
                                       class="mr-2 rounded border-white border-opacity-30 bg-white bg-opacity-20 text-blue-600 focus:ring-blue-500 focus:ring-opacity-50">
                                <span>Ingat saya</span>
                            </label>
                            
                            <button type="button" 
                                    onclick="showForgotPassword()"
                                    class="text-white text-opacity-80 hover:text-white text-sm transition-colors">
                                Lupa password?
                            </button>
                        </div>

                        <button type="submit" 
                                class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-semibold py-3 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50">
                            <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                        </button>
                    </div>
                </form>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="p-8 hidden">
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="space-y-6">
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-white mb-2">
                                <i class="fas fa-id-card mr-2"></i>Nama Lengkap
                            </label>
                            <input type="text" 
                                   id="full_name" 
                                   name="full_name" 
                                   class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition-colors"
                                   placeholder="Masukkan nama lengkap"
                                   required
                                   autocomplete="name">
                        </div>

                        <div>
                            <label for="reg_username" class="block text-sm font-medium text-white mb-2">
                                <i class="fas fa-user mr-2"></i>Username
                            </label>
                            <input type="text" 
                                   id="reg_username" 
                                   name="username" 
                                   class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition-colors"
                                   placeholder="Pilih username"
                                   required
                                   autocomplete="username"
                                   pattern="[a-zA-Z0-9_]+"
                                   title="Username hanya boleh mengandung huruf, angka, dan underscore">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-white mb-2">
                                <i class="fas fa-envelope mr-2"></i>Email
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition-colors"
                                   placeholder="Masukkan email"
                                   required
                                   autocomplete="email">
                        </div>

                        <div>
                            <label for="reg_password" class="block text-sm font-medium text-white mb-2">
                                <i class="fas fa-lock mr-2"></i>Password
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="reg_password" 
                                       name="password" 
                                       class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition-colors pr-12"
                                       placeholder="Buat password (min. 6 karakter)"
                                       required
                                       autocomplete="new-password"
                                       minlength="6">
                                <button type="button" 
                                        onclick="togglePassword('reg_password')"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-white text-opacity-60 hover:text-white transition-colors">
                                    <i id="reg_password-icon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-white mb-2">
                                <i class="fas fa-lock mr-2"></i>Konfirmasi Password
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent transition-colors pr-12"
                                       placeholder="Ulangi password"
                                       required
                                       autocomplete="new-password"
                                       minlength="6">
                                <button type="button" 
                                        onclick="togglePassword('confirm_password')"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-white text-opacity-60 hover:text-white transition-colors">
                                    <i id="confirm_password-icon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <input type="checkbox" 
                                   id="terms" 
                                   required
                                   class="mt-1 mr-3 rounded border-white border-opacity-30 bg-white bg-opacity-20 text-blue-600 focus:ring-blue-500 focus:ring-opacity-50">
                            <label for="terms" class="text-white text-sm">
                                Saya setuju dengan <button type="button" onclick="showTerms()" class="underline hover:no-underline">syarat dan ketentuan</button> yang berlaku
                            </label>
                        </div>

                        <button type="submit" 
                                class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-semibold py-3 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50">
                            <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Demo Accounts Info -->
        <div class="mt-8 text-center">
            <div class="glass-effect rounded-lg p-4">
                <h3 class="text-white font-medium mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Akun Demo
                </h3>
                <div class="text-white text-opacity-80 text-sm space-y-1">
                    <div><strong>Admin:</strong> admin / admin123</div>
                    <div><strong>Manager:</strong> manager / admin123</div>
                    <div><strong>User:</strong> user1 / admin123</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="glass-effect rounded-2xl max-w-md w-full p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-white">
                    <i class="fas fa-key mr-2"></i>Lupa Password
                </h3>
                <button onclick="closeForgotPassword()" class="text-white text-opacity-60 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="forgotPasswordForm">
                <div class="mb-6">
                    <label for="reset_email" class="block text-sm font-medium text-white mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input type="email" 
                           id="reset_email" 
                           name="email" 
                           class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent"
                           placeholder="Masukkan email Anda"
                           required>
                </div>
                
                <div class="text-white text-opacity-80 text-sm mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Link reset password akan dikirim ke email Anda.
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" 
                            onclick="closeForgotPassword()"
                            class="flex-1 bg-white bg-opacity-10 hover:bg-opacity-20 text-white font-medium py-3 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-medium py-3 rounded-lg transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Terms Modal -->
    <div id="termsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="glass-effect rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-white border-opacity-20">
                <h3 class="text-xl font-semibold text-white">
                    <i class="fas fa-file-contract mr-2"></i>Syarat dan Ketentuan
                </h3>
                <button onclick="closeTerms()" class="text-white text-opacity-60 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <div class="text-white text-opacity-90 text-sm space-y-4">
                    <p>Dengan menggunakan sistem ini, Anda menyetujui:</p>
                    
                    <div class="space-y-3">
                        <div>
                            <h4 class="font-semibold text-white">1. Penggunaan yang Bertanggung Jawab</h4>
                            <p>Pengguna bertanggung jawab atas semua aktivitas yang dilakukan menggunakan akun mereka.</p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-white">2. Privasi Data</h4>
                            <p>Kami menghormati privasi Anda dan akan melindungi informasi personal sesuai kebijakan privasi.</p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-white">3. Larangan Spam</h4>
                            <p>Dilarang menggunakan sistem untuk mengirim pesan spam atau konten yang melanggar hukum.</p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-white">4. Keamanan Akun</h4>
                            <p>Pengguna bertanggung jawab menjaga keamanan password dan informasi login.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-6 border-t border-white border-opacity-20">
                <button onclick="closeTerms()" 
                        class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-medium py-3 rounded-lg transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tab) {
            const loginTab = document.getElementById('login-tab');
            const registerTab = document.getElementById('register-tab');
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            
            if (tab === 'login') {
                loginTab.classList.add('border-white');
                loginTab.classList.remove('border-transparent', 'text-opacity-60');
                registerTab.classList.add('border-transparent', 'text-opacity-60');
                registerTab.classList.remove('border-white');
                
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
            } else {
                registerTab.classList.add('border-white');
                registerTab.classList.remove('border-transparent', 'text-opacity-60');
                loginTab.classList.add('border-transparent', 'text-opacity-60');
                loginTab.classList.remove('border-white');
                
                registerForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
            }
        }
        
        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('reg_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Password tidak cocok');
                this.classList.add('border-red-500');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-500');
            }
        });
        
        // Forgot password modal
        function showForgotPassword() {
            document.getElementById('forgotPasswordModal').classList.remove('hidden');
        }
        
        function closeForgotPassword() {
            document.getElementById('forgotPasswordModal').classList.add('hidden');
        }
        
        // Terms modal
        function showTerms() {
            document.getElementById('termsModal').classList.remove('hidden');
        }
        
        function closeTerms() {
            document.getElementById('termsModal').classList.add('hidden');
        }
        
        // Form submissions
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sedang Masuk...';
            submitBtn.disabled = true;
        });
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mendaftar...';
            submitBtn.disabled = true;
        });
        
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simulate forgot password process
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                alert('Link reset password telah dikirim ke email Anda (fitur demo)');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                closeForgotPassword();
            }, 2000);
        });
        
        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'forgotPasswordModal') {
                closeForgotPassword();
            }
            if (e.target.id === 'termsModal') {
                closeTerms();
            }
        });
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Handle enter key in forms
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                const form = e.target.closest('form');
                if (form) {
                    form.querySelector('button[type="submit"]').click();
                }
            }
        });
    </script>
</body>
</html>