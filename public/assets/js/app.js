// public/assets/js/app.js - ENHANCED VERSION WITH SMOOTH MODALS

// Global state management
window.app = {
    modals: {},
    forms: {},
    state: {
        currentUser: null,
        notifications: [],
        contacts: [],
        groups: [],
        templates: []
    }
};

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing WhatsApp Notification System...');
    
    initializeModalSystem();
    initializeFormHandlers();
    initializeTemplateSelector();
    initializeSendToTypeHandler();
    initializeRepeatOptions();
    initializePhoneFormatting();
    initializeTabHandling();
    setDefaultDateTime();
    
    console.log('✅ Application initialized successfully');
});

// ENHANCED MODAL MANAGEMENT SYSTEM
function initializeModalSystem() {
    console.log('🔧 Initializing modal system...');
    
    // List of all modals in the system
    const modalConfigs = [
        { id: 'addContactModal', backdrop: true, keyboard: true },
        { id: 'editContactModal', backdrop: true, keyboard: true },
        { id: 'addGroupModal', backdrop: true, keyboard: true },
        { id: 'editGroupModal', backdrop: true, keyboard: true },
        { id: 'addTemplateModal', backdrop: true, keyboard: true },
        { id: 'editTemplateModal', backdrop: true, keyboard: true },
        { id: 'addUserModal', backdrop: true, keyboard: true },
        { id: 'editUserModal', backdrop: true, keyboard: true },
        { id: 'userActivityModal', backdrop: true, keyboard: true },
        { id: 'previewModal', backdrop: true, keyboard: true },
        { id: 'successModal', backdrop: 'static', keyboard: false },
        { id: 'errorModal', backdrop: 'static', keyboard: false },
        { id: 'confirmModal', backdrop: 'static', keyboard: false }
    ];
    
    // Initialize all modals
    modalConfigs.forEach(config => {
        const element = document.getElementById(config.id);
        if (element) {
            try {
                const modal = new Modal(element, {
                    backdrop: config.backdrop,
                    keyboard: config.keyboard,
                    focus: true,
                    placement: 'center',
                    onShow: () => {
                        console.log(`📱 Modal opened: ${config.id}`);
                        document.body.style.overflow = 'hidden';
                        
                        // Auto-focus first input
                        setTimeout(() => {
                            const firstInput = element.querySelector('input:not([type="hidden"]), textarea, select');
                            if (firstInput) firstInput.focus();
                        }, 150);
                    },
                    onHide: () => {
                        console.log(`📱 Modal closed: ${config.id}`);
                        document.body.style.overflow = '';
                        
                        // Clear form errors
                        clearFormErrors(element);
                    }
                });
                
                window.app.modals[config.id] = modal;
                console.log(`✅ Modal initialized: ${config.id}`);
            } catch (error) {
                console.error(`❌ Error initializing modal ${config.id}:`, error);
            }
        }
    });
    
    // Setup global modal event handlers
    setupModalEventHandlers();
}

function setupModalEventHandlers() {
    // Handle close buttons with data-modal-hide
    document.addEventListener('click', function(e) {
        const hideButton = e.target.closest('[data-modal-hide]');
        if (hideButton) {
            const modalId = hideButton.getAttribute('data-modal-hide');
            hideModal(modalId);
        }
        
        // Handle show buttons
        const showButton = e.target.closest('[data-modal-target]');
        if (showButton) {
            const modalId = showButton.getAttribute('data-modal-target');
            showModal(modalId);
        }
    });
    
    // Handle ESC key globally
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = Object.keys(window.app.modals).filter(modalId => {
                const element = document.getElementById(modalId);
                return element && !element.classList.contains('hidden');
            });
            
            if (openModals.length > 0) {
                hideModal(openModals[openModals.length - 1]); // Close topmost modal
            }
        }
    });
}

// Enhanced modal functions
function showModal(modalId) {
    const modal = window.app.modals[modalId];
    if (modal) {
        try {
            modal.show();
            return true;
        } catch (error) {
            console.error(`❌ Error showing modal ${modalId}:`, error);
            return false;
        }
    } else {
        console.warn(`⚠️ Modal not found: ${modalId}`);
        return false;
    }
}

