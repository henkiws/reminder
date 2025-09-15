<!-- Add Template Modal -->
<div id="addTemplateModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Tambah Template Baru
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="addTemplateModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="addTemplateForm" class="p-4 md:p-5">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="template_title" class="block mb-2 text-sm font-medium text-gray-900">Judul Template</label>
                        <input type="text" name="title" id="template_title" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" placeholder="Masukkan judul template" required>
                    </div>
                    <div>
                        <label for="template_category" class="block mb-2 text-sm font-medium text-gray-900">Kategori (Opsional)</label>
                        <select name="category_id" id="template_category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="template_message" class="block mb-2 text-sm font-medium text-gray-900">Template Pesan</label>
                        <textarea name="message_template" id="template_message" rows="8" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" placeholder="Tulis template pesan di sini..." required></textarea>
                        <p class="mt-1 text-sm text-gray-500">
                            Gunakan variabel seperti {name}, {date}, {time}, {location}, {agenda} untuk personalisasi
                        </p>
                    </div>
                </div>
                
                <!-- Template Variables Preview -->
                <div id="template-variables-preview" class="mb-4 hidden">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Variabel yang ditemukan:</h4>
                    <div id="variables-list" class="flex flex-wrap gap-2"></div>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button type="button" data-modal-hide="addTemplateModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Batal
                    </button>
                    <button type="submit" class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div id="editTemplateModal" tabindex="-1" aria-hidden="true" role="dialog" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Edit Template
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="editTemplateModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="editTemplateForm" class="p-4 md:p-5">
                <input type="hidden" id="edit_template_id" name="id">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="edit_template_title" class="block mb-2 text-sm font-medium text-gray-900">Judul Template</label>
                        <input type="text" name="title" id="edit_template_title" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="edit_template_category" class="block mb-2 text-sm font-medium text-gray-900">Kategori</label>
                        <select name="category_id" id="edit_template_category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="edit_template_message" class="block mb-2 text-sm font-medium text-gray-900">Template Pesan</label>
                        <textarea name="message_template" id="edit_template_message" rows="8" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" data-modal-hide="editTemplateModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Batal
                    </button>
                    <button type="submit" class="text-white inline-flex items-center bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Template management functions
function editTemplate(id, title, messageTemplate, categoryId) {
    document.getElementById('edit_template_id').value = id;
    document.getElementById('edit_template_title').value = title;
    document.getElementById('edit_template_message').value = messageTemplate;
    document.getElementById('edit_template_category').value = categoryId || '';
    
    // Show modal
    if (window.modalInstances && window.modalInstances.editTemplateModal) {
        window.modalInstances.editTemplateModal.show();
    } else {
        const modalElement = document.getElementById('editTemplateModal');
        const modalInstance = new Modal(modalElement);
        modalInstance.show();
    }
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

function handleAddTemplate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    console.log('Submitting template:', data);
    
    // Show loading state
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
            if (window.modalInstances && window.modalInstances.addTemplateModal) {
                window.modalInstances.addTemplateModal.hide();
            }
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
        // Restore button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function handleEditTemplate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    console.log('Submitting template edit:', data);
    
    // Show loading state
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
            if (window.modalInstances && window.modalInstances.editTemplateModal) {
                window.modalInstances.editTemplateModal.hide();
            }
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
        // Restore button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Function to update variables preview
function updateVariablesPreview(textarea) {
    const message = textarea.value;
    const variables = extractTemplateVariables(message);
    const preview = document.getElementById('template-variables-preview');
    const variablesList = document.getElementById('variables-list');
    
    if (variables.length > 0) {
        preview.classList.remove('hidden');
        variablesList.innerHTML = variables.map(variable => 
            `<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">{${variable}}</span>`
        ).join('');
    } else {
        preview.classList.add('hidden');
    }
}

// Initialize template management when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add template form handler
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
    
    // Edit template form handler
    const editTemplateForm = document.getElementById('editTemplateForm');
    if (editTemplateForm) {
        editTemplateForm.addEventListener('submit', handleEditTemplate);
    }
    
    // Initialize modals for template management
    const templateModals = ['addTemplateModal', 'editTemplateModal'];
    templateModals.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement && (!window.modalInstances || !window.modalInstances[modalId])) {
            try {
                const modalInstance = new Modal(modalElement);
                if (!window.modalInstances) window.modalInstances = {};
                window.modalInstances[modalId] = modalInstance;
                console.log('Initialized template modal:', modalId);
            } catch (error) {
                console.error('Error initializing template modal:', modalId, error);
            }
        }
    });
});

// Extract template variables function (reuse from create notification)
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
</script>