<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Manajemen Grup</h3>
        <button data-modal-target="addGroupModal" data-modal-toggle="addGroupModal" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
            <i class="fas fa-plus mr-2"></i>Tambah Grup
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Grup</th>
                    <th scope="col" class="px-6 py-3">ID Grup</th>
                    <th scope="col" class="px-6 py-3">Deskripsi</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Tanggal Ditambahkan</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $group): ?>
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        <?php echo htmlspecialchars($group['name']); ?>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm">
                        <?php echo htmlspecialchars($group['group_id']); ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php echo htmlspecialchars($group['description'] ?: '-'); ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                            Aktif
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?php echo date('d/m/Y', strtotime($group['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            <button onclick="editGroup(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>', '<?php echo htmlspecialchars($group['group_id']); ?>', '<?php echo htmlspecialchars($group['description']); ?>')" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteGroup(<?php echo $group['id']; ?>)" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Group Modal -->
<div id="addGroupModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Tambah Grup Baru
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="addGroupModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="addGroupForm" class="p-4 md:p-5">
                <div class="grid gap-4 mb-4 grid-cols-1">
                    <div>
                        <label for="group_name" class="block mb-2 text-sm font-medium text-gray-900">Nama Grup</label>
                        <input type="text" name="name" id="group_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" placeholder="Masukkan nama grup" required>
                    </div>
                    <div>
                        <label for="group_id" class="block mb-2 text-sm font-medium text-gray-900">ID Grup WhatsApp</label>
                        <input type="text" name="group_id" id="group_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" placeholder="120363xxxxx@g.us" required>
                        <p class="mt-1 text-sm text-gray-500">Dapatkan ID grup dari Fonnte dashboard</p>
                    </div>
                    <div>
                        <label for="group_description" class="block mb-2 text-sm font-medium text-gray-900">Deskripsi (Opsional)</label>
                        <textarea name="description" id="group_description" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" placeholder="Deskripsi grup..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button data-modal-hide="addGroupModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Batal
                    </button>
                    <button type="submit" class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah Grup
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Group Modal -->
<div id="editGroupModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Edit Grup
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-toggle="editGroupModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <form id="editGroupForm" class="p-4 md:p-5">
                <input type="hidden" id="edit_group_id" name="id">
                <div class="grid gap-4 mb-4 grid-cols-1">
                    <div>
                        <label for="edit_group_name" class="block mb-2 text-sm font-medium text-gray-900">Nama Grup</label>
                        <input type="text" name="name" id="edit_group_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="edit_group_group_id" class="block mb-2 text-sm font-medium text-gray-900">ID Grup WhatsApp</label>
                        <input type="text" name="group_id" id="edit_group_group_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="edit_group_description" class="block mb-2 text-sm font-medium text-gray-900">Deskripsi</label>
                        <textarea name="description" id="edit_group_description" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4">
                    <button data-modal-hide="editGroupModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
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