function hideModal(modalId) {
    const modal = window.app.modals[modalId];
    if (modal) {
        try {
            modal.hide();
            return true;
        } catch (error) {
            console.error(`❌ Error hiding modal ${modalId}:`, error);
            return false;
        }
    } else {
        console.warn(`⚠️ Modal not found: ${modalId}`);
        return false;
    }
}

function hideAllModals() {
    Object.keys(window.app.modals).forEach(modalId => {
        hideModal(modalId);
    });
}

// ENHANCED FORM HANDLING SYSTEM
function initializeFormHandlers() {
    console.log('📋 Initializing form handlers...');
    
    const formConfigs = [
        { id: 'notificationForm', handler: handleNotificationSubmit },
        { id: 'addContactForm', handler: handleAddContact },
        { id: 'editContactForm', handler: handleEditContact },
        { id: 'addGroupForm', handler: handleAddGroup },
        { id: 'editGroupForm', handler: handleEditGroup },
        { id: 'addTemplateForm', handler: handleAddTemplate },
        { id: 'editTemplateForm', handler: handleEditTemplate },
        { id: 'addUserForm', handler: handleAddUser },
        { id: 'editUserForm', handler: handleEditUser }
    ];
    
    formConfigs.forEach(config => {
        const form = document.getElementById(config.id);
        if (form) {
            form.addEventListener('submit', config.handler);
            
            // Add real-time validation
            addFormValidation(form);
            
            console.log(`✅ Form handler added: ${config.id}`);
        }
    });
}

function addFormValidation(form) {
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Field ini wajib diisi';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Format email tidak valid';
        }
    }
    
    // Phone validation
    if (field.type === 'tel' && value) {
        const phoneRegex = /^(\+?62|0)[0-9]{8,13}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            isValid = false;
            errorMessage = 'Format nomor telepon tidak valid';
        }
    }
    
    // Password validation
    if (field.type === 'password' && value && value.length < 6) {
        isValid = false;
        errorMessage = 'Password minimal 6 karakter';
    }
    
    // Show/hide error
    showFieldError(field, isValid ? null : errorMessage);
    
    return isValid;
}

function showFieldError(field, errorMessage) {
    // Remove existing error
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    field.classList.remove('error', 'border-red-500');
    
    if (errorMessage) {
        field.classList.add('error', 'border-red-500');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-red-500 text-xs mt-1';
        errorDiv.textContent = errorMessage;
        
        field.parentNode.appendChild(errorDiv);
    } else {
        field.classList.add('border-green-500');
        setTimeout(() => field.classList.remove('border-green-500'), 2000);
    }
}

function clearFormErrors(container) {
    const errors = container.querySelectorAll('.field-error');
    errors.forEach(error => error.remove());
    
    const errorFields = container.querySelectorAll('.error');
    errorFields.forEach(field => {
        field.classList.remove('error', 'border-red-500');
    });
}

