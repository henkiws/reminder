<?php
// components/contacts.php - Complete Enhanced Version with Fixed Search
?>

<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Manajemen Kontak</h3>
            <p class="text-sm text-gray-600 mt-1">Kelola daftar kontak WhatsApp untuk notifikasi</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="bg-blue-50 px-3 py-2 rounded-lg text-sm">
                <span class="font-medium text-blue-900"><?php echo count($contacts); ?></span>
                <span class="text-blue-700">Kontak Total</span>
            </div>
            <button type="button" data-modal-target="addContactModal" data-modal-toggle="addContactModal" 
                    class="bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-white transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Kontak
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
                       id="contactSearch" 
                       placeholder="Cari kontak berdasarkan nama atau nomor..." 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full pl-10 pr-10 p-2.5 transition-colors">
                <!-- Clear search button -->
                <div class="absolute inset-y-0 right-0 flex items-center">
                    <button type="button" 
                            id="clearContactSearchBtn"
                            onclick="clearContactSearch()" 
                            class="hidden mr-3 text-gray-400 hover:text-gray-600 transition-colors"
                            title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <!-- Search results count -->
            <div id="contactSearchResultsCount" class="mt-1 text-xs text-gray-500 hidden"></div>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="exportContacts()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                <i class="fas fa-download mr-2"></i>Export
            </button>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <div id="bulkActions" class="hidden mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-center justify-between">
            <span class="text-sm text-yellow-800">
                <span id="selectedCount">0</span> kontak dipilih
            </span>
            <div class="flex space-x-2">
                <button onclick="bulkDelete()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors">
                    <i class="fas fa-trash mr-1"></i>Hapus
                </button>
                <button onclick="bulkExport()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors">
                    <i class="fas fa-download mr-1"></i>Export
                </button>
                <button onclick="clearSelection()" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm transition-colors">
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
                            <input id="selectAll" type="checkbox" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                            <label for="selectAll" class="sr-only">Select all</label>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortContactTable('name')">
                        <div class="flex items-center">
                            Nama
                            <i class="fas fa-sort ml-1"></i>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortContactTable('phone')">
                        <div class="flex items-center">
                            Nomor WhatsApp
                            <i class="fas fa-sort ml-1"></i>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortContactTable('created_at')">
                        <div class="flex items-center">
                            Ditambahkan
                            <i class="fas fa-sort ml-1"></i>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody id="contactsTableBody">
                <?php foreach ($contacts as $contact): ?>
                <tr class="bg-white border-b hover:bg-gray-50 transition-colors contact-row" data-contact-id="<?php echo $contact['id']; ?>">
                    <td class="w-4 p-4">
                        <div class="flex items-center">
                            <input type="checkbox" class="contact-checkbox w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500" 
                                   value="<?php echo $contact['id']; ?>">
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-800 font-medium mr-3">
                                <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900" data-searchable="name"><?php echo htmlspecialchars($contact['name']); ?></div>
                                <?php if (!empty($contact['notes'])): ?>
                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($contact['notes']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-mono text-sm" data-searchable="phone"><?php echo htmlspecialchars($contact['phone']); ?></div>
                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($contact['phone'], ENT_QUOTES); ?>')" 
                                class="text-xs text-blue-600 hover:text-blue-800 mt-1">
                            <i class="fas fa-copy mr-1"></i>Copy
                        </button>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                            Aktif
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm"><?php echo date('d/m/Y', strtotime($contact['created_at'])); ?></div>
                        <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($contact['created_at'])); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            <button type="button"
                                    class="text-blue-600 hover:text-blue-900 transition-colors edit-contact-btn" 
                                    data-contact-id="<?php echo $contact['id']; ?>"
                                    data-contact-name="<?php echo htmlspecialchars($contact['name']); ?>"
                                    data-contact-phone="<?php echo htmlspecialchars($contact['phone']); ?>"
                                    data-contact-notes="<?php echo htmlspecialchars($contact['notes'] ?? ''); ?>"
                                    title="Edit Kontak">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button"
                                    class="text-red-600 hover:text-red-900 transition-colors delete-contact-btn"
                                    data-contact-id="<?php echo $contact['id']; ?>"
                                    data-contact-name="<?php echo htmlspecialchars($contact['name']); ?>"
                                    title="Hapus Kontak">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button onclick="viewContactHistory(<?php echo $contact['id']; ?>)" 
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
        
        <?php if (empty($contacts)): ?>
        <div class="text-center py-12">
            <div class="max-w-sm mx-auto">
                <i class="fas fa-address-book text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada kontak</h3>
                <p class="text-gray-500 mb-6">Mulai dengan menambahkan kontak WhatsApp pertama Anda untuk mengirim notifikasi.</p>
                <button data-modal-target="addContactModal" data-modal-toggle="addContactModal" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Tambah Kontak Pertama
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination (if needed) -->
    <?php if (count($contacts) > 10): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Menampilkan <span class="font-medium">1</span> sampai <span class="font-medium"><?php echo count($contacts); ?></span> dari <span class="font-medium"><?php echo count($contacts); ?></span> kontak
        </div>
        <div class="flex space-x-2">
            <button class="bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm" disabled>Previous</button>
            <button class="bg-green-600 text-white px-3 py-1 rounded text-sm">1</button>
            <button class="bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm" disabled>Next</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Enhanced Add Contact Modal -->
