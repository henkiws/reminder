<?php
// components/create-notification.php - Enhanced Version
?>

<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Buat Pemberitahuan Baru</h3>
            <p class="text-sm text-gray-600 mt-1">Jadwalkan atau kirim notifikasi WhatsApp otomatis</p>
        </div>
        <div class="flex items-center space-x-2">
            <button type="button" onclick="previewMessage()" class="bg-blue-100 text-blue-800 hover:bg-blue-200 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-eye mr-2"></i>Preview
            </button>
            <button type="button" onclick="resetForm()" class="bg-gray-100 text-gray-800 hover:bg-gray-200 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </button>
        </div>
    </div>
    
    <form id="notificationForm" class="space-y-6">
        <!-- Basic Information -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Informasi Dasar</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block mb-2 text-sm font-medium text-gray-900">
                        Judul Notifikasi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" required
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 transition-colors" 
                           placeholder="Masukkan judul notifikasi">
                    <p class="mt-1 text-xs text-gray-500">Judul untuk mengidentifikasi notifikasi ini</p>
                </div>
                
                <div>
                    <label for="priority" class="block mb-2 text-sm font-medium text-gray-900">Prioritas</label>
                    <select id="priority" name="priority" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                        <option value="normal" selected>Normal</option>
                        <option value="low">Rendah</option>
                        <option value="high">Tinggi</option>
                        <option value="urgent">Mendesak</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Template Selection -->
        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Template Pesan</h4>
            <div>
                <label for="template" class="block mb-2 text-sm font-medium text-gray-900">Pilih Template (Opsional)</label>
                <select id="template" name="template" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                    <option value="">-- Pilih Template atau Tulis Manual --</option>
                    <?php 
                    $currentCategory = '';
                    foreach ($templates as $template): 
                        if ($template['category_name'] != $currentCategory) {
                            if ($currentCategory != '') echo '</optgroup>';
                            $currentCategory = $template['category_name'] ?: 'Umum';
                            echo '<optgroup label="' . htmlspecialchars($currentCategory) . '">';
                        }
                    ?>
                        <option value="<?php echo $template['id']; ?>" 
                                data-template="<?php echo htmlspecialchars($template['message_template']); ?>"
                                data-variables="<?php echo htmlspecialchars(json_encode($template['variables'] ?? [])); ?>">
                            <?php echo htmlspecialchars($template['title']); ?>
                        </option>
                    <?php endforeach; 
                    if ($currentCategory != '') echo '</optgroup>';
                    ?>
                </select>
                <p class="mt-1 text-xs text-gray-500">Pilih template untuk mempercepat pembuatan pesan</p>
            </div>
        </div>

        <!-- Message Content -->
        <div>
            <label for="message" class="block mb-2 text-sm font-medium text-gray-900">
                Pesan <span class="text-red-500">*</span>
            </label>
            <textarea id="message" name="message" rows="6" required
                      class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 transition-colors" 
                      placeholder="Tulis pesan Anda di sini..."></textarea>
            
            <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-yellow-600 mt-0.5 mr-2"></i>
                    <div class="text-xs text-yellow-800">
                        <strong>Tips:</strong> Gunakan placeholder seperti <code>{name}</code>, <code>{date}</code>, <code>{time}</code> untuk personalisasi pesan.
                        <br>Contoh: "Halo {name}, ada rapat pada {date} pukul {time} di {location}"
                    </div>
                </div>
            </div>

            <!-- Character Count -->
            <div class="mt-2 flex justify-between items-center text-xs text-gray-500">
                <span>Karakter: <span id="char-count">0</span></span>
                <span>Estimasi SMS: <span id="sms-count">0</span> bagian</span>
            </div>
        </div>

        <!-- Template Variables Input Section -->
        <div id="template-variables-container" class="hidden bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                <i class="fas fa-code mr-2 text-green-600"></i>
                Isi Nilai untuk Template Variables
            </h4>
            <div id="template-variables-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Dynamic variable inputs will be created here by JavaScript -->
            </div>
        </div>

        <!-- Send To Type Selection -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Penerima Notifikasi</h4>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900">
                    Kirim Ke <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                        <input type="radio" name="send_to_type" value="contact" required
                               class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Kontak Individual</div>
                            <div class="text-xs text-gray-500">Kirim ke kontak yang dipilih</div>
                        </div>
                    </label>
                    <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                        <input type="radio" name="send_to_type" value="group" required
                               class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Grup WhatsApp</div>
                            <div class="text-xs text-gray-500">Kirim ke grup yang dipilih</div>
                        </div>
                    </label>
                    <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                        <input type="radio" name="send_to_type" value="both" required
                               class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Kontak & Grup</div>
                            <div class="text-xs text-gray-500">Kirim ke keduanya</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Contacts Selection -->
        <div id="contacts-section" class="hidden">
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-gray-900">Pilih Kontak</label>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="selectAllContacts()" class="text-xs text-blue-600 hover:text-blue-800">Pilih Semua</button>
                    <span class="text-gray-300">|</span>
                    <button type="button" onclick="unselectAllContacts()" class="text-xs text-gray-600 hover:text-gray-800">Batal Semua</button>
                </div>
            </div>
            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3 bg-white">
                <div class="mb-3">
                    <input type="text" id="contact-search" placeholder="Cari kontak..." 
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                </div>
                <div id="contacts-list">
                    <?php if (empty($contacts)): ?>
                        <div class="text-center py-4 text-gray-500 text-sm">
                            <i class="fas fa-address-book text-2xl mb-2 text-gray-300"></i>
                            <p>Belum ada kontak</p>
                            <button type="button" data-modal-target="addContactModal" data-modal-toggle="addContactModal" class="mt-2 text-blue-600 hover:text-blue-800 text-xs">
                                Tambah kontak pertama
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                        <label class="flex items-center mb-2 p-2 rounded hover:bg-gray-50 transition-colors contact-item">
                            <input type="checkbox" name="contacts[]" value="<?php echo $contact['id']; ?>" 
                                   class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                            <div class="ml-3 flex-1">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($contact['name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($contact['phone']); ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500">
                <span id="selected-contacts-count">0</span> kontak dipilih
            </div>
        </div>

        <!-- Groups Selection -->
        <div id="groups-section" class="hidden">
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-gray-900">Pilih Grup</label>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="selectAllGroups()" class="text-xs text-blue-600 hover:text-blue-800">Pilih Semua</button>
                    <span class="text-gray-300">|</span>
                    <button type="button" onclick="unselectAllGroups()" class="text-xs text-gray-600 hover:text-gray-800">Batal Semua</button>
                </div>
            </div>
            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3 bg-white">
                <div class="mb-3">
                    <input type="text" id="group-search" placeholder="Cari grup..." 
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                </div>
                <div id="groups-list">
                    <?php if (empty($groups)): ?>
                        <div class="text-center py-4 text-gray-500 text-sm">
                            <i class="fas fa-users text-2xl mb-2 text-gray-300"></i>
                            <p>Belum ada grup</p>
                            <button type="button" data-modal-target="addGroupModal" data-modal-toggle="addGroupModal" class="mt-2 text-blue-600 hover:text-blue-800 text-xs">
                                Tambah grup pertama
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($groups as $group): ?>
                        <label class="flex items-center mb-2 p-2 rounded hover:bg-gray-50 transition-colors group-item">
                            <input type="checkbox" name="groups[]" value="<?php echo $group['id']; ?>" 
                                   class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                            <div class="ml-3 flex-1">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($group['name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($group['group_id']); ?></div>
                                <?php if ($group['description']): ?>
                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($group['description']); ?></div>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500">
                <span id="selected-groups-count">0</span> grup dipilih
            </div>
        </div>

        <!-- Enhanced Schedule Settings -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Pengaturan Jadwal</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="scheduled_date" class="block mb-2 text-sm font-medium text-gray-900">Tanggal Kirim</label>
                    <input type="date" id="scheduled_date" name="scheduled_date" required
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                </div>
                <div>
                    <label for="scheduled_time" class="block mb-2 text-sm font-medium text-gray-900">Waktu Kirim</label>
                    <input type="time" id="scheduled_time" name="scheduled_time" required
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                </div>
            </div>
        </div>

        <!-- Enhanced Repeat Options -->
        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Pengaturan Pengulangan</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="repeat_type" class="block mb-2 text-sm font-medium text-gray-900">Jenis Pengulangan</label>
                    <select id="repeat_type" name="repeat_type" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                        <option value="once" selected>Sekali saja</option>
                        <option value="daily">Harian</option>
                        <option value="weekly">Mingguan</option>
                        <option value="monthly">Bulanan</option>
                        <option value="yearly">Tahunan</option>
                        <option value="custom">Kustom</option>
                    </select>
                </div>
                <div id="interval-container">
                    <label for="repeat_interval" class="block mb-2 text-sm font-medium text-gray-900">Interval</label>
                    <select id="repeat_interval" name="repeat_interval" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                        <option value="1">Setiap 1</option>
                    </select>
                </div>
            </div>

            <!-- Custom Unit Selector (Hidden by default) -->
            <div id="custom-unit-container" class="hidden mt-4">
                <label for="repeat_unit" class="block mb-2 text-sm font-medium text-gray-900">Unit Waktu</label>
                <select id="repeat_unit" name="repeat_unit" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                    <option value="day">Hari</option>
                    <option value="week">Minggu</option>
                    <option value="month">Bulan</option>
                    <option value="year">Tahun</option>
                </select>
            </div>

            <!-- Repeat Options Details -->
            <div id="repeat-options" class="hidden mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="repeat_count" class="block mb-2 text-sm font-medium text-gray-900">Jumlah Maksimal (Opsional)</label>
                        <input type="number" id="repeat_count" name="repeat_count" min="1" max="999"
                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5"
                               placeholder="Kosongkan untuk tanpa batas">
                        <p class="mt-1 text-xs text-gray-500">Maksimal berapa kali pengulangan</p>
                    </div>
                    <div>
                        <label for="repeat_until" class="block mb-2 text-sm font-medium text-gray-900">Sampai Tanggal (Opsional)</label>
                        <input type="date" id="repeat_until" name="repeat_until"
                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                        <p class="mt-1 text-xs text-gray-500">Batas akhir pengulangan</p>
                    </div>
                </div>
                
                <!-- Repeat Preview -->
                <div id="repeat-preview" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                    <div class="text-sm font-medium text-blue-900 mb-1">Preview Jadwal:</div>
                    <div id="repeat-preview-text" class="text-xs text-blue-800"></div>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-gray-200">
            <button type="submit" name="action" value="schedule" 
                    class="flex-1 text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-3 text-center transition-colors">
                <i class="fas fa-clock mr-2"></i>Jadwalkan Notifikasi
            </button>
            
            <button type="submit" name="action" value="send_now" 
                    class="flex-1 text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center transition-colors">
                <i class="fas fa-paper-plane mr-2"></i>Kirim Sekarang
            </button>
            
            <button type="button" onclick="saveDraft()" 
                    class="sm:flex-none text-gray-700 bg-gray-200 hover:bg-gray-300 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-3 text-center transition-colors">
                <i class="fas fa-save mr-2"></i>Simpan Draft
            </button>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div id="previewModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-eye mr-2 text-blue-600"></i>Preview Pesan
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="previewModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <div class="p-4 md:p-5 space-y-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <i class="fab fa-whatsapp text-green-600 text-xl mr-2"></i>
                        <h4 class="font-medium text-green-800">Pesan WhatsApp</h4>
                    </div>
                    <div id="previewContent" class="text-gray-700 whitespace-pre-line bg-white p-3 rounded border"></div>
                </div>
                
                <div id="previewStats" class="grid grid-cols-2 gap-4 text-sm">
                    <div class="bg-blue-50 p-3 rounded">
                        <div class="font-medium text-blue-900">Panjang Pesan</div>
                        <div class="text-blue-700"><span id="previewCharCount">0</span> karakter</div>
                    </div>
                    <div class="bg-yellow-50 p-3 rounded">
                        <div class="font-medium text-yellow-900">Estimasi SMS</div>
                        <div class="text-yellow-700"><span id="previewSmsCount">0</span> bagian</div>
                    </div>
                </div>
            </div>
            <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b">
                <button data-modal-hide="previewModal" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced create notification scripts
document.addEventListener('DOMContentLoaded', function() {
    initializeCreateNotificationForm();
});

function initializeCreateNotificationForm() {
    // Character counter
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            updateCharacterCount();
            updateVariableInputs();
        });
    }
    
    // Search functionality
    initializeSearchFunctionality();
    
    // Selection counters
    initializeSelectionCounters();
    
    // Repeat preview
    initializeRepeatPreview();
    
    // Template selector enhancement
    enhanceTemplateSelector();
}

function updateCharacterCount() {
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    const smsCount = document.getElementById('sms-count');
    
    if (messageTextarea && charCount && smsCount) {
        const length = messageTextarea.value.length;
        charCount.textContent = length;
        
        // Calculate SMS parts (160 chars per SMS for basic, 70 for unicode)
        let smsparts = 1;
        if (length > 160) {
            smsparts = Math.ceil(length / 153); // 153 chars per part in multipart SMS
        }
        smsCount.textContent = smsparts;
    }
}

function initializeSearchFunctionality() {
    // Contact search
    const contactSearch = document.getElementById('contact-search');
    if (contactSearch) {
        contactSearch.addEventListener('input', function() {
            filterItems('contact-item', this.value);
        });
    }
    
    // Group search
    const groupSearch = document.getElementById('group-search');
    if (groupSearch) {
        groupSearch.addEventListener('input', function() {
            filterItems('group-item', this.value);
        });
    }
}

function filterItems(className, searchTerm) {
    const items = document.querySelectorAll('.' + className);
    const term = searchTerm.toLowerCase();
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(term)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function initializeSelectionCounters() {
    // Contact selection counter
    const contactCheckboxes = document.querySelectorAll('input[name="contacts[]"]');
    const contactCounter = document.getElementById('selected-contacts-count');
    
    if (contactCheckboxes && contactCounter) {
        contactCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checked = document.querySelectorAll('input[name="contacts[]"]:checked').length;
                contactCounter.textContent = checked;
            });
        });
    }
    
    // Group selection counter
    const groupCheckboxes = document.querySelectorAll('input[name="groups[]"]');
    const groupCounter = document.getElementById('selected-groups-count');
    
    if (groupCheckboxes && groupCounter) {
        groupCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checked = document.querySelectorAll('input[name="groups[]"]:checked').length;
                groupCounter.textContent = checked;
            });
        });
    }
}

