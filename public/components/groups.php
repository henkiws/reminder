<?php
// components/groups.php - Enhanced Version
?>

<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Manajemen Grup WhatsApp</h3>
            <p class="text-sm text-gray-600 mt-1">Kelola daftar grup WhatsApp untuk broadcast notifikasi</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="bg-purple-50 px-3 py-2 rounded-lg text-sm">
                <span class="font-medium text-purple-900"><?php echo count($groups); ?></span>
                <span class="text-purple-700">Grup Total</span>
            </div>
            <button type="button" data-modal-target="addGroupModal" data-modal-toggle="addGroupModal" 
                    class="bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-white transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Grup
            </button>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="groupSearch" placeholder="Cari grup berdasarkan nama atau ID..." 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full pl-10 p-2.5">
            </div>
        </div>
        <div class="flex gap-2">
            <select id="groupFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                <option value="all">Semua Grup</option>
                <option value="personal">Grup Pribadi</option>
                <option value="shared">Grup Bersama</option>
            </select>
            <button type="button" onclick="testAllGroups()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                <i class="fas fa-paper-plane mr-2"></i>Test Semua
            </button>
            <button type="button" onclick="exportGroups()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                <i class="fas fa-download mr-2"></i>Export
            </button>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <div id="groupBulkActions" class="hidden mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-center justify-between">
            <span class="text-sm text-yellow-800">
                <span id="selectedGroupCount">0</span> grup dipilih
            </span>
            <div class="flex space-x-2">
                <button onclick="bulkTestGroups()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors">
                    <i class="fas fa-paper-plane mr-1"></i>Test
                </button>
                <button onclick="bulkDeleteGroups()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors">
                    <i class="fas fa-trash mr-1"></i>Hapus
                </button>
                <button onclick="clearGroupSelection()" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm transition-colors">
                    Batal
                </button>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="p-4">
                        <div class="flex items-center">
                            <input id="selectAllGroups" type="checkbox" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                            <label for="selectAllGroups" class="sr-only">Select all</label>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortGroupTable('name')">
                        <div class="flex items-center">
                            Nama Grup
                            <i class="fas fa-sort ml-1"></i>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">ID Grup</th>
                    <th scope="col" class="px-6 py-3">Deskripsi</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Pemilik</th>
                    <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortGroupTable('created_at')">
                        <div class="flex items-center">
                            Ditambahkan
                            <i class="fas fa-sort ml-1"></i>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody id="groupsTableBody">
                <?php foreach ($groups as $group): ?>
                <tr class="bg-white border-b hover:bg-gray-50 transition-colors group-row" data-group-id="<?php echo $group['id']; ?>">
                    <td class="w-4 p-4">
                        <div class="flex items-center">
                            <input type="checkbox" class="group-checkbox w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500" 
                                   value="<?php echo $group['id']; ?>">
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-800 font-medium mr-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($group['name']); ?></div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Dibuat <?php echo date('d M Y', strtotime($group['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                            <?php echo htmlspecialchars($group['group_id']); ?>
                        </div>
                        <button onclick="copyToClipboard('<?php echo $group['group_id']; ?>')" 
                                class="text-xs text-blue-600 hover:text-blue-800 mt-1">
                            <i class="fas fa-copy mr-1"></i>Copy ID
                        </button>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($group['description']): ?>
                            <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($group['description']); ?>">
                                <?php echo htmlspecialchars($group['description']); ?>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400 italic">Tidak ada deskripsi</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                            Aktif
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($group['user_id']): ?>
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                                <i class="fas fa-user mr-1"></i>Pribadi
                            </span>
                        <?php else: ?>
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-1 rounded">
                                <i class="fas fa-globe mr-1"></i>Bersama
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm"><?php echo date('d/m/Y', strtotime($group['created_at'])); ?></div>
                        <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($group['created_at'])); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            <button onclick="sendTestGroupMessage(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['group_id']); ?>')" 
                                    class="text-green-600 hover:text-green-900 transition-colors" title="Kirim Pesan Test">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button onclick="getGroupInfo(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['group_id']); ?>')" 
                                    class="text-blue-600 hover:text-blue-900 transition-colors" title="Info Grup">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <button onclick="editGroup(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($group['group_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($group['description'] ?? '', ENT_QUOTES); ?>')" 
                                    class="text-orange-600 hover:text-orange-900 transition-colors" title="Edit Grup">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteGroup(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?>')" 
                                    class="text-red-600 hover:text-red-900 transition-colors" title="Hapus Grup">
                                <i class="fas fa-trash"></i>
                            </button>
                            <div class="relative">
                                <button onclick="toggleGroupMenu(<?php echo $group['id']; ?>)" 
                                        class="text-gray-600 hover:text-gray-900 transition-colors" title="Menu Lainnya">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div id="groupMenu<?php echo $group['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                    <div class="py-1">
                                        <button onclick="viewGroupHistory(<?php echo $group['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                            <i class="fas fa-history mr-2"></i>Riwayat Pesan
                                        </button>
                                        <button onclick="getGroupMembers(<?php echo $group['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                            <i class="fas fa-users mr-2"></i>Lihat Anggota
                                        </button>
                                        <button onclick="duplicateGroup(<?php echo $group['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                            <i class="fas fa-clone mr-2"></i>Duplikasi
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($groups)): ?>
        <div class="text-center py-12">
            <div class="max-w-sm mx-auto">
                <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada grup</h3>
                <p class="text-gray-500 mb-6">Tambahkan grup WhatsApp untuk mengirim notifikasi broadcast ke banyak orang sekaligus.</p>
                <button data-modal-target="addGroupModal" data-modal-toggle="addGroupModal" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Tambah Grup Pertama
                </button>
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-left">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>Cara mendapatkan ID Grup:
                    </h4>
                    <ol class="text-xs text-blue-800 space-y-1">
                        <li>1. Login ke dashboard Fonnte.com</li>
                        <li>2. Pilih menu "Device" atau "Perangkat"</li>
                        <li>3. Klik "Get Group List" atau "Daftar Grup"</li>
                        <li>4. Copy ID grup yang diinginkan</li>
                    </ol>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Add Group Modal -->
<div id="addGroupModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-users-cog mr-2 text-green-600"></i>
                    Tambah Grup Baru
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-hide="addGroupModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="addGroupForm" class="p-4 md:p-5">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="group_name" class="block mb-2 text-sm font-medium text-gray-900">
                            Nama Grup <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="group_name" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" 
                               placeholder="Masukkan nama grup">
                        <p class="mt-1 text-xs text-gray-500">Nama untuk mengidentifikasi grup ini dalam sistem</p>
                    </div>
                    <div>
                        <label for="group_id" class="block mb-2 text-sm font-medium text-gray-900">
                            ID Grup WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="group_id" id="group_id" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 font-mono" 
                               placeholder="120363xxxxx@g.us">
                        <div class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Dapatkan ID grup dari dashboard Fonnte
                        </div>
                    </div>
                    <div>
                        <label for="group_description" class="block mb-2 text-sm font-medium text-gray-900">Deskripsi (Opsional)</label>
                        <textarea name="description" id="group_description" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" 
                                  placeholder="Deskripsi grup untuk memudahkan identifikasi..."></textarea>
                    </div>
                </div>
                
                <!-- Group ID Helper -->
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">
                        <i class="fas fa-question-circle mr-2"></i>Tidak tahu ID Grup?
                    </h4>
                    <div class="space-y-2">
                        <button type="button" onclick="getGroupList()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm transition-colors">
                            <i class="fas fa-list mr-2"></i>Ambil Daftar Grup dari Fonnte
                        </button>
                        <div class="text-xs text-blue-800">
                            Atau kunjungi: <a href="https://fonnte.com" target="_blank" class="underline">dashboard Fonnte</a> → Device → Get Group List
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button type="button" data-modal-hide="addGroupModal" 
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah Grup
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enhanced Edit Group Modal -->
<div id="editGroupModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-edit mr-2 text-orange-600"></i>
                    Edit Grup
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-hide="editGroupModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="editGroupForm" class="p-4 md:p-5">
                <input type="hidden" id="edit_group_id" name="id">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="edit_group_name" class="block mb-2 text-sm font-medium text-gray-900">
                            Nama Grup <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="edit_group_name" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label for="edit_group_group_id" class="block mb-2 text-sm font-medium text-gray-900">
                            ID Grup WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="group_id" id="edit_group_group_id" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 font-mono">
                    </div>
                    <div>
                        <label for="edit_group_description" class="block mb-2 text-sm font-medium text-gray-900">Deskripsi</label>
                        <textarea name="description" id="edit_group_description" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" data-modal-hide="editGroupModal" 
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="text-white inline-flex items-center bg-orange-600 hover:bg-orange-700 focus:ring-4 focus:outline-none focus:ring-orange-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Group Info Modal -->
<div id="groupInfoModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                    Informasi Grup
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-hide="groupInfoModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <div class="p-4 md:p-5">
                <div id="groupInfoContent" class="space-y-4">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                        <p class="mt-2 text-gray-500">Mengambil informasi grup...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced group management scripts
document.addEventListener('DOMContentLoaded', function() {
    initializeGroupManagement();
});

function initializeGroupManagement() {
    // Search functionality
    const searchInput = document.getElementById('groupSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterGroups();
        });
    }
    
    // Filter functionality
    const filterSelect = document.getElementById('groupFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            filterGroups();
        });
    }
    
    // Select all functionality
    const selectAllCheckbox = document.getElementById('selectAllGroups');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            toggleSelectAllGroups(this.checked);
        });
    }
    
    // Individual checkbox functionality
    const checkboxes = document.querySelectorAll('.group-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateGroupBulkActions();
        });
    });
    
    // Close group menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[id^="groupMenu"]') && !e.target.closest('button[onclick*="toggleGroupMenu"]')) {
            closeAllGroupMenus();
        }
    });
}

