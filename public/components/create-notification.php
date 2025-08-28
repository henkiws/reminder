<div class="bg-white p-6 rounded-lg shadow">
    <h3 class="text-lg font-semibold text-gray-900 mb-6">Buat Pemberitahuan Baru</h3>
    
    <form id="notificationForm" class="space-y-6">
        <!-- Judul dan Template -->
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

        <!-- Pesan -->
        <div>
            <label for="message" class="block mb-2 text-sm font-medium text-gray-900">Pesan *</label>
            <textarea id="message" name="message" rows="5" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" placeholder="Tulis pesan Anda di sini..." required></textarea>
            <p class="mt-2 text-sm text-gray-500">
                Gunakan placeholder seperti {name}, {date}, {time} untuk personalisasi pesan
            </p>
        </div>

        <!-- Penerima -->
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900">Kirim Ke *</label>
            <div class="flex items-center mb-4">
                <input id="send-to-contacts" type="radio" value="contact" name="send_to_type" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                <label for="send-to-contacts" class="ml-2 text-sm font-medium text-gray-900">Kontak</label>
            </div>
            <div class="flex items-center mb-4">
                <input id="send-to-groups" type="radio" value="group" name="send_to_type" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                <label for="send-to-groups" class="ml-2 text-sm font-medium text-gray-900">Grup</label>
            </div>
            <div class="flex items-center mb-4">
                <input id="send-to-both" type="radio" value="both" name="send_to_type" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                <label for="send-to-both" class="ml-2 text-sm font-medium text-gray-900">Kontak & Grup</label>
            </div>
        </div>

        <!-- Pilih Kontak -->
        <div id="contacts-section" class="hidden">
            <label class="block mb-2 text-sm font-medium text-gray-900">Pilih Kontak</label>
            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-4">
                <?php foreach ($contacts as $contact): ?>
                <div class="flex items-center mb-2">
                    <input id="contact-<?php echo $contact['id']; ?>" type="checkbox" name="contacts[]" value="<?php echo $contact['id']; ?>" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                    <label for="contact-<?php echo $contact['id']; ?>" class="ml-2 text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($contact['name']); ?> (<?php echo htmlspecialchars($contact['phone']); ?>)
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pilih Grup -->
        <div id="groups-section" class="hidden">
            <label class="block mb-2 text-sm font-medium text-gray-900">Pilih Grup</label>
            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-4">
                <?php foreach ($groups as $group): ?>
                <div class="flex items-center mb-2">
                    <input id="group-<?php echo $group['id']; ?>" type="checkbox" name="groups[]" value="<?php echo $group['id']; ?>" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                    <label for="group-<?php echo $group['id']; ?>" class="ml-2 text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($group['name']); ?>
                        <?php if ($group['description']): ?>
                            <span class="text-gray-500 text-xs">(<?php echo htmlspecialchars($group['description']); ?>)</span>
                        <?php endif; ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Jadwal -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="scheduled_date" class="block mb-2 text-sm font-medium text-gray-900">Tanggal Pengiriman *</label>
                <input type="date" id="scheduled_date" name="scheduled_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
            </div>
            
            <div>
                <label for="scheduled_time" class="block mb-2 text-sm font-medium text-gray-900">Waktu Pengiriman *</label>
                <input type="time" id="scheduled_time" name="scheduled_time" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
            </div>
        </div>

        <!-- Pengulangan -->
        <div>
            <label for="repeat_type" class="block mb-2 text-sm font-medium text-gray-900">Pengulangan</label>
            <select id="repeat_type" name="repeat_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                <option value="once">Sekali saja</option>
                <option value="daily">Harian</option>
                <option value="weekly">Mingguan</option>
                <option value="monthly">Bulanan</option>
            </select>
        </div>

        <!-- Interval Pengulangan -->
        <div id="repeat-options" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="repeat_interval" class="block mb-2 text-sm font-medium text-gray-900">Interval</label>
                <input type="number" id="repeat_interval" name="repeat_interval" min="1" value="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
                <p class="mt-1 text-sm text-gray-500">Contoh: 2 untuk setiap 2 hari</p>
            </div>
            
            <div>
                <label for="repeat_until" class="block mb-2 text-sm font-medium text-gray-900">Batas Akhir</label>
                <input type="date" id="repeat_until" name="repeat_until" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5">
            </div>
        </div>

        <!-- Variable Helper -->
        <div class="bg-gray-100 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Variabel yang Tersedia:</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                <span class="bg-white px-2 py-1 rounded text-gray-700">{name}</span>
                <span class="bg-white px-2 py-1 rounded text-gray-700">{date}</span>
                <span class="bg-white px-2 py-1 rounded text-gray-700">{time}</span>
                <span class="bg-white px-2 py-1 rounded text-gray-700">{location}</span>
                <span class="bg-white px-2 py-1 rounded text-gray-700">{agenda}</span>
                <span class="bg-white px-2 py-1 rounded text-gray-700">{deadline_date}</span>
                <span class="bg-white px-2 py-1 rounded text-gray-700">{deadline_time}</span>
                <span class="bg-white px-2 py-1 rounded text-gray-700">{announcement}</span>
            </div>
        </div>

        <!-- Submit Buttons -->
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