<div id="addContactModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-user-plus mr-2 text-green-600"></i>
                    Tambah Kontak Baru
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-hide="addContactModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="addContactForm" class="p-4 md:p-5">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="contact_name" class="block mb-2 text-sm font-medium text-gray-900">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="contact_name" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" 
                               placeholder="Masukkan nama lengkap">
                    </div>
                    <div>
                        <label for="contact_phone" class="block mb-2 text-sm font-medium text-gray-900">
                            Nomor WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" name="phone" id="contact_phone" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" 
                               placeholder="628xxxxxxxxx">
                        <div class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Format: 628xxxxxxxxx (gunakan kode negara 62)
                        </div>
                    </div>
                    <div>
                        <label for="contact_notes" class="block mb-2 text-sm font-medium text-gray-900">Catatan (Opsional)</label>
                        <textarea name="notes" id="contact_notes" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" 
                                  placeholder="Catatan tambahan tentang kontak ini..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" data-modal-hide="addContactModal" 
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah Kontak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enhanced Edit Contact Modal -->
<div id="editContactModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-user-edit mr-2 text-blue-600"></i>
                    Edit Kontak
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-hide="editContactModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="editContactForm" class="p-4 md:p-5">
                <input type="hidden" id="edit_contact_id" name="id">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="edit_contact_name" class="block mb-2 text-sm font-medium text-gray-900">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="edit_contact_name" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label for="edit_contact_phone" class="block mb-2 text-sm font-medium text-gray-900">
                            Nomor WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" name="phone" id="edit_contact_phone" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label for="edit_contact_notes" class="block mb-2 text-sm font-medium text-gray-900">Catatan</label>
                        <textarea name="notes" id="edit_contact_notes" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" data-modal-hide="editContactModal" 
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="text-white inline-flex items-center bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Complete Contact Management JavaScript with Fixed Search
document.addEventListener('DOMContentLoaded', function() {
    initializeContactManagement();
});

function initializeContactManagement() {
    initializeContactSearch();
    initializeContactSelection();
    initializeContactMenus();
    initializeContactForms();
}

// ========== SEARCH FUNCTIONALITY ==========
function initializeContactSearch() {
    const searchInput = document.getElementById('contactSearch');
    const clearBtn = document.getElementById('clearContactSearchBtn');
    const filterSelect = document.getElementById('contactFilter');
    
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
                filterContacts();
                updateContactSearchResults();
            }, 300);
        });
        
        // Clear search on Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearContactSearch();
            }
        });
    }
    
    // Filter select
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            filterContacts();
            updateContactSearchResults();
        });
    }
}