function initializeRepeatPreview() {
    const repeatType = document.getElementById('repeat_type');
    const repeatInterval = document.getElementById('repeat_interval');
    const repeatUnit = document.getElementById('repeat_unit');
    const repeatUntil = document.getElementById('repeat_until');
    const repeatCount = document.getElementById('repeat_count');
    
    [repeatType, repeatInterval, repeatUnit, repeatUntil, repeatCount].forEach(element => {
        if (element) {
            element.addEventListener('change', updateRepeatPreview);
        }
    });
}

function updateRepeatPreview() {
    const repeatType = document.getElementById('repeat_type').value;
    const repeatInterval = parseInt(document.getElementById('repeat_interval').value);
    const repeatUnit = document.getElementById('repeat_unit')?.value || 'day';
    const repeatUntil = document.getElementById('repeat_until').value;
    const repeatCount = document.getElementById('repeat_count').value;
    const scheduledDate = document.getElementById('scheduled_date').value;
    
    const preview = document.getElementById('repeat-preview');
    const previewText = document.getElementById('repeat-preview-text');
    
    if (repeatType === 'once') {
        preview.classList.add('hidden');
        return;
    }
    
    preview.classList.remove('hidden');
    
    let text = `Dimulai: ${scheduledDate || 'Tanggal yang dipilih'}\n`;
    
    if (repeatType === 'custom') {
        text += `Diulang setiap ${repeatInterval} ${repeatUnit}\n`;
    } else {
        const intervalText = {
            'daily': `Diulang setiap ${repeatInterval} hari`,
            'weekly': `Diulang setiap ${repeatInterval} minggu`,
            'monthly': `Diulang setiap ${repeatInterval} bulan`,
            'yearly': `Diulang setiap ${repeatInterval} tahun`
        };
        text += intervalText[repeatType] + '\n';
    }
    
    if (repeatUntil) {
        text += `Berakhir: ${repeatUntil}`;
    } else if (repeatCount) {
        text += `Maksimal ${repeatCount} kali pengulangan`;
    } else {
        text += 'Tanpa batas waktu';
    }
    
    previewText.textContent = text;
}

