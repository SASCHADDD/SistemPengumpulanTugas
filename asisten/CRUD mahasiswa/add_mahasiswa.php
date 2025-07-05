<?php
// Atur judul halaman & halaman aktif
$pageTitle = 'Tambah Pengguna Baru';
$activePage = 'mahasiswa'; // Agar menu 'Manajemen Mahasiswa' tetap aktif

// --- PATH YANG DIPERBAIKI ---
// Naik satu level ke folder 'asisten', lalu masuk ke 'templates'
require_once '../templates/header.php'; 
?>

<main class="container mx-auto p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?= htmlspecialchars($pageTitle) ?></h1>

    <div class="bg-white p-6 rounded-xl shadow-md max-w-lg mx-auto">
        <form action="mahasiswa_process.php" method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="mb-4">
                <label for="nama" class="block text-gray-700 font-bold mb-2">Nama Lengkap</label>
                <input type="text" name="nama" id="nama" class="w-full px-3 py-2 border rounded-lg" required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                <input type="email" name="email" id="email" class="w-full px-3 py-2 border rounded-lg" required>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                <input type="password" name="password" id="password" class="w-full px-3 py-2 border rounded-lg" required>
            </div>

            <div class="mb-4">
                <label for="role" class="block text-gray-700 font-bold mb-2">Role</label>
                <select name="role" id="role" class="w-full px-3 py-2 border rounded-lg">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="asisten">Asisten</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white font-bold px-6 py-3 rounded-lg hover:bg-blue-700">Simpan Pengguna</button>
        </form>
    </div>
</main>

<?php 
// --- PATH YANG DIPERBAIKI ---
require_once '../templates/footer.php'; 
?>