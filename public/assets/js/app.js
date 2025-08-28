// public/assets/js/app.js

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeFormHandlers();
    initializeTemplateSelector();
    initializeSendToTypeHandler();
    initializeRepeatOptions();
    setDefaultDateTime();
    initializePhoneFormatting();
    initializeTabHandling();
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

// Handle form submissions
function initializeFormHandlers() {
    // Notification form
    const notificationForm = document.getElementById('notificationForm');
    if (notificationForm) {
        notificationForm.addEventListener('submit', handleNotificationSubmit);
    }
    
    // Add contact form
    const addContactForm = document.getElementById('addContactForm');
    if (addContactForm) {
        addContactForm.addEventListener('submit', handleAddContact);
    }
    
    // Edit contact form
    const editContactForm = document.getElementById('editContactForm');
    if (editContactForm) {
        editContactForm.addEventListener('submit', handleEditContact);
    }
    
    // Add group form
    const addGroupForm = document.getElementById('addGroupForm');
    if (addGroupForm) {
        addGroupForm.addEventListener('submit', handleAddGroup);
    }
    
    // Edit group form
    const editGroupForm = document.getElementById('editGroupForm');
    if (editGroupForm) {
        editGroupForm.addEventListener('submit', handleEditGroup);
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

// Contact management functions
function handleAddContact(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
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
            e.target.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan kontak');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function handleEditContact(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
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
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal memperbarui kontak');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function editContact(id, name, phone) {
    document.getElementById('edit_contact_id').value = id;
    document.getElementById('edit_contact_name').value = name;
    document.getElementById('edit_contact_phone').value = phone;
    
    // Show modal using Flowbite
    const modal = new Modal(document.getElementById('editContactModal'));
    modal.show();
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
                showError('Gagal menghapus kontak');
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
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan grup');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function handleEditGroup(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
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
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal memperbarui grup');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function editGroup(id, name, groupId, description) {
    document.getElementById('edit_group_id').value = id;
    document.getElementById('edit_group_name').value = name;
    document.getElementById('edit_group_group_id').value = groupId;
    document.getElementById('edit_group_description').value = description;
    
    // Show modal using Flowbite
    const modal = new Modal(document.getElementById('editGroupModal'));
    modal.show();
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
                showError('Gagal menghapus grup');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Terjadi kesalahan sistem');
        });
    }
}

// User management functions
function handleAddUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
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
            showSuccess('User berhasil ditambahkan');
            e.target.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Gagal menambahkan user: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Terjadi kesalahan sistem');
    });
}

function handleEditUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Convert checkbox to boolean
    data.is_active = document.getElementById('edit_user_active').checked;
    
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
}

function editUser(id, fullName, email, username, isActive) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_user_full_name').value = fullName;
    document.getElementById('edit_user_email').value = email;
    document.getElementById('edit_user_username').value = username;
    document.getElementById('edit_user_active').checked = isActive;
    
    const modal = new Modal(document.getElementById('editUserModal'));
    modal.show();
}

function deleteUser(id) {
    if (confirm('Apakah Anda yakin ingin menonaktifkan user ini?')) {
        fetch(`api.php/user/${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showSuccess('User berhasil dinonaktifkan');
                setTimeout(() => location.reload(), 1500);
            } else {
                showError('Gagal menonaktifkan user: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Terjadi kesalahan sistem');
        });
    }
}

function viewUserActivity(userId) {
    fetch(`api.php/user/${userId}/activity`)
    .then(response => response.json())
    .then(data => {
        const content = document.getElementById('activityLogContent');
        
        if (data.length === 0) {
            content.innerHTML = '<p class="text-gray-500 text-center">Tidak ada aktivitas</p>';
        } else {
            content.innerHTML = data.map(log => `
                <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-gray-400"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="text-sm font-medium text-gray-900">${escapeHtml(log.action)}</div>
                        ${log.description ? `<div class="text-sm text-gray-600">${escapeHtml(log.description)}</div>` : ''}
                        <div class="text-xs text-gray-500 mt-1">
                            ${new Date(log.created_at).toLocaleString('id-ID')}
                            ${log.ip_address ? ` • IP: ${escapeHtml(log.ip_address)}` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        const modal = new Modal(document.getElementById('userActivityModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Gagal memuat aktivitas user');
    });
}

function showUserManagement() {
    // Load user management content dynamically
    fetch('components/user-management.php')
    .then(response => response.text())
    .then(html => {
        // Create a new tab content area for user management
        const tabContent = document.getElementById('default-tab-content');
        
        // Hide all existing tabs
        const allTabs = tabContent.querySelectorAll('[role="tabpanel"]');
        allTabs.forEach(tab => tab.classList.add('hidden'));
        
        // Remove existing user management tab if any
        const existingUserTab = document.getElementById('user-management');
        if (existingUserTab) {
            existingUserTab.remove();
        }
        
        // Create new user management tab
        const userManagementDiv = document.createElement('div');
        userManagementDiv.id = 'user-management';
        userManagementDiv.className = 'p-4 rounded-lg bg-gray-50';
        userManagementDiv.setAttribute('role', 'tabpanel');
        userManagementDiv.innerHTML = html;
        
        tabContent.appendChild(userManagementDiv);
        
        // Initialize form handlers for user management
        const addUserForm = document.getElementById('addUserForm');
        if (addUserForm) {
            addUserForm.addEventListener('submit', handleAddUser);
        }
        
        const editUserForm = document.getElementById('editUserForm');
        if (editUserForm) {
            editUserForm.addEventListener('submit', handleEditUser);
        }
    })
    .catch(error => {
        console.error('Error loading user management:', error);
        showError('Gagal memuat halaman manajemen user');
    });
}

function showProfile() {
    // Show user profile modal or page
    alert('Fitur profil pengguna akan segera tersedia');
}

// Template functions
function useTemplate(template) {
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        messageTextarea.value = template;
        
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

// Utility functions
function showSuccess(message) {
    const modal = document.getElementById('successModal');
    const messageElement = document.getElementById('successMessage');
    
    if (modal && messageElement) {
        messageElement.textContent = message;
        const modalInstance = new Modal(modal);
        modalInstance.show();
    } else {
        alert(message);
    }
}

function showError(message) {
    const modal = document.getElementById('errorModal');
    const messageElement = document.getElementById('errorMessage');
    
    if (modal && messageElement) {
        messageElement.textContent = message;
        const modalInstance = new Modal(modal);
        modalInstance.show();
    } else {
        alert(message);
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

// Auto-refresh functionality (optional)
let autoRefreshInterval;

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        // Only refresh if we're on notifications tab
        const notificationsTab = document.getElementById('notifications');
        if (notificationsTab && !notificationsTab.classList.contains('hidden')) {
            location.reload();
        }
    }, 30000); // 30 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Handle tab changes to manage auto-refresh
function initializeTabHandling() {
    const tabButtons = document.querySelectorAll('[data-tabs-target]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-tabs-target');
            if (target === '#notifications') {
                // Uncomment to enable auto-refresh on notifications tab
                // startAutoRefresh();
            } else {
                // stopAutoRefresh();
            }
        });
    });
}