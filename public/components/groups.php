<?php
// components/groups.php - Complete Enhanced Version with Fixed Search
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
    
    <!-- Enhanced Search and Filter Section -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" 
                       id="groupSearch" 
                       placeholder="Cari grup berdasarkan nama atau ID..." 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full pl-10 pr-10 p-2.5 transition-colors">
                <!-- Clear search button -->
                <div class="absolute inset-y-0 right-0 flex items-center">
                    <button type="button" 
                            id="clearSearchBtn"
                            onclick="clearSearch()" 
                            class="hidden mr-3 text-gray-400 hover:text-gray-600 transition-colors"
                            title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <!-- Search results count -->
            <div id="searchResultsCount" class="mt-1 text-xs text-gray-500 hidden"></div>
        </div>
        <div class="flex gap-2">
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
                                <div class="font-medium text-gray-900" data-searchable="name"><?php echo htmlspecialchars($group['name']); ?></div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Dibuat <?php echo date('d M Y', strtotime($group['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-mono text-xs bg-gray-100 px-2 py-1 rounded" data-searchable="id">
                            <?php echo htmlspecialchars($group['group_id']); ?>
                        </div>
                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($group['group_id'], ENT_QUOTES); ?>')" 
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
                        <div class="text-sm"><?php echo date('d/m/Y', strtotime($group['created_at'])); ?></div>
                        <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($group['created_at'])); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            <button type="button"
                                    class="text-blue-600 hover:text-blue-900 transition-colors edit-group-btn" 
                                    data-group-id="<?php echo $group['id']; ?>"
                                    data-group-name="<?php echo htmlspecialchars($group['name']); ?>"
                                    data-group-whatsapp-id="<?php echo htmlspecialchars($group['group_id']); ?>"
                                    data-group-description="<?php echo htmlspecialchars($group['description'] ?? ''); ?>"
                                    title="Edit Grup">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button"
                                    class="text-red-600 hover:text-red-900 transition-colors delete-group-btn"
                                    data-group-id="<?php echo $group['id']; ?>"
                                    data-group-name="<?php echo htmlspecialchars($group['name']); ?>"
                                    title="Hapus Grup">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button onclick="viewGroupHistory(<?php echo $group['id']; ?>)" 
                                    class="text-gray-600 hover:text-gray-900 transition-colors" 
                                    title="Riwayat Pesan">
                                <i class="fas fa-history"></i>
                            </button>
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
// Complete Group Management JavaScript with Fixed Search
document.addEventListener('DOMContentLoaded', function() {
    initializeGroupManagement();
});

function initializeGroupManagement() {
    initializeGroupSearch();
    initializeGroupSelection();
    initializeGroupMenus();
    initializeGroupForms();
}

// ========== SEARCH FUNCTIONALITY ==========
function initializeGroupSearch() {
    const searchInput = document.getElementById('groupSearch');
    const clearBtn = document.getElementById('clearSearchBtn');
    const filterSelect = document.getElementById('groupFilter');
    
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
                filterGroups();
                updateSearchResults();
            }, 300);
        });
        
        // Clear search on Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSearch();
            }
        });
    }
    
    // Filter select
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            filterGroups();
            updateSearchResults();
        });
    }
}

