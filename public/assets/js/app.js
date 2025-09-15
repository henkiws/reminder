// public/assets/js/app.js - FIXED VERSION

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeAllModals();
    initializeFormHandlers();
    initializeTemplateSelector();
    initializeSendToTypeHandler();
    initializeRepeatOptions();
    setDefaultDateTime();
    initializePhoneFormatting();
    initializeTabHandling();
    console.log('Application initialized successfully');
});

// Set default date and time to now
function setDefaultDateTime() {
    const now = new Date();
    const date = now.toISOString().split('T')[0];
    const time = now.toTimeString().slice(0, 5);
    
    const dateInput = document.getElementById('scheduled_date');
    const timeInput = document.getElementById('scheduled_time');
    
    if (dateInput) dateInput.value = date;
    if (timeInput) timeInput.value = time;
}

// Initialize all modals
function initializeAllModals() {
    // List of all modal IDs used in the system
    const modalIds = [
        'addContactModal',
        'editContactModal', 
        'addGroupModal',
        'editGroupModal',
        'addUserModal',
        'editUserModal',
        'userActivityModal',
        'previewModal',
        'successModal',
        'errorModal',
        'addTemplateModal',
        'editTemplateModal'
    ];
    
    // Initialize global modal instances storage
    if (!window.modalInstances) {
        window.modalInstances = {};
    }
    
    modalIds.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            try {
                const modalInstance = new Modal(modalElement, {
                    backdrop: 'dynamic',
                    backdropClasses: 'bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
                    closable: true,
                    onHide: () => {
                        console.log('Modal ' + modalId + ' is hidden');
                    },
                    onShow: () => {
                        console.log('Modal ' + modalId + ' is shown');
                    }
                });
                
                window.modalInstances[modalId] = modalInstance;
                console.log('Initialized modal:', modalId);
            } catch (error) {
                console.error('Error initializing modal:', modalId, error);
            }
        }
    });
}

// Handle form submissions
function initializeFormHandlers() {
    // Notification form
    const notificationForm = document.getElementById('notificationForm');
    if (notificationForm) {
        notificationForm.addEventListener('submit', handleNotificationSubmit);
    }
    
    // Contact forms
    const addContactForm = document.getElementById('addContactForm');
    if (addContactForm) {
        addContactForm.addEventListener('submit', handleAddContact);
    }
    
    const editContactForm = document.getElementById('editContactForm');
    if (editContactForm) {
        editContactForm.addEventListener('submit', handleEditContact);
    }
    
    // Group forms
    const addGroupForm = document.getElementById('addGroupForm');
    if (addGroupForm) {
        addGroupForm.addEventListener('submit', handleAddGroup);
    }
    
    const editGroupForm = document.getElementById('editGroupForm');
    if (editGroupForm) {
        editGroupForm.addEventListener('submit', handleEditGroup);
    }
    
    // Template forms
    const addTemplateForm = document.getElementById('addTemplateForm');
    if (addTemplateForm) {
        addTemplateForm.addEventListener('submit', handleAddTemplate);
        
        // Add variables preview for add form
        const addMessageTextarea = document.getElementById('template_message');
        if (addMessageTextarea) {
            addMessageTextarea.addEventListener('input', function() {
                updateVariablesPreview(this);
            });
        }
    }
    
    const editTemplateForm = document.getElementById('editTemplateForm');
    if (editTemplateForm) {
        editTemplateForm.addEventListener('submit', handleEditTemplate);
    }
    
    // User forms (if available)
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', handleAddUser);
    }
    
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', handleEditUser);
    }
}

// Handle notification form submission
function handleNotificationSubmit(e) {
    e.preventDefault();
    
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
    
    // Validate required fields
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
    
    // Show loading
    const submitBtn = document.activeElement;
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
    
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
        // Restore button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Handle template selector
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
            }
        });
    }
}

// Handle send to type changes
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
                
                // Show relevant sections
                if (this.value === 'contact' || this.value === 'both') {
                    contactsSection.classList.remove('hidden');
                }
                
                if (this.value === 'group' || this.value === 'both') {
                    groupsSection.classList.remove('hidden');
                }
            }
        });
    });
}

