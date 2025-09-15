<?php
// public/group-history.php - Group message history
require_once 'auth_check.php';
require_once '../classes/NotificationManager.php';

$database = new Database();
$db = $database->getConnection();
$notificationManager = new NotificationManager($db);

$groupId = $_GET['id'] ?? null;

if (!$groupId) {
    header('Location: index.php');
    exit;
}

// Get group details
$groupQuery = "SELECT g.*, u.full_name as owner_name 
               FROM wa_groups g 
               LEFT JOIN users u ON g.user_id = u.id 
               WHERE g.id = ? AND (g.user_id = ? OR g.user_id IS NULL OR ? IN (SELECT id FROM users WHERE role_id IN (1,2)))";
$groupStmt = $db->prepare($groupQuery);
$groupStmt->execute([$groupId, $currentUser['id'], $currentUser['id']]);
$group = $groupStmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    header('Location: index.php?error=group_not_found');
    exit;
}

// Get message history for this group
$historyQuery = "SELECT ml.*, sn.title as notification_title, sn.scheduled_datetime, sn.status as notification_status,
                        u.full_name as sender_name
                 FROM message_logs ml
                 JOIN scheduled_notifications sn ON ml.notification_id = sn.id
                 LEFT JOIN users u ON sn.user_id = u.id
                 WHERE ml.recipient_type = 'group' AND ml.recipient_id = ?
                 ORDER BY ml.sent_at DESC";
$historyStmt = $db->prepare($historyQuery);
$historyStmt->execute([$groupId]);
$messages = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "SELECT 
                   COUNT(*) as total_messages,
                   SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_messages,
                   SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_messages,
                   MIN(sent_at) as first_message_date,
                   MAX(sent_at) as last_message_date
               FROM message_logs 
               WHERE recipient_type = 'group' AND recipient_id = ?";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute([$groupId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Calculate success rate
$successRate = 0;
if ($stats['total_messages'] > 0) {
    $successRate = ($stats['successful_messages'] / $stats['total_messages']) * 100;
}

// Get recent notifications this group is part of
$recentNotificationsQuery = "SELECT DISTINCT sn.id, sn.title, sn.scheduled_datetime, sn.status
                            FROM scheduled_notifications sn
                            JOIN notification_groups ng ON sn.id = ng.notification_id
                            WHERE ng.group_id = ?
                            ORDER BY sn.created_at DESC
                            LIMIT 10";