function enhanceTemplateSelector() {
    const templateSelect = document.getElementById('template');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const template = selectedOption.dataset.template;
                const variables = JSON.parse(selectedOption.dataset.variables || '[]');
                
                document.getElementById('message').value = template;
                updateCharacterCount();
                updateVariableInputs();
                
                // Highlight the textarea briefly
                const messageTextarea = document.getElementById('message');
                messageTextarea.classList.add('ring-2', 'ring-green-500');
                setTimeout(() => {
                    messageTextarea.classList.remove('ring-2', 'ring-green-500');
                }, 1000);
            }
        });
    }
}

// Selection helper functions
function selectAllContacts() {
    const checkboxes = document.querySelectorAll('input[name="contacts[]"]');
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('.contact-item').style.display !== 'none') {
            checkbox.checked = true;
        }
    });
    updateSelectionCount('contacts');
}

function unselectAllContacts() {
    const checkboxes = document.querySelectorAll('input[name="contacts[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    updateSelectionCount('contacts');
}

function selectAllGroups() {
    const checkboxes = document.querySelectorAll('input[name="groups[]"]');
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('.group-item').style.display !== 'none') {
            checkbox.checked = true;
        }
    });
    updateSelectionCount('groups');
}

function unselectAllGroups() {
    const checkboxes = document.querySelectorAll('input[name="groups[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    updateSelectionCount('groups');
}

function updateSelectionCount(type) {
    const checkboxes = document.querySelectorAll(`input[name="${type}[]"]:checked`);
    const counter = document.getElementById(`selected-${type}-count`);
    if (counter) {
        counter.textContent = checkboxes.length;
    }
}

// Form helper functions
function resetForm() {
    if (confirm('Apakah Anda yakin ingin mereset form? Semua data akan hilang.')) {
        document.getElementById('notificationForm').reset();
        setDefaultDateTime();
        hideRecipientSections();
        clearTemplateVariables();
        updateCharacterCount();
        updateSelectionCount('contacts');
        updateSelectionCount('groups');
        
        // Reset repeat options
        document.getElementById('repeat-options').classList.add('hidden');
        document.getElementById('custom-unit-container').classList.add('hidden');
        document.getElementById('repeat-preview').classList.add('hidden');
    }
}

function saveDraft() {
    const formData = new FormData(document.getElementById('notificationForm'));
    const data = Object.fromEntries(formData);
    
    // Save to localStorage for now (you can implement server-side storage)
    localStorage.setItem('notification_draft', JSON.stringify(data));
    showSuccess('Draft berhasil disimpan');
}

function loadDraft() {
    const draft = localStorage.getItem('notification_draft');
    if (draft) {
        const data = JSON.parse(draft);
        // Populate form with draft data
        Object.keys(data).forEach(key => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element) {
                element.value = data[key];
            }
        });
        showSuccess('Draft berhasil dimuat');
    }
}

