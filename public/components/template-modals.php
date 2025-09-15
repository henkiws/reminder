<?php
// components/template-modals.php - Enhanced Template Management Modals
?>

<!-- Enhanced Add Template Modal -->
<div id="addTemplateModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-4xl max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-file-plus mr-2 text-green-600"></i>
                    Tambah Template Baru
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-hide="addTemplateModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            
            <form id="addTemplateForm" class="p-4 md:p-5">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column - Template Info -->
                    <div class="space-y-4">
                        <div>
                            <label for="template_title" class="block mb-2 text-sm font-medium text-gray-900">
                                Judul Template <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="template_title" required
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" 
                                   placeholder="Masukkan judul template">
                            <p class="mt-1 text-xs text-gray-500">Nama untuk mengidentifikasi template ini</p>
                        </div>
                        
                        <div>
                            <label for="template_category" class="block mb-2 text-sm font-medium text-gray-900">Kategori</label>
                            <select name="category_id" id="template_category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Quick Template Suggestions -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-blue-900 mb-3">Template Cepat</h4>
                            <div class="space-y-2">
                                <button type="button" onclick="loadQuickTemplate('meeting')" class="w-full text-left p-2 text-xs bg-white border border-blue-200 rounded hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-users mr-2 text-blue-600"></i>Template Rapat
                                </button>
                                <button type="button" onclick="loadQuickTemplate('deadline')" class="w-full text-left p-2 text-xs bg-white border border-blue-200 rounded hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-clock mr-2 text-red-600"></i>Template Deadline
                                </button>
                                <button type="button" onclick="loadQuickTemplate('announcement')" class="w-full text-left p-2 text-xs bg-white border border-blue-200 rounded hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-bullhorn mr-2 text-yellow-600"></i>Template Pengumuman
                                </button>
                                <button type="button" onclick="loadQuickTemplate('reminder')" class="w-full text-left p-2 text-xs bg-white border border-blue-200 rounded hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-bell mr-2 text-green-600"></i>Template Reminder
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column - Message Content -->
                    <div class="space-y-4">
                        <div>
                            <label for="template_message" class="block mb-2 text-sm font-medium text-gray-900">
                                Template Pesan <span class="text-red-500">*</span>
                            </label>
                            <textarea name="message_template" id="template_message" rows="10" required
                                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 font-mono" 
                                      placeholder="Tulis template pesan di sini..."></textarea>
                            
                            <!-- Character Count -->
                            <div class="mt-2 flex justify-between text-xs text-gray-500">
                                <span>Karakter: <span id="add_char_count">0</span></span>
                                <span>Estimasi SMS: <span id="add_sms_count">0</span> bagian</span>
                            </div>
                        </div>
                        
                        <!-- Variable Helper -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-yellow-900 mb-3">
                                <i class="fas fa-code mr-2"></i>Variabel Tersedia
                            </h4>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <button type="button" onclick="insertVariable('name')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {name} - Nama penerima
                                </button>
                                <button type="button" onclick="insertVariable('date')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {date} - Tanggal
                                </button>
                                <button type="button" onclick="insertVariable('time')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {time} - Waktu
                                </button>
                                <button type="button" onclick="insertVariable('location')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {location} - Lokasi
                                </button>
                                <button type="button" onclick="insertVariable('agenda')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {agenda} - Agenda
                                </button>
                                <button type="button" onclick="insertVariable('deadline_date')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {deadline_date} - Deadline
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Template Variables Preview -->
                <div id="add_template_variables_preview" class="mt-6 hidden">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Variabel yang Ditemukan:</h4>
                    <div id="add_variables_list" class="flex flex-wrap gap-2"></div>
                </div>
                
                <!-- Preview Section -->
                <div class="mt-6 border-t pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-gray-900">Preview Template</h4>
                        <button type="button" onclick="previewAddTemplate()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <i class="fas fa-eye mr-1"></i>Preview dengan Data Sample
                        </button>
                    </div>
                    <div id="add_template_preview" class="bg-gray-50 border border-gray-300 rounded-lg p-3 text-sm text-gray-700 min-h-[60px]">
                        <em class="text-gray-500">Preview akan muncul di sini...</em>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6 pt-4 border-t">
                    <button type="button" data-modal-hide="addTemplateModal" 
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 transition-colors">
                        Batal
                    </button>
                    <button type="button" onclick="saveAndUseTemplate()" 
                            class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-plus mr-2"></i>Simpan & Gunakan
                    </button>
                    <button type="submit" 
                            class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enhanced Edit Template Modal -->
