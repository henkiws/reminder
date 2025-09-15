<?php
// public/notification-details.php - Detailed notification view
require_once 'auth_check.php';
require_once '../classes/NotificationManager.php';

$database = new Database();
$db = $database->getConnection();
$notificationManager = new NotificationManager($db);

$notificationId = $_GET['id'] ?? null;

if (!$notificationId) {
    header('Location: index.php');
    exit;
}

// Get notification details
$query = "SELECT sn.*, u.full_name as created_by_name, u.username as created_by_username,
                 mt.title as template_title, mt.message_template,
                 COUNT(DISTINCT nc.contact_id) as contact_count,
                 COUNT(DISTINCT ng.group_id) as group_count
          FROM scheduled_notifications sn 
          LEFT JOIN users u ON sn.user_id = u.id
          LEFT JOIN message_templates mt ON sn.template_id = mt.id
          LEFT JOIN notification_contacts nc ON sn.id = nc.notification_id
          LEFT JOIN notification_groups ng ON sn.id = ng.notification_id
          WHERE sn.id = ? AND (sn.user_id = ? OR ? IN (SELECT id FROM users WHERE role_id IN (1,2)))
          GROUP BY sn.id";

$stmt = $db->prepare($query);
$stmt->execute([$notificationId, $currentUser['id'], $currentUser['id']]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    header('Location: index.php?error=notification_not_found');
    exit;
}

// Get contacts
$contactsQuery = "SELECT c.*, nc.created_at as added_at 
                  FROM contacts c 
                  JOIN notification_contacts nc ON c.id = nc.contact_id 
                  WHERE nc.notification_id = ?";
$contactsStmt = $db->prepare($contactsQuery);
$contactsStmt->execute([$notificationId]);
$contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get groups
$groupsQuery = "SELECT g.*, ng.created_at as added_at 
                FROM wa_groups g 
                JOIN notification_groups ng ON g.id = ng.group_id 
                WHERE ng.notification_id = ?";
$groupsStmt = $db->prepare($groupsQuery);
$groupsStmt->execute([$notificationId]);
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get message logs
$logsQuery = "SELECT ml.*, c.name as contact_name, g.name as group_name 
              FROM message_logs ml
              LEFT JOIN contacts c ON ml.recipient_id = c.id AND ml.recipient_type = 'contact'
              LEFT JOIN wa_groups g ON ml.recipient_id = g.id AND ml.recipient_type = 'group'
              WHERE ml.notification_id = ?
              ORDER BY ml.sent_at DESC";
$logsStmt = $db->prepare($logsQuery);
$logsStmt->execute([$notificationId]);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalSent = 0;
$totalFailed = 0;
$successRate = 0;

foreach ($logs as $log) {
    if ($log['status'] === 'success') {
        $totalSent++;
    } else {
        $totalFailed++;
    }
}