function filterContacts() {
    const searchInput = document.getElementById('contactSearch');
    const filterSelect = document.getElementById('contactFilter');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const filterValue = filterSelect ? filterSelect.value : 'all';
    const rows = document.querySelectorAll('.contact-row');
    
    let visibleCount = 0;
    
    rows.forEach(row => {
        const nameElement = row.querySelector('[data-searchable="name"]');
        const phoneElement = row.querySelector('[data-searchable="phone"]');
        const statusCell = row.querySelector('td:nth-child(4)');
        
        if (!nameElement || !phoneElement) return;
        
        const name = nameElement.textContent.toLowerCase();
        const phone = phoneElement.textContent.toLowerCase();
        const isActive = statusCell && statusCell.textContent.toLowerCase().includes('aktif');
        
        let showRow = true;
        
        // Apply search filter
        if (searchTerm && !name.includes(searchTerm) && !phone.includes(searchTerm)) {
            showRow = false;
        }
        
        // Apply status filter
        if (filterValue === 'active' && !isActive) {
            showRow = false;
        } else if (filterValue === 'recent') {
            // Add logic for recent contacts if needed
        }
        
        if (showRow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    updateContactEmptyState(visibleCount, searchTerm);
    updateBulkActionsAfterContactFilter();
}

function updateContactSearchResults() {
    const searchInput = document.getElementById('contactSearch');
    const resultsCount = document.getElementById('contactSearchResultsCount');
    const visibleRows = document.querySelectorAll('.contact-row:not([style*="display: none"])');
    const totalRows = document.querySelectorAll('.contact-row');
    
    if (resultsCount && searchInput && searchInput.value.trim()) {
        const count = visibleRows.length;
        const total = totalRows.length;
        
        if (count === total) {
            resultsCount.classList.add('hidden');
        } else {
            resultsCount.classList.remove('hidden');
            resultsCount.textContent = `Menampilkan ${count} dari ${total} kontak`;
        }
    } else if (resultsCount) {
        resultsCount.classList.add('hidden');
    }
}

function updateContactEmptyState(visibleCount, searchTerm) {
    const tbody = document.getElementById('contactsTableBody');
    const existingMessage = document.getElementById('noContactSearchResults');
    
    // Remove existing no-results message
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Show no results message if no visible rows and search is active
    if (visibleCount === 0 && searchTerm) {
        const noResultsRow = document.createElement('tr');
        noResultsRow.id = 'noContactSearchResults';
        noResultsRow.innerHTML = `
            <td colspan="6" class="px-6 py-12 text-center">
                <div class="max-w-sm mx-auto">
                    <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada hasil</h3>
                    <p class="text-gray-500 mb-4">
                        Tidak ditemukan kontak dengan kata kunci "<strong>${searchTerm}</strong>"
                    </p>
                    <div class="space-y-2">
                        <button onclick="clearContactSearch()" class="text-blue-600 hover:text-blue-800 font-medium block mx-auto">
                            <i class="fas fa-times mr-1"></i>Hapus pencarian
                        </button>
                        <button data-modal-target="addContactModal" data-modal-toggle="addContactModal" 
                                class="text-green-600 hover:text-green-800 font-medium">
                            <i class="fas fa-plus mr-1"></i>Tambah kontak baru
                        </button>
                    </div>
                </div>
            </td>
        `;
        tbody.appendChild(noResultsRow);
    }
}

function clearContactSearch() {
    const searchInput = document.getElementById('contactSearch');
    const clearBtn = document.getElementById('clearContactSearchBtn');
    const resultsCount = document.getElementById('contactSearchResultsCount');
    
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
    
    filterContacts();
}

// ========== SELECTION FUNCTIONALITY ==========
function initializeContactSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            toggleSelectAll(this.checked);
        });
    }
    
    // Individual checkbox functionality
    const checkboxes = document.querySelectorAll('.contact-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
        });
    });
}

function toggleSelectAll(checked) {
    const checkboxes = document.querySelectorAll('.contact-checkbox');
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('.contact-row');
        // Only affect visible rows
        if (row && row.style.display !== 'none') {
            checkbox.checked = checked;
        }
    });
    updateBulkActions();
}

function updateBulkActions() {
    updateBulkActionsAfterContactFilter();
}

function updateBulkActionsAfterContactFilter() {
    // Update bulk actions to only consider visible rows
    const visibleCheckedBoxes = document.querySelectorAll('.contact-row:not([style*="display: none"]) .contact-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (visibleCheckedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
        selectedCount.textContent = visibleCheckedBoxes.length;
    } else {
        bulkActions.classList.add('hidden');
    }
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.contact-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
    
    updateBulkActions();
}

// ========== MENU FUNCTIONALITY ==========
function initializeContactMenus() {
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[id^="contactMenu"]') && !e.target.closest('button[onclick*="toggleContactMenu"]')) {
            closeAllContactMenus();
        }
    });
}