function filterGroups() {
    const searchTerm = document.getElementById('groupSearch').value.toLowerCase();
    const filterValue = document.getElementById('groupFilter').value;
    const rows = document.querySelectorAll('.group-row');
    
    rows.forEach(row => {
        const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const groupId = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const isPersonal = row.querySelector('.fa-user') !== null;
        
        let showRow = true;
        
        // Apply search filter
        if (searchTerm && !name.includes(searchTerm) && !groupId.includes(searchTerm)) {
            showRow = false;
        }
        
        // Apply type filter
        if (filterValue === 'personal' && !isPersonal) {
            showRow = false;
        } else if (filterValue === 'shared' && isPersonal) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function toggleSelectAllGroups(checked) {
    const checkboxes = document.querySelectorAll('.group-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('.group-row').style.display !== 'none') {
            checkbox.checked = checked;
        }
    });
    updateGroupBulkActions();
}

function updateGroupBulkActions() {
    const checkedBoxes = document.querySelectorAll('.group-checkbox:checked');
    const bulkActions = document.getElementById('groupBulkActions');
    const selectedCount = document.getElementById('selectedGroupCount');
    
    if (checkedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
        selectedCount.textContent = checkedBoxes.length;
    } else {
        bulkActions.classList.add('hidden');
    }
}

function clearGroupSelection() {
    const checkboxes = document.querySelectorAll('.group-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    document.getElementById('selectAllGroups').checked = false;
    updateGroupBulkActions();
}

function sortGroupTable(column) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    let columnIndex;
    switch (column) {
        case 'name': columnIndex = 1; break;
        case 'created_at': columnIndex = 6; break;
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
}

function toggleGroupMenu(groupId) {
    closeAllGroupMenus();
    const menu = document.getElementById('groupMenu' + groupId);
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

function closeAllGroupMenus() {
    const menus = document.querySelectorAll('[id^="groupMenu"]');
    menus.forEach(menu => menu.classList.add('hidden'));
}

function sendTestGroupMessage(groupId, groupWaId) {
    showConfirmModal(
        'Kirim Pesan Test',
        `Kirim pesan test ke grup ${groupWaId}?`,
        'Kirim',
        'primary',
        () => {
            const testMessage = `📢 TEST MESSAGE 📢

Halo semua! Ini adalah pesan test dari sistem notifikasi WhatsApp.

Pesan ini dikirim pada: ${new Date().toLocaleString('id-ID')}

Jika Anda menerima pesan ini, berarti sistem notifikasi berfungsi dengan baik.

Terima kasih! 🙏`;

            fetch('api.php/send-test-group', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    group_id: groupId,
                    group_wa_id: groupWaId,
                    message: testMessage
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Pesan test berhasil dikirim ke grup');
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

function getGroupInfo(groupId, groupWaId) {
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
            group_id: groupId,
            group_wa_id: groupWaId
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
                            <div class="text-gray-700 font-mono text-xs">${groupWaId}</div>
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
                    <p class="text-sm text-gray-500">${result.error || 'Grup mungkin tidak tersedia atau API error'}</p>
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
                <p class="text-sm text-gray-500">Silakan coba lagi nanti</p>
            </div>
        `;
    });
}

function getGroupList() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengambil...';
    
    fetch('api.php/get-group-list', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.groups) {
            // Show group selection modal
            showGroupListModal(result.groups);
        } else {
            showError('Gagal mengambil daftar grup: ' + (result.error || 'API error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan saat mengambil daftar grup');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function showGroupListModal(groups) {
    // Create and show group list selection modal
    let modalHTML = `
        <div id="groupListModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full m-4 max-h-96 overflow-hidden">
                <div class="p-4 border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Pilih Grup dari Fonnte</h3>
                        <button onclick="closeGroupListModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="p-4 overflow-y-auto max-h-80">
                    ${groups.map(group => `
                        <div class="flex items-center justify-between p-3 border-b hover:bg-gray-50 cursor-pointer" onclick="selectGroupFromList('${group.id}', '${group.name}')">
                            <div>
                                <div class="font-medium">${group.name}</div>
                                <div class="text-xs text-gray-500 font-mono">${group.id}</div>
                            </div>
                            <button class="text-blue-600 hover:text-blue-800 text-sm">Pilih</button>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function selectGroupFromList(groupId, groupName) {
    document.getElementById('group_name').value = groupName;
    document.getElementById('group_id').value = groupId;
    closeGroupListModal();
}

function closeGroupListModal() {
    const modal = document.getElementById('groupListModal');
    if (modal) {
        modal.remove();
    }
}

function editGroup(id, name, groupId, description) {
    document.getElementById('edit_group_id').value = id;
    document.getElementById('edit_group_name').value = name;
    document.getElementById('edit_group_group_id').value = groupId;
    document.getElementById('edit_group_description').value = description || '';
    
    showModal('editGroupModal');
}

function viewGroupHistory(groupId) {
    window.open(`group-history.php?id=${groupId}`, '_blank');
}

function getGroupMembers(groupId) {
    // Implementation for viewing group members
    alert('Fitur lihat anggota grup akan segera tersedia');
}

function duplicateGroup(groupId) {
    showConfirmModal(
        'Duplikasi Grup',
        'Apakah Anda yakin ingin menduplikasi grup ini?',
        'Duplikasi',
        'primary',
        () => {
            alert('Fitur duplikasi grup akan segera tersedia');
        }
    );
}

function testAllGroups() {
    const groups = document.querySelectorAll('.group-row');
    if (groups.length === 0) {
        showError('Tidak ada grup untuk ditest');
        return;
    }
    
    showConfirmModal(
        'Test Semua Grup',
        `Kirim pesan test ke ${groups.length} grup?`,
        'Kirim',
        'primary',
        () => {
            // Implementation for testing all groups
            alert('Fitur test semua grup akan segera tersedia');
        }
    );
}

function bulkTestGroups() {
    const checkedBoxes = document.querySelectorAll('.group-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    showConfirmModal(
        'Test Grup Terpilih',
        `Kirim pesan test ke ${checkedBoxes.length} grup yang dipilih?`,
        'Kirim',
        'primary',
        () => {
            // Implementation for bulk testing groups
            alert('Fitur test grup massal akan segera tersedia');
        }
    );
}

function bulkDeleteGroups() {
    const checkedBoxes = document.querySelectorAll('.group-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    showConfirmModal(
        'Hapus Grup',
        `Apakah Anda yakin ingin menghapus ${checkedBoxes.length} grup yang dipilih?`,
        'Hapus',
        'danger',
        () => {
            const groupIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            fetch('api.php/groups/bulk-delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ group_ids: groupIds })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(`${groupIds.length} grup berhasil dihapus`);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal menghapus grup: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

function exportGroups() {
    window.open('api.php/groups/export?all=1', '_blank');
}
</script>