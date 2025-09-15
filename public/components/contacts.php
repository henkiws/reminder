<?php
// components/contacts.php - Enhanced Version
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
    
    <!-- Search and Filter -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="contactSearch" placeholder="Cari kontak berdasarkan nama atau nomor..." 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full pl-10 p-2.5">
            </div>
        </div>
        <div class="flex gap-2">
            <select id="contactFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                <option value="all">Semua Kontak</option>
                <option value="personal">Kontak Pribadi</option>
                <option value="shared">Kontak Bersama</option>
            </select>
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
                    <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortTable('name')">
                        <div class="flex items-center">
                            Nama
                            <i class="fas fa-sort ml-1"></i>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortTable('phone')">
                        <div class="flex items-center">
                            Nomor WhatsApp
                            <i class="fas fa-sort ml-1"></i>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Pemilik</th>
                    <th scope="col" class="px-6 py-3 cursor-pointer hover:bg-gray-100" onclick="sortTable('created_at')">
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
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($contact['name']); ?></div>
                                <?php if (!empty($contact['notes'])): ?>
                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($contact['notes']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-mono text-sm"><?php echo htmlspecialchars($contact['phone']); ?></div>
                        <button onclick="copyToClipboard('<?php echo $contact['phone']; ?>')" 
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
                        <?php if ($contact['user_id']): ?>
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
                        <div class="text-sm"><?php echo date('d/m/Y', strtotime($contact['created_at'])); ?></div>
                        <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($contact['created_at'])); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            <button onclick="sendTestMessage(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['phone']); ?>')" 
                                    class="text-green-600 hover:text-green-900 transition-colors" title="Kirim Pesan Test">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button onclick="editContact(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($contact['phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($contact['notes'] ?? '', ENT_QUOTES); ?>')" 
                                    class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit Kontak">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteContact(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['name'], ENT_QUOTES); ?>')" 
                                    class="text-red-600 hover:text-red-900 transition-colors" title="Hapus Kontak">
                                <i class="fas fa-trash"></i>
                            </button>
                            <div class="relative">
                                <button onclick="toggleContactMenu(<?php echo $contact['id']; ?>)" 
                                        class="text-gray-600 hover:text-gray-900 transition-colors" title="Menu Lainnya">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div id="contactMenu<?php echo $contact['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                    <div class="py-1">
                                        <button onclick="viewContactHistory(<?php echo $contact['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                            <i class="fas fa-history mr-2"></i>Riwayat Pesan
                                        </button>
                                        <button onclick="addToGroup(<?php echo $contact['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                            <i class="fas fa-users mr-2"></i>Tambah ke Grup
                                        </button>
                                        <button onclick="duplicateContact(<?php echo $contact['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
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
// Enhanced contact management scripts
document.addEventListener('DOMContentLoaded', function() {
    initializeContactManagement();
});

function initializeContactManagement() {
    // Search functionality
    const searchInput = document.getElementById('contactSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterContacts();
        });
    }
    
    // Filter functionality
    const filterSelect = document.getElementById('contactFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            filterContacts();
        });
    }
    
    // Select all functionality
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
    
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneInput(this);
        });
    });
    
    // Close contact menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[id^="contactMenu"]') && !e.target.closest('button[onclick*="toggleContactMenu"]')) {
            closeAllContactMenus();
        }
    });
}

function filterContacts() {
    const searchTerm = document.getElementById('contactSearch').value.toLowerCase();
    const filterValue = document.getElementById('contactFilter').value;
    const rows = document.querySelectorAll('.contact-row');
    
    rows.forEach(row => {
        const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const phone = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const isPersonal = row.querySelector('.fa-user') !== null;
        
        let showRow = true;
        
        // Apply search filter
        if (searchTerm && !name.includes(searchTerm) && !phone.includes(searchTerm)) {
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

function toggleSelectAll(checked) {
    const checkboxes = document.querySelectorAll('.contact-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('.contact-row').style.display !== 'none') {
            checkbox.checked = checked;
        }
    });
    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (checkedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
        selectedCount.textContent = checkedBoxes.length;
    } else {
        bulkActions.classList.add('hidden');
    }
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.contact-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
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

function sortTable(column) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    let columnIndex;
    switch (column) {
        case 'name': columnIndex = 1; break;
        case 'phone': columnIndex = 2; break;
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

function sendTestMessage(contactId, phone) {
    showConfirmModal(
        'Kirim Pesan Test',
        `Kirim pesan test ke ${phone}?`,
        'Kirim',
        'primary',
        () => {
            // Implement test message functionality
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

function editContact(id, name, phone, notes) {
    document.getElementById('edit_contact_id').value = id;
    document.getElementById('edit_contact_name').value = name;
    document.getElementById('edit_contact_phone').value = phone;
    document.getElementById('edit_contact_notes').value = notes || '';
    
    showModal('editContactModal');
}

function viewContactHistory(contactId) {
    // Open contact history modal or page
    window.open(`contact-history.php?id=${contactId}`, '_blank');
}

function addToGroup(contactId) {
    // Implement add to group functionality
    alert('Fitur tambah ke grup akan segera tersedia');
}

function duplicateContact(contactId) {
    // Implement duplicate contact functionality
    showConfirmModal(
        'Duplikasi Kontak',
        'Apakah Anda yakin ingin menduplikasi kontak ini?',
        'Duplikasi',
        'primary',
        () => {
            // Implementation here
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
    
    // Create export request
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
    // Export all contacts
    window.open('api.php/contacts/export?all=1', '_blank');
}
</script>