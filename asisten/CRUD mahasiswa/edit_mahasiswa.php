<?php
$pageTitle = 'Edit Pengguna';
$activePage = 'mahasiswa'; // Untuk menandai menu aktif

// --- PATH YANG SUDAH DIPERBAIKI ---
// Naik satu level (ke folder asisten), lalu masuk ke folder templates
require_once '../templates/header.php'; 
// Naik dua level untuk mencapai folder root, lalu cari config.php
require_once '../../config.php';

// Validasi ID dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mahasiswa.php");
    exit();
}
$id = $_GET['id'];

// Ambil data pengguna dari DB
$stmt = $conn->prepare("SELECT id, nama, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "Pengguna tidak ditemukan.";
    // Pastikan footer dipanggil sebelum script berhenti jika terjadi error
    require_once '../templates/footer.php';
    exit();
}
?>
<main class="container mx-auto p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?= htmlspecialchars($pageTitle) ?></h1>
    <div class="bg-white p-6 rounded-xl shadow-md max-w-lg mx-auto">
        <form action="mahasiswa_process.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <div class="mb-4">
                <label for="nama" class="block text-gray-700 font-bold mb-2">Nama Lengkap</label>
                <input type="text" name="nama" id="nama" class="w-full px-3 py-2 border rounded-lg" value="<?= htmlspecialchars($user['nama']) ?>" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                <input type="email" name="email" id="email" class="w-full px-3 py-2 border rounded-lg" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-bold mb-2">Password Baru</label>
                <input type="password" name="password" id="password" class="w-full px-3 py-2 border rounded-lg" placeholder="Kosongkan jika tidak ingin diubah">
            </div>
            <div class="mb-4">
                <label for="role" class="block text-gray-700 font-bold mb-2">Role</label>
                <select name="role" id="role" class="w-full px-3 py-2 border rounded-lg">
                    <option value="mahasiswa" <?= ($user['role'] == 'mahasiswa') ? 'selected' : '' ?>>Mahasiswa</option>
                    <option value="asisten" <?= ($user['role'] == 'asisten') ? 'selected' : '' ?>>Asisten</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white font-bold px-6 py-3 rounded-lg hover:bg-green-700">Update Pengguna</button>
        </form>
    </div>
</main>
<?php 
// --- PATH FOOTER YANG JUGA DIPERBAIKI ---
require_once '../templates/footer.php'; 
?>