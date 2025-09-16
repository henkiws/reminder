<?php
// components/user-management.php - Enhanced User Management
// Only accessible by admin users
requirePermission('user.read', 'Anda tidak memiliki akses ke manajemen user');

$users = $auth->getAllUsers();
$roles = $auth->getAllRoles();

// Get user statistics
$userStatsQuery = "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_logins,
        COUNT(DISTINCT role_id) as roles_in_use
    FROM users
";
$userStatsStmt = $db->prepare($userStatsQuery);
$userStatsStmt->execute();
$userStats = $userStatsStmt->fetch(PDO::FETCH_ASSOC);

// Get recent user activities
$recentActivitiesQuery = "
    SELECT 
        al.*, 
        u.full_name, 
        u.username
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
";
$recentActivitiesStmt = $db->prepare($recentActivitiesQuery);
$recentActivitiesStmt->execute();
$recentActivities = $recentActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- User Management Header -->
    <div class="bg-white rounded-lg shadow">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div class="text-white">
                    <h1 class="text-3xl font-bold">Manajemen User</h1>
                    <p class="text-blue-100 text-lg mt-1">Kelola pengguna dan hak akses sistem</p>
                </div>
                <?php if (hasPermission('user.create')): ?>
                <button data-modal-target="addUserModal" data-modal-toggle="addUserModal" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Tambah User Baru
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total User</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['total_users']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">User Aktif</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['active_users']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-sign-in-alt text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Login 30 Hari</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['recent_logins']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <i class="fas fa-user-tag text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Role Aktif</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['roles_in_use']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Users List -->
        <div class="xl:col-span-2 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h3 class="text-lg font-semibold text-gray-900">Daftar User</h3>
                    
                    <!-- Search and Filter -->
                    <div class="flex gap-2 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-none">
                            <input type="text" 
                                   id="userSearch" 
                                   placeholder="Cari user..." 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 pl-3 pr-10 py-2">
                            <button type="button" 
                                    id="clearUserSearchBtn"
                                    onclick="clearUserSearch()" 
                                    class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <select id="roleFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2">
                            <option value="all">Semua Role</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="statusFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2">
                            <option value="all">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>
                
                <!-- Search Results Count -->
                <div id="userSearchResultsCount" class="mt-2 text-xs text-gray-500 hidden"></div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortUserTable('full_name')">
                                <div class="flex items-center">
                                    User
                                    <i class="fas fa-sort ml-1"></i>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3">Role</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortUserTable('last_login')">
                                <div class="flex items-center">
                                    Last Login
                                    <i class="fas fa-sort ml-1"></i>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php foreach ($users as $user): ?>
                        <tr class="bg-white border-b hover:bg-gray-50 user-row" 
                            data-user-id="<?php echo $user['id']; ?>"
                            data-role-id="<?php echo $user['role_id'] ?? ''; ?>"
                            data-status="<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-white font-medium mr-3">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 searchable-name">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 searchable-username">
                                            @<?php echo htmlspecialchars($user['username']); ?>
                                        </div>
                                        <div class="text-xs text-gray-400 searchable-email">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($user['role_name']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($user['is_active']): ?>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                                        <i class="fas fa-check mr-1"></i>Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full">
                                        <i class="fas fa-times mr-1"></i>Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <?php if ($user['last_login']): ?>
                                        <?php echo date('d/m/Y', strtotime($user['last_login'])); ?>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('H:i', strtotime($user['last_login'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Belum pernah login</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <?php if (hasPermission('user.update')): ?>
                                    <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>', <?php echo $user['is_active'] ? 'true' : 'false'; ?>, <?php echo $user['role_id'] ?? 'null'; ?>)" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('user.delete') && $user['id'] != $currentUser['id']): ?>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>')" class="text-red-600 hover:text-red-900 transition-colors" title="Hapus User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="viewUserActivity(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>')" class="text-green-600 hover:text-green-900 transition-colors" title="Lihat Aktivitas">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    
                                    <button onclick="resetUserPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>')" class="text-purple-600 hover:text-purple-900 transition-colors" title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($users)): ?>
                <div class="text-center py-12">
                    <div class="max-w-sm mx-auto">
                        <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada user</h3>
                        <p class="text-gray-500 mb-6">Tambahkan user pertama untuk memulai</p>
                        <?php if (hasPermission('user.create')): ?>
                        <button data-modal-target="addUserModal" data-modal-toggle="addUserModal" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Tambah User Pertama
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Aktivitas Terbaru</h3>
            </div>
            <div class="px-6 py-4">
                <?php if (!empty($recentActivities)): ?>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($recentActivities as $activity): ?>
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <?php
                                $iconClass = 'fas fa-user';
                                switch ($activity['action']) {
                                    case 'login': $iconClass = 'fas fa-sign-in-alt text-green-600'; break;
                                    case 'logout': $iconClass = 'fas fa-sign-out-alt text-red-600'; break;
                                    case 'register': $iconClass = 'fas fa-user-plus text-blue-600'; break;
                                    case 'password_change': $iconClass = 'fas fa-key text-purple-600'; break;
                                    case 'profile_update': $iconClass = 'fas fa-edit text-orange-600'; break;
                                    default: $iconClass = 'fas fa-circle text-gray-600'; break;
                                }
                                ?>
                                <i class="<?php echo $iconClass; ?> text-xs"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">
                                <span class="font-medium">
                                    <?php echo $activity['full_name'] ? htmlspecialchars($activity['full_name']) : 'System'; ?>
                                </span>
                                <span class="text-gray-600">
                                    <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                                </span>
                            </p>
                            <?php if ($activity['description']): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo htmlspecialchars($activity['description']); ?>
                            </p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-1">
                                <?php echo timeAgo($activity['created_at']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
</div>

<!-- Add User Modal -->
<?php if (hasPermission('user.create')): ?>
<div id="addUserModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">Tambah User Baru</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="addUserModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form id="addUserForm" class="p-4 md:p-5">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="user_full_name" class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                        <input type="text" name="full_name" id="user_full_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="user_username" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                        <input type="text" name="username" id="user_username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="user_email" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                        <input type="email" name="email" id="user_email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="user_password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                        <input type="password" name="password" id="user_password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required minlength="6">
                        <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                    </div>
                    <div>
                        <label for="user_role" class="block mb-2 text-sm font-medium text-gray-900">Role</label>
                        <select name="role_id" id="user_role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                            <option value="">Pilih Role</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button data-modal-hide="addUserModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5">
                        Batal
                    </button>
                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                        <i class="fas fa-plus mr-2"></i>Tambah User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit User Modal -->
<?php if (hasPermission('user.update')): ?>
<div id="editUserModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">Edit User</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="editUserModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form id="editUserForm" class="p-4 md:p-5">
                <input type="hidden" id="edit_user_id" name="id">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="edit_user_full_name" class="block mb-2 text-sm font-medium text-gray-900">Nama Lengkap</label>
                        <input type="text" name="full_name" id="edit_user_full_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="edit_user_username" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                        <input type="text" name="username" id="edit_user_username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" readonly>
                        <p class="text-xs text-gray-500 mt-1">Username tidak dapat diubah</p>
                    </div>
                    <div>
                        <label for="edit_user_email" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                        <input type="email" name="email" id="edit_user_email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="edit_user_role" class="block mb-2 text-sm font-medium text-gray-900">Role</label>
                        <select name="role_id" id="edit_user_role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="edit_user_active" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm font-medium text-gray-900">User Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button data-modal-hide="editUserModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5">
                        Batal
                    </button>
                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- User Activity Modal -->
<div id="userActivityModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    <span id="activityModalTitle">Activity Log</span>
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="userActivityModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <div class="p-4 md:p-5">
                <div id="activityLogContent" class="space-y-4 max-h-96 overflow-y-auto">
                    <!-- Activity logs will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// User Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeUserManagement();
});

function initializeUserManagement() {
    initializeUserSearch();
    initializeUserFilters();
    initializeUserForms();
}

// ========== USER SEARCH & FILTER ==========
function initializeUserSearch() {
    const searchInput = document.getElementById('userSearch');
    const clearBtn = document.getElementById('clearUserSearchBtn');
    
    if (searchInput) {
        let searchTimeout;
        
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
                filterUsers();
                updateUserSearchResults();
            }, 300);
        });
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearUserSearch();
            }
        });
    }
}