function validateForm(form) {
    const fields = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// ENHANCED NOTIFICATION FORM HANDLING
function handleNotificationSubmit(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        showError('Mohon perbaiki kesalahan pada form');
        return;
    }
    
    const formData = new FormData(e.target);
    const data = {};
    
    // Convert FormData to object
    for (let [key, value] of formData.entries()) {
        if (key.endsWith('[]')) {
            const arrayKey = key.slice(0, -2);
            if (!data[arrayKey]) data[arrayKey] = [];
            data[arrayKey].push(value);
        } else {
            data[key] = value;
        }
    }
    
    // Get action type from the clicked button
    const action = document.activeElement.value || 'schedule';
    data.action = action;
    
    // Collect template variables
    const templateVars = {};
    const variableInputs = document.querySelectorAll('[name^="template_vars["]');
    variableInputs.forEach(input => {
        const match = input.name.match(/template_vars\[(\w+)\]/);
        if (match && input.value.trim()) {
            templateVars[match[1]] = input.value.trim();
        }
    });
    
    if (Object.keys(templateVars).length > 0) {
        data.template_vars = templateVars;
    }
    
    // Enhanced validation
    if (!data.title || !data.message || !data.send_to_type) {
        showError('Harap lengkapi semua field yang wajib diisi');
        return;
    }
    
    // Validate recipients
    if (data.send_to_type === 'contact' && (!data.contacts || data.contacts.length === 0)) {
        showError('Harap pilih minimal satu kontak');
        return;
    }
    
    if (data.send_to_type === 'group' && (!data.groups || data.groups.length === 0)) {
        showError('Harap pilih minimal satu grup');
        return;
    }
    
    if (data.send_to_type === 'both' && 
        (!data.contacts || data.contacts.length === 0) && 
        (!data.groups || data.groups.length === 0)) {
        showError('Harap pilih minimal satu kontak atau grup');
        return;
    }
    
    // Validate schedule time
    if (action === 'schedule') {
        const scheduledDateTime = new Date(data.scheduled_date + ' ' + data.scheduled_time);
        const now = new Date();
        
        if (scheduledDateTime <= now) {
            showError('Waktu penjadwalan harus di masa depan');
            return;
        }
    }
    
    // Show loading state
    const submitBtn = document.activeElement;
    const originalText = submitBtn.innerHTML;
    setButtonLoading(submitBtn, true);
    
    // Send request
    fetch('api.php/notification', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (action === 'send_now') {
                if (result.send_result && result.send_result.success) {
                    showSuccess(`Notifikasi berhasil dikirim ke ${result.send_result.sent} penerima`);
                } else {
                    showError('Notifikasi disimpan tapi gagal dikirim: ' + (result.send_result?.error || 'Unknown error'));
                }
            } else {
                showSuccess('Notifikasi berhasil dijadwalkan');
            }
            
            // Reset form
            e.target.reset();
            setDefaultDateTime();
            hideRecipientSections();
            clearTemplateVariables();
            
            // Refresh page after 2 seconds
            setTimeout(() => location.reload(), 2000);
        } else {
            showError('Gagal menyimpan notifikasi: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false, originalText);
    });
}

// ENHANCED REPEAT SYSTEM
function initializeRepeatOptions() {
    const repeatSelect = document.getElementById('repeat_type');
    const repeatOptions = document.getElementById('repeat-options');
    const intervalContainer = document.getElementById('interval-container');
    
    if (repeatSelect && repeatOptions) {
        repeatSelect.addEventListener('change', function() {
            updateRepeatOptions(this.value);
        });
        
        // Initialize with current value
        updateRepeatOptions(repeatSelect.value);
    }
}

function updateRepeatOptions(repeatType) {
    const repeatOptions = document.getElementById('repeat-options');
    const intervalSelect = document.getElementById('repeat_interval');
    const unitSelect = document.getElementById('repeat_unit');
    
    if (!repeatOptions) return;
    
    if (repeatType === 'once') {
        repeatOptions.classList.add('hidden');
    } else {
        repeatOptions.classList.remove('hidden');
        
        // Update interval options based on repeat type
        if (intervalSelect) {
            updateIntervalOptions(repeatType, intervalSelect);
        }
        
        // Show/hide unit selector for custom repeat
        if (unitSelect) {
            if (repeatType === 'custom') {
                unitSelect.parentElement.classList.remove('hidden');
            } else {
                unitSelect.parentElement.classList.add('hidden');
            }
        }
    }
}

