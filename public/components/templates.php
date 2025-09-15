<?php
// components/templates.php - FIXED VERSION with Perfect CRUD
?>

<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Manajemen Template Pesan</h3>
            <p class="text-sm text-gray-600 mt-1">Kelola template pesan untuk mempercepat pembuatan notifikasi</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="bg-indigo-50 px-3 py-2 rounded-lg text-sm">
                <span class="font-medium text-indigo-900"><?php echo count($templates); ?></span>
                <span class="text-indigo-700">Template Total</span>
            </div>
            <button type="button" data-modal-target="addTemplateModal" data-modal-toggle="addTemplateModal" 
                    class="bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-white transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Template
            </button>
        </div>
    </div>
    
    <!-- Enhanced Search and Filter -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="templateSearch" placeholder="Cari template berdasarkan judul atau konten..." 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full pl-10 p-2.5">
            </div>
        </div>
        <div class="flex gap-2">
            <select id="templateCategoryFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                <option value="">Semua Kategori</option>
                <?php 
                $usedCategories = [];
                foreach ($templates as $template) {
                    if ($template['category_name'] && !in_array($template['category_name'], $usedCategories)) {
                        $usedCategories[] = $template['category_name'];
                        echo '<option value="' . htmlspecialchars($template['category_name']) . '">' . htmlspecialchars($template['category_name']) . '</option>';
                    }
                }
                ?>
            </select>
            <select id="templateTypeFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                <option value="">Semua Jenis</option>
                <option value="system">Template Sistem</option>
                <option value="personal">Template Pribadi</option>
            </select>
            <button type="button" onclick="resetTemplateFilters()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                <i class="fas fa-undo mr-2"></i>Reset
            </button>
        </div>
    </div>
    
    <!-- Template Grid -->
    <div class="grid gap-6" id="templatesGrid">
        <?php foreach ($templates as $template): ?>
        <div class="border border-gray-200 rounded-lg p-6 template-card hover:shadow-lg transition-all duration-300" 
             data-template-id="<?php echo $template['id']; ?>"
             data-category="<?php echo htmlspecialchars($template['category_name'] ?? ''); ?>"
             data-type="<?php echo $template['user_id'] ? 'personal' : 'system'; ?>">
            
            <!-- Template Header -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <h4 class="text-lg font-semibold text-gray-900 mr-3"><?php echo htmlspecialchars($template['title']); ?></h4>
                        
                        <!-- Category Badge -->
                        <?php if ($template['category_name']): ?>
                            <span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                                <i class="fas fa-tag mr-1"></i>
                                <?php echo htmlspecialchars($template['category_name']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Type Badge -->
                        <?php if ($template['user_id']): ?>
                            <span class="ml-2 inline-flex items-center bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                                <i class="fas fa-user mr-1"></i>Pribadi
                            </span>
                        <?php else: ?>
                            <span class="ml-2 inline-flex items-center bg-purple-100 text-purple-800 text-xs font-medium px-2 py-1 rounded-full">
                                <i class="fas fa-globe mr-1"></i>Sistem
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Template Stats -->
                    <div class="flex items-center text-xs text-gray-500 space-x-4">
                        <span>
                            <i class="fas fa-calendar mr-1"></i>
                            Dibuat <?php echo date('d M Y', strtotime($template['created_at'])); ?>
                        </span>
                        <?php if (isset($template['usage_count'])): ?>
                        <span>
                            <i class="fas fa-chart-line mr-1"></i>
                            Digunakan <?php echo $template['usage_count']; ?>x
                        </span>
                        <?php endif; ?>
                        <span>
                            <i class="fas fa-text-width mr-1"></i>
                            <?php echo strlen($template['message_template']); ?> karakter
                        </span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex space-x-2">
                    
                    
                    <button onclick="previewTemplate('<?php echo htmlspecialchars($template['message_template'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($template['title'], ENT_QUOTES); ?>')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            title="Preview Template">
                        <i class="fas fa-eye"></i>
                    </button>
                    
                    <?php if ($template['user_id'] == $currentUser['id'] || hasPermission('template.update')): ?>
                    <button onclick="editTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($template['message_template'], ENT_QUOTES); ?>', <?php echo $template['category_id'] ?: 'null'; ?>)" 
                            class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            title="Edit Template">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($template['user_id'] == $currentUser['id'] || hasPermission('template.delete')): ?>
                    <button onclick="deleteTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['title'], ENT_QUOTES); ?>')" 
                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            title="Hapus Template">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                    
                    <div class="relative">
                        <button onclick="toggleTemplateMenu(<?php echo $template['id']; ?>)" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                title="Menu Lainnya">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div id="templateMenu<?php echo $template['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                            <div class="py-1">
                                <button onclick="duplicateTemplate(<?php echo $template['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                    <i class="fas fa-clone mr-2"></i>Duplikasi Template
                                </button>
                                <button onclick="exportTemplate(<?php echo $template['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                    <i class="fas fa-download mr-2"></i>Export Template
                                </button>
                                <?php if (hasPermission('template.read')): ?>
                                <button onclick="viewTemplateStats(<?php echo $template['id']; ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                    <i class="fas fa-chart-bar mr-2"></i>Lihat Statistik
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Template Content -->
            <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-green-500 mb-4">
                <div class="text-sm text-gray-700 font-mono leading-relaxed">
                    <?php 
                    $preview = nl2br(htmlspecialchars($template['message_template']));
                    // Highlight template variables with better styling
                    $preview = preg_replace('/\{(\w+)\}/', '<span class="bg-yellow-200 text-yellow-900 px-2 py-1 rounded-md font-semibold">{$1}</span>', $preview);
                    echo $preview; 
                    ?>
                </div>
            </div>
            
            <!-- Template Variables -->
            <?php 
            $variables = [];
            if (preg_match_all('/\{(\w+)\}/', $template['message_template'], $matches)) {
                $variables = array_unique($matches[1]);
            }
            if (!empty($variables)): ?>
            <div class="border-t pt-4">
                <div class="flex items-center justify-between mb-2">
                    <h5 class="text-sm font-medium text-gray-900">
                        <i class="fas fa-code mr-2 text-blue-600"></i>
                        Template Variables (<?php echo count($variables); ?>)
                    </h5>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($variables as $var): ?>
                    <span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                        <i class="fas fa-code mr-1"></i>{<?php echo $var; ?>}
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($templates)): ?>
        <div class="text-center py-16">
            <div class="max-w-sm mx-auto">
                <i class="fas fa-file-alt text-8xl text-gray-300 mb-6"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">Belum ada template</h3>
                <p class="text-gray-500 mb-6">Buat template pesan untuk mempercepat proses pembuatan notifikasi dan meningkatkan konsistensi pesan.</p>
                <button data-modal-target="addTemplateModal" data-modal-toggle="addTemplateModal" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Buat Template Pertama
                </button>
                
                <!-- Quick Template Suggestions -->
                <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg text-left">
                    <h4 class="text-sm font-medium text-blue-900 mb-3">Saran Template Populer:</h4>
                    <div class="space-y-2">
                        <button onclick="createQuickTemplate('meeting')" class="w-full text-left p-2 text-xs bg-white border border-blue-200 rounded hover:bg-blue-50 transition-colors">
                            <i class="fas fa-users mr-2 text-blue-600"></i>Template Undangan Rapat
                        </button>
                        <button onclick="createQuickTemplate('deadline')" class="w-full text-left p-2 text-xs bg-white border border-blue-200 rounded hover:bg-blue-50 transition-colors">
                            <i class="fas fa-clock mr-2 text-red-600"></i>Template Pengingat Deadline
                        </button>
                        <button onclick="createQuickTemplate('announcement')" class="w-full text-left p-2 text-xs bg-white border border-blue-200 rounded hover:bg-blue-50 transition-colors">
                            <i class="fas fa-bullhorn mr-2 text-yellow-600"></i>Template Pengumuman
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Template Modals -->
<?php include 'components/template-modals.php'; ?>