// Hide recipient sections
function hideRecipientSections() {
    const contactsSection = document.getElementById('contacts-section');
    const groupsSection = document.getElementById('groups-section');
    
    if (contactsSection) contactsSection.classList.add('hidden');
    if (groupsSection) groupsSection.classList.add('hidden');
}

// Handle repeat options
function initializeRepeatOptions() {
    const repeatSelect = document.getElementById('repeat_type');
    const repeatOptions = document.getElementById('repeat-options');
    
    if (repeatSelect && repeatOptions) {
        repeatSelect.addEventListener('change', function() {
            if (this.value === 'once') {
                repeatOptions.classList.add('hidden');
            } else {
                repeatOptions.classList.remove('hidden');
            }
        });
    }
}

// Template Variables Functions
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
        
        const label = document.createElement('label');
        label.className = 'block text-sm font-medium text-gray-700 mb-1';
        label.textContent = getVariableLabel(variable);
        label.setAttribute('for', 'var_' + variable);
        
        let input;
        if (variable === 'agenda' || variable === 'announcement') {
            input = document.createElement('textarea');
            input.rows = 3;
        } else {
            input = document.createElement('input');
            input.type = getVariableInputType(variable);
        }
        
        input.name = 'template_vars[' + variable + ']';
        input.id = 'var_' + variable;
        input.className = 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5';
        input.placeholder = getVariablePlaceholder(variable);
        
        // Set default values for some variables
        if (variable === 'date') {
            input.value = new Date().toLocaleDateString('id-ID');
        } else if (variable === 'time') {
            input.value = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
        }
        
        div.appendChild(label);
        div.appendChild(input);
        grid.appendChild(div);
    });
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
        'announcement': 'Teks Pengumuman'
    };
    return labels[variable] || variable.replace('_', ' ').toUpperCase();
}

function getVariableInputType(variable) {
    if (variable === 'date' || variable === 'deadline_date') {
        return 'date';
    } else if (variable === 'time' || variable === 'deadline_time') {
        return 'time';
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
        'announcement': 'Teks pengumuman lengkap'
    };
    return placeholders[variable] || 'Masukkan nilai untuk ' + variable;
}

function updateVariableInputs() {
    const messageTextarea = document.getElementById('message');
    if (!messageTextarea) return;
    
    const message = messageTextarea.value;
    const variables = extractTemplateVariables(message);
    createVariableInputs(variables);
}

// Event listener for message textarea changes
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    const templateSelect = document.getElementById('template');
    
    if (messageTextarea) {
        // Update when message changes
        messageTextarea.addEventListener('input', updateVariableInputs);
        
        // Initial check
        updateVariableInputs();
    }
    
    if (templateSelect) {
        // Update when template is selected
        templateSelect.addEventListener('change', function() {
            setTimeout(updateVariableInputs, 100); // Small delay to let template load
        });
    }
});

// Function to preview message with variables filled
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
    
    // Show preview
    const modal = document.getElementById('previewModal');
    const previewContent = document.getElementById('previewContent');
    if (previewContent) {
        previewContent.innerHTML = message.replace(/\n/g, '<br>');
    }
    
    // Show modal
    showModal('previewModal');
}