function updateIntervalOptions(repeatType, intervalSelect) {
    const options = {
        'daily': [
            { value: 1, text: 'Setiap hari' },
            { value: 2, text: 'Setiap 2 hari' },
            { value: 3, text: 'Setiap 3 hari' },
            { value: 7, text: 'Setiap minggu (7 hari)' }
        ],
        'weekly': [
            { value: 1, text: 'Setiap minggu' },
            { value: 2, text: 'Setiap 2 minggu' },
            { value: 3, text: 'Setiap 3 minggu' },
            { value: 4, text: 'Setiap bulan (4 minggu)' }
        ],
        'monthly': [
            { value: 1, text: 'Setiap bulan' },
            { value: 2, text: 'Setiap 2 bulan' },
            { value: 3, text: 'Setiap 3 bulan (kuartal)' },
            { value: 6, text: 'Setiap 6 bulan' },
            { value: 12, text: 'Setiap tahun (12 bulan)' }
        ],
        'yearly': [
            { value: 1, text: 'Setiap tahun' },
            { value: 2, text: 'Setiap 2 tahun' },
            { value: 5, text: 'Setiap 5 tahun' }
        ],
        'custom': [
            { value: 1, text: '1' },
            { value: 2, text: '2' },
            { value: 3, text: '3' },
            { value: 4, text: '4' },
            { value: 5, text: '5' },
            { value: 6, text: '6' },
            { value: 7, text: '7' },
            { value: 10, text: '10' },
            { value: 14, text: '14' },
            { value: 15, text: '15' },
            { value: 30, text: '30' }
        ]
    };
    
    const currentValue = intervalSelect.value;
    intervalSelect.innerHTML = '';
    
    const intervalOptions = options[repeatType] || options['custom'];
    intervalOptions.forEach(option => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        if (option.value == currentValue) {
            optionElement.selected = true;
        }
        intervalSelect.appendChild(optionElement);
    });
}

// TEMPLATE MANAGEMENT FUNCTIONS
function initializeTemplateSelector() {
    const templateSelect = document.getElementById('template');
    const messageTextarea = document.getElementById('message');
    
    if (templateSelect && messageTextarea) {
        templateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const template = selectedOption.dataset.template;
                messageTextarea.value = template;
                
                // Trigger variable inputs update
                updateVariableInputs();
                
                // Show animation
                messageTextarea.classList.add('border-green-500');
                setTimeout(() => messageTextarea.classList.remove('border-green-500'), 1000);
            }
        });
    }
}

function updateVariableInputs() {
    const messageTextarea = document.getElementById('message');
    if (!messageTextarea) return;
    
    const message = messageTextarea.value;
    const variables = extractTemplateVariables(message);
    createVariableInputs(variables);
}

function extractTemplateVariables(text) {
    const regex = /\{(\w+)\}/g;
    const variables = [];
    let match;
    
    while ((match = regex.exec(text)) !== null) {
        if (!variables.includes(match[1])) {
            variables.push(match[1]);
        }
    }
    return variables;
}

function createVariableInputs(variables) {
    const container = document.getElementById('template-variables-container');
    const grid = document.getElementById('template-variables-grid');
    
    if (!container || !grid) return;
    
    grid.innerHTML = '';
    
    if (variables.length === 0) {
        container.classList.add('hidden');
        return;
    }
    
    container.classList.remove('hidden');
    
    variables.forEach(variable => {
        const div = document.createElement('div');
        div.className = 'variable-input-group';
        
        const label = document.createElement('label');
        label.className = 'block text-sm font-medium text-gray-700 mb-1';
        label.textContent = getVariableLabel(variable);
        label.setAttribute('for', 'var_' + variable);
        
        let input;
        if (variable === 'agenda' || variable === 'announcement' || variable === 'description') {
            input = document.createElement('textarea');
            input.rows = 3;
            input.className = 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 resize-y';
        } else {
            input = document.createElement('input');
            input.type = getVariableInputType(variable);
            input.className = 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5';
        }
        
        input.name = 'template_vars[' + variable + ']';
        input.id = 'var_' + variable;
        input.placeholder = getVariablePlaceholder(variable);
        
        // Set default values for some variables
        if (variable === 'date') {
            input.value = new Date().toLocaleDateString('id-ID');
        } else if (variable === 'time') {
            input.value = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
        }
        
        // Add help text for some variables
        const helpText = getVariableHelpText(variable);
        if (helpText) {
            const help = document.createElement('p');
            help.className = 'text-xs text-gray-500 mt-1';
            help.textContent = helpText;
            div.appendChild(label);
            div.appendChild(input);
            div.appendChild(help);
        } else {
            div.appendChild(label);
            div.appendChild(input);
        }
        
        grid.appendChild(div);
    });
    
    // Animate container appearance
    container.style.opacity = '0';
    container.style.transform = 'translateY(-10px)';
    setTimeout(() => {
        container.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';
    }, 50);
}