$recentNotificationsStmt = $db->prepare($recentNotificationsQuery);
$recentNotificationsStmt->execute([$groupId]);
$recentNotifications = $recentNotificationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get group info from WhatsApp API (if available)
$groupInfo = null;
try {
    // This would call Fonnte API to get group details
    // $groupInfo = $whatsapp->getGroupInfo($group['group_id']);
} catch (Exception $e) {
    // API call failed, continue without group info
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Grup - <?php echo htmlspecialchars($group['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed w-full z-20 top-0 left-0 shadow-sm">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex items-center">
                <a href="index.php#groups" class="flex items-center mr-4 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span class="text-sm">Kembali ke Grup</span>
                </a>
                <div>
                    <span class="text-xl font-semibold text-gray-900">Riwayat Grup</span>
                    <div class="text-xs text-gray-500">Histori komunikasi dan analitik grup</div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <button onclick="sendTestGroupMessage()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-paper-plane mr-2"></i>Kirim Test
                </button>
                
                <button onclick="getGroupInfo()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-info-circle mr-2"></i>Info Grup
                </button>
                
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($currentUser['role_name']); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <div class="pt-20 p-4">
        <div class="max-w-screen-xl mx-auto">
            <!-- Group Header -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center text-white text-2xl mr-6">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($group['name']); ?></h1>
                            <div class="flex items-center space-x-4 text-sm text-gray-600 mt-2">
                                <span>
                                    <i class="fas fa-hashtag mr-1"></i>
                                    ID: <?php echo htmlspecialchars($group['group_id']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar mr-1"></i>
                                    Ditambahkan <?php echo date('d M Y', strtotime($group['created_at'])); ?>
                                </span>
                                <?php if ($group['owner_name']): ?>
                                <span>
                                    <i class="fas fa-user mr-1"></i>
                                    Dibuat oleh <?php echo htmlspecialchars($group['owner_name']); ?>
                                </span>
                                <?php else: ?>
                                <span>
                                    <i class="fas fa-globe mr-1"></i>
                                    Grup Bersama
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($group['description']): ?>
                            <div class="mt-2 text-sm text-gray-700">
                                <i class="fas fa-align-left mr-1"></i>
                                <?php echo htmlspecialchars($group['description']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button onclick="editGroup()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-edit mr-2"></i>Edit Grup
                        </button>
                        <button onclick="exportGroupHistory()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                        <button onclick="manageGroupMembers()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-users-cog mr-2"></i>Kelola Anggota
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-500 rounded-lg">
                            <i class="fas fa-envelope text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo $stats['total_messages']; ?></h3>
                            <p class="text-sm text-gray-600">Total Pesan</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-500 rounded-lg">
                            <i class="fas fa-check text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo $stats['successful_messages']; ?></h3>
                            <p class="text-sm text-gray-600">Berhasil</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-500 rounded-lg">
                            <i class="fas fa-times text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo $stats['failed_messages']; ?></h3>
                            <p class="text-sm text-gray-600">Gagal</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-500 rounded-lg">
                            <i class="fas fa-percentage text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo number_format($successRate, 1); ?>%</h3>
                            <p class="text-sm text-gray-600">Success Rate</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Message History -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Riwayat Pesan Grup</h3>
                            <div class="flex space-x-2">
                                <select id="statusFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2">
                                    <option value="">Semua Status</option>
                                    <option value="success">Berhasil</option>
                                    <option value="failed">Gagal</option>
                                </select>
                                <input type="date" id="dateFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2">
                                <button onclick="refreshHistory()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-4 max-h-96 overflow-y-auto" id="messageHistory">
                            <?php if (!empty($messages)): ?>
                                <?php foreach ($messages as $message): ?>
                                <div class="message-item border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors" 
                                     data-status="<?php echo $message['status']; ?>" 
                                     data-date="<?php echo date('Y-m-d', strtotime($message['sent_at'])); ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($message['notification_title']); ?></h4>
                                                
                                                <?php if ($message['status'] === 'success'): ?>
                                                    <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded">
                                                        <i class="fas fa-check mr-1"></i>Terkirim
                                                    </span>
                                                <?php else: ?>
                                                    <span class="ml-2 bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded">
                                                        <i class="fas fa-times mr-1"></i>Gagal
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="text-sm text-gray-600 mb-2">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo date('d M Y H:i', strtotime($message['sent_at'])); ?>
                                                
                                                <?php if ($message['sender_name']): ?>
                                                <span class="ml-4">
                                                    <i class="fas fa-user mr-1"></i>
                                                    <?php echo htmlspecialchars($message['sender_name']); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="bg-purple-50 p-3 rounded text-sm font-mono text-gray-700 border-l-4 border-purple-500">
                                                <?php echo nl2br(htmlspecialchars(substr($message['message'], 0, 200))); ?>
                                                <?php if (strlen($message['message']) > 200): ?>
                                                    <span class="text-purple-600 cursor-pointer" onclick="showFullMessage('<?php echo htmlspecialchars($message['message'], ENT_QUOTES); ?>')">
                                                        ... Lihat selengkapnya
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="ml-4 flex space-x-2">
                                            <button onclick="viewNotificationDetails(<?php echo $message['notification_id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                                <i class="fas fa-external-link-alt"></i>
                                            </button>
                                            
                                            <?php if ($message['response_data']): ?>
                                            <button onclick="showResponseData('<?php echo htmlspecialchars(json_encode(json_decode($message['response_data'])), ENT_QUOTES); ?>')" 
                                                    class="text-gray-600 hover:text-gray-800 text-sm">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p class="text-lg font-medium">Belum ada riwayat pesan</p>
                                <p class="text-sm">Grup ini belum pernah menerima pesan notifikasi</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Message Activity Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Aktivitas Pesan (30 Hari)</h3>
                        <canvas id="activityChart" width="400" height="200"></canvas>
                    </div>
                    
                    <!-- Group Statistics -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistik Grup</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Pesan Pertama:</span>
                                <span class="text-sm font-medium text-gray-900">
                                    <?php echo $stats['first_message_date'] ? date('d M Y', strtotime($stats['first_message_date'])) : 'Belum ada'; ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Pesan Terakhir:</span>
                                <span class="text-sm font-medium text-gray-900">
                                    <?php echo $stats['last_message_date'] ? date('d M Y', strtotime($stats['last_message_date'])) : 'Belum ada'; ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Rata-rata per Hari:</span>
                                <span class="text-sm font-medium text-gray-900">
                                    <?php 
                                    if ($stats['first_message_date'] && $stats['last_message_date']) {
                                        $days = max(1, (strtotime($stats['last_message_date']) - strtotime($stats['first_message_date'])) / 86400);
                                        echo number_format($stats['total_messages'] / $days, 1);
                                    } else {
                                        echo '0';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Notifications -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Notifikasi Terkait</h3>
                        
                        <?php if (!empty($recentNotifications)): ?>
                        <div class="space-y-3">
                            <?php foreach ($recentNotifications as $notification): ?>
                            <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-sm text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <?php echo date('d M Y H:i', strtotime($notification['scheduled_datetime'])); ?>
                                        </div>
                                    </div>
                                    <button onclick="viewNotificationDetails(<?php echo $notification['id']; ?>)" 
                                            class="text-purple-600 hover:text-purple-800 text-sm">
                                        <i class="fas fa-external-link-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-sm text-gray-500">Belum ada notifikasi terkait</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
                        <div class="space-y-3">
                            <button onclick="createNotificationForGroup()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-plus mr-2"></i>Buat Notifikasi Baru
                            </button>
                            <button onclick="getGroupMembers()" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-users mr-2"></i>Lihat Anggota Grup
                            </button>
                            <button onclick="copyGroupInfo()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-copy mr-2"></i>Copy Info Grup
                            </button>
                            <button onclick="analyzeGroupActivity()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-chart-line mr-2"></i>Analisa Aktivitas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Message Modal -->
    <div id="fullMessageModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 class="text-lg font-semibold text-gray-900">Pesan Lengkap</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="fullMessageModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <div id="fullMessageContent" class="bg-gray-50 p-4 rounded text-sm font-mono text-gray-800 whitespace-pre-line"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Group Info Modal -->
    <div id="groupInfoModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 class="text-lg font-semibold text-gray-900">Informasi Grup WhatsApp</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="groupInfoModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <div id="groupInfoContent">
                        <div class="text-center py-8">
                            <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                            <p class="mt-2 text-gray-500">Mengambil informasi grup...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Response Data Modal -->
    <div id="responseModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 class="text-lg font-semibold text-gray-900">Response Data API</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="responseModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <pre id="responseContent" class="bg-gray-100 p-4 rounded text-sm text-gray-800 overflow-auto max-h-96"></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilters();
            initializeActivityChart();
        });

        function initializeFilters() {
            const statusFilter = document.getElementById('statusFilter');
            const dateFilter = document.getElementById('dateFilter');

            statusFilter.addEventListener('change', filterMessages);
            dateFilter.addEventListener('change', filterMessages);
        }

        function filterMessages() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const messageItems = document.querySelectorAll('.message-item');

            messageItems.forEach(item => {
                let showItem = true;

                if (statusFilter && item.dataset.status !== statusFilter) {
                    showItem = false;
                }

                if (dateFilter && item.dataset.date !== dateFilter) {
                    showItem = false;
                }

                item.style.display = showItem ? 'block' : 'none';
            });
        }

        function initializeActivityChart() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            
            fetch(`api.php/group/${<?php echo $groupId; ?>}/activity-chart`)
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Pesan Terkirim',
                            data: data.successful,
                            borderColor: 'rgb(147, 51, 234)',
                            backgroundColor: 'rgba(147, 51, 234, 0.1)',
                            tension: 0.1
                        }, {
                            label: 'Pesan Gagal',
                            data: data.failed,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
                new Chart(ctx, {
                    type: 'line',
                    data: { labels: [], datasets: [] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            });
        }

        function showFullMessage(message) {
            document.getElementById('fullMessageContent').textContent = message;
            showModal('fullMessageModal');
        }

        function showResponseData(responseData) {
            try {
                const data = JSON.parse(responseData);
                document.getElementById('responseContent').textContent = JSON.stringify(data, null, 2);
            } catch (e) {
                document.getElementById('responseContent').textContent = responseData;
            }
            showModal('responseModal');
        }

        function sendTestGroupMessage() {
            const testMessage = `📱 TEST MESSAGE - GRUP 📱

Halo anggota grup <?php echo htmlspecialchars($group['name']); ?>!

Ini adalah pesan test dari sistem notifikasi WhatsApp.

Pesan ini dikirim pada: ${new Date().toLocaleString('id-ID')}

Jika grup menerima pesan ini, berarti sistem notifikasi berfungsi dengan baik.

Terima kasih! 🙏`;

            showConfirmModal(
                'Kirim Pesan Test ke Grup',
                `Kirim pesan test ke grup ${<?php echo json_encode($group['name']); ?>}?`,
                'Kirim',
                'primary',
                () => {
                    fetch('api.php/send-test-group', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            group_id: <?php echo $groupId; ?>,
                            group_wa_id: '<?php echo $group['group_id']; ?>',
                            message: testMessage
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess('Pesan test berhasil dikirim ke grup');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showError('Gagal mengirim pesan test: ' + result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Terjadi kesalahan sistem');
                    });
                }
            );
        }

        function getGroupInfo() {
            document.getElementById('groupInfoContent').innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                    <p class="mt-2 text-gray-500">Mengambil informasi grup...</p>
                </div>
            `;
            
            showModal('groupInfoModal');
            
            fetch('api.php/group-info', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    group_id: <?php echo $groupId; ?>,
                    group_wa_id: '<?php echo $group['group_id']; ?>'
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    const info = result.data;
                    document.getElementById('groupInfoContent').innerHTML = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="text-sm font-medium text-gray-900">Nama Grup</div>
                                    <div class="text-gray-700">${info.name || 'Tidak tersedia'}</div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="text-sm font-medium text-gray-900">ID Grup</div>
                                    <div class="text-gray-700 font-mono text-xs"><?php echo $group['group_id']; ?></div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="text-sm font-medium text-gray-900">Jumlah Anggota</div>
                                    <div class="text-gray-700">${info.participants ? info.participants.length : 'Tidak tersedia'} orang</div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="text-sm font-medium text-gray-900">Status</div>
                                    <div class="text-green-600">Aktif</div>
                                </div>
                            </div>
                            
                            ${info.description ? `
                            <div class="bg-blue-50 p-3 rounded">
                                <div class="text-sm font-medium text-blue-900">Deskripsi Grup</div>
                                <div class="text-blue-800 text-sm mt-1">${info.description}</div>
                            </div>
                            ` : ''}
                            
                            ${info.participants && info.participants.length > 0 ? `
                            <div>
                                <div class="text-sm font-medium text-gray-900 mb-2">Anggota Grup (${info.participants.length})</div>
                                <div class="max-h-40 overflow-y-auto bg-gray-50 rounded p-3">
                                    ${info.participants.slice(0, 10).map(participant => `
                                        <div class="flex items-center py-1">
                                            <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs mr-2">
                                                ${participant.name ? participant.name.charAt(0).toUpperCase() : '?'}
                                            </div>
                                            <div class="text-sm">
                                                <div class="font-medium">${participant.name || 'Nama tidak tersedia'}</div>
                                                <div class="text-xs text-gray-500">${participant.id}</div>
                                            </div>
                                        </div>
                                    `).join('')}
                                    ${info.participants.length > 10 ? `
                                        <div class="text-center text-xs text-gray-500 mt-2">
                                            Dan ${info.participants.length - 10} anggota lainnya...
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    document.getElementById('groupInfoContent').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-4xl text-yellow-400"></i>
                            <p class="mt-2 text-gray-700">Gagal mengambil informasi grup</p>
                            <p class="text-sm text-gray-500">${result.error || 'Grup mungkin tidak tersedia'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('groupInfoContent').innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-400"></i>
                        <p class="mt-2 text-gray-700">Terjadi kesalahan sistem</p>
                    </div>
                `;
            });
        }

        function refreshHistory() {
            location.reload();
        }

        function editGroup() {
            window.parent.postMessage({
                action: 'editGroup',
                groupId: <?php echo $groupId; ?>,
                name: <?php echo json_encode($group['name']); ?>,
                groupWaId: '<?php echo $group['group_id']; ?>',
                description: <?php echo json_encode($group['description']); ?>
            }, '*');
        }

        function exportGroupHistory() {
            fetch(`api.php/group/${<?php echo $groupId; ?>}/export-history`)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `group_history_${<?php echo $groupId; ?>}.csv`;
                a.click();
                window.URL.revokeObjectURL(url);
                showSuccess('Riwayat grup berhasil diexport');
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Gagal mengexport riwayat grup');
            });
        }

        function manageGroupMembers() {
            getGroupInfo();
        }

        function viewNotificationDetails(notificationId) {
            window.open(`notification-details.php?id=${notificationId}`, '_blank');
        }

        function createNotificationForGroup() {
            const url = `index.php?group=${<?php echo $groupId; ?>}#create`;
            if (window.parent !== window) {
                window.parent.location.href = url;
            } else {
                window.location.href = url;
            }
        }

        function getGroupMembers() {
            getGroupInfo();
        }

        function copyGroupInfo() {
            const groupInfo = `Nama: <?php echo $group['name']; ?>\nID Grup: <?php echo $group['group_id']; ?><?php echo $group['description'] ? '\nDeskripsi: ' . $group['description'] : ''; ?>`;
            
            navigator.clipboard.writeText(groupInfo).then(() => {
                showSuccess('Info grup berhasil disalin');
            }).catch(() => {
                showError('Gagal menyalin info grup');
            });
        }

        function analyzeGroupActivity() {
            // Open a detailed analytics page or modal
            window.open(`group-analytics.php?id=${<?php echo $groupId; ?>}`, '_blank');
        }
    </script>
</body>
</html>