<!-- Template Preview Modal -->
<div id="templatePreviewModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-eye mr-2 text-blue-600"></i>
                    Preview Template: <span id="previewTemplateTitle">Template</span>
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors" data-modal-hide="templatePreviewModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <div class="p-4 md:p-5">
                <div id="templatePreviewContent" class="bg-green-50 border border-green-200 rounded-lg p-4 whitespace-pre-line"></div>
                
                <div class="mt-4 flex justify-end space-x-3">
                    <button type="button" data-modal-hide="templatePreviewModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 transition-colors">
                        Tutup
                    </button>
                    <button type="button" onclick="useTemplateFromPreview()" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        <i class="fas fa-copy mr-2"></i>Gunakan Template
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced Template Management Functions
let currentPreviewTemplate = '';

document.addEventListener('DOMContentLoaded', function() {
    initializeTemplateManagement();
});

function initializeTemplateManagement() {
    // Search functionality
    const searchInput = document.getElementById('templateSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterTemplates();
        });
    }
    
    // Filter functionality
    const categoryFilter = document.getElementById('templateCategoryFilter');
    const typeFilter = document.getElementById('templateTypeFilter');
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterTemplates);
    }
    
    if (typeFilter) {
        typeFilter.addEventListener('change', filterTemplates);
    }
    
    // Close template menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[id^="templateMenu"]') && !e.target.closest('button[onclick*="toggleTemplateMenu"]')) {
            closeAllTemplateMenus();
        }
    });
}

