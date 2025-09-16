<?php
// components/profile.php - User Profile Management
$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    redirect('login.php');
}

// Get user's activity statistics
$userStats = [];
try {
    $statsQuery = "
        SELECT 
            COUNT(DISTINCT sn.id) as notification_count,
            COUNT(DISTINCT c.id) as contact_count,
            COUNT(DISTINCT g.id) as group_count,
            COUNT(DISTINCT mt.id) as template_count,
            COUNT(DISTINCT al.id) as recent_activity_count
        FROM users u
        LEFT JOIN scheduled_notifications sn ON u.id = sn.user_id
        LEFT JOIN contacts c ON u.id = c.user_id
        LEFT JOIN wa_groups g ON u.id = g.user_id
        LEFT JOIN message_templates mt ON u.id = mt.user_id
        LEFT JOIN activity_logs al ON u.id = al.user_id AND al.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        WHERE u.id = ?
        GROUP BY u.id
    ";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute([$currentUser['id']]);
    $userStats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'notification_count' => 0,
        'contact_count' => 0,
        'group_count' => 0,
        'template_count' => 0,
        'recent_activity_count' => 0
    ];
} catch (Exception $e) {
    error_log("Error getting user stats: " . $e->getMessage());
    $userStats = [
        'notification_count' => 0,
        'contact_count' => 0,
        'group_count' => 0,
        'template_count' => 0,
        'recent_activity_count' => 0
    ];
}

