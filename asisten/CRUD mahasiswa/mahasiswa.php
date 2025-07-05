<?php
$pageTitle = 'Manajemen Pengguna';
$activePage = 'mahasiswa';

// Path yang sudah benar
require_once '../templates/header.php';
require_once '../../config.php';

// Keamanan sudah ada di header.php, tapi double check tidak masalah
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../../login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Ambil semua data pengguna dari database
$sql = "SELECT id, nama, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<main class="container mx-auto p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($pageTitle) ?></h1>
        <a href="add_mahasiswa.php" class="bg-blue-600 text-white font-bold px-4 py-2 rounded-lg hover:bg-blue-700">
            + Tambah Pengguna
        </a>
    </div>

    <?php if (isset($_SESSION['pesan_sukses'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
            <?= $_SESSION['pesan_sukses'] ?>
        </div>
        <?php unset($_SESSION['pesan_sukses']); ?>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-md">
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left">Nama Lengkap</th>
                        <th class="py-2 px-4 text-left">Email</th>
                        <th class="py-2 px-4 text-left">Role</th>
                        <th class="py-2 px-4 text-left">Tanggal Daftar</th>
                        <th class="py-2 px-4 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $result->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4 font-semibold"><?= htmlspecialchars($user['nama']) ?></td>
                            <td class="py-2 px-4"><?= htmlspecialchars($user['email']) ?></td>
                            <td class="py-2 px-4">
                                <span class="px-2.5 py-0.5 text-xs rounded-full <?= $user['role'] == 'asisten' ? 'bg-blue-200 text-blue-800' : 'bg-gray-200 text-gray-800' ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td class="py-2 px-4 text-sm"><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                            <td class="py-2 px-4 flex items-center space-x-2">
                                <a href="edit_mahasiswa.php?id=<?= $user['id'] ?>" class="text-green-600 hover:text-green-800">Edit</a>
    
                                <?php if ($user['id'] !== $current_user_id): ?>
                                    <!-- FORM HAPUS YANG SUDAH DIPERBAIKI -->
                                    <form action="mahasiswa_process.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php 
// Path yang sudah benar
require_once '../templates/footer.php';
$conn->close();
?>