function filterTemplates() {
    const searchTerm = document.getElementById('templateSearch').value.toLowerCase();
    const categoryFilter = document.getElementById('templateCategoryFilter').value;
    const typeFilter = document.getElementById('templateTypeFilter').value;
    const templateCards = document.querySelectorAll('.template-card');
    
    templateCards.forEach(card => {
        const title = card.querySelector('h4').textContent.toLowerCase();
        const content = card.querySelector('.font-mono').textContent.toLowerCase();
        const category = card.dataset.category;
        const type = card.dataset.type;
        
        let showCard = true;
        
        // Apply search filter
        if (searchTerm && !title.includes(searchTerm) && !content.includes(searchTerm)) {
            showCard = false;
        }
        
        // Apply category filter
        if (categoryFilter && category !== categoryFilter) {
            showCard = false;
        }
        
        // Apply type filter
        if (typeFilter && type !== typeFilter) {
            showCard = false;
        }
        
        // Show/hide with animation
        if (showCard) {
            card.style.display = 'block';
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        } else {
            card.style.display = 'none';
        }
    });
}

function resetTemplateFilters() {
    document.getElementById('templateSearch').value = '';
    document.getElementById('templateCategoryFilter').value = '';
    document.getElementById('templateTypeFilter').value = '';
    filterTemplates();
}

