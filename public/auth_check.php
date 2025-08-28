<?php
require_once '../config/database.php';
require_once '../classes/AuthManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthManager($db);

// Redirect if already logged in
// if ($auth->isLoggedIn()) {
//     header('Location: index.php');
//     exit;
// }

$error = '';
$success = '';

// Handle login form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $result = $auth->login($username, $password, $remember);
        
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Handle registration form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'register') {
    $userData = [
        'username' => $_POST['reg_username'] ?? '',
        'email' => $_POST['reg_email'] ?? '',
        'password' => $_POST['reg_password'] ?? '',
        'full_name' => $_POST['reg_full_name'] ?? '',
        'role_id' => 3 // Default to User role
    ];
    
    $confirmPassword = $_POST['reg_confirm_password'] ?? '';
    
    // Validation
    if (empty($userData['username']) || empty($userData['email']) || 
        empty($userData['password']) || empty($userData['full_name'])) {
        $error = 'Semua field harus diisi';
    } elseif ($userData['password'] !== $confirmPassword) {
        $error = 'Password dan konfirmasi password tidak sama';
    } elseif (strlen($userData['password']) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        $result = $auth->register($userData);
        
        if ($result['success']) {
            $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
        } else {
            $error = $result['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pemberitahuan WhatsApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="flex justify-center">
                    <i class="fab fa-whatsapp text-green-500 text-6xl mb-4"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Sistem Pemberitahuan WA</h2>
                <p class="mt-2 text-gray-600">Masuk ke akun Anda atau daftar akun baru</p>
            </div>

            <!-- Login/Register Toggle -->
            <div class="flex justify-center">
                <div class="bg-gray-200 p-1 rounded-lg">
                    <button id="loginTab" class="px-4 py-2 text-sm font-medium rounded-md bg-white text-gray-900 shadow-sm transition-all">
                        Masuk
                    </button>
                    <button id="registerTab" class="px-4 py-2 text-sm font-medium rounded-md text-gray-500 hover:text-gray-900 transition-all">
                        Daftar
                    </button>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-6" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username atau Email</label>
                    <div class="mt-1 relative">
                        <input id="username" name="username" type="text" required 
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Masukkan username atau email">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1 relative">
                        <input id="password" name="password" type="password" required 
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Masukkan password">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Ingat saya
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-green-600 hover:text-green-500">
                            Lupa password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-green-500 group-hover:text-green-400"></i>
                        </span>
                        Masuk
                    </button>
                </div>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="space-y-6 hidden" method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="reg_full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <div class="mt-1">
                            <input id="reg_full_name" name="reg_full_name" type="text" required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" 
                                   placeholder="Masukkan nama lengkap">
                        </div>
                    </div>

                    <div>
                        <label for="reg_username" class="block text-sm font-medium text-gray-700">Username</label>
                        <div class="mt-1">
                            <input id="reg_username" name="reg_username" type="text" required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" 
                                   placeholder="Pilih username unik">
                        </div>
                    </div>

                    <div>
                        <label for="reg_email" class="block text-sm font-medium text-gray-700">Email</label>
                        <div class="mt-1">
                            <input id="reg_email" name="reg_email" type="email" required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" 
                                   placeholder="nama@example.com">
                        </div>
                    </div>

                    <div>
                        <label for="reg_password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1">
                            <input id="reg_password" name="reg_password" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" 
                                   placeholder="Minimal 6 karakter">
                        </div>
                    </div>

                    <div>
                        <label for="reg_confirm_password" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                        <div class="mt-1">
                            <input id="reg_confirm_password" name="reg_confirm_password" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" 
                                   placeholder="Ulangi password">
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-user-plus text-green-500 group-hover:text-green-400"></i>
                        </span>
                        Daftar Akun
                    </button>
                </div>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="text-sm font-medium text-blue-800 mb-2">Demo Credentials:</h4>
                <div class="text-xs text-blue-700 space-y-1">
                    <div><strong>Admin:</strong> admin / admin123</div>
                    <div><strong>Manager:</strong> manager / admin123</div>
                    <div><strong>User:</strong> user1 / admin123</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        loginTab.addEventListener('click', function() {
            // Switch active tab
            loginTab.className = 'px-4 py-2 text-sm font-medium rounded-md bg-white text-gray-900 shadow-sm transition-all';
            registerTab.className = 'px-4 py-2 text-sm font-medium rounded-md text-gray-500 hover:text-gray-900 transition-all';
            
            // Show/hide forms
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
        });

        registerTab.addEventListener('click', function() {
            // Switch active tab
            registerTab.className = 'px-4 py-2 text-sm font-medium rounded-md bg-white text-gray-900 shadow-sm transition-all';
            loginTab.className = 'px-4 py-2 text-sm font-medium rounded-md text-gray-500 hover:text-gray-900 transition-all';
            
            // Show/hide forms
            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        });

        // Password confirmation validation
        const regPassword = document.getElementById('reg_password');
        const regConfirmPassword = document.getElementById('reg_confirm_password');

        regConfirmPassword.addEventListener('input', function() {
            if (regPassword.value !== regConfirmPassword.value) {
                regConfirmPassword.setCustomValidity('Password tidak sama');
            } else {
                regConfirmPassword.setCustomValidity('');
            }
        });
    </script>
</body>
</html>