function getVariableLabel(variable) {
    const labels = {
        'name': 'Nama Penerima',
        'date': 'Tanggal',
        'time': 'Waktu',
        'location': 'Lokasi/Tempat',
        'agenda': 'Agenda',
        'deadline_date': 'Tanggal Deadline',
        'deadline_time': 'Waktu Deadline',
        'announcement': 'Teks Pengumuman',
        'event_name': 'Nama Acara',
        'task_title': 'Judul Tugas',
        'task_description': 'Deskripsi Tugas',
        'item': 'Item/Nama Tugas',
        'reminder_text': 'Teks Pengingat',
        'period': 'Periode',
        'report_content': 'Isi Laporan',
        'sender': 'Nama Pengirim'
    };
    return labels[variable] || variable.replace('_', ' ').toUpperCase();
}

function getVariableInputType(variable) {
    if (variable === 'date' || variable === 'deadline_date') {
        return 'date';
    } else if (variable === 'time' || variable === 'deadline_time') {
        return 'time';
    } else if (variable === 'email') {
        return 'email';
    } else if (variable === 'phone') {
        return 'tel';
    }
    return 'text';
}

function getVariablePlaceholder(variable) {
    const placeholders = {
        'name': 'Nama akan diambil dari kontak',
        'date': 'DD/MM/YYYY',
        'time': 'HH:MM',
        'location': 'Contoh: Ruang Meeting A',
        'agenda': 'Contoh: Review Project Progress',
        'deadline_date': 'DD/MM/YYYY',
        'deadline_time': 'HH:MM',
        'announcement': 'Teks pengumuman lengkap',
        'event_name': 'Nama acara atau kegiatan',
        'task_title': 'Judul atau nama tugas',
        'item': 'Nama item yang harus dikumpulkan',
        'reminder_text': 'Teks pengingat',
        'sender': 'Nama pengirim pesan'
    };
    return placeholders[variable] || 'Masukkan nilai untuk ' + variable;
}

function getVariableHelpText(variable) {
    const helpTexts = {
        'name': 'Akan otomatis diisi dengan nama dari kontak',
        'date': 'Format: DD/MM/YYYY',
        'time': 'Format: HH:MM (24 jam)',
        'deadline_date': 'Tanggal batas waktu',
        'deadline_time': 'Waktu batas waktu'
    };
    return helpTexts[variable] || null;
}

function clearTemplateVariables() {
    const container = document.getElementById('template-variables-container');
    if (container) {
        container.classList.add('hidden');
        const grid = document.getElementById('template-variables-grid');
        if (grid) grid.innerHTML = '';
    }
}

// MESSAGE PREVIEW FUNCTION
function previewMessage() {
    const messageTextarea = document.getElementById('message');
    if (!messageTextarea) return;
    
    let message = messageTextarea.value;
    
    // Get all template variable inputs
    const variableInputs = document.querySelectorAll('[name^="template_vars["]');
    
    variableInputs.forEach(input => {
        const variable = input.name.match(/template_vars\[(\w+)\]/)[1];
        const value = input.value || `{${variable}}`;
        message = message.replace(new RegExp(`\\{${variable}\\}`, 'g'), value);
    });
    
    // Show preview with sample contact name
    message = message.replace(/\{name\}/g, 'John Doe (Contoh)');
    
    // Show preview modal
    const previewContent = document.getElementById('previewContent');
    if (previewContent) {
        previewContent.innerHTML = message.replace(/\n/g, '<br>');
    }
    
    showModal('previewModal');
}

// CONTACT MANAGEMENT FUNCTIONS
function handleAddContact(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        return;
    }
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Format phone number
    data.phone = formatPhoneNumber(data.phone);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, true);
    
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
            e.target.reset();
            hideModal('addContactModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan kontak: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
}

function handleEditContact(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        return;
    }
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Format phone number
    data.phone = formatPhoneNumber(data.phone);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, true);
    
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
            showError('Gagal memperbarui kontak: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
}

function editContact(id, name, phone) {
    document.getElementById('edit_contact_id').value = id;
    document.getElementById('edit_contact_name').value = name;
    document.getElementById('edit_contact_phone').value = phone;
    
    showModal('editContactModal');
}

