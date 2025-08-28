<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Template Pesan</h3>
    </div>
    
    <div class="grid gap-6">
        <?php foreach ($templates as $template): ?>
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($template['title']); ?></h4>
                    <?php if ($template['category_name']): ?>
                        <span class="inline-block bg-gray-100 text-gray-800 text-xs font-medium px-2 py-1 rounded mt-1">
                            <?php echo htmlspecialchars($template['category_name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <button onclick="useTemplate('<?php echo htmlspecialchars($template['message_template']); ?>')" class="text-green-600 hover:text-green-900 text-sm font-medium">
                    <i class="fas fa-copy mr-1"></i>Gunakan Template
                </button>
            </div>
            
            <div class="bg-gray-50 p-3 rounded text-sm text-gray-700">
                <?php echo nl2br(htmlspecialchars($template['message_template'])); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>