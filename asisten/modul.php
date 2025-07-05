<?php
$pageTitle = 'Manajemen Modul';
$activePage = 'modul';
require_once 'templates/header.php'; 

// Mulai session dan sertakan file konfigurasi
require_once '../config.php';

// --- Keamanan: Cek otentikasi dan role pengguna ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

// Ambil ID asisten dari session
$asisten_id = $_SESSION['user_id'];
$pesan = ''; // Variabel untuk menyimpan pesan feedback


// --- Ambil Nama Praktikum ---
$stmt_check = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
$stmt_check->bind_param("i", $praktikum_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    echo "Praktikum tidak ditemukan.";
    exit();
}
$praktikum = $result_check->fetch_assoc();
$nama_praktikum = $praktikum['nama_praktikum'];
$stmt_check->close();

// --- LOGIKA UNTUK PROSES FORM (CREATE, UPDATE, DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -- Aksi: Tambah Modul --
    if ($action === 'tambah') {
        $judul = $_POST['judul_modul'];
        $deskripsi = $_POST['deskripsi'];
        $file_path = null;

        // Proses upload file jika ada
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['file_materi']['tmp_name'];
            $file_name = basename($_FILES['file_materi']['name']);
            $upload_dir = '../uploads/materi/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $file_path = $upload_dir . uniqid() . '-' . $file_name;
            move_uploaded_file($file_tmp_path, $file_path);
        }

        $stmt_insert = $conn->prepare("INSERT INTO modul (praktikum_id, judul_modul, deskripsi, file_materi) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("isss", $praktikum_id, $judul, $deskripsi, $file_path);
        if($stmt_insert->execute()) {
            $pesan = "Modul berhasil ditambahkan!";
        } else {
            $pesan = "Gagal menambahkan modul.";
        }
        $stmt_insert->close();
    }
    
    // -- Aksi: Hapus Modul --
    elseif ($action === 'hapus') {
        $modul_id = $_POST['modul_id'];
        
        // Ambil path file untuk dihapus dari server
        $stmt_getfile = $conn->prepare("SELECT file_materi FROM modul WHERE id = ? AND praktikum_id IN (SELECT id FROM mata_praktikum WHERE asisten_id = ?)");
        $stmt_getfile->bind_param("ii", $modul_id, $asisten_id);
        $stmt_getfile->execute();
        $file_to_delete = $stmt_getfile->get_result()->fetch_assoc();
        if ($file_to_delete && !empty($file_to_delete['file_materi']) && file_exists($file_to_delete['file_materi'])) {
            unlink($file_to_delete['file_materi']);
        }
        $stmt_getfile->close();
        
        // Hapus dari database
        $stmt_delete = $conn->prepare("DELETE FROM modul WHERE id = ?");
        $stmt_delete->bind_param("i", $modul_id);
        if($stmt_delete->execute()) {
            $pesan = "Modul berhasil dihapus!";
        } else {
            $pesan = "Gagal menghapus modul.";
        }
        $stmt_delete->close();
    }
    
    // -- Aksi: Edit Modul --
    elseif ($action === 'edit') {
        $modul_id = $_POST['modul_id'];
        $judul = $_POST['judul_modul'];
        $deskripsi = $_POST['deskripsi'];

        // Ambil path file lama
        $stmt_get_old_file = $conn->prepare("SELECT file_materi FROM modul WHERE id = ?");
        $stmt_get_old_file->bind_param("i", $modul_id);
        $stmt_get_old_file->execute();
        $old_file_path = $stmt_get_old_file->get_result()->fetch_assoc()['file_materi'];
        $stmt_get_old_file->close();
        
        $file_path = $old_file_path;

        // Jika ada file baru diupload, proses dan hapus file lama
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
            if ($old_file_path && file_exists($old_file_path)) {
                unlink($old_file_path);
            }
            $file_tmp_path = $_FILES['file_materi']['tmp_name'];
            $file_name = basename($_FILES['file_materi']['name']);
            $upload_dir = '../uploads/materi/';
            $file_path = $upload_dir . uniqid() . '-' . $file_name;
            move_uploaded_file($file_tmp_path, $file_path);
        }

        $stmt_update = $conn->prepare("UPDATE modul SET judul_modul = ?, deskripsi = ?, file_materi = ? WHERE id = ?");
        $stmt_update->bind_param("sssi", $judul, $deskripsi, $file_path, $modul_id);
        if($stmt_update->execute()){
            $pesan = "Modul berhasil diperbarui!";
        } else {
            $pesan = "Gagal memperbarui modul.";
        }
        $stmt_update->close();
        // Redirect untuk membersihkan form dari mode edit
        header("Location: modul.php?praktikum_id=" . $praktikum_id);
        exit();
    }
}