function initializeUserFilters() {
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    [roleFilter, statusFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', function() {
                filterUsers();
                updateUserSearchResults();
            });
        }
    });
}

function filterUsers() {
    const searchInput = document.getElementById('userSearch');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const roleValue = roleFilter ? roleFilter.value : 'all';
    const statusValue = statusFilter ? statusFilter.value : 'all';
    
    const rows = document.querySelectorAll('.user-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const nameElement = row.querySelector('.searchable-name');
        const usernameElement = row.querySelector('.searchable-username');
        const emailElement = row.querySelector('.searchable-email');
        const roleId = row.dataset.roleId;
        const status = row.dataset.status;
        
        if (!nameElement || !usernameElement || !emailElement) return;
        
        const name = nameElement.textContent.toLowerCase();
        const username = usernameElement.textContent.toLowerCase();
        const email = emailElement.textContent.toLowerCase();
        
        let showRow = true;
        
        // Apply search filter
        if (searchTerm && !name.includes(searchTerm) && !username.includes(searchTerm) && !email.includes(searchTerm)) {
            showRow = false;
        }
        
        // Apply role filter
        if (roleValue !== 'all' && roleId !== roleValue) {
            showRow = false;
        }
        
        // Apply status filter
        if (statusValue !== 'all' && status !== statusValue) {
            showRow = false;
        }
        
        if (showRow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    updateUserEmptyState(visibleCount, searchTerm, roleValue, statusValue);
}

function updateUserSearchResults() {
    const resultsCount = document.getElementById('userSearchResultsCount');
    const visibleRows = document.querySelectorAll('.user-row:not([style*="display: none"])');
    const totalRows = document.querySelectorAll('.user-row');
    
    if (resultsCount) {
        const count = visibleRows.length;
        const total = totalRows.length;
        
        if (count === total) {
            resultsCount.classList.add('hidden');
        } else {
            resultsCount.classList.remove('hidden');
            resultsCount.textContent = `Menampilkan ${count} dari ${total} user`;
        }
    }
}

function updateUserEmptyState(visibleCount, searchTerm, roleValue, statusValue) {
    const tbody = document.getElementById('usersTableBody');
    const existingMessage = document.getElementById('noUserResults');
    
    // Remove existing no-results message
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Show no results message if no visible rows and filters are active
    const hasActiveFilters = searchTerm || roleValue !== 'all' || statusValue !== 'all';
    
    if (visibleCount === 0 && hasActiveFilters) {
        const noResultsRow = document.createElement('tr');
        noResultsRow.id = 'noUserResults';
        noResultsRow.innerHTML = `
            <td colspan="5" class="px-6 py-12 text-center">
                <div class="max-w-sm mx-auto">
                    <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada hasil</h3>
                    <p class="text-gray-500 mb-4">
                        Tidak ditemukan user dengan kriteria yang dipilih
                    </p>
                    <button onclick="resetUserFilters()" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-undo mr-1"></i>Reset semua filter
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(noResultsRow);
    }
}

function clearUserSearch() {
    const searchInput = document.getElementById('userSearch');
    const clearBtn = document.getElementById('clearUserSearchBtn');
    const resultsCount = document.getElementById('userSearchResultsCount');
    
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
    
    filterUsers();
}

function resetUserFilters() {
    const searchInput = document.getElementById('userSearch');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const clearBtn = document.getElementById('clearUserSearchBtn');
    const resultsCount = document.getElementById('userSearchResultsCount');
    
    if (searchInput) searchInput.value = '';
    if (roleFilter) roleFilter.value = 'all';
    if (statusFilter) statusFilter.value = 'all';
    if (clearBtn) clearBtn.classList.add('hidden');
    if (resultsCount) resultsCount.classList.add('hidden');
    
    filterUsers();
}

function sortUserTable(column) {
    const table = document.querySelector('#usersTableBody').parentElement;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr:not(#noUserResults)'));
    
    let columnIndex;
    switch (column) {
        case 'full_name': columnIndex = 0; break;
        case 'last_login': columnIndex = 3; break;
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
        filterUsers();
    }, 100);
}

// ========== USER ACTIONS ==========
function editUser(id, fullName, email, username, isActive, roleId) {
    const modal = document.getElementById('editUserModal');
    const form = document.getElementById('editUserForm');
    
    if (modal && form) {
        // Populate form
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_user_full_name').value = fullName;
        document.getElementById('edit_user_email').value = email;
        document.getElementById('edit_user_username').value = username;
        document.getElementById('edit_user_active').checked = isActive;
        
        // Set role
        const roleSelect = document.getElementById('edit_user_role');
        if (roleSelect && roleId) {
            roleSelect.value = roleId;
        }
        
        showModal('editUserModal');
    }
}

function deleteUser(id, fullName) {
    showConfirmModal(
        'Hapus User',
        `Apakah Anda yakin ingin menghapus user "${fullName}"?`,
        'Hapus',
        'danger',
        () => {
            fetch(`api.php/user/${id}`, {
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

function viewUserActivity(userId, fullName) {
    const modal = document.getElementById('userActivityModal');
    const titleEl = document.getElementById('activityModalTitle');
    const contentEl = document.getElementById('activityLogContent');
    
    if (titleEl) {
        titleEl.textContent = `Activity Log - ${fullName}`;
    }
    
    if (contentEl) {
        contentEl.innerHTML = `
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Memuat aktivitas user...</p>
            </div>
        `;
    }
    
    showModal('userActivityModal');
    
    // Load user activity
    fetch(`api.php/user/${userId}/activity`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.activities) {
            let html = '<div class="space-y-4">';
            
            data.activities.forEach(activity => {
                html += `
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-${getActivityIcon(activity.action)} text-xs"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 font-medium">
                                ${getActivityTitle(activity.action)}
                            </p>
                            ${activity.description ? `<p class="text-xs text-gray-500 mt-1">${activity.description}</p>` : ''}
                            <p class="text-xs text-gray-400 mt-1">
                                ${formatDateTime(activity.created_at)}
                            </p>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            contentEl.innerHTML = html;
        } else {
            contentEl.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-history text-gray-300 text-4xl mb-4"></i>
                    <p class="text-gray-500">Tidak ada aktivitas yang ditemukan</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        contentEl.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-red-300 text-4xl mb-4"></i>
                <p class="text-red-500">Gagal memuat data aktivitas</p>
            </div>
        `;
    });
}

function resetUserPassword(userId, fullName) {
    showConfirmModal(
        'Reset Password',
        `Apakah Anda yakin ingin mereset password untuk "${fullName}"?`,
        'Reset Password',
        'primary',
        () => {
            fetch(`api.php/user/${userId}/reset-password`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(`Password berhasil direset. Password baru: ${result.new_password}`);
                } else {
                    showError('Gagal mereset password: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

// ========== FORM HANDLERS ==========
function initializeUserForms() {
    // Add User Form
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('api.php/user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    hideModal('addUserModal');
                    showSuccess('User berhasil ditambahkan');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal menambahkan user: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        });
    }
    
    // Edit User Form
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Convert checkbox to boolean
            data.is_active = data.is_active === 'on';
            
            const userId = data.id;
            delete data.id;
            
            fetch(`api.php/user/${userId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    hideModal('editUserModal');
                    showSuccess('User berhasil diperbarui');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal memperbarui user: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        });
    }
}

// ========== HELPER FUNCTIONS ==========
function getActivityIcon(action) {
    switch (action) {
        case 'login': return 'sign-in-alt';
        case 'logout': return 'sign-out-alt';
        case 'register': return 'user-plus';
        case 'password_change': return 'key';
        case 'profile_update': return 'edit';
        default: return 'circle';
    }
}

function getActivityTitle(action) {
    switch (action) {
        case 'login': return 'User Login';
        case 'logout': return 'User Logout';
        case 'register': return 'User Registration';
        case 'password_change': return 'Password Changed';
        case 'profile_update': return 'Profile Updated';
        default: return action.charAt(0).toUpperCase() + action.slice(1).replace('_', ' ');
    }
}

function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString('id-ID');
}

// Helper function untuk format waktu
function timeAgo(datetime) {
    const time = time() - strtotime(datetime);
    
    if (time < 60) return 'Baru saja';
    if (time < 3600) return Math.floor(time/60) + ' menit lalu';
    if (time < 86400) return Math.floor(time/3600) + ' jam lalu';
    if (time < 2592000) return Math.floor(time/86400) + ' hari lalu';
    if (time < 31104000) return Math.floor(time/2592000) + ' bulan lalu';
    
    return Math.floor(time/31104000) + ' tahun lalu';
}

// Make functions globally available
window.editUser = editUser;
window.deleteUser = deleteUser;
window.viewUserActivity = viewUserActivity;
window.resetUserPassword = resetUserPassword;
window.clearUserSearch = clearUserSearch;
window.resetUserFilters = resetUserFilters;
</script>