$totalLogs = count($logs);
if ($totalLogs > 0) {
    $successRate = ($totalSent / $totalLogs) * 100;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Notifikasi - <?php echo htmlspecialchars($notification['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed w-full z-20 top-0 left-0 shadow-sm">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex items-center">
                <a href="index.php" class="flex items-center mr-4 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span class="text-sm">Kembali</span>
                </a>
                <div>
                    <span class="text-xl font-semibold text-gray-900">Detail Notifikasi</span>
                    <div class="text-xs text-gray-500">Informasi lengkap dan statistik pengiriman</div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($currentUser['role_name']); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <div class="pt-20 p-4">
        <div class="max-w-screen-xl mx-auto">
            <!-- Notification Header -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <h1 class="text-2xl font-bold text-gray-900 mr-4"><?php echo htmlspecialchars($notification['title']); ?></h1>
                            
                            <!-- Status Badge -->
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
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full <?php echo $config['class']; ?>">
                                <i class="<?php echo $config['icon']; ?> mr-2"></i>
                                <?php echo $config['text']; ?>
                            </span>
                            
                            <!-- Priority Badge -->
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
                            <span class="ml-2 px-3 py-1 text-sm font-medium rounded <?php echo $priorityStyle['class']; ?>">
                                <?php echo $priorityStyle['text']; ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-500 space-x-4">
                            <span>
                                <i class="fas fa-user mr-1"></i>
                                Dibuat oleh: <?php echo htmlspecialchars($notification['created_by_name']); ?>
                            </span>
                            <span>
                                <i class="fas fa-calendar mr-1"></i>
                                <?php echo date('d M Y H:i', strtotime($notification['created_at'])); ?>
                            </span>
                            <span>
                                <i class="fas fa-clock mr-1"></i>
                                Dijadwalkan: <?php echo date('d M Y H:i', strtotime($notification['scheduled_datetime'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 mt-4 lg:mt-0">
                        <?php if ($notification['status'] === 'pending'): ?>
                            <button onclick="sendNotificationNow(<?php echo $notification['id']; ?>)" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-paper-plane mr-2"></i>Kirim Sekarang
                            </button>
                        <?php endif; ?>
                        
                        <button onclick="editNotification(<?php echo $notification['id']; ?>)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-edit mr-2"></i>Edit
                        </button>
                        
                        <button onclick="duplicateNotification(<?php echo $notification['id']; ?>)" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-clone mr-2"></i>Duplikasi
                        </button>
                        
                        <button onclick="exportNotificationData(<?php echo $notification['id']; ?>)" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-500 rounded-lg">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-blue-900">Total Penerima</h3>
                                <p class="text-2xl font-bold text-blue-800"><?php echo $notification['contact_count'] + $notification['group_count']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-500 rounded-lg">
                                <i class="fas fa-check text-white"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-green-900">Berhasil Terkirim</h3>
                                <p class="text-2xl font-bold text-green-800"><?php echo $totalSent; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-500 rounded-lg">
                                <i class="fas fa-times text-white"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-red-900">Gagal Terkirim</h3>
                                <p class="text-2xl font-bold text-red-800"><?php echo $totalFailed; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-500 rounded-lg">
                                <i class="fas fa-percentage text-white"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-purple-900">Success Rate</h3>
                                <p class="text-2xl font-bold text-purple-800"><?php echo number_format($successRate, 1); ?>%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button class="notification-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm active" data-tab="message">
                            <i class="fas fa-envelope mr-2"></i>Pesan
                        </button>
                        <button class="notification-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="recipients">
                            <i class="fas fa-users mr-2"></i>Penerima (<?php echo $notification['contact_count'] + $notification['group_count']; ?>)
                        </button>
                        <button class="notification-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="logs">
                            <i class="fas fa-history mr-2"></i>Log Pengiriman (<?php echo count($logs); ?>)
                        </button>
                        <button class="notification-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="schedule">
                            <i class="fas fa-clock mr-2"></i>Penjadwalan
                        </button>
                    </nav>
                </div>
            </div>
            
            <!-- Tab Contents -->
            <div class="space-y-6">
                <!-- Message Tab -->
                <div id="message-tab" class="tab-content">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Konten Pesan</h3>
                        
                        <?php if ($notification['template_title']): ?>
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                                <span class="text-sm font-medium text-blue-900">Template: <?php echo htmlspecialchars($notification['template_title']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="whitespace-pre-line font-mono text-sm text-gray-800">
                                <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($notification['template_variables']): ?>
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Template Variables</h4>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <?php 
                                $variables = json_decode($notification['template_variables'], true);
                                if ($variables):
                                    foreach ($variables as $key => $value): ?>
                                    <div class="flex items-center py-1">
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">{<?php echo $key; ?>}</span>
                                        <span class="text-sm text-gray-700"><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                <?php endforeach; 
                                endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recipients Tab -->
                <div id="recipients-tab" class="tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Contacts -->
                        <?php if (!empty($contacts)): ?>
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-user mr-2 text-blue-600"></i>
                                Kontak Individual (<?php echo count($contacts); ?>)
                            </h3>
                            <div class="space-y-3">
                                <?php foreach ($contacts as $contact): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium mr-3">
                                            <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($contact['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($contact['phone']); ?></div>
                                        </div>
                                    </div>
                                    <button onclick="viewContactHistory(<?php echo $contact['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-history mr-1"></i>History
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Groups -->
                        <?php if (!empty($groups)): ?>
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-users mr-2 text-purple-600"></i>
                                Grup WhatsApp (<?php echo count($groups); ?>)
                            </h3>
                            <div class="space-y-3">
                                <?php foreach ($groups as $group): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white mr-3">
                                            <i class="fas fa-users text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($group['name']); ?></div>
                                            <div class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($group['group_id']); ?></div>
                                        </div>
                                    </div>
                                    <button onclick="viewGroupHistory(<?php echo $group['id']; ?>)" class="text-purple-600 hover:text-purple-800 text-sm">
                                        <i class="fas fa-history mr-1"></i>History
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Logs Tab -->
                <div id="logs-tab" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Log Pengiriman Pesan</h3>
                            <button onclick="refreshLogs()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh
                            </button>
                        </div>
                        
                        <?php if (!empty($logs)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Penerima</th>
                                        <th scope="col" class="px-6 py-3">Nomor/ID</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3">Waktu Kirim</th>
                                        <th scope="col" class="px-6 py-3">Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <?php if ($log['recipient_type'] === 'contact'): ?>
                                                    <i class="fas fa-user text-blue-500 mr-2"></i>
                                                    <span><?php echo htmlspecialchars($log['contact_name'] ?: 'Unknown Contact'); ?></span>
                                                <?php else: ?>
                                                    <i class="fas fa-users text-purple-500 mr-2"></i>
                                                    <span><?php echo htmlspecialchars($log['group_name'] ?: 'Unknown Group'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-mono text-xs"><?php echo htmlspecialchars($log['phone_number']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($log['status'] === 'success'): ?>
                                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded">
                                                    <i class="fas fa-check mr-1"></i>Berhasil
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded">
                                                    <i class="fas fa-times mr-1"></i>Gagal
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm"><?php echo date('d/m/Y', strtotime($log['sent_at'])); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo date('H:i:s', strtotime($log['sent_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($log['response_data']): ?>
                                                <button onclick="showResponseData('<?php echo htmlspecialchars(json_encode(json_decode($log['response_data'])), ENT_QUOTES); ?>')" 
                                                        class="text-blue-600 hover:text-blue-800 text-xs">
                                                    <i class="fas fa-info-circle mr-1"></i>Lihat Response
                                                </button>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">No response</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-4"></i>
                            <p>Belum ada log pengiriman</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Schedule Tab -->
                <div id="schedule-tab" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Penjadwalan</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Dijadwalkan</label>
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <div class="text-lg font-medium text-gray-900">
                                            <?php echo date('l, d F Y', strtotime($notification['scheduled_datetime'])); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Pukul <?php echo date('H:i', strtotime($notification['scheduled_datetime'])); ?> WIB
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pengulangan</label>
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <?php
                                        $repeatTypes = [
                                            'once' => 'Sekali saja',
                                            'daily' => 'Harian',
                                            'weekly' => 'Mingguan', 
                                            'monthly' => 'Bulanan',
                                            'yearly' => 'Tahunan'
                                        ];
                                        ?>
                                        <span class="text-gray-900 font-medium">
                                            <?php echo $repeatTypes[$notification['repeat_type']] ?? ucfirst($notification['repeat_type']); ?>
                                        </span>
                                        <?php if ($notification['repeat_type'] !== 'once'): ?>
                                            <div class="text-sm text-gray-500 mt-1">
                                                Interval: Setiap <?php echo $notification['repeat_interval']; ?> 
                                                <?php echo $notification['repeat_type'] === 'daily' ? 'hari' : ($notification['repeat_type'] === 'weekly' ? 'minggu' : ($notification['repeat_type'] === 'monthly' ? 'bulan' : 'tahun')); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <?php if ($notification['repeat_until']): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Berakhir Sampai</label>
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <span class="text-gray-900 font-medium">
                                            <?php echo date('d F Y', strtotime($notification['repeat_until'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($notification['repeat_count']): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Maksimal Pengulangan</label>
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <span class="text-gray-900 font-medium"><?php echo $notification['repeat_count']; ?> kali</span>
                                        <div class="text-sm text-gray-500 mt-1">
                                            Sudah dijalankan: <?php echo $notification['current_repeat_count']; ?> kali
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Statistik Pengiriman</label>
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Berhasil terkirim:</span>
                                            <span class="font-medium text-green-600"><?php echo $notification['sent_count']; ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Gagal terkirim:</span>
                                            <span class="font-medium text-red-600"><?php echo $notification['failed_count']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.notification-tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    
                    // Remove active class from all tabs
                    tabs.forEach(t => {
                        t.classList.remove('active', 'border-blue-500', 'text-blue-600');
                        t.classList.add('border-transparent', 'text-gray-500');
                    });
                    
                    // Add active class to clicked tab
                    this.classList.add('active', 'border-blue-500', 'text-blue-600');
                    this.classList.remove('border-transparent', 'text-gray-500');
                    
                    // Hide all content
                    contents.forEach(content => {
                        content.classList.add('hidden');
                    });
                    
                    // Show target content
                    const targetContent = document.getElementById(targetTab + '-tab');
                    if (targetContent) {
                        targetContent.classList.remove('hidden');
                    }
                });
            });
        });

        function showResponseData(responseData) {
            const modal = document.getElementById('responseModal');
            const content = document.getElementById('responseContent');
            
            try {
                const data = JSON.parse(responseData);
                content.textContent = JSON.stringify(data, null, 2);
            } catch (e) {
                content.textContent = responseData;
            }
            
            showModal('responseModal');
        }

        function sendNotificationNow(id) {
            showConfirmModal(
                'Kirim Notifikasi',
                'Apakah Anda yakin ingin mengirim notifikasi ini sekarang?',
                'Kirim',
                'primary',
                () => {
                    fetch('api.php/send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ notification_id: id })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess(`Notifikasi berhasil dikirim ke ${result.sent} penerima`);
                            setTimeout(() => location.reload(), 2000);
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

        function editNotification(id) {
            window.location.href = `index.php?edit=${id}#create`;
        }

        function duplicateNotification(id) {
            showConfirmModal(
                'Duplikasi Notifikasi',
                'Buat salinan notifikasi ini dengan pengaturan yang sama?',
                'Duplikasi',
                'primary',
                () => {
                    fetch(`api.php/notification/${id}/duplicate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess('Notifikasi berhasil diduplikasi');
                            setTimeout(() => window.location.href = `notification-details.php?id=${result.new_id}`, 1500);
                        } else {
                            showError('Gagal menduplikasi notifikasi: ' + result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Terjadi kesalahan sistem');
                    });
                }
            );
        }

        function exportNotificationData(id) {
            fetch(`api.php/notification/${id}/export`)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `notification_${id}_data.json`;
                a.click();
                window.URL.revokeObjectURL(url);
                showSuccess('Data notifikasi berhasil diexport');
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Gagal mengexport data notifikasi');
            });
        }

        function viewContactHistory(contactId) {
            window.open(`contact-history.php?id=${contactId}`, '_blank');
        }

        function viewGroupHistory(groupId) {
            window.open(`group-history.php?id=${groupId}`, '_blank');
        }

        function refreshLogs() {
            location.reload();
        }
    </script>
</body>
</html>