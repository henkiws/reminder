<?php
// Use the clean authentication check
require_once 'auth.php';

require_once '../config/database.php';
require_once '../classes/NotificationManager.php';

// Ensure $currentUser is available
// if (!isset($currentUser) || !$currentUser) {
//     header('Location: login.php');
//     exit;
// }

$database = new Database();
$db = $database->getConnection();
$notificationManager = new NotificationManager($db);

// Get data for the interface based on user permissions and ownership
$templates = $notificationManager->getTemplates($currentUser['id']);
$contacts = $notificationManager->getContacts($currentUser['id']);
$groups = $notificationManager->getGroups($currentUser['id']);
$notifications = $notificationManager->getNotifications(20, 0, $currentUser['id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pemberitahuan WhatsApp Otomatis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed w-full z-20 top-0 left-0">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex items-center">
                <i class="fab fa-whatsapp text-green-500 text-2xl mr-3"></i>
                <span class="self-center text-2xl font-semibold whitespace-nowrap">WA Notifikasi</span>
            </div>
            
            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">
                    Selamat datang, <strong><?php echo htmlspecialchars($currentUser['full_name']); ?></strong>
                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">
                        <?php echo htmlspecialchars($currentUser['role_name']); ?>
                    </span>
                </span>
                
                <button id="userMenuButton" data-dropdown-toggle="userMenu" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300" type="button">
                    <span class="sr-only">Open user menu</span>
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-medium">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                </button>
                
                <!-- User Dropdown Menu -->
                <div id="userMenu" class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow">
                    <div class="px-4 py-3">
                        <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                        <span class="block text-sm font-medium text-gray-500 truncate"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    </div>
                    <ul class="py-2">
                        <?php if (hasPermission('user.read')): ?>
                        <li>
                            <button onclick="showUserManagement()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-users mr-2"></i>Manajemen User
                            </button>
                        </li>
                        <?php endif; ?>
                        <li>
                            <button onclick="showProfile()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profil Saya
                            </button>
                        </li>
                        <li>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <button data-collapse-toggle="navbar-default" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                <span class="sr-only">Open main menu</span>
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
                </svg>
            </button>
        </div>
    </nav>

    <div class="pt-20 p-4">
        <div class="max-w-screen-xl mx-auto">
            <!-- Tab Navigation -->
            <div class="mb-4 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="default-tab" data-tabs-toggle="#default-tab-content" role="tablist">
                    <li class="me-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 rounded-t-lg text-green-600 border-green-600" id="create-tab" data-tabs-target="#create" type="button" role="tab" aria-controls="create" aria-selected="true">
                            <i class="fas fa-plus mr-2"></i>Buat Notifikasi
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="notifications-tab" data-tabs-target="#notifications" type="button" role="tab" aria-controls="notifications" aria-selected="false">
                            <i class="fas fa-bell mr-2"></i>Daftar Notifikasi
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="contacts-tab" data-tabs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="false">
                            <i class="fas fa-users mr-2"></i>Kontak
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="groups-tab" data-tabs-target="#groups" type="button" role="tab" aria-controls="groups" aria-selected="false">
                            <i class="fas fa-users-cog mr-2"></i>Grup
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="templates-tab" data-tabs-target="#templates" type="button" role="tab" aria-controls="templates" aria-selected="false">
                            <i class="fas fa-file-alt mr-2"></i>Template
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div id="default-tab-content">
                <!-- Create Notification Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50" id="create" role="tabpanel" aria-labelledby="create-tab">
                    <?php include 'components/create-notification.php'; ?>
                </div>

                <!-- Notifications List Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Daftar Notifikasi</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Judul</th>
                                        <th scope="col" class="px-6 py-3">Jadwal</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3">Penerima</th>
                                        <th scope="col" class="px-6 py-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php echo date('d/m/Y H:i', strtotime($notification['scheduled_datetime'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $statusClass = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'sent' => 'bg-green-100 text-green-800',
                                                'failed' => 'bg-red-100 text-red-800',
                                                'cancelled' => 'bg-gray-100 text-gray-800'
                                            ];
                                            $statusText = [
                                                'pending' => 'Menunggu',
                                                'sent' => 'Terkirim',
                                                'failed' => 'Gagal',
                                                'cancelled' => 'Dibatalkan'
                                            ];
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusClass[$notification['status']]; ?>">
                                                <?php echo $statusText[$notification['status']]; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <?php if ($notification['contact_count'] > 0): ?>
                                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                                                        <?php echo $notification['contact_count']; ?> Kontak
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($notification['group_count'] > 0): ?>
                                                    <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2 py-1 rounded">
                                                        <?php echo $notification['group_count']; ?> Grup
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <?php if ($notification['status'] == 'pending'): ?>
                                                    <button onclick="sendNotificationNow(<?php echo $notification['id']; ?>)" class="text-green-600 hover:text-green-900">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="viewNotificationDetails(<?php echo $notification['id']; ?>)" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Contacts Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
                    <?php include 'components/contacts.php'; ?>
                </div>

                <!-- Groups Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50" id="groups" role="tabpanel" aria-labelledby="groups-tab">
                    <?php include 'components/groups.php'; ?>
                </div>

                <!-- Templates Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50" id="templates" role="tabpanel" aria-labelledby="templates-tab">
                    <?php include 'components/templates.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="p-4 md:p-5 text-center">
                    <div class="mx-auto mb-4 text-green-500 w-12 h-12">
                        <i class="fas fa-check-circle text-5xl"></i>
                    </div>
                    <h3 class="mb-5 text-lg font-normal text-gray-500" id="successMessage">Berhasil!</h3>
                    <button data-modal-hide="successModal" type="button" class="text-white bg-green-600 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="p-4 md:p-5 text-center">
                    <div class="mx-auto mb-4 text-red-500 w-12 h-12">
                        <i class="fas fa-exclamation-circle text-5xl"></i>
                    </div>
                    <h3 class="mb-5 text-lg font-normal text-gray-500" id="errorMessage">Terjadi kesalahan!</h3>
                    <button data-modal-hide="errorModal" type="button" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>