function filterGroups() {
    const searchInput = document.getElementById('groupSearch');
    const filterSelect = document.getElementById('groupFilter');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const filterValue = filterSelect ? filterSelect.value : 'all';
    const rows = document.querySelectorAll('.group-row');
    
    let visibleCount = 0;
    
    rows.forEach(row => {
        const nameElement = row.querySelector('[data-searchable="name"]');
        const idElement = row.querySelector('[data-searchable="id"]');
        const statusCell = row.querySelector('td:nth-child(5)');
        
        if (!nameElement || !idElement) return;
        
        const name = nameElement.textContent.toLowerCase();
        const groupId = idElement.textContent.toLowerCase();
        const isActive = statusCell && statusCell.textContent.toLowerCase().includes('aktif');
        
        let showRow = true;
        
        // Apply search filter
        if (searchTerm && !name.includes(searchTerm) && !groupId.includes(searchTerm)) {
            showRow = false;
        }
        
        // Apply status filter
        if (filterValue === 'active' && !isActive) {
            showRow = false;
        } else if (filterValue === 'recent') {
            // Add logic for recent groups if needed
        }
        
        if (showRow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    updateEmptyState(visibleCount, searchTerm);
    updateBulkActionsAfterFilter();
}

function updateSearchResults() {
    const searchInput = document.getElementById('groupSearch');
    const resultsCount = document.getElementById('searchResultsCount');
    const visibleRows = document.querySelectorAll('.group-row:not([style*="display: none"])');
    const totalRows = document.querySelectorAll('.group-row');
    
    if (resultsCount && searchInput && searchInput.value.trim()) {
        const count = visibleRows.length;
        const total = totalRows.length;
        
        if (count === total) {
            resultsCount.classList.add('hidden');
        } else {
            resultsCount.classList.remove('hidden');
            resultsCount.textContent = `Menampilkan ${count} dari ${total} grup`;
        }
    } else if (resultsCount) {
        resultsCount.classList.add('hidden');
    }
}

function updateEmptyState(visibleCount, searchTerm) {
    const tbody = document.getElementById('groupsTableBody');
    const existingMessage = document.getElementById('noSearchResults');
    
    // Remove existing no-results message
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Show no results message if no visible rows and search is active
    if (visibleCount === 0 && searchTerm) {
        const noResultsRow = document.createElement('tr');
        noResultsRow.id = 'noSearchResults';
        noResultsRow.innerHTML = `
            <td colspan="7" class="px-6 py-12 text-center">
                <div class="max-w-sm mx-auto">
                    <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada hasil</h3>
                    <p class="text-gray-500 mb-4">
                        Tidak ditemukan grup dengan kata kunci "<strong>${searchTerm}</strong>"
                    </p>
                    <div class="space-y-2">
                        <button onclick="clearSearch()" class="text-blue-600 hover:text-blue-800 font-medium block mx-auto">
                            <i class="fas fa-times mr-1"></i>Hapus pencarian
                        </button>
                        <button data-modal-target="addGroupModal" data-modal-toggle="addGroupModal" 
                                class="text-green-600 hover:text-green-800 font-medium">
                            <i class="fas fa-plus mr-1"></i>Tambah grup baru
                        </button>
                    </div>
                </div>
            </td>
        `;
        tbody.appendChild(noResultsRow);
    }
}

function clearSearch() {
    const searchInput = document.getElementById('groupSearch');
    const clearBtn = document.getElementById('clearSearchBtn');
    const resultsCount = document.getElementById('searchResultsCount');
    
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
    
    filterGroups();
}

// ========== SELECTION FUNCTIONALITY ==========
function initializeGroupSelection() {
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
}

function toggleSelectAllGroups(checked) {
    const checkboxes = document.querySelectorAll('.group-checkbox');
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('.group-row');
        // Only affect visible rows
        if (row && row.style.display !== 'none') {
            checkbox.checked = checked;
        }
    });
    updateGroupBulkActions();
}

function updateGroupBulkActions() {
    updateBulkActionsAfterFilter();
}

function updateBulkActionsAfterFilter() {
    // Update bulk actions to only consider visible rows
    const visibleCheckedBoxes = document.querySelectorAll('.group-row:not([style*="display: none"]) .group-checkbox:checked');
    const bulkActions = document.getElementById('groupBulkActions');
    const selectedCount = document.getElementById('selectedGroupCount');
    
    if (visibleCheckedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
        selectedCount.textContent = visibleCheckedBoxes.length;
    } else {
        bulkActions.classList.add('hidden');
    }
}

function clearGroupSelection() {
    const checkboxes = document.querySelectorAll('.group-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    
    const selectAllCheckbox = document.getElementById('selectAllGroups');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
    
    updateGroupBulkActions();
}

// ========== MENU FUNCTIONALITY ==========
function initializeGroupMenus() {
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[id^="groupMenu"]') && !e.target.closest('button[onclick*="toggleGroupMenu"]')) {
            closeAllGroupMenus();
        }
    });
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

// ========== FORM FUNCTIONALITY ==========
function initializeGroupForms() {
    // Edit group buttons
    const editButtons = document.querySelectorAll('.edit-group-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.groupId;
            const name = this.dataset.groupName;
            const groupId = this.dataset.groupWhatsappId;
            const description = this.dataset.groupDescription;
            
            editGroup(id, name, groupId, description);
        });
    });
    
    // Delete group buttons
    const deleteButtons = document.querySelectorAll('.delete-group-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.groupId;
            const name = this.dataset.groupName;
            
            deleteGroup(id, name);
        });
    });
    
    // Add group form
    const addForm = document.getElementById('addGroupForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAddGroupForm();
        });
    }
    
    // Edit group form
    const editForm = document.getElementById('editGroupForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitEditGroupForm();
        });
    }
}

function editGroup(id, name, groupId, description) {
    document.getElementById('edit_group_id').value = id;
    document.getElementById('edit_group_name').value = name;
    document.getElementById('edit_group_group_id').value = groupId;
    document.getElementById('edit_group_description').value = description || '';
    
    showModal('editGroupModal');
}

function deleteGroup(id, name) {
    showConfirmModal(
        'Hapus Grup',
        `Apakah Anda yakin ingin menghapus grup "${name}"?`,
        'Hapus',
        'danger',
        () => {
            fetch('api.php/group', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Grup berhasil dihapus');
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

function submitAddGroupForm() {
    const formData = new FormData(document.getElementById('addGroupForm'));
    const data = Object.fromEntries(formData);
    
    fetch('api.php/group', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Grup berhasil ditambahkan');
            hideModal('addGroupModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan grup: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function submitEditGroupForm() {
    const formData = new FormData(document.getElementById('editGroupForm'));
    const data = Object.fromEntries(formData);
    
    fetch('api.php/group', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Grup berhasil diperbarui');
            hideModal('editGroupModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal memperbarui grup: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

// ========== API FUNCTIONS ==========
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

// ========== UTILITY FUNCTIONS ==========
function sortGroupTable(column) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr:not(#noSearchResults)'));
    
    let columnIndex;
    switch (column) {
        case 'name': columnIndex = 1; break;
        case 'created_at': columnIndex = 5; break;
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
        filterGroups();
    }, 100);
}

function viewGroupHistory(groupId) {
    window.open(`group-history.php?id=${groupId}`, '_self');
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

function bulkTestGroups() {
    const checkedBoxes = document.querySelectorAll('.group-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    showConfirmModal(
        'Test Grup Terpilih',
        `Kirim pesan test ke ${checkedBoxes.length} grup yang dipilih?`,
        'Kirim',
        'primary',
        () => {
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

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showSuccess('ID grup berhasil disalin ke clipboard');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showSuccess('ID grup berhasil disalin');
    });
}

// Note: Helper functions like showModal, hideModal, showConfirmModal, showSuccess, showError 
// should be available globally from your main app.js file
</script>