function deleteContact(id, name) {
    showConfirmModal(
        'Hapus Kontak',
        `Apakah Anda yakin ingin menghapus kontak "${name}"?`,
        'Hapus',
        'danger',
        () => {
            fetch(`api.php/contact/${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Kontak berhasil dihapus');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal menghapus kontak: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

// GROUP MANAGEMENT FUNCTIONS
function handleAddGroup(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        return;
    }
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, true);
    
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
            e.target.reset();
            hideModal('addGroupModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan grup: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
}

function handleEditGroup(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        return;
    }
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, true);
    
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
            showError('Gagal memperbarui grup: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
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
            fetch(`api.php/group/${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Grup berhasil dihapus');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal menghapus grup: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

// TEMPLATE MANAGEMENT FUNCTIONS
function handleAddTemplate(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        return;
    }
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Extract variables from template
    const variables = extractTemplateVariables(data.message_template);
    if (variables.length > 0) {
        data.variables = variables;
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, true);
    
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
            showSuccess('Template berhasil ditambahkan');
            e.target.reset();
            hideModal('addTemplateModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan template: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
}

function handleEditTemplate(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        return;
    }
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Extract variables from template
    const variables = extractTemplateVariables(data.message_template);
    if (variables.length > 0) {
        data.variables = variables;
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, true);
    
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
            hideModal('editTemplateModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal memperbarui template: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
}

function editTemplate(id, title, messageTemplate, categoryId) {
    document.getElementById('edit_template_id').value = id;
    document.getElementById('edit_template_title').value = title;
    document.getElementById('edit_template_message').value = messageTemplate;
    document.getElementById('edit_template_category').value = categoryId || '';
    
    // Update variables preview
    updateTemplateVariablesPreview(messageTemplate);
    
    showModal('editTemplateModal');
}

function deleteTemplate(id, title) {
    showConfirmModal(
        'Hapus Template',
        `Apakah Anda yakin ingin menghapus template "${title}"?`,
        'Hapus',
        'danger',
        () => {
            fetch(`api.php/template/${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Template berhasil dihapus');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal menghapus template: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

function useTemplate(template) {
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        messageTextarea.value = template;
        
        // Update variable inputs
        updateVariableInputs();
        
        // Switch to create notification tab
        const createTab = document.getElementById('create-tab');
        if (createTab) {
            createTab.click();
        }
        
        // Scroll to message field with animation
        setTimeout(() => {
            messageTextarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
            messageTextarea.focus();
            
            // Add highlight effect
            messageTextarea.classList.add('border-green-500', 'ring-2', 'ring-green-200');
            setTimeout(() => {
                messageTextarea.classList.remove('border-green-500', 'ring-2', 'ring-green-200');
            }, 2000);
        }, 100);
    }
}

function updateTemplateVariablesPreview(message) {
    const variables = extractTemplateVariables(message);
    const preview = document.getElementById('template-variables-preview');
    const variablesList = document.getElementById('variables-list');
    
    if (preview && variablesList) {
        if (variables.length > 0) {
            preview.classList.remove('hidden');
            variablesList.innerHTML = variables.map(variable => 
                `<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">{${variable}}</span>`
            ).join('');
        } else {
            preview.classList.add('hidden');
        }
    }
}

// USER MANAGEMENT FUNCTIONS
function handleAddUser(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        return;
    }
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, true);
    
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
            showSuccess('User berhasil ditambahkan');
            e.target.reset();
            hideModal('addUserModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan user: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
}

function handleEditUser(e) {
    e.preventDefault();
    
    if (!validateForm(e.target)) {
        return;
    }
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Convert checkbox to boolean
    data.is_active = formData.has('is_active');
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, true);
    
    fetch(`api.php/user/${data.id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('User berhasil diperbarui');
            hideModal('editUserModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal memperbarui user: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
}

function editUser(id, fullName, email, username, isActive) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_user_full_name').value = fullName;
    document.getElementById('edit_user_email').value = email;
    document.getElementById('edit_user_username').value = username;
    document.getElementById('edit_user_active').checked = isActive;
    
    showModal('editUserModal');
}

function deleteUser(id, username) {
    showConfirmModal(
        'Hapus User',
        `Apakah Anda yakin ingin menghapus user "${username}"?`,
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
                    showError('Gagal menghapus user: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

// NOTIFICATION FUNCTIONS
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

function viewNotificationDetails(id) {
    // This could open a modal with detailed notification info and logs
    window.open(`notification-details.php?id=${id}`, '_blank');
}

// UTILITY FUNCTIONS
function initializeSendToTypeHandler() {
    const sendToRadios = document.querySelectorAll('input[name="send_to_type"]');
    
    sendToRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const contactsSection = document.getElementById('contacts-section');
            const groupsSection = document.getElementById('groups-section');
            
            if (contactsSection && groupsSection) {
                // Hide all sections first
                contactsSection.classList.add('hidden');
                groupsSection.classList.add('hidden');
                
                // Show relevant sections with animation
                if (this.value === 'contact' || this.value === 'both') {
                    setTimeout(() => {
                        contactsSection.classList.remove('hidden');
                        contactsSection.style.opacity = '0';
                        setTimeout(() => {
                            contactsSection.style.transition = 'opacity 0.3s ease';
                            contactsSection.style.opacity = '1';
                        }, 50);
                    }, 100);
                }
                
                if (this.value === 'group' || this.value === 'both') {
                    setTimeout(() => {
                        groupsSection.classList.remove('hidden');
                        groupsSection.style.opacity = '0';
                        setTimeout(() => {
                            groupsSection.style.transition = 'opacity 0.3s ease';
                            groupsSection.style.opacity = '1';
                        }, 50);
                    }, 150);
                }
            }
        });
    });
}

function hideRecipientSections() {
    const contactsSection = document.getElementById('contacts-section');
    const groupsSection = document.getElementById('groups-section');
    
    if (contactsSection) contactsSection.classList.add('hidden');
    if (groupsSection) groupsSection.classList.add('hidden');
}

function setDefaultDateTime() {
    const now = new Date();
    const date = now.toISOString().split('T')[0];
    const time = now.toTimeString().slice(0, 5);
    
    const dateInput = document.getElementById('scheduled_date');
    const timeInput = document.getElementById('scheduled_time');
    
    if (dateInput && !dateInput.value) dateInput.value = date;
    if (timeInput && !timeInput.value) timeInput.value = time;
}

function initializePhoneFormatting() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = formatPhoneNumber(this.value);
        });
    });
}

function formatPhoneNumber(phone) {
    // Remove all non-numeric characters
    let cleaned = phone.replace(/\D/g, '');
    
    // Remove leading zero and replace with 62
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    
    // If doesn't start with 62, add it
    if (!cleaned.startsWith('62') && cleaned.length > 0) {
        cleaned = '62' + cleaned;
    }
    
    return cleaned;
}

function initializeTabHandling() {
    const tabButtons = document.querySelectorAll('[data-tabs-target]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-tabs-target');
            console.log('Tab clicked:', target);
            
            // Add active state animation
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
}

// ENHANCED UI FUNCTIONS
function showSuccess(message) {
    showNotificationModal('success', 'Berhasil!', message, 'fas fa-check-circle', 'text-green-500');
}

function showError(message) {
    showNotificationModal('error', 'Terjadi Kesalahan!', message, 'fas fa-exclamation-circle', 'text-red-500');
}

function showNotificationModal(type, title, message, icon, iconClass) {
    const modalId = type + 'Modal';
    const titleElement = document.querySelector(`#${modalId} h3`);
    const messageElement = document.querySelector(`#${modalId} #${type}Message`);
    const iconElement = document.querySelector(`#${modalId} i`);
    
    if (titleElement) titleElement.textContent = title;
    if (messageElement) messageElement.textContent = message;
    if (iconElement) {
        iconElement.className = icon + ' text-5xl';
        iconElement.parentElement.className = iconElement.parentElement.className.replace(/text-\w+-\d+/, iconClass);
    }
    
    showModal(modalId);
    
    // Auto-hide success messages after 3 seconds
    if (type === 'success') {
        setTimeout(() => {
            hideModal(modalId);
        }, 3000);
    }
}

function showConfirmModal(title, message, confirmText, confirmType, onConfirm) {
    // Create confirm modal if it doesn't exist
    let confirmModal = document.getElementById('confirmModal');
    if (!confirmModal) {
        createConfirmModal();
        confirmModal = document.getElementById('confirmModal');
    }
    
    const titleElement = confirmModal.querySelector('h3');
    const messageElement = confirmModal.querySelector('.confirm-message');
    const confirmButton = confirmModal.querySelector('.confirm-button');
    const cancelButton = confirmModal.querySelector('.cancel-button');
    
    if (titleElement) titleElement.textContent = title;
    if (messageElement) messageElement.textContent = message;
    if (confirmButton) {
        confirmButton.textContent = confirmText;
        
        // Set button style based on type
        const typeClasses = {
            'primary': 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-300',
            'danger': 'bg-red-600 hover:bg-red-700 focus:ring-red-300',
            'warning': 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-300',
            'success': 'bg-green-600 hover:bg-green-700 focus:ring-green-300'
        };
        
        confirmButton.className = `text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center confirm-button ${typeClasses[confirmType] || typeClasses.primary}`;
        
        // Remove existing event listeners
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
        
        // Add new event listener
        newConfirmButton.addEventListener('click', function() {
            hideModal('confirmModal');
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }
    
    if (cancelButton) {
        cancelButton.onclick = () => hideModal('confirmModal');
    }
    
    showModal('confirmModal');
}

function createConfirmModal() {
    const modalHTML = `
        <div id="confirmModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-white rounded-lg shadow">
                    <div class="p-4 md:p-5 text-center">
                        <div class="mx-auto mb-4 text-gray-400 w-12 h-12">
                            <i class="fas fa-question-circle text-5xl"></i>
                        </div>
                        <h3 class="mb-5 text-lg font-normal text-gray-500">Konfirmasi</h3>
                        <p class="mb-5 text-sm text-gray-500 confirm-message">Apakah Anda yakin?</p>
                        <div class="flex justify-center space-x-4">
                            <button type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 cancel-button">
                                Batal
                            </button>
                            <button type="button" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center confirm-button">
                                Konfirmasi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Initialize the modal
    const modalElement = document.getElementById('confirmModal');
    if (modalElement) {
        const modal = new Modal(modalElement);
        window.app.modals['confirmModal'] = modal;
    }
}

function setButtonLoading(button, isLoading, originalText = null) {
    if (isLoading) {
        button.dataset.originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
    } else {
        button.disabled = false;
        button.innerHTML = originalText || button.dataset.originalText || button.innerHTML;
    }
}

// ENHANCED EVENT LISTENERS
document.addEventListener('DOMContentLoaded', function() {
    // Add template message event listeners
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        messageTextarea.addEventListener('input', updateVariableInputs);
        updateVariableInputs(); // Initial check
    }
    
    // Add template selector event listeners
    const templateSelect = document.getElementById('template');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            setTimeout(updateVariableInputs, 100);
        });
    }
    
    // Add template message event listeners for template forms
    const templateMessageInputs = document.querySelectorAll('#template_message, #edit_template_message');
    templateMessageInputs.forEach(input => {
        input.addEventListener('input', function() {
            updateTemplateVariablesPreview(this.value);
        });
    });
    
    // Add smooth scrolling to all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add loading animation to all buttons
    document.querySelectorAll('button[type="submit"]').forEach(button => {
        button.addEventListener('click', function() {
            if (this.type === 'submit') {
                setTimeout(() => {
                    if (this.form && this.form.checkValidity()) {
                        setButtonLoading(this, true);
                    }
                }, 100);
            }
        });
    });
});

// DEBUGGING AND UTILITY FUNCTIONS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function showUserManagement() {
    alert('Fitur manajemen user akan segera tersedia');
}

function showProfile() {
    alert('Fitur profil pengguna akan segera tersedia');
}

// Console log for debugging
console.log('📱 WhatsApp Notification System JavaScript loaded successfully');
console.log('🎯 Available functions:', Object.keys(window).filter(key => typeof window[key] === 'function'));