// Enhanced preview function
function previewMessage() {
    const messageTextarea = document.getElementById('message');
    const titleInput = document.getElementById('title');
    
    if (!messageTextarea || !messageTextarea.value.trim()) {
        showError('Mohon tulis pesan terlebih dahulu');
        return;
    }
    
    let message = messageTextarea.value;
    
    // Get all template variable inputs
    const variableInputs = document.querySelectorAll('[name^="template_vars["]');
    
    variableInputs.forEach(input => {
        const variable = input.name.match(/template_vars\[(\w+)\]/)[1];
        const value = input.value || `{${variable}}`;
        message = message.replace(new RegExp(`\\{${variable}\\}`, 'g'), value);
    });
    
    // Replace name with sample name if not filled
    message = message.replace(/\{name\}/g, 'John Doe (Contoh)');
    
    // Show preview
    const previewContent = document.getElementById('previewContent');
    const previewCharCount = document.getElementById('previewCharCount');
    const previewSmsCount = document.getElementById('previewSmsCount');
    
    if (previewContent) {
        previewContent.innerHTML = message.replace(/\n/g, '<br>');
    }
    
    if (previewCharCount) {
        previewCharCount.textContent = message.length;
    }
    
    if (previewSmsCount) {
        const smsCount = message.length > 160 ? Math.ceil(message.length / 153) : 1;
        previewSmsCount.textContent = smsCount;
    }
    
    showModal('previewModal');
}
</script>