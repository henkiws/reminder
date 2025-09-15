<?php
// public/contact-history.php - Contact message history
require_once 'auth_check.php';
require_once '../classes/NotificationManager.php';

$database = new Database();
$db = $database->getConnection();
$notificationManager = new NotificationManager($db);

$contactId = $_GET['id'] ?? null;

if (!$contactId) {
    header('Location: index.php');
    exit;
}

// Get contact details
$contactQuery = "SELECT c.*, u.full_name as owner_name 
                 FROM contacts c 
                 LEFT JOIN users u ON c.user_id = u.id 
                 WHERE c.id = ? AND (c.user_id = ? OR c.user_id IS NULL OR ? IN (SELECT id FROM users WHERE role_id IN (1,2)))";
$contactStmt = $db->prepare($contactQuery);
$contactStmt->execute([$contactId, $currentUser['id'], $currentUser['id']]);
$contact = $contactStmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    header('Location: index.php?error=contact_not_found');
    exit;
}

// Get message history for this contact
$historyQuery = "SELECT ml.*, sn.title as notification_title, sn.scheduled_datetime, sn.status as notification_status,
                        u.full_name as sender_name
                 FROM message_logs ml
                 JOIN scheduled_notifications sn ON ml.notification_id = sn.id
                 LEFT JOIN users u ON sn.user_id = u.id
                 WHERE ml.recipient_type = 'contact' AND ml.recipient_id = ?
                 ORDER BY ml.sent_at DESC";
$historyStmt = $db->prepare($historyQuery);
$historyStmt->execute([$contactId]);
$messages = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "SELECT 
                   COUNT(*) as total_messages,
                   SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_messages,
                   SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_messages,
                   MIN(sent_at) as first_message_date,
                   MAX(sent_at) as last_message_date
               FROM message_logs 
               WHERE recipient_type = 'contact' AND recipient_id = ?";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute([$contactId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Calculate success rate
$successRate = 0;
if ($stats['total_messages'] > 0) {
    $successRate = ($stats['successful_messages'] / $stats['total_messages']) * 100;
}

// Get recent notifications this contact is part of
$recentNotificationsQuery = "SELECT DISTINCT sn.id, sn.title, sn.scheduled_datetime, sn.status
                            FROM scheduled_notifications sn
                            JOIN notification_contacts nc ON sn.id = nc.notification_id
                            WHERE nc.contact_id = ?
                            ORDER BY sn.created_at DESC
                            LIMIT 10";