<div id="editTemplateModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-4xl max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-file-edit mr-2 text-blue-600"></i>
                    Edit Template
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-hide="editTemplateModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            
            <form id="editTemplateForm" class="p-4 md:p-5">
                <input type="hidden" id="edit_template_id" name="id">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column - Template Info -->
                    <div class="space-y-4">
                        <div>
                            <label for="edit_template_title" class="block mb-2 text-sm font-medium text-gray-900">
                                Judul Template <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="edit_template_title" required
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                        </div>
                        
                        <div>
                            <label for="edit_template_category" class="block mb-2 text-sm font-medium text-gray-900">Kategori</label>
                            <select name="category_id" id="edit_template_category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Template Statistics -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Statistik Template</h4>
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <div class="text-gray-500">Digunakan</div>
                                    <div class="font-medium text-blue-600" id="edit_usage_count">0 kali</div>
                                </div>
                                <div>
                                    <div class="text-gray-500">Terakhir Diubah</div>
                                    <div class="font-medium text-gray-900" id="edit_last_modified">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column - Message Content -->
                    <div class="space-y-4">
                        <div>
                            <label for="edit_template_message" class="block mb-2 text-sm font-medium text-gray-900">
                                Template Pesan <span class="text-red-500">*</span>
                            </label>
                            <textarea name="message_template" id="edit_template_message" rows="10" required
                                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 font-mono"></textarea>
                            
                            <!-- Character Count -->
                            <div class="mt-2 flex justify-between text-xs text-gray-500">
                                <span>Karakter: <span id="edit_char_count">0</span></span>
                                <span>Estimasi SMS: <span id="edit_sms_count">0</span> bagian</span>
                            </div>
                        </div>
                        
                        <!-- Variable Helper -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-yellow-900 mb-3">
                                <i class="fas fa-code mr-2"></i>Variabel Tersedia
                            </h4>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <button type="button" onclick="insertEditVariable('name')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {name} - Nama penerima
                                </button>
                                <button type="button" onclick="insertEditVariable('date')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {date} - Tanggal
                                </button>
                                <button type="button" onclick="insertEditVariable('time')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {time} - Waktu
                                </button>
                                <button type="button" onclick="insertEditVariable('location')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {location} - Lokasi
                                </button>
                                <button type="button" onclick="insertEditVariable('agenda')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {agenda} - Agenda
                                </button>
                                <button type="button" onclick="insertEditVariable('deadline_date')" class="text-left p-1 bg-white border border-yellow-200 rounded hover:bg-yellow-100 transition-colors">
                                    {deadline_date} - Deadline
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Template Variables Preview -->
                <div id="edit_template_variables_preview" class="mt-6 hidden">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Variabel yang Ditemukan:</h4>
                    <div id="edit_variables_list" class="flex flex-wrap gap-2"></div>
                </div>
                
                <!-- Preview Section -->
                <div class="mt-6 border-t pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-gray-900">Preview Template</h4>
                        <button type="button" onclick="previewEditTemplate()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <i class="fas fa-eye mr-1"></i>Preview dengan Data Sample
                        </button>
                    </div>
                    <div id="edit_template_preview" class="bg-gray-50 border border-gray-300 rounded-lg p-3 text-sm text-gray-700 min-h-[60px]">
                        <em class="text-gray-500">Preview akan muncul di sini...</em>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6 pt-4 border-t">
                    <button type="button" data-modal-hide="editTemplateModal" 
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 transition-colors">
                        Batal
                    </button>
                    <button type="button" onclick="updateAndUseTemplate()" 
                            class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-plus mr-2"></i>Update & Gunakan
                    </button>
                    <button type="submit" 
                            class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Enhanced template management scripts