function toggleContactMenu(contactId) {
    closeAllContactMenus();
    const menu = document.getElementById('contactMenu' + contactId);
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

function closeAllContactMenus() {
    const menus = document.querySelectorAll('[id^="contactMenu"]');
    menus.forEach(menu => menu.classList.add('hidden'));
}

// ========== FORM FUNCTIONALITY ==========
function initializeContactForms() {
    // Edit contact buttons
    const editButtons = document.querySelectorAll('.edit-contact-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.contactId;
            const name = this.dataset.contactName;
            const phone = this.dataset.contactPhone;
            const notes = this.dataset.contactNotes;
            
            editContact(id, name, phone, notes);
        });
    });
    
    // Delete contact buttons
    const deleteButtons = document.querySelectorAll('.delete-contact-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.contactId;
            const name = this.dataset.contactName;
            
            deleteContact(id, name);
        });
    });
    
    // Add contact form
    const addForm = document.getElementById('addContactForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAddContactForm();
        });
    }
    
    // Edit contact form
    const editForm = document.getElementById('editContactForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitEditContactForm();
        });
    }
    
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneInput(this);
        });
    });
}

function editContact(id, name, phone, notes) {
    document.getElementById('edit_contact_id').value = id;
    document.getElementById('edit_contact_name').value = name;
    document.getElementById('edit_contact_phone').value = phone;
    document.getElementById('edit_contact_notes').value = notes || '';
    
    showModal('editContactModal');
}

function deleteContact(id, name) {
    showConfirmModal(
        'Hapus Kontak',
        `Apakah Anda yakin ingin menghapus kontak "${name}"?`,
        'Hapus',
        'danger',
        () => {
            fetch('api.php/contact', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Kontak berhasil dihapus');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal menghapus kontak: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

function submitAddContactForm() {
    const formData = new FormData(document.getElementById('addContactForm'));
    const data = Object.fromEntries(formData);
    
    fetch('api.php/contact', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Kontak berhasil ditambahkan');
            hideModal('addContactModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan kontak: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function submitEditContactForm() {
    const formData = new FormData(document.getElementById('editContactForm'));
    const data = Object.fromEntries(formData);
    
    fetch('api.php/contact', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Kontak berhasil diperbarui');
            hideModal('editContactModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal memperbarui kontak: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function formatPhoneInput(input) {
    let value = input.value.replace(/\D/g, '');
    
    // Ensure it starts with 62
    if (value.startsWith('0')) {
        value = '62' + value.substring(1);
    } else if (!value.startsWith('62') && value.length > 0) {
        value = '62' + value;
    }
    
    input.value = value;
}

// ========== API FUNCTIONS ==========
function sendTestMessage(contactId, phone) {
    showConfirmModal(
        'Kirim Pesan Test',
        `Kirim pesan test ke ${phone}?`,
        'Kirim',
        'primary',
        () => {
            fetch('api.php/send-test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    contact_id: contactId,
                    phone: phone,
                    message: 'Ini adalah pesan test dari sistem notifikasi WhatsApp.'
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Pesan test berhasil dikirim');
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

// ========== UTILITY FUNCTIONS ==========
function sortContactTable(column) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr:not(#noContactSearchResults)'));
    
    let columnIndex;
    switch (column) {
        case 'name': columnIndex = 1; break;
        case 'phone': columnIndex = 2; break;
        case 'created_at': columnIndex = 4; break;
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
        filterContacts();
    }, 100);
}

function viewContactHistory(contactId) {
    window.open(`contact-history.php?id=${contactId}`, '_self');
}

function addToGroup(contactId) {
    alert('Fitur tambah ke grup akan segera tersedia');
}

function duplicateContact(contactId) {
    showConfirmModal(
        'Duplikasi Kontak',
        'Apakah Anda yakin ingin menduplikasi kontak ini?',
        'Duplikasi',
        'primary',
        () => {
            alert('Fitur duplikasi kontak akan segera tersedia');
        }
    );
}

function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    showConfirmModal(
        'Hapus Kontak',
        `Apakah Anda yakin ingin menghapus ${checkedBoxes.length} kontak yang dipilih?`,
        'Hapus',
        'danger',
        () => {
            const contactIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            fetch('api.php/contacts/bulk-delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ contact_ids: contactIds })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess(`${contactIds.length} kontak berhasil dihapus`);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal menghapus kontak: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

function bulkExport() {
    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    const contactIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    fetch('api.php/contacts/export', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ contact_ids: contactIds })
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'contacts_export.csv';
        a.click();
        window.URL.revokeObjectURL(url);
        showSuccess('Kontak berhasil diexport');
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Gagal mengexport kontak');
    });
}

function exportContacts() {
    window.open('api.php/contacts/export?all=1', '_blank');
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showSuccess('Nomor berhasil disalin ke clipboard');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showSuccess('Nomor berhasil disalin');
    });
}

// Note: Helper functions like showModal, hideModal, showConfirmModal, showSuccess, showError 
// should be available globally from your main app.js file
</script>