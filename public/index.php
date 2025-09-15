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
        </div>
    </nav>

    <div class="pt-20 p-4">
        <div class="max-w-screen-xl mx-auto">
            <!-- Enhanced Tab Navigation -->
            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="default-tab" data-tabs-toggle="#default-tab-content" role="tablist">
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 rounded-t-lg text-green-600 border-green-600" id="create-tab" data-tabs-target="#create" type="button" role="tab" aria-controls="create" aria-selected="true">
                            <i class="fas fa-plus mr-2"></i>
                            <span>Buat Notifikasi</span>
                            <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">New</span>
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="notifications-tab" data-tabs-target="#notifications" type="button" role="tab" aria-controls="notifications" aria-selected="false">
                            <i class="fas fa-bell mr-2"></i>
                            <span>Notifikasi</span>
                            <?php if ($stats['pending'] > 0): ?>
                            <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full"><?php echo $stats['pending']; ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="contacts-tab" data-tabs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="false">
                            <i class="fas fa-address-book mr-2"></i>
                            <span>Kontak</span>
                            <span class="ml-2 text-xs text-gray-500">(<?php echo count($contacts); ?>)</span>
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="groups-tab" data-tabs-target="#groups" type="button" role="tab" aria-controls="groups" aria-selected="false">
                            <i class="fas fa-users-cog mr-2"></i>
                            <span>Grup</span>
                            <span class="ml-2 text-xs text-gray-500">(<?php echo count($groups); ?>)</span>
                        </button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-flex items-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="templates-tab" data-tabs-target="#templates" type="button" role="tab" aria-controls="templates" aria-selected="false">
                            <i class="fas fa-file-alt mr-2"></i>
                            <span>Template</span>
                            <span class="ml-2 text-xs text-gray-500">(<?php echo count($templates); ?>)</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div id="default-tab-content">
                <!-- Create Notification Tab -->
                <div class="p-4 rounded-lg bg-gray-50 fade-in" id="create" role="tabpanel" aria-labelledby="create-tab">
                    <?php include 'components/create-notification.php'; ?>
                </div>

                <!-- Enhanced Notifications List Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Daftar Notifikasi</h3>
                            <div class="flex space-x-2">
                                <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                                    <option value="">Semua Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="sent">Terkirim</option>
                                    <option value="failed">Gagal</option>
                                </select>
                                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                                    <i class="fas fa-filter mr-2"></i>Filter
                                </button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Notifikasi</th>
                                        <th scope="col" class="px-6 py-3">Jadwal</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3">Penerima</th>
                                        <th scope="col" class="px-6 py-3">Prioritas</th>
                                        <th scope="col" class="px-6 py-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50 notification-card">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <?php if ($notification['repeat_type'] != 'once'): ?>
                                                    <i class="fas fa-redo-alt mr-1"></i>
                                                    <?php echo ucfirst($notification['repeat_type']); ?>
                                                <?php else: ?>
                                                    <i class="fas fa-clock mr-1"></i>Sekali
                                                <?php endif; ?>
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
                                                    <button onclick="sendNotificationNow(<?php echo $notification['id']; ?>)" class="text-green-600 hover:text-green-900" title="Kirim Sekarang">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="viewNotificationDetails(<?php echo $notification['id']; ?>)" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editNotification(<?php echo $notification['id']; ?>)" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($notifications)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-bell-slash text-4xl text-gray-300 mb-4"></i>
                                                <p class="text-lg font-medium">Belum ada notifikasi</p>
                                                <p class="text-sm">Buat notifikasi pertama Anda dengan mengklik tab "Buat Notifikasi"</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
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
                                        <button onclick="useTemplate('<?php echo htmlspecialchars($template['message_template']); ?>')" class="bg-green-100 text-green-800 hover:bg-green-200 px-3 py-1 rounded text-sm font-medium transition-colors">
                                            <i class="fas fa-copy mr-1"></i>Gunakan
                                        </button>
                                        <?php if ($template['user_id'] == $currentUser['id'] || hasPermission('template.update')): ?>
                                        <button onclick="editTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($template['message_template'], ENT_QUOTES); ?>', <?php echo $template['category_id'] ?: 'null'; ?>)" class="bg-blue-100 text-blue-800 hover:bg-blue-200 px-3 py-1 rounded text-sm font-medium transition-colors">
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

    <script src="assets/js/app.js"></script>
    <script>
        // Template filtering function
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
        
        // Add enhanced notification functions
        function editNotification(id) {
            // Redirect to edit page or open modal
            alert('Edit notification feature coming soon');
        }
        
        // Enhanced tab switching with animation
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('[data-tabs-target]');
            const tabPanels = document.querySelectorAll('[role="tabpanel"]');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const target = this.getAttribute('data-tabs-target');
                    const targetPanel = document.querySelector(target);
                    
                    // Remove active classes from all buttons
                    tabButtons.forEach(btn => {
                        btn.classList.remove('text-green-600', 'border-green-600');
                        btn.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('text-green-600', 'border-green-600');
                    this.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                    
                    // Hide all panels
                    tabPanels.forEach(panel => {
                        panel.classList.add('hidden');
                    });
                    
                    // Show target panel with animation
                    if (targetPanel) {
                        targetPanel.classList.remove('hidden');
                        targetPanel.classList.add('fade-in');
                        setTimeout(() => {
                            targetPanel.classList.remove('fade-in');
                        }, 500);
                    }
                });
            });
        });
    </script>
</body>
</html>