document.addEventListener('DOMContentLoaded', function() {
    initializeTemplateModals();
});

function initializeTemplateModals() {
    // Character counter for add template
    const addTemplateMessage = document.getElementById('template_message');
    if (addTemplateMessage) {
        addTemplateMessage.addEventListener('input', function() {
            updateAddTemplateStats();
            updateAddTemplateVariables();
        });
    }
    
    // Character counter for edit template
    const editTemplateMessage = document.getElementById('edit_template_message');
    if (editTemplateMessage) {
        editTemplateMessage.addEventListener('input', function() {
            updateEditTemplateStats();
            updateEditTemplateVariables();
        });
    }
}

function updateAddTemplateStats() {
    const message = document.getElementById('template_message').value;
    updateTemplateStats(message, 'add_char_count', 'add_sms_count');
}

function updateEditTemplateStats() {
    const message = document.getElementById('edit_template_message').value;
    updateTemplateStats(message, 'edit_char_count', 'edit_sms_count');
}

function updateTemplateStats(message, charCountId, smsCountId) {
    const charCount = document.getElementById(charCountId);
    const smsCount = document.getElementById(smsCountId);
    
    if (charCount) charCount.textContent = message.length;
    
    if (smsCount) {
        const smsparts = message.length > 160 ? Math.ceil(message.length / 153) : 1;
        smsCount.textContent = smsparts;
    }
}

function updateAddTemplateVariables() {
    const message = document.getElementById('template_message').value;
    updateTemplateVariablesDisplay(message, 'add_template_variables_preview', 'add_variables_list');
}

function updateEditTemplateVariables() {
    const message = document.getElementById('edit_template_message').value;
    updateTemplateVariablesDisplay(message, 'edit_template_variables_preview', 'edit_variables_list');
}

function updateTemplateVariablesDisplay(message, previewId, listId) {
    const variables = extractTemplateVariables(message);
    const preview = document.getElementById(previewId);
    const list = document.getElementById(listId);
    
    if (!preview || !list) return;
    
    if (variables.length > 0) {
        preview.classList.remove('hidden');
        list.innerHTML = variables.map(variable => 
            `<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">{${variable}}</span>`
        ).join('');
    } else {
        preview.classList.add('hidden');
    }
}

// Quick template loading
function loadQuickTemplate(type) {
    const templates = {
        'meeting': {
            title: 'Reminder Rapat',
            message: `Halo {name},

Ini adalah pengingat untuk rapat yang akan diadakan pada:
📅 Tanggal: {date}
🕐 Waktu: {time}
📍 Tempat: {location}
📋 Agenda: {agenda}

Mohon hadir tepat waktu. Terima kasih.

Best regards,
Tim Manajemen`
        },
        'deadline': {
            title: 'Pengingat Deadline',
            message: `⚠️ REMINDER DEADLINE ⚠️

Halo {name},

Batas waktu pengumpulan {item} adalah:
📅 {deadline_date}
⏰ Pukul: {deadline_time}

Mohon segera diselesaikan untuk menghindari keterlambatan.

Terima kasih atas perhatiannya.`
        },
        'announcement': {
            title: 'Pengumuman Penting',
            message: `📢 PENGUMUMAN PENTING 📢

Kepada: {name}
Tanggal: {date}

{announcement}

Informasi lebih lanjut dapat menghubungi:
📞 Admin: 08xx-xxxx-xxxx
📧 Email: info@perusahaan.com

Terima kasih atas perhatiannya.

Salam,
{sender}`
        },
        'reminder': {
            title: 'Pengingat Umum',
            message: `🔔 PENGINGAT

Halo {name},

{reminder_text}

Waktu: {date} pukul {time}

Jangan sampai terlewat ya!

Terima kasih.`
        }
    };
    
    const template = templates[type];
    if (template) {
        document.getElementById('template_title').value = template.title;
        document.getElementById('template_message').value = template.message;
        
        // Update stats and variables
        updateAddTemplateStats();
        updateAddTemplateVariables();
        
        // Set category based on type
        const categoryMap = {
            'meeting': '1',
            'deadline': '2', 
            'announcement': '3',
            'reminder': '4'
        };
        
        const categorySelect = document.getElementById('template_category');
        if (categorySelect && categoryMap[type]) {
            categorySelect.value = categoryMap[type];
        }
    }
}