// Get recent activity logs
$recentActivities = [];
try {
    $activityQuery = "
        SELECT action, description, created_at 
        FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ";
    $activityStmt = $db->prepare($activityQuery);
    $activityStmt->execute([$currentUser['id']]);
    $recentActivities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error getting recent activities: " . $e->getMessage());
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Profile Header -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-8">
            <div class="flex items-center space-x-6">
                <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-white text-3xl font-bold">
                    <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                </div>
                <div class="text-white">
                    <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($currentUser['full_name']); ?></h1>
                    <p class="text-green-100 text-lg">@<?php echo htmlspecialchars($currentUser['username']); ?></p>
                    <div class="flex items-center mt-2">
                        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-user-tag mr-2"></i>
                            <?php echo htmlspecialchars($currentUser['role_name']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-bell text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Notifikasi</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['notification_count']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-address-book text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Kontak</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['contact_count']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Grup</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['group_count']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <i class="fas fa-file-alt text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Template</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['template_count']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Profile Information -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Informasi Profil</h3>
                    <button onclick="editProfile()" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-edit mr-1"></i>Edit Profil
                    </button>
                </div>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <p class="mt-1 text-sm text-gray-900" id="profile-fullname">
                            <?php echo htmlspecialchars($currentUser['full_name']); ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <p class="mt-1 text-sm text-gray-900">
                            @<?php echo htmlspecialchars($currentUser['username']); ?>
                        </p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <p class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo htmlspecialchars($currentUser['role_name']); ?>
                        </span>
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Permissions</label>
                    <div class="mt-2 flex flex-wrap gap-1">
                        <?php foreach ($currentUser['permissions'] as $permission): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                            <?php echo htmlspecialchars($permission); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Aktivitas Terbaru</h3>
            </div>
            <div class="px-6 py-4">
                <?php if (!empty($recentActivities)): ?>
                <div class="flow-root">
                    <ul class="-mb-8">
                        <?php foreach ($recentActivities as $index => $activity): ?>
                        <li>
                            <div class="relative pb-8">
                                <?php if ($index < count($recentActivities) - 1): ?>
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                <?php endif; ?>
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center ring-8 ring-white">
                                            <i class="fas fa-user text-white text-xs"></i>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-900">
                                                <span class="font-medium"><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></span>
                                            </p>
                                            <?php if ($activity['description']): ?>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right text-xs whitespace-nowrap text-gray-500">
                                            <?php echo timeAgo($activity['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-history text-gray-300 text-4xl mb-4"></i>
                    <p class="text-gray-500">Belum ada aktivitas terbaru</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Keamanan</h3>
        </div>
        <div class="px-6 py-4">
            <div class="space-y-4">
                <div class="flex items-center justify-between py-3">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Ubah Password</h4>
                        <p class="text-sm text-gray-500">Update password Anda untuk keamanan yang lebih baik</p>
                    </div>
                    <button onclick="changePassword()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-key mr-2"></i>Ubah Password
                    </button>
                </div>
                
                <div class="flex items-center justify-between py-3 border-t border-gray-200">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Sesi Aktif</h4>
                        <p class="text-sm text-gray-500">Kelola perangkat yang terhubung ke akun Anda</p>
                    </div>
                    <button onclick="viewActiveSessions()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-devices mr-2"></i>Lihat Sesi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">Edit Profil</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="editProfileModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form id="editProfileForm" class="p-4 md:p-5">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="profile_full_name" class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                        <input type="text" name="full_name" id="profile_full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="profile_username" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                        <input type="text" name="username" id="profile_username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" readonly>
                        <p class="text-xs text-gray-500 mt-1">Username tidak dapat diubah</p>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button data-modal-hide="editProfileModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5">
                        Batal
                    </button>
                    <button type="submit" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="changePasswordModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">Ubah Password</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="changePasswordModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form id="changePasswordForm" class="p-4 md:p-5">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="current_password" class="block mb-2 text-sm font-medium text-gray-900">Password Saat Ini</label>
                        <input type="password" name="current_password" id="current_password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="new_password" class="block mb-2 text-sm font-medium text-gray-900">Password Baru</label>
                        <input type="password" name="new_password" id="new_password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required minlength="6">
                        <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                    </div>
                    <div>
                        <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button data-modal-hide="changePasswordModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5">
                        Batal
                    </button>
                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                        <i class="fas fa-key mr-2"></i>Ubah Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Active Sessions Modal -->
<div id="activeSessionsModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">Sesi Aktif</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="activeSessionsModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <div class="p-4 md:p-5">
                <div id="activeSessionsContent">
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
                        <p class="mt-2 text-gray-500">Memuat sesi aktif...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function untuk format waktu
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit lalu';
    if ($time < 86400) return floor($time/3600) . ' jam lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari lalu';
    if ($time < 31104000) return floor($time/2592000) . ' bulan lalu';
    
    return floor($time/31104000) . ' tahun lalu';
}
?>

<script>
function editProfile() {
    showModal('editProfileModal');
}

function changePassword() {
    showModal('changePasswordModal');
}

function viewActiveSessions() {
    showModal('activeSessionsModal');
    loadActiveSessions();
}

function loadActiveSessions() {
    fetch('api.php/user/sessions')
    .then(response => response.json())
    .then(data => {
        const content = document.getElementById('activeSessionsContent');
        if (data.success && data.sessions) {
            let html = '<div class="space-y-4">';
            
            data.sessions.forEach(session => {
                const isCurrentSession = session.is_current;
                html += `
                    <div class="flex items-center justify-between p-4 border rounded-lg ${isCurrentSession ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'}">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 ${isCurrentSession ? 'bg-green-100' : 'bg-gray-100'} rounded-lg">
                                <i class="fas fa-${getDeviceIcon(session.user_agent)} ${isCurrentSession ? 'text-green-600' : 'text-gray-600'}"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">
                                    ${getDeviceInfo(session.user_agent)}
                                    ${isCurrentSession ? '<span class="text-green-600 text-sm">(Sesi ini)</span>' : ''}
                                </p>
                                <p class="text-sm text-gray-500">IP: ${session.ip_address}</p>
                                <p class="text-xs text-gray-400">Login: ${formatDateTime(session.created_at)}</p>
                            </div>
                        </div>
                        ${!isCurrentSession ? `
                            <button onclick="terminateSession('${session.session_token}')" 
                                    class="text-red-600 hover:text-red-800 font-medium text-sm">
                                <i class="fas fa-times mr-1"></i>Akhiri
                            </button>
                        ` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            content.innerHTML = html;
        } else {
            content.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-gray-300 text-4xl mb-4"></i>
                    <p class="text-gray-500">Gagal memuat sesi aktif</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('activeSessionsContent').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-red-300 text-4xl mb-4"></i>
                <p class="text-red-500">Terjadi kesalahan saat memuat data</p>
            </div>
        `;
    });
}

function getDeviceIcon(userAgent) {
    if (!userAgent) return 'desktop';
    
    const ua = userAgent.toLowerCase();
    if (ua.includes('mobile') || ua.includes('android') || ua.includes('iphone')) {
        return 'mobile-alt';
    } else if (ua.includes('tablet') || ua.includes('ipad')) {
        return 'tablet-alt';
    }
    return 'desktop';
}

function getDeviceInfo(userAgent) {
    if (!userAgent) return 'Unknown Device';
    
    const ua = userAgent.toLowerCase();
    let device = 'Unknown Device';
    let browser = 'Unknown Browser';
    
    // Detect device
    if (ua.includes('android')) device = 'Android Device';
    else if (ua.includes('iphone')) device = 'iPhone';
    else if (ua.includes('ipad')) device = 'iPad';
    else if (ua.includes('windows')) device = 'Windows PC';
    else if (ua.includes('mac')) device = 'Mac';
    else if (ua.includes('linux')) device = 'Linux PC';
    
    // Detect browser
    if (ua.includes('chrome')) browser = 'Chrome';
    else if (ua.includes('firefox')) browser = 'Firefox';
    else if (ua.includes('safari')) browser = 'Safari';
    else if (ua.includes('edge')) browser = 'Edge';
    
    return `${browser} on ${device}`;
}

function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString('id-ID');
}

function terminateSession(sessionToken) {
    showConfirmModal(
        'Akhiri Sesi',
        'Apakah Anda yakin ingin mengakhiri sesi ini?',
        'Akhiri Sesi',
        'danger',
        () => {
            fetch('api.php/user/sessions/terminate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ session_token: sessionToken })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Sesi berhasil diakhiri');
                    loadActiveSessions(); // Reload sessions
                } else {
                    showError('Gagal mengakhiri sesi: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

// Handle Edit Profile Form
document.addEventListener('DOMContentLoaded', function() {
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('api.php/user/profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    hideModal('editProfileModal');
                    showSuccess('Profil berhasil diperbarui');
                    
                    // Update display
                    document.getElementById('profile-fullname').textContent = data.full_name;
                    
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal memperbarui profil: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        });
    }
    
    // Handle Change Password Form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Validate password confirmation
            if (data.new_password !== data.confirm_password) {
                showError('Konfirmasi password tidak cocok');
                return;
            }
            
            fetch('api.php/user/change-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    hideModal('changePasswordModal');
                    showSuccess('Password berhasil diubah');
                    this.reset();
                } else {
                    showError('Gagal mengubah password: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        });
    }
});
</script>