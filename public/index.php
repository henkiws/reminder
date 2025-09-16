<?php
// Enhanced Main Application - public/index.php
require_once 'auth_check.php';
require_once '../config/database.php';
require_once '../classes/NotificationManager.php';

$database = new Database();
$db = $database->getConnection();
$notificationManager = new NotificationManager($db);

// Get data for the interface based on user permissions and ownership
$templates = $notificationManager->getTemplates($currentUser['id']);
$contacts = $notificationManager->getContacts($currentUser['id']);
$groups = $notificationManager->getGroups($currentUser['id']);
$notifications = $notificationManager->getNotifications(20, 0, $currentUser['id']);

// Get categories for template management
$query = "SELECT * FROM notification_categories WHERE is_active = 1 ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notification statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM scheduled_notifications 
    WHERE user_id = ? OR ? IN (SELECT id FROM users WHERE role_id IN (1,2))
";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute([$currentUser['id'], $currentUser['id']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
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
    <style>
        .notification-card {
            transition: all 0.3s ease;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .tab-active {
            border-color: rgb(34 197 94) !important;
            color: rgb(34 197 94) !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Enhanced Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed w-full z-20 top-0 left-0 shadow-sm">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex items-center">
                <i class="fab fa-whatsapp text-green-500 text-2xl mr-3"></i>
                <div>
                    <span class="self-center text-2xl font-semibold whitespace-nowrap">WA Notifikasi</span>
                    <div class="text-xs text-gray-500">Sistem Pemberitahuan Otomatis</div>
                </div>
            </div>
            
            <!-- Stats Display -->
            <div class="hidden md:flex items-center space-x-6 text-sm">
                <div class="text-center">
                    <div class="font-bold text-blue-600"><?php echo $stats['total']; ?></div>
                    <div class="text-gray-500">Total</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-yellow-600"><?php echo $stats['pending']; ?></div>
                    <div class="text-gray-500">Pending</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-green-600"><?php echo $stats['sent']; ?></div>
                    <div class="text-gray-500">Terkirim</div>
                </div>
            </div>
            
            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                <div class="hidden md:block text-right">
                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div class="text-xs text-gray-500">
                        <?php echo htmlspecialchars($currentUser['role_name']); ?>
                    </div>
                </div>
                
                <button id="userMenuButton" data-dropdown-toggle="userMenu" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300" type="button">
                    <span class="sr-only">Open user menu</span>
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-medium">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                </button>
                
                <!-- User Dropdown Menu -->
                <div id="userMenu" class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow">
                    <div class="px-4 py-3">
                        <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                        <span class="block text-sm font-medium text-gray-500 truncate">@<?php echo htmlspecialchars($currentUser['username']); ?></span>
                    </div>
                    <ul class="py-2">
                        <?php if (hasPermission('user.read')): ?>
                        <li>
                            <button onclick="showUserManagementTab()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-users mr-2"></i>Manajemen User
                            </button>
                        </li>
                        <?php endif; ?>
                        <li>
                            <button onclick="showProfileTab()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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
        </div>
    </nav>

    <div class="pt-20 p-4">
        <div class="max-w-screen-xl mx-auto">
            <!-- Enhanced Tab Navigation -->
            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="main-tabs" role="tablist">
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 rounded-t-lg tab-button tab-active" 
                                id="create-tab" 
                                onclick="switchTab('create')" 
                                type="button" 
                                role="tab" 
                                aria-controls="create" 
                                aria-selected="true">
                            <i class="fas fa-plus mr-2"></i>
                            <span>Buat Notifikasi</span>
                            <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">New</span>
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" 
                                id="notifications-tab" 
                                onclick="switchTab('notifications')" 
                                type="button" 
                                role="tab" 
                                aria-controls="notifications" 
                                aria-selected="false">
                            <i class="fas fa-bell mr-2"></i>
                            <span>Notifikasi</span>
                            <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full"><?php echo $stats['pending']; ?></span>
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" 
                                id="contacts-tab" 
                                onclick="switchTab('contacts')" 
                                type="button" 
                                role="tab" 
                                aria-controls="contacts" 
                                aria-selected="false">
                            <i class="fas fa-address-book mr-2"></i>
                            <span>Kontak</span>
                            <span class="ml-2 text-xs text-gray-500 px-2 py-1">(<?php echo count($contacts); ?>)</span>
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" 
                                id="groups-tab" 
                                onclick="switchTab('groups')" 
                                type="button" 
                                role="tab" 
                                aria-controls="groups" 
                                aria-selected="false">
                            <i class="fas fa-users-cog mr-2"></i>
                            <span>Grup</span>
                            <span class="ml-2 text-xs text-gray-500 px-2 py-1">(<?php echo count($groups); ?>)</span>
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" 
                                id="templates-tab" 
                                onclick="switchTab('templates')" 
                                type="button" 
                                role="tab" 
                                aria-controls="templates" 
                                aria-selected="false">
                            <i class="fas fa-file-alt mr-2"></i>
                            <span>Template</span>
                            <span class="ml-2 text-xs text-gray-500 px-2 py-1">(<?php echo count($templates); ?>)</span>
                        </button>
                    </li>
                    
                    <!-- Profile Tab (Initially Hidden) -->
                    <li class="me-2 hidden" role="presentation" id="profile-tab-li">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" 
                                id="profile-tab" 
                                onclick="switchTab('profile')" 
                                type="button" 
                                role="tab" 
                                aria-controls="profile" 
                                aria-selected="false">
                            <i class="fas fa-user mr-2"></i>
                            <span>Profil Saya</span>
                        </button>
                    </li>
                    
                    <!-- User Management Tab (Initially Hidden, Admin Only) -->
                    <?php if (hasPermission('user.read')): ?>
                    <li class="me-2 hidden" role="presentation" id="user-management-tab-li">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" 
                                id="user-management-tab" 
                                onclick="switchTab('user-management')" 
                                type="button" 
                                role="tab" 
                                aria-controls="user-management" 
                                aria-selected="false">
                            <i class="fas fa-users mr-2"></i>
                            <span>Manajemen User</span>
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Tab Content -->
            <div id="tab-content">
                <!-- Create Notification Tab -->
                <div class="p-4 rounded-lg bg-gray-50 fade-in tab-panel" id="create" role="tabpanel" aria-labelledby="create-tab">
                    <?php include 'components/create-notification.php'; ?>
                </div>

                <!-- Enhanced Notifications List Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50 tab-panel" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Daftar Notifikasi</h3>
                                <p class="text-sm text-gray-600 mt-1">Kelola dan pantau semua notifikasi yang dijadwalkan</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="bg-blue-50 px-3 py-2 rounded-lg text-sm">
                                    <span class="font-medium text-blue-900"><?php echo count($notifications); ?></span>
                                    <span class="text-blue-700">Total Notifikasi</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Enhanced Search and Filter Section -->
                        <div class="mb-6 flex flex-col lg:flex-row gap-4">
                            <div class="flex-1">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" 
                                           id="notificationSearch" 
                                           placeholder="Cari notifikasi berdasarkan judul atau pesan..." 
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full pl-10 pr-10 p-2.5 transition-colors">
                                    <!-- Clear search button -->
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <button type="button" 
                                                id="clearNotificationSearchBtn"
                                                onclick="clearNotificationSearch()" 
                                                class="hidden mr-3 text-gray-400 hover:text-gray-600 transition-colors"
                                                title="Hapus pencarian">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Search results count -->
                                <div id="notificationSearchResultsCount" class="mt-1 text-xs text-gray-500 hidden"></div>
                            </div>
                            <div class="flex gap-2 flex-wrap">
                                <select id="notificationStatusFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                                    <option value="all">Semua Status</option>
                                    <option value="pending">Menunggu</option>
                                    <option value="sent">Terkirim</option>
                                    <option value="failed">Gagal</option>
                                    <option value="cancelled">Dibatalkan</option>
                                    <option value="completed">Selesai</option>
                                </select>
                                <select id="notificationPriorityFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                                    <option value="all">Semua Prioritas</option>
                                    <option value="low">Rendah</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">Tinggi</option>
                                    <option value="urgent">Mendesak</option>
                                </select>
                                <select id="notificationTypeFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                                    <option value="all">Semua Jenis</option>
                                    <option value="once">Sekali</option>
                                    <option value="daily">Harian</option>
                                    <option value="weekly">Mingguan</option>
                                    <option value="monthly">Bulanan</option>
                                    <option value="yearly">Tahunan</option>
                                </select>
                                <button type="button" onclick="resetNotificationFilters()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition-colors">
                                    <i class="fas fa-undo mr-2"></i>Reset
                                </button>
                            </div>
                        </div>

                        <!-- Filter Summary -->
                        <div id="filterSummary" class="hidden mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-800">
                                    <i class="fas fa-filter mr-2"></i>
                                    Filter aktif: <span id="activeFiltersText"></span>
                                </span>
                                <button onclick="resetNotificationFilters()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Hapus semua filter
                                </button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortNotificationTable('title')">
                                            <div class="flex items-center">
                                                Notifikasi
                                                <i class="fas fa-sort ml-1"></i>
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortNotificationTable('scheduled_datetime')">
                                            <div class="flex items-center">
                                                Jadwal
                                                <i class="fas fa-sort ml-1"></i>
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3">Penerima</th>
                                        <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortNotificationTable('priority')">
                                            <div class="flex items-center">
                                                Prioritas
                                                <i class="fas fa-sort ml-1"></i>
                                            </div>
                                        </th>
                                        <th scope="col" class="px-6 py-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="notificationsTableBody">
                                    <?php foreach ($notifications as $notification): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50 notification-card notification-row" 
                                        data-notification-id="<?php echo $notification['id']; ?>"
                                        data-status="<?php echo $notification['status']; ?>"
                                        data-priority="<?php echo $notification['priority'] ?? 'normal'; ?>"
                                        data-repeat-type="<?php echo $notification['repeat_type'] ?? 'once'; ?>">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900" data-searchable="title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                            <div class="text-xs text-gray-500 mt-1" data-searchable="message">
                                                <?php if ($notification['repeat_type'] != 'once'): ?>
                                                    <i class="fas fa-redo-alt mr-1"></i>
                                                    <?php echo ucfirst($notification['repeat_type']); ?>
                                                <?php else: ?>
                                                    <i class="fas fa-clock mr-1"></i>Sekali
                                                <?php endif; ?>
                                                <!-- Show message preview for search -->
                                                <span class="hidden"><?php echo htmlspecialchars(substr($notification['message_content'] ?? '', 0, 100)); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm"><?php echo date('d/m/Y', strtotime($notification['scheduled_datetime'])); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($notification['scheduled_datetime'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $statusConfig = [
                                                'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'Menunggu', 'icon' => 'fas fa-clock'],
                                                'sent' => ['class' => 'bg-green-100 text-green-800', 'text' => 'Terkirim', 'icon' => 'fas fa-check'],
                                                'failed' => ['class' => 'bg-red-100 text-red-800', 'text' => 'Gagal', 'icon' => 'fas fa-times'],
                                                'cancelled' => ['class' => 'bg-gray-100 text-gray-800', 'text' => 'Dibatalkan', 'icon' => 'fas fa-ban'],
                                                'completed' => ['class' => 'bg-blue-100 text-blue-800', 'text' => 'Selesai', 'icon' => 'fas fa-check-double']
                                            ];
                                            $config = $statusConfig[$notification['status']] ?? $statusConfig['pending'];
                                            ?>
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full <?php echo $config['class']; ?>">
                                                <i class="<?php echo $config['icon']; ?> mr-1"></i>
                                                <?php echo $config['text']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <?php if ($notification['contact_count'] > 0): ?>
                                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                                                        <i class="fas fa-user mr-1"></i><?php echo $notification['contact_count']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($notification['group_count'] > 0): ?>
                                                    <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2 py-1 rounded">
                                                        <i class="fas fa-users mr-1"></i><?php echo $notification['group_count']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $priorityConfig = [
                                                'low' => ['class' => 'bg-gray-100 text-gray-800', 'text' => 'Rendah'],
                                                'normal' => ['class' => 'bg-blue-100 text-blue-800', 'text' => 'Normal'],
                                                'high' => ['class' => 'bg-orange-100 text-orange-800', 'text' => 'Tinggi'],
                                                'urgent' => ['class' => 'bg-red-100 text-red-800', 'text' => 'Mendesak']
                                            ];
                                            $priority = $notification['priority'] ?? 'normal';
                                            $priorityStyle = $priorityConfig[$priority];
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded <?php echo $priorityStyle['class']; ?>">
                                                <?php echo $priorityStyle['text']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <?php if ($notification['status'] == 'pending'): ?>
                                                    <button onclick="sendNotificationNow(<?php echo $notification['id']; ?>)" class="text-green-600 hover:text-green-900 transition-colors" title="Kirim Sekarang">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="viewNotificationDetails(<?php echo $notification['id']; ?>)" class="text-blue-600 hover:text-blue-900 transition-colors" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (in_array($notification['status'], ['pending', 'failed'])): ?>
                                                    <button onclick="deleteNotification(<?php echo $notification['id']; ?>)" class="text-red-600 hover:text-red-900 transition-colors" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (empty($notifications)): ?>
                            <div class="text-center py-12">
                                <div class="max-w-sm mx-auto">
                                    <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada notifikasi</h3>
                                    <p class="text-gray-500 mb-6">Buat notifikasi pertama Anda dengan mengklik tab "Buat Notifikasi"</p>
                                    <button onclick="switchTab('create')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                        <i class="fas fa-plus mr-2"></i>Buat Notifikasi Pertama
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contacts Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50 tab-panel" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
                    <?php include 'components/contacts.php'; ?>
                </div>

                <!-- Groups Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50 tab-panel" id="groups" role="tabpanel" aria-labelledby="groups-tab">
                    <?php include 'components/groups.php'; ?>
                </div>

                <!-- Templates Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50 tab-panel" id="templates" role="tabpanel" aria-labelledby="templates-tab">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Manajemen Template Pesan</h3>
                            <button data-modal-target="addTemplateModal" data-modal-toggle="addTemplateModal" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                <i class="fas fa-plus mr-2"></i>Tambah Template
                            </button>
                        </div>
                        
                        <!-- Template Categories Filter -->
                        <div class="mb-6">
                            <div class="flex flex-wrap gap-2">
                                <button class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 hover:bg-blue-200" onclick="filterTemplates('all')">
                                    Semua (<?php echo count($templates); ?>)
                                </button>
                                <?php 
                                $categoryCount = [];
                                foreach ($templates as $template) {
                                    $cat = $template['category_name'] ?: 'Uncategorized';
                                    $categoryCount[$cat] = ($categoryCount[$cat] ?? 0) + 1;
                                }
                                
                                foreach ($categoryCount as $catName => $count): ?>
                                <button class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 hover:bg-gray-200" onclick="filterTemplates('<?php echo htmlspecialchars($catName); ?>')">
                                    <?php echo htmlspecialchars($catName); ?> (<?php echo $count; ?>)
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="grid gap-6" id="templates-grid">
                            <?php foreach ($templates as $template): ?>
                            <div class="border border-gray-200 rounded-lg p-4 template-card notification-card" data-category="<?php echo htmlspecialchars($template['category_name'] ?: 'Uncategorized'); ?>">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <h4 class="text-lg font-medium text-gray-900 mr-3"><?php echo htmlspecialchars($template['title']); ?></h4>
                                            <?php if ($template['category_name']): ?>
                                                <span class="inline-block bg-gray-100 text-gray-800 text-xs font-medium px-2 py-1 rounded">
                                                    <i class="fas fa-tag mr-1"></i>
                                                    <?php echo htmlspecialchars($template['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center text-xs text-gray-500 space-x-4">
                                            <span>
                                                <?php if ($template['user_id']): ?>
                                                    <i class="fas fa-user mr-1"></i>Template Pribadi
                                                <?php else: ?>
                                                    <i class="fas fa-globe mr-1"></i>Template Sistem
                                                <?php endif; ?>
                                            </span>
                                            <?php if (isset($template['usage_count'])): ?>
                                            <span>
                                                <i class="fas fa-chart-line mr-1"></i>Digunakan <?php echo $template['usage_count']; ?>x
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($template['user_id'] == $currentUser['id'] || hasPermission('template.update')): ?>
                                        <button class="bg-blue-100 text-blue-800 hover:bg-blue-200 px-3 py-1 rounded text-sm font-medium transition-colors edit-template-btn"
                                                data-template-id="<?php echo $template['id']; ?>"
                                                data-template-title="<?php echo htmlspecialchars($template['title']); ?>"
                                                data-template-message="<?php echo htmlspecialchars($template['message_template']); ?>"
                                                data-template-category="<?php echo $template['category_id'] ?: ''; ?>">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($template['user_id'] == $currentUser['id'] || hasPermission('template.delete')): ?>
                                        <button onclick="deleteTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['title'], ENT_QUOTES); ?>')" class="bg-red-100 text-red-800 hover:bg-red-200 px-3 py-1 rounded text-sm font-medium transition-colors">
                                            <i class="fas fa-trash mr-1"></i>Hapus
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 p-3 rounded text-sm text-gray-700 border-l-4 border-green-500">
                                    <?php 
                                    $preview = nl2br(htmlspecialchars($template['message_template']));
                                    // Highlight template variables
                                    $preview = preg_replace('/\{(\w+)\}/', '<span class="bg-yellow-200 text-yellow-800 px-1 rounded">{$1}</span>', $preview);
                                    echo $preview; 
                                    ?>
                                </div>
                                
                                <!-- Show template variables -->
                                <?php 
                                $variables = [];
                                if (preg_match_all('/\{(\w+)\}/', $template['message_template'], $matches)) {
                                    $variables = array_unique($matches[1]);
                                }
                                if (!empty($variables)): ?>
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <div class="text-xs text-gray-500 mb-2">Template Variables:</div>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($variables as $var): ?>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{<?php echo $var; ?>}</span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($templates)): ?>
                            <div class="text-center py-12 text-gray-500">
                                <i class="fas fa-file-alt text-6xl mb-4 text-gray-300"></i>
                                <h3 class="text-lg font-medium mb-2">Belum ada template</h3>
                                <p class="text-sm mb-4">Buat template pesan untuk mempercepat proses pembuatan notifikasi</p>
                                <button data-modal-target="addTemplateModal" data-modal-toggle="addTemplateModal" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                                    <i class="fas fa-plus mr-2"></i>Buat Template Pertama
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Template Management Modals -->
                    <?php include 'components/template-modals.php'; ?>
                </div>
                
                <!-- Profile Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50 tab-panel" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <?php include 'components/profile.php'; ?>
                </div>
                
                <!-- User Management Tab -->
                <?php if (hasPermission('user.read')): ?>
                <div class="hidden p-4 rounded-lg bg-gray-50 tab-panel" id="user-management" role="tabpanel" aria-labelledby="user-management-tab">
                    <?php include 'components/user-management.php'; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Global Modals -->
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

    <!-- Confirm Modal -->
    <div id="confirmModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="p-4 md:p-5 text-center">
                    <div class="mx-auto mb-4 text-gray-400 w-12 h-12">
                        <i class="fas fa-question-circle text-5xl" id="confirmIcon"></i>
                    </div>
                    <h3 class="mb-5 text-lg font-normal text-gray-500" id="confirmTitle">Konfirmasi</h3>
                    <p class="mb-5 text-sm text-gray-500" id="confirmMessage">Apakah Anda yakin?</p>
                    <div class="flex justify-center space-x-4">
                        <button data-modal-hide="confirmModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                            Batal
                        </button>
                        <button id="confirmButton" type="button" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                            <span id="confirmButtonText">Konfirmasi</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Enhanced Application JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            initializeApplication();
        });

        function initializeApplication() {
            initializeNotificationManagement();
            initializeTemplateManagement();
            initializeUserManagement();
        }

        // ========== TAB MANAGEMENT ==========
        function switchTab(tabName) {
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                btn.setAttribute('aria-selected', 'false');
            });
            
            // Add active class to clicked button
            const activeButton = document.getElementById(tabName + '-tab');
            if (activeButton) {
                activeButton.classList.add('tab-active');
                activeButton.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                activeButton.setAttribute('aria-selected', 'true');
            }
            
            // Hide all panels
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            
            // Show target panel with animation
            const targetPanel = document.getElementById(tabName);
            if (targetPanel) {
                targetPanel.classList.remove('hidden');
                targetPanel.classList.add('fade-in');
                setTimeout(() => {
                    targetPanel.classList.remove('fade-in');
                }, 500);
            }
        }

        function showProfileTab() {
            // Show profile tab
            const profileTabLi = document.getElementById('profile-tab-li');
            if (profileTabLi) {
                profileTabLi.classList.remove('hidden');
                switchTab('profile');
            }
        }

        function showUserManagementTab() {
            // Show user management tab
            const userManagementTabLi = document.getElementById('user-management-tab-li');
            if (userManagementTabLi) {
                userManagementTabLi.classList.remove('hidden');
                switchTab('user-management');
            }
        }

        // ========== NOTIFICATION MANAGEMENT ==========
        function initializeNotificationManagement() {
            initializeNotificationSearch();
            initializeNotificationFilters();
        }

        function initializeNotificationSearch() {
            const searchInput = document.getElementById('notificationSearch');
            const clearBtn = document.getElementById('clearNotificationSearchBtn');
            
            if (searchInput) {
                let searchTimeout;
                
                // Real-time search with debounce
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const searchTerm = this.value.trim();
                    
                    // Show/hide clear button
                    if (clearBtn) {
                        if (searchTerm) {
                            clearBtn.classList.remove('hidden');
                        } else {
                            clearBtn.classList.add('hidden');
                        }
                    }
                    
                    // Debounced search
                    searchTimeout = setTimeout(() => {
                        filterNotifications();
                        updateNotificationSearchResults();
                        updateFilterSummary();
                    }, 300);
                });
                
                // Clear search on Escape
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        clearNotificationSearch();
                    }
                });
            }
        }

        function initializeNotificationFilters() {
            const statusFilter = document.getElementById('notificationStatusFilter');
            const priorityFilter = document.getElementById('notificationPriorityFilter');
            const typeFilter = document.getElementById('notificationTypeFilter');
            
            [statusFilter, priorityFilter, typeFilter].forEach(filter => {
                if (filter) {
                    filter.addEventListener('change', function() {
                        filterNotifications();
                        updateNotificationSearchResults();
                        updateFilterSummary();
                    });
                }
            });
        }

        function filterNotifications() {
            const searchInput = document.getElementById('notificationSearch');
            const statusFilter = document.getElementById('notificationStatusFilter');
            const priorityFilter = document.getElementById('notificationPriorityFilter');
            const typeFilter = document.getElementById('notificationTypeFilter');
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const statusValue = statusFilter ? statusFilter.value : 'all';
            const priorityValue = priorityFilter ? priorityFilter.value : 'all';
            const typeValue = typeFilter ? typeFilter.value : 'all';
            
            const rows = document.querySelectorAll('.notification-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const titleElement = row.querySelector('[data-searchable="title"]');
                const messageElement = row.querySelector('[data-searchable="message"]');
                const status = row.dataset.status;
                const priority = row.dataset.priority;
                const repeatType = row.dataset.repeatType;
                
                if (!titleElement || !messageElement) return;
                
                const title = titleElement.textContent.toLowerCase();
                const message = messageElement.textContent.toLowerCase();
                
                let showRow = true;
                
                // Apply search filter
                if (searchTerm && !title.includes(searchTerm) && !message.includes(searchTerm)) {
                    showRow = false;
                }
                
                // Apply status filter
                if (statusValue !== 'all' && status !== statusValue) {
                    showRow = false;
                }
                
                // Apply priority filter
                if (priorityValue !== 'all' && priority !== priorityValue) {
                    showRow = false;
                }
                
                // Apply type filter
                if (typeValue !== 'all' && repeatType !== typeValue) {
                    showRow = false;
                }
                
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNotificationEmptyState(visibleCount, searchTerm, statusValue, priorityValue, typeValue);
        }

        function updateNotificationSearchResults() {
            const resultsCount = document.getElementById('notificationSearchResultsCount');
            const visibleRows = document.querySelectorAll('.notification-row:not([style*="display: none"])');
            const totalRows = document.querySelectorAll('.notification-row');
            
            if (resultsCount) {
                const count = visibleRows.length;
                const total = totalRows.length;
                
                if (count === total) {
                    resultsCount.classList.add('hidden');
                } else {
                    resultsCount.classList.remove('hidden');
                    resultsCount.textContent = `Menampilkan ${count} dari ${total} notifikasi`;
                }
            }
        }

        function updateNotificationEmptyState(visibleCount, searchTerm, statusValue, priorityValue, typeValue) {
            const tbody = document.getElementById('notificationsTableBody');
            const existingMessage = document.getElementById('noNotificationResults');
            
            // Remove existing no-results message
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Show no results message if no visible rows and filters are active
            const hasActiveFilters = searchTerm || statusValue !== 'all' || priorityValue !== 'all' || typeValue !== 'all';
            
            if (visibleCount === 0 && hasActiveFilters) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noNotificationResults';
                noResultsRow.innerHTML = `
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="max-w-sm mx-auto">
                            <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada hasil</h3>
                            <p class="text-gray-500 mb-4">
                                Tidak ditemukan notifikasi dengan kriteria yang dipilih
                            </p>
                            <div class="space-y-2">
                                <button onclick="resetNotificationFilters()" class="text-blue-600 hover:text-blue-800 font-medium block mx-auto">
                                    <i class="fas fa-undo mr-1"></i>Reset semua filter
                                </button>
                                <button onclick="switchTab('create')" class="text-green-600 hover:text-green-800 font-medium">
                                    <i class="fas fa-plus mr-1"></i>Buat notifikasi baru
                                </button>
                            </div>
                        </div>
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
        }

        function updateFilterSummary() {
            const searchInput = document.getElementById('notificationSearch');
            const statusFilter = document.getElementById('notificationStatusFilter');
            const priorityFilter = document.getElementById('notificationPriorityFilter');
            const typeFilter = document.getElementById('notificationTypeFilter');
            const filterSummary = document.getElementById('filterSummary');
            const activeFiltersText = document.getElementById('activeFiltersText');
            
            const searchTerm = searchInput ? searchInput.value.trim() : '';
            const statusValue = statusFilter ? statusFilter.value : 'all';
            const priorityValue = priorityFilter ? priorityFilter.value : 'all';
            const typeValue = typeFilter ? typeFilter.value : 'all';
            
            const activeFilters = [];
            
            if (searchTerm) {
                activeFilters.push(`Pencarian: "${searchTerm}"`);
            }
            if (statusValue !== 'all') {
                const statusText = statusFilter.options[statusFilter.selectedIndex].text;
                activeFilters.push(`Status: ${statusText}`);
            }
            if (priorityValue !== 'all') {
                const priorityText = priorityFilter.options[priorityFilter.selectedIndex].text;
                activeFilters.push(`Prioritas: ${priorityText}`);
            }
            if (typeValue !== 'all') {
                const typeText = typeFilter.options[typeFilter.selectedIndex].text;
                activeFilters.push(`Jenis: ${typeText}`);
            }
            
            if (activeFilters.length > 0 && filterSummary && activeFiltersText) {
                filterSummary.classList.remove('hidden');
                activeFiltersText.textContent = activeFilters.join(', ');
            } else if (filterSummary) {
                filterSummary.classList.add('hidden');
            }
        }

        function clearNotificationSearch() {
            const searchInput = document.getElementById('notificationSearch');
            const clearBtn = document.getElementById('clearNotificationSearchBtn');
            const resultsCount = document.getElementById('notificationSearchResultsCount');
            
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            
            if (clearBtn) {
                clearBtn.classList.add('hidden');
            }
            
            if (resultsCount) {
                resultsCount.classList.add('hidden');
            }
            
            filterNotifications();
            updateFilterSummary();
        }

        function resetNotificationFilters() {
            const searchInput = document.getElementById('notificationSearch');
            const statusFilter = document.getElementById('notificationStatusFilter');
            const priorityFilter = document.getElementById('notificationPriorityFilter');
            const typeFilter = document.getElementById('notificationTypeFilter');
            const clearBtn = document.getElementById('clearNotificationSearchBtn');
            const resultsCount = document.getElementById('notificationSearchResultsCount');
            
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = 'all';
            if (priorityFilter) priorityFilter.value = 'all';
            if (typeFilter) typeFilter.value = 'all';
            if (clearBtn) clearBtn.classList.add('hidden');
            if (resultsCount) resultsCount.classList.add('hidden');
            
            filterNotifications();
            updateFilterSummary();
        }

        function sortNotificationTable(column) {
            const table = document.querySelector('#notifications table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr:not(#noNotificationResults)'));
            
            let columnIndex;
            switch (column) {
                case 'title': columnIndex = 0; break;
                case 'scheduled_datetime': columnIndex = 1; break;
                case 'priority': columnIndex = 4; break;
                default: return;
            }
            
            const sortedRows = rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim();
                const bText = b.cells[columnIndex].textContent.trim();
                return aText.localeCompare(bText);
            });
            
            // Clear tbody and append sorted rows
            tbody.innerHTML = '';
            sortedRows.forEach(row => tbody.appendChild(row));
            
            // Re-apply filters
            setTimeout(() => {
                filterNotifications();
                updateFilterSummary();
            }, 100);
        }

        // ========== NOTIFICATION ACTIONS ==========
        function sendNotificationNow(notificationId) {
            showConfirmModal(
                'Kirim Notifikasi Sekarang',
                'Apakah Anda yakin ingin mengirim notifikasi ini sekarang?',
                'Kirim Sekarang',
                'primary',
                () => {
                    fetch('api.php/notification/send-now', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ id: notificationId })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess('Notifikasi berhasil dikirim');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showError('Gagal mengirim notifikasi: ' + result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Terjadi kesalahan sistem');
                    });
                }
            );
        }

        function viewNotificationDetails(notificationId) {
            window.open(`notification-details.php?id=${notificationId}`, '_self');
        }

        function editNotification(notificationId) {
            window.open(`edit-notification.php?id=${notificationId}`, '_self');
        }

        function deleteNotification(notificationId) {
            showConfirmModal(
                'Hapus Notifikasi',
                'Apakah Anda yakin ingin menghapus notifikasi ini?',
                'Hapus',
                'danger',
                () => {
                    fetch('api.php/notification/'+notificationId, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess('Notifikasi berhasil dihapus');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showError('Gagal menghapus notifikasi: ' + result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Terjadi kesalahan sistem');
                    });
                }
            );
        }

        // ========== TEMPLATE MANAGEMENT ==========
        function initializeTemplateManagement() {
            // Edit template buttons
            const editButtons = document.querySelectorAll('.edit-template-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.templateId;
                    const title = this.dataset.templateTitle;
                    const message = this.dataset.templateMessage;
                    const categoryId = this.dataset.templateCategory;
                    
                    editTemplate(id, title, message, categoryId);
                });
            });
        }

        function editTemplate(id, title, messageTemplate, categoryId) {
            // This would open the edit template modal
            // For now, just show an alert
            alert(`Edit template: ${title} (ID: ${id})`);
        }

        function deleteTemplate(id, title) {
            showConfirmModal(
                'Hapus Template',
                `Apakah Anda yakin ingin menghapus template "${title}"?`,
                'Hapus',
                'danger',
                () => {
                    fetch('api.php/template', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess('Template berhasil dihapus');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showError('Gagal menghapus template: ' + result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Terjadi kesalahan sistem');
                    });
                }
            );
        }

        function filterTemplates(category) {
            const cards = document.querySelectorAll('.template-card');
            const buttons = document.querySelectorAll('[onclick^="filterTemplates"]');
            
            // Update button states
            buttons.forEach(btn => {
                btn.classList.remove('bg-blue-100', 'text-blue-800');
                btn.classList.add('bg-gray-100', 'text-gray-800');
            });
            
            // Highlight active button
            event.target.classList.remove('bg-gray-100', 'text-gray-800');
            event.target.classList.add('bg-blue-100', 'text-blue-800');
            
            // Filter cards
            cards.forEach(card => {
                const cardCategory = card.dataset.category;
                if (category === 'all' || cardCategory === category) {
                    card.style.display = 'block';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // ========== USER MANAGEMENT ==========
        function initializeUserManagement() {
            // This would be implemented based on the user-management.php component
        }

        function editUser(id, fullName, email, username, isActive) {
            // Implement edit user functionality
            console.log('Edit user:', { id, fullName, email, username, isActive });
        }

        function deleteUser(id) {
            showConfirmModal(
                'Hapus User',
                'Apakah Anda yakin ingin menghapus user ini?',
                'Hapus',
                'danger',
                () => {
                    fetch('api.php/user/' + id, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess('User berhasil dihapus');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showError('Gagal menghapus user: ' + result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Terjadi kesalahan sistem');
                    });
                }
            );
        }

        function viewUserActivity(userId) {
            // Implement view user activity
            console.log('View activity for user:', userId);
        }

        // ========== GLOBAL UTILITY FUNCTIONS ==========
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');
            }
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }
        }

        function showSuccess(message) {
            const modal = document.getElementById('successModal');
            const messageEl = document.getElementById('successMessage');
            if (modal && messageEl) {
                messageEl.textContent = message;
                showModal('successModal');
            }
        }

        function showError(message) {
            const modal = document.getElementById('errorModal');
            const messageEl = document.getElementById('errorMessage');
            if (modal && messageEl) {
                messageEl.textContent = message;
                showModal('errorModal');
            }
        }

        function showConfirmModal(title, message, buttonText, type, callback) {
            const modal = document.getElementById('confirmModal');
            const titleEl = document.getElementById('confirmTitle');
            const messageEl = document.getElementById('confirmMessage');
            const buttonEl = document.getElementById('confirmButton');
            const buttonTextEl = document.getElementById('confirmButtonText');
            const iconEl = document.getElementById('confirmIcon');
            
            if (modal && titleEl && messageEl && buttonEl && buttonTextEl && iconEl) {
                titleEl.textContent = title;
                messageEl.textContent = message;
                buttonTextEl.textContent = buttonText;
                
                // Set button color based on type
                buttonEl.className = 'text-white font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center';
                if (type === 'danger') {
                    buttonEl.classList.add('bg-red-600', 'hover:bg-red-700', 'focus:ring-red-300');
                    iconEl.className = 'fas fa-exclamation-triangle text-5xl text-red-500';
                } else {
                    buttonEl.classList.add('bg-blue-600', 'hover:bg-blue-700', 'focus:ring-blue-300');
                    iconEl.className = 'fas fa-question-circle text-5xl text-gray-400';
                }
                
                // Set up callback
                buttonEl.onclick = function() {
                    hideModal('confirmModal');
                    if (callback) callback();
                };
                
                showModal('confirmModal');
            }
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fixed') && e.target.classList.contains('z-50')) {
                const modalId = e.target.id;
                if (modalId && modalId.includes('Modal')) {
                    hideModal(modalId);
                }
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = ['successModal', 'errorModal', 'confirmModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal && !modal.classList.contains('hidden')) {
                        hideModal(modalId);
                    }
                });
            }
        });
    </script>
</body>
</html>