// Variable insertion functions
function insertVariable(variable) {
    insertIntoTextarea('template_message', `{${variable}}`);
}

function insertEditVariable(variable) {
    insertIntoTextarea('edit_template_message', `{${variable}}`);
}

function insertIntoTextarea(textareaId, text) {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const currentValue = textarea.value;
    
    textarea.value = currentValue.substring(0, start) + text + currentValue.substring(end);
    
    // Move cursor to end of inserted text
    const newCursorPos = start + text.length;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    textarea.focus();
    
    // Trigger input event to update stats
    textarea.dispatchEvent(new Event('input'));
}

// Preview functions
function previewAddTemplate() {
    const message = document.getElementById('template_message').value;
    previewTemplate(message, 'add_template_preview');
}

function previewEditTemplate() {
    const message = document.getElementById('edit_template_message').value;
    previewTemplate(message, 'edit_template_preview');
}

function previewTemplate(message, previewId) {
    if (!message.trim()) {
        document.getElementById(previewId).innerHTML = '<em class="text-gray-500">Tulis template terlebih dahulu...</em>';
        return;
    }
    
    // Sample data for preview
    const sampleData = {
        name: 'John Doe',
        date: new Date().toLocaleDateString('id-ID'),
        time: '14:00',
        location: 'Ruang Meeting A',
        agenda: 'Review Progress Project Q1',
        deadline_date: new Date(Date.now() + 7*24*60*60*1000).toLocaleDateString('id-ID'),
        deadline_time: '17:00',
        item: 'Laporan Bulanan',
        announcement: 'Informasi penting mengenai update sistem yang akan dilakukan minggu depan.',
        reminder_text: 'Jangan lupa untuk menghadiri rapat evaluasi bulanan.',
        sender: 'Tim Management'
    };
    
    let processedMessage = message;
    Object.keys(sampleData).forEach(key => {
        const regex = new RegExp(`\\{${key}\\}`, 'g');
        processedMessage = processedMessage.replace(regex, sampleData[key]);
    });
    
    document.getElementById(previewId).innerHTML = processedMessage.replace(/\n/g, '<br>');
}

// Save and use functions
function saveAndUseTemplate() {
    const form = document.getElementById('addTemplateForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Extract variables
    const variables = extractTemplateVariables(data.message_template);
    if (variables.length > 0) {
        data.variables = variables;
    }
    
    fetch('api.php/template', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Template berhasil disimpan');
            
            // Use the template immediately
            useTemplate(data.message_template);
            
            hideModal('addTemplateModal');
            form.reset();
        } else {
            showError('Gagal menyimpan template: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function updateAndUseTemplate() {
    const form = document.getElementById('editTemplateForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Extract variables
    const variables = extractTemplateVariables(data.message_template);
    if (variables.length > 0) {
        data.variables = variables;
    }
    
    fetch('api.php/template', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Template berhasil diperbarui');
            
            // Use the template immediately
            useTemplate(data.message_template);
            
            hideModal('editTemplateModal');
        } else {
            showError('Gagal memperbarui template: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

// Enhanced editTemplate function to populate the edit modal
function editTemplate(id, title, messageTemplate, categoryId, usageCount, lastModified) {
    document.getElementById('edit_template_id').value = id;
    document.getElementById('edit_template_title').value = title;
    document.getElementById('edit_template_message').value = messageTemplate;
    document.getElementById('edit_template_category').value = categoryId || '';
    
    // Update statistics
    document.getElementById('edit_usage_count').textContent = (usageCount || 0) + ' kali';
    document.getElementById('edit_last_modified').textContent = lastModified || '-';
    
    // Update character count and variables
    updateEditTemplateStats();
    updateEditTemplateVariables();
    
    showModal('editTemplateModal');
}
</script>