// --- LOGIKA UNTUK TAMPILAN (READ) ---
// Variabel untuk mode edit
$edit_mode = false;
$modul_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['modul_id'])) {
    $edit_mode = true;
    $modul_id_edit = $_GET['modul_id'];
    $stmt_edit = $conn->prepare("SELECT * FROM modul WHERE id = ?");
    $stmt_edit->bind_param("i", $modul_id_edit);
    $stmt_edit->execute();
    $modul_to_edit = $stmt_edit->get_result()->fetch_assoc();
    $stmt_edit->close();
}


// Ambil semua modul untuk praktikum ini
$list_modul = [];
$stmt_get_all = $conn->prepare("SELECT * FROM modul WHERE praktikum_id = ? ORDER BY id ASC");
$stmt_get_all->bind_param("i", $praktikum_id);
$stmt_get_all->execute();
$result_all = $stmt_get_all->get_result();
while ($row = $result_all->fetch_assoc()) {
    $list_modul[] = $row;
}
$stmt_get_all->close();


$pageTitle = 'Kelola Modul';
include 'templates/header.php'; // Sertakan header asisten
?>

<main class="container mx-auto p-8">
    <a href="dashboard.php" class="text-blue-600 hover:underline mb-6 inline-block">&larr; Kembali ke Dashboard</a>
    
    <h1 class="text-3xl font-bold text-gray-800 mb-2">Kelola Modul</h1>
    <h2 class="text-xl font-semibold text-gray-600 mb-8"><?= htmlspecialchars($nama_praktikum) ?></h2>
    
    <?php if ($pesan): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
            <span class="font-bold"><?= htmlspecialchars($pesan) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-md mb-8">
        <h3 class="text-2xl font-bold text-gray-800 mb-4"><?= $edit_mode ? 'Edit Modul' : 'Tambah Modul Baru' ?></h3>
        <form action="modul.php?praktikum_id=<?= $praktikum_id ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $edit_mode ? 'edit' : 'tambah' ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="modul_id" value="<?= htmlspecialchars($modul_to_edit['id']) ?>">
            <?php endif; ?>

            <div class="mb-4">
                <label for="judul_modul" class="block text-gray-700 font-bold mb-2">Judul Modul</label>
                <input type="text" name="judul_modul" id="judul_modul" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= htmlspecialchars($modul_to_edit['judul_modul'] ?? '') ?>" required>
            </div>
            <div class="mb-4">
                <label for="deskripsi" class="block text-gray-700 font-bold mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($modul_to_edit['deskripsi'] ?? '') ?></textarea>
            </div>
            <div class="mb-4">
                <label for="file_materi" class="block text-gray-700 font-bold mb-2">File Materi (PDF/DOCX)</label>
                <?php if ($edit_mode && !empty($modul_to_edit['file_materi'])): ?>
                    <p class="text-sm text-gray-500 mb-2">File saat ini: <a href="<?= htmlspecialchars($modul_to_edit['file_materi']) ?>" target="_blank" class="text-blue-600 hover:underline"><?= basename($modul_to_edit['file_materi']) ?></a></p>
                    <p class="text-sm text-gray-500 mb-2">Unggah file baru untuk mengganti file lama.</p>
                <?php endif; ?>
                <input type="file" name="file_materi" id="file_materi" class="w-full">
            </div>
            <div>
                <button type="submit" class="bg-blue-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-300">
                    <?= $edit_mode ? 'Update Modul' : 'Tambah Modul' ?>
                </button>
                <?php if($edit_mode): ?>
                    <a href="modul.php?praktikum_id=<?= $praktikum_id ?>" class="ml-4 text-gray-600 hover:underline">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-md">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Daftar Modul</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left">Judul Modul</th>
                        <th class="py-2 px-4 text-left">File Materi</th>
                        <th class="py-2 px-4 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list_modul)): ?>
                        <tr>
                            <td colspan="3" class="py-4 px-4 text-center text-gray-500">Belum ada modul untuk praktikum ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($list_modul as $modul): ?>
                            <tr class="border-b">
                                <td class="py-2 px-4"><?= htmlspecialchars($modul['judul_modul']) ?></td>
                                <td class="py-2 px-4">
                                    <?php if (!empty($modul['file_materi'])): ?>
                                        <a href="<?= htmlspecialchars($modul['file_materi']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                            Unduh
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 flex items-center space-x-2">
                                    <a href="modul.php?praktikum_id=<?= $praktikum_id ?>&action=edit&modul_id=<?= $modul['id'] ?>" class="text-green-600 hover:text-green-800">Edit</a>
                                    <form action="modul.php?praktikum_id=<?= $praktikum_id ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus modul ini?');">
                                        <input type="hidden" name="action" value="hapus">
                                        <input type="hidden" name="modul_id" value="<?= $modul['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
// 3. Panggil Footer
require_once 'templates/footer.php';
?>