function toggleTemplateMenu(templateId) {
    closeAllTemplateMenus();
    const menu = document.getElementById('templateMenu' + templateId);
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

function closeAllTemplateMenus() {
    const menus = document.querySelectorAll('[id^="templateMenu"]');
    menus.forEach(menu => menu.classList.add('hidden'));
}

// ENHANCED USE TEMPLATE FUNCTION - This is the main fix for the "Gunakan" button
function useTemplate(template) {
    // Switch to create notification tab
    const createTab = document.getElementById('create-tab');
    if (createTab) {
        createTab.click();
    }
    
    // Wait for tab to switch, then populate the form
    setTimeout(() => {
        const messageTextarea = document.getElementById('message');
        if (messageTextarea) {
            messageTextarea.value = template;
            
            // Trigger change events to update character count and variables
            messageTextarea.dispatchEvent(new Event('input'));
            
            // Scroll to message field with smooth animation
            messageTextarea.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Focus on the message field
            messageTextarea.focus();
            
            // Add visual feedback
            messageTextarea.classList.add('border-green-500', 'ring-2', 'ring-green-200', 'bg-green-50');
            
            // Show success message
            showSuccess('Template berhasil dimuat! Anda dapat mengedit pesan dan mengatur penerima.');
            
            // Remove visual feedback after 3 seconds
            setTimeout(() => {
                messageTextarea.classList.remove('border-green-500', 'ring-2', 'ring-green-200', 'bg-green-50');
            }, 3000);
            
            // Update template variables if function exists
            if (typeof updateVariableInputs === 'function') {
                updateVariableInputs();
            }
        } else {
            showError('Gagal menemukan form pesan. Pastikan Anda berada di tab "Buat Notifikasi".');
        }
    }, 300);
}

function previewTemplate(template, title) {
    currentPreviewTemplate = template;
    
    // Set title
    document.getElementById('previewTemplateTitle').textContent = title;
    
    // Process template with sample data
    const sampleData = {
        name: 'John Doe',
        date: new Date().toLocaleDateString('id-ID'),
        time: '14:00',
        location: 'Ruang Meeting A',
        agenda: 'Review Progress Project',
        deadline_date: new Date(Date.now() + 7*24*60*60*1000).toLocaleDateString('id-ID'),
        deadline_time: '17:00',
        item: 'Laporan Bulanan',
        announcement: 'Informasi penting mengenai update sistem',
        reminder_text: 'Jangan lupa menghadiri rapat evaluasi',
        sender: 'Tim Management'
    };
    
    let processedTemplate = template;
    Object.keys(sampleData).forEach(key => {
        const regex = new RegExp(`\\{${key}\\}`, 'g');
        processedTemplate = processedTemplate.replace(regex, sampleData[key]);
    });
    
    document.getElementById('templatePreviewContent').textContent = processedTemplate;
    showModal('templatePreviewModal');
}

function useTemplateFromPreview() {
    hideModal('templatePreviewModal');
    useTemplate(currentPreviewTemplate);
}

function editTemplate(id, title, messageTemplate, categoryId) {
    // Populate edit form
    document.getElementById('edit_template_id').value = id;
    document.getElementById('edit_template_title').value = title;
    document.getElementById('edit_template_message').value = messageTemplate;
    document.getElementById('edit_template_category').value = categoryId || '';
    
    // Update character count and variables if functions exist
    if (typeof updateEditTemplateStats === 'function') {
        updateEditTemplateStats();
    }
    if (typeof updateEditTemplateVariables === 'function') {
        updateEditTemplateVariables();
    }
    
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

function duplicateTemplate(templateId) {
    showConfirmModal(
        'Duplikasi Template',
        'Apakah Anda yakin ingin menduplikasi template ini?',
        'Duplikasi',
        'primary',
        () => {
            fetch(`api.php/template/${templateId}/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSuccess('Template berhasil diduplikasi');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError('Gagal menduplikasi template: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Terjadi kesalahan sistem');
            });
        }
    );
}

function exportTemplate(templateId) {
    fetch(`api.php/template/${templateId}/export`)
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `template_${templateId}.json`;
        a.click();
        window.URL.revokeObjectURL(url);
        showSuccess('Template berhasil diexport');
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Gagal mengexport template');
    });
}

function viewTemplateStats(templateId) {
    // Open template statistics modal or page
    window.open(`template-stats.php?id=${templateId}`, '_blank');
}

function createQuickTemplate(type) {
    // Open add template modal with predefined content
    if (typeof loadQuickTemplate === 'function') {
        showModal('addTemplateModal');
        setTimeout(() => loadQuickTemplate(type), 300);
    }
}

// Template form handlers
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

// Initialize form handlers when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const addTemplateForm = document.getElementById('addTemplateForm');
    if (addTemplateForm) {
        addTemplateForm.addEventListener('submit', handleAddTemplate);
    }
    
    const editTemplateForm = document.getElementById('editTemplateForm');
    if (editTemplateForm) {
        editTemplateForm.addEventListener('submit', handleEditTemplate);
    }
});

// Helper function to extract template variables
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

console.log('Template management initialized successfully');
</script>