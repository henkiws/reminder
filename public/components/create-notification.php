<div class="bg-white p-6 rounded-lg shadow">
    <h3 class="text-lg font-semibold text-gray-900 mb-6">Buat Pemberitahuan Baru</h3>
    
    <form id="notificationForm" class="space-y-6">
        <!-- Title and Template (existing) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="block mb-2 text-sm font-medium text-gray-900">Judul Notifikasi *</label>
                <input type="text" id="title" name="title" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
            </div>
            
            <div>
                <label for="template" class="block mb-2 text-sm font-medium text-gray-900">Template (Opsional)</label>
                <select id="template" name="template" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                    <option value="">Pilih Template</option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?php echo $template['id']; ?>" data-template="<?php echo htmlspecialchars($template['message_template']); ?>">
                            <?php echo htmlspecialchars($template['title']); ?> 
                            <?php if ($template['category_name']): ?>
                                (<?php echo htmlspecialchars($template['category_name']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Message (existing) -->
        <div>
            <label for="message" class="block mb-2 text-sm font-medium text-gray-900">Pesan *</label>
            <textarea id="message" name="message" rows="5" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" placeholder="Tulis pesan Anda di sini..." required></textarea>
            <p class="mt-2 text-sm text-gray-500">
                Gunakan placeholder seperti {name}, {date}, {time} untuk personalisasi pesan
            </p>
            
            <!-- Preview Button -->
            <div class="mt-2">
                <button type="button" onclick="previewMessage()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-eye mr-1"></i>Preview Pesan
                </button>
            </div>
        </div>

        <!-- NEW: Template Variables Input Section -->
        <div id="template-variables-container" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
            <!-- Dynamic variable inputs will be created here by JavaScript -->
        </div>

        <!-- Rest of the form (recipients, schedule, etc.) remains the same -->
        <!-- ... existing code ... -->

        <!-- Submit Buttons with updated functionality -->
        <div class="flex space-x-4">
            <button type="submit" name="action" value="schedule" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                <i class="fas fa-clock mr-2"></i>Jadwalkan Notifikasi
            </button>
            
            <button type="submit" name="action" value="send_now" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                <i class="fas fa-paper-plane mr-2"></i>Kirim Sekarang
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
                    Preview Pesan
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="previewModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <div class="p-4 md:p-5 space-y-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="font-medium text-green-800 mb-2">Pesan yang akan dikirim:</h4>
                    <div id="previewContent" class="text-gray-700 whitespace-pre-line"></div>
                </div>
            </div>
            <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b">
                <button data-modal-hide="previewModal" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to extract variables from template
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

// Function to create variable input fields
function createVariableInputs(variables) {
    const container = document.getElementById('template-variables-container');
    container.innerHTML = '';
    
    if (variables.length === 0) {
        container.classList.add('hidden');
        return;
    }
    
    container.classList.remove('hidden');
    
    const header = document.createElement('h4');
    header.className = 'text-sm font-medium text-gray-900 mb-3';
    header.textContent = 'Isi Nilai untuk Template Variables:';
    container.appendChild(header);
    
    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 md:grid-cols-2 gap-4';
    
    variables.forEach(variable => {
        const div = document.createElement('div');
        
        const label = document.createElement('label');
        label.className = 'block text-sm font-medium text-gray-700 mb-1';
        label.textContent = getVariableLabel(variable);
        label.setAttribute('for', 'var_' + variable);
        
        const input = document.createElement('input');
        input.type = getVariableInputType(variable);
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
    
    container.appendChild(grid);
}

// Function to get human-readable labels for variables
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

// Function to get input type for variables
function getVariableInputType(variable) {
    if (variable === 'date' || variable === 'deadline_date') {
        return 'date';
    } else if (variable === 'time' || variable === 'deadline_time') {
        return 'time';
    } else if (variable === 'agenda' || variable === 'announcement') {
        return 'textarea'; // Will be handled separately
    }
    return 'text';
}

// Function to get placeholder text
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

// Function to create textarea for long variables
function createTextareaIfNeeded(variable, container) {
    if (variable === 'agenda' || variable === 'announcement') {
        const lastInput = container.querySelector('#var_' + variable);
        if (lastInput) {
            const textarea = document.createElement('textarea');
            textarea.name = lastInput.name;
            textarea.id = lastInput.id;
            textarea.className = lastInput.className;
            textarea.placeholder = lastInput.placeholder;
            textarea.rows = 3;
            lastInput.parentNode.replaceChild(textarea, lastInput);
        }
    }
}

// Event listener for message textarea changes
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    const templateSelect = document.getElementById('template');
    
    function updateVariableInputs() {
        const message = messageTextarea.value;
        const variables = extractTemplateVariables(message);
        createVariableInputs(variables);
        
        // Convert text inputs to textareas for long variables
        variables.forEach(variable => {
            createTextareaIfNeeded(variable, document.getElementById('template-variables-container'));
        });
    }
    
    // Update when message changes
    messageTextarea.addEventListener('input', updateVariableInputs);
    
    // Update when template is selected
    templateSelect.addEventListener('change', function() {
        setTimeout(updateVariableInputs, 100); // Small delay to let template load
    });
    
    // Initial check
    updateVariableInputs();
});

// Function to preview message with variables filled
function previewMessage() {
    const messageTextarea = document.getElementById('message');
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
    previewContent.innerHTML = message.replace(/\n/g, '<br>');
    
    // Show modal (assuming you're using Flowbite)
    const modalInstance = new Modal(modal);
    modalInstance.show();
}
</script>