$recentNotificationsStmt = $db->prepare($recentNotificationsQuery);
$recentNotificationsStmt->execute([$contactId]);
$recentNotifications = $recentNotificationsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Kontak - <?php echo htmlspecialchars($contact['name']); ?></title>
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
                <a href="index.php#contacts" class="flex items-center mr-4 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span class="text-sm">Kembali ke Kontak</span>
                </a>
                <div>
                    <span class="text-xl font-semibold text-gray-900">Riwayat Kontak</span>
                    <div class="text-xs text-gray-500">Histori komunikasi dan statistik</div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <button onclick="sendTestMessage()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-paper-plane mr-2"></i>Kirim Test
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
            <!-- Contact Header -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-6">
                            <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($contact['name']); ?></h1>
                            <div class="flex items-center space-x-4 text-sm text-gray-600 mt-2">
                                <span>
                                    <i class="fas fa-phone mr-1"></i>
                                    <?php echo htmlspecialchars($contact['phone']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar mr-1"></i>
                                    Ditambahkan <?php echo date('d M Y', strtotime($contact['created_at'])); ?>
                                </span>
                                <?php if ($contact['owner_name']): ?>
                                <span>
                                    <i class="fas fa-user mr-1"></i>
                                    Dibuat oleh <?php echo htmlspecialchars($contact['owner_name']); ?>
                                </span>
                                <?php else: ?>
                                <span>
                                    <i class="fas fa-globe mr-1"></i>
                                    Kontak Bersama
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($contact['notes']): ?>
                            <div class="mt-2 text-sm text-gray-700">
                                <i class="fas fa-sticky-note mr-1"></i>
                                <?php echo htmlspecialchars($contact['notes']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button onclick="editContact()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-edit mr-2"></i>Edit Kontak
                        </button>
                        <button onclick="exportContactHistory()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-500 rounded-lg">
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
                        <div class="p-3 bg-purple-500 rounded-lg">
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
                            <h3 class="text-lg font-semibold text-gray-900">Riwayat Pesan</h3>
                            <div class="flex space-x-2">
                                <select id="statusFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2">
                                    <option value="">Semua Status</option>
                                    <option value="success">Berhasil</option>
                                    <option value="failed">Gagal</option>
                                </select>
                                <input type="date" id="dateFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2">
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
                                            
                                            <div class="bg-gray-50 p-3 rounded text-sm font-mono text-gray-700">
                                                <?php echo nl2br(htmlspecialchars(substr($message['message'], 0, 200))); ?>
                                                <?php if (strlen($message['message']) > 200): ?>
                                                    <span class="text-blue-600 cursor-pointer" onclick="showFullMessage('<?php echo htmlspecialchars($message['message'], ENT_QUOTES); ?>')">
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
                                <p class="text-sm">Kontak ini belum pernah menerima pesan notifikasi</p>
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
                                            class="text-blue-600 hover:text-blue-800 text-sm">
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
                            <button onclick="createNotificationForContact()" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-plus mr-2"></i>Buat Notifikasi Baru
                            </button>
                            <button onclick="addToGroup()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-users mr-2"></i>Tambah ke Grup
                            </button>
                            <button onclick="copyContactInfo()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-copy mr-2"></i>Copy Info Kontak
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

                // Filter by status
                if (statusFilter && item.dataset.status !== statusFilter) {
                    showItem = false;
                }

                // Filter by date
                if (dateFilter && item.dataset.date !== dateFilter) {
                    showItem = false;
                }

                item.style.display = showItem ? 'block' : 'none';
            });
        }

        function initializeActivityChart() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            
            // Get last 30 days data
            fetch(`api.php/contact/${<?php echo $contactId; ?>}/activity-chart`)
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Pesan Terkirim',
                            data: data.successful,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
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
                // Create empty chart
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: []
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
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

        function sendTestMessage() {
            const testMessage = `📱 TEST MESSAGE 📱

Halo <?php echo htmlspecialchars($contact['name']); ?>!

Ini adalah pesan test dari sistem notifikasi WhatsApp.

Pesan ini dikirim pada: ${new Date().toLocaleString('id-ID')}

Jika Anda menerima pesan ini, berarti sistem notifikasi berfungsi dengan baik.

Terima kasih! 🙏`;

            showConfirmModal(
                'Kirim Pesan Test',
                `Kirim pesan test ke ${<?php echo json_encode($contact['name']); ?>}?`,
                'Kirim',
                'primary',
                () => {
                    fetch('api.php/send-test', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            contact_id: <?php echo $contactId; ?>,
                            phone: '<?php echo $contact['phone']; ?>',
                            message: testMessage
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess('Pesan test berhasil dikirim');
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

        function editContact() {
            window.parent.postMessage({
                action: 'editContact',
                contactId: <?php echo $contactId; ?>,
                name: <?php echo json_encode($contact['name']); ?>,
                phone: '<?php echo $contact['phone']; ?>',
                notes: <?php echo json_encode($contact['notes']); ?>
            }, '*');
        }

        function exportContactHistory() {
            fetch(`api.php/contact/${<?php echo $contactId; ?>}/export-history`)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `contact_history_${<?php echo $contactId; ?>}.csv`;
                a.click();
                window.URL.revokeObjectURL(url);
                showSuccess('Riwayat kontak berhasil diexport');
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Gagal mengexport riwayat kontak');
            });
        }

        function viewNotificationDetails(notificationId) {
            window.open(`notification-details.php?id=${notificationId}`, '_blank');
        }

        function createNotificationForContact() {
            const url = `index.php?contact=${<?php echo $contactId; ?>}#create`;
            if (window.parent !== window) {
                window.parent.location.href = url;
            } else {
                window.location.href = url;
            }
        }

        function addToGroup() {
            alert('Fitur tambah ke grup akan segera tersedia');
        }

        function copyContactInfo() {
            const contactInfo = `Nama: <?php echo $contact['name']; ?>\nTelepon: <?php echo $contact['phone']; ?><?php echo $contact['notes'] ? '\nCatatan: ' . $contact['notes'] : ''; ?>`;
            
            navigator.clipboard.writeText(contactInfo).then(() => {
                showSuccess('Info kontak berhasil disalin');
            }).catch(() => {
                showError('Gagal menyalin info kontak');
            });
        }
    </script>
</body>
</html>