// Contact management functions
function handleAddContact(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    
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
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function handleEditContact(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    
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
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function editContact(id, name, phone) {
    document.getElementById('edit_contact_id').value = id;
    document.getElementById('edit_contact_name').value = name;
    document.getElementById('edit_contact_phone').value = phone;
    
    showModal('editContactModal');
}

function deleteContact(id) {
    if (confirm('Apakah Anda yakin ingin menghapus kontak ini?')) {
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
}

// Group management functions
function handleAddGroup(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    
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
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function handleEditGroup(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    
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
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function editGroup(id, name, groupId, description) {
    document.getElementById('edit_group_id').value = id;
    document.getElementById('edit_group_name').value = name;
    document.getElementById('edit_group_group_id').value = groupId;
    document.getElementById('edit_group_description').value = description || '';
    
    showModal('editGroupModal');
}

function deleteGroup(id) {
    if (confirm('Apakah Anda yakin ingin menghapus grup ini?')) {
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
}

// Template management functions
function handleAddTemplate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    
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
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function handleEditTemplate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    
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
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function editTemplate(id, title, messageTemplate, categoryId) {
    document.getElementById('edit_template_id').value = id;
    document.getElementById('edit_template_title').value = title;
    document.getElementById('edit_template_message').value = messageTemplate;
    document.getElementById('edit_template_category').value = categoryId || '';
    
    showModal('editTemplateModal');
}

function deleteTemplate(id) {
    if (confirm('Apakah Anda yakin ingin menghapus template ini?')) {
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
}

function updateVariablesPreview(textarea) {
    const message = textarea.value;
    const variables = extractTemplateVariables(message);
    const preview = document.getElementById('template-variables-preview');
    const variablesList = document.getElementById('variables-list');
    
    if (!preview || !variablesList) return;
    
    if (variables.length > 0) {
        preview.classList.remove('hidden');
        variablesList.innerHTML = variables.map(variable => 
            `<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">{${variable}}</span>`
        ).join('');
    } else {
        preview.classList.add('hidden');
    }
}

// Template functions
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
        
        // Scroll to message field
        setTimeout(() => {
            messageTextarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
            messageTextarea.focus();
        }, 100);
    }
}

// Notification functions
function sendNotificationNow(id) {
    if (confirm('Apakah Anda yakin ingin mengirim notifikasi ini sekarang?')) {
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
}

function viewNotificationDetails(id) {
    // This could open a modal with detailed notification info and logs
    alert('Fitur detail notifikasi akan segera tersedia');
}

// User management functions (placeholder)
function showUserManagement() {
    alert('Fitur manajemen user akan segera tersedia');
}

function showProfile() {
    alert('Fitur profil pengguna akan segera tersedia');
}

// Modal management functions
function showModal(modalId) {
    console.log('Attempting to show modal:', modalId);
    
    let modalInstance = window.modalInstances[modalId];
    
    if (!modalInstance) {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            modalInstance = new Modal(modalElement);
            window.modalInstances[modalId] = modalInstance;
            console.log('Created new modal instance for:', modalId);
        } else {
            console.error('Modal element not found:', modalId);
            return false;
        }
    }
    
    try {
        modalInstance.show();
        return true;
    } catch (error) {
        console.error('Error showing modal:', modalId, error);
        return false;
    }
}

function hideModal(modalId) {
    console.log('Attempting to hide modal:', modalId);
    
    const modalInstance = window.modalInstances[modalId];
    if (modalInstance) {
        try {
            modalInstance.hide();
            return true;
        } catch (error) {
            console.error('Error hiding modal:', modalId, error);
            return false;
        }
    } else {
        console.warn('Modal instance not found:', modalId);
        return false;
    }
}

// Utility functions
function showSuccess(message) {
    console.log('Showing success message:', message);
    
    const messageElement = document.getElementById('successMessage');
    if (messageElement) {
        messageElement.textContent = message;
        showModal('successModal');
    } else {
        alert('Success: ' + message);
    }
}

function showError(message) {
    console.log('Showing error message:', message);
    
    const messageElement = document.getElementById('errorMessage');
    if (messageElement) {
        messageElement.textContent = message;
        showModal('errorModal');
    } else {
        alert('Error: ' + message);
    }
}

// Phone number formatting
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    // Remove leading zero and replace with 62
    if (value.startsWith('0')) {
        value = '62' + value.substring(1);
    }
    
    // If doesn't start with 62, add it
    if (!value.startsWith('62')) {
        value = '62' + value;
    }
    
    input.value = value;
}

// Initialize phone number formatting
function initializePhoneFormatting() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('blur', () => formatPhoneNumber(input));
    });
}

// Escape HTML to prevent XSS
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

// Handle tab changes
function initializeTabHandling() {
    const tabButtons = document.querySelectorAll('[data-tabs-target]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-tabs-target');
            console.log('Tab clicked:', target);
        });
    });
}

// Event listeners for modal close buttons and ESC key
document.addEventListener('DOMContentLoaded', function() {
    // Handle close buttons with data-modal-hide attribute
    document.querySelectorAll('[data-modal-hide]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal-hide');
            hideModal(modalId);
        });
    });
    
    // Handle ESC key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Find and close the topmost modal
            for (const modalId in window.modalInstances) {
                const modalElement = document.getElementById(modalId);
                if (modalElement && !modalElement.classList.contains('hidden')) {
                    hideModal(modalId);
                    break;
                }
            }
        }
    });
});

console.log('Modal management system loaded successfully');