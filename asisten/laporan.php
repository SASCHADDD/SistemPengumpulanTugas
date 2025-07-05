<?php
// Atur judul halaman dan halaman aktif untuk navigasi
$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';
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
$pesan = '';

// --- PROSES FORM PENILAIAN (METHOD POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nilai') {
    $submission_id = $_POST['submission_id'];
    $nilai = $_POST['nilai'];
    $feedback = $_POST['feedback'];
    
    // Update laporan di database dengan nilai, feedback, dan ubah status menjadi 'Selesai'
    $stmt_nilai = $conn->prepare("UPDATE pengumpulan_laporan SET nilai = ?, feedback = ?, status = 'Selesai' WHERE id = ?");
    $stmt_nilai->bind_param("dsi", $nilai, $feedback, $submission_id);
    
    if ($stmt_nilai->execute()) {
        $_SESSION['pesan_sukses'] = "Nilai berhasil disimpan.";
    } else {
        $_SESSION['pesan_error'] = "Gagal menyimpan nilai.";
    }
    $stmt_nilai->close();
    
    // Redirect untuk menghindari resubmission form (Post-Redirect-Get Pattern)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Menampilkan pesan dari session jika ada
if (isset($_SESSION['pesan_sukses'])) {
    $pesan = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6' role='alert'><strong>Sukses!</strong> {$_SESSION['pesan_sukses']}</div>";
    unset($_SESSION['pesan_sukses']);
}
if (isset($_SESSION['pesan_error'])) {
    $pesan = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6' role='alert'><strong>Error!</strong> {$_SESSION['pesan_error']}</div>";
    unset($_SESSION['pesan_error']);
}


// --- LOGIKA FILTER DAN PENGAMBILAN DATA (METHOD GET) ---

// Ambil data untuk dropdown filter
$praktikums = $conn->query("SELECT id, nama_praktikum FROM mata_praktikum WHERE asisten_id = $asisten_id");
$moduls = $conn->query("SELECT m.id, m.judul_modul FROM modul m JOIN mata_praktikum mp ON m.praktikum_id = mp.id WHERE mp.asisten_id = $asisten_id");
$mahasiswas = $conn->query("SELECT DISTINCT u.id, u.nama FROM users u JOIN pengumpulan_laporan pl ON u.id = pl.mahasiswa_id JOIN modul m ON pl.modul_id = m.id JOIN mata_praktikum mp ON m.praktikum_id = mp.id WHERE mp.asisten_id = $asisten_id");

// Persiapkan query dasar untuk mengambil laporan
$sql = "SELECT 
            pl.id, pl.tanggal_kumpul, pl.status, pl.nilai, pl.file_laporan,
            m.judul_modul, 
            mp.nama_praktikum, 
            u.nama AS nama_mahasiswa
        FROM 
            pengumpulan_laporan pl
        JOIN 
            users u ON pl.mahasiswa_id = u.id
        JOIN 
            modul m ON pl.modul_id = m.id
        JOIN 
            mata_praktikum mp ON m.praktikum_id = mp.id
        WHERE 
            mp.asisten_id = ?"; // Filter wajib: hanya praktikum yg diampu asisten ini

$params = [$asisten_id];
$types = "i";

// Terapkan filter dari URL (GET)
$filter_praktikum_id = $_GET['praktikum_id'] ?? '';
$filter_modul_id = $_GET['modul_id'] ?? '';
$filter_mahasiswa_id = $_GET['mahasiswa_id'] ?? '';
$filter_status = $_GET['status'] ?? '';

if (!empty($filter_praktikum_id)) { $sql .= " AND mp.id = ?"; $params[] = $filter_praktikum_id; $types .= "i"; }
if (!empty($filter_modul_id)) { $sql .= " AND m.id = ?"; $params[] = $filter_modul_id; $types .= "i"; }
if (!empty($filter_mahasiswa_id)) { $sql .= " AND u.id = ?"; $params[] = $filter_mahasiswa_id; $types .= "i"; }
if (!empty($filter_status)) { $sql .= " AND pl.status = ?"; $params[] = $filter_status; $types .= "s"; }

$sql .= " ORDER BY pl.tanggal_kumpul DESC";

// Eksekusi query utama
$stmt_laporan = $conn->prepare($sql);
if (count($params) > 1) {
    $stmt_laporan->bind_param($types, ...$params);
} else {
    $stmt_laporan->bind_param($types, $asisten_id);
}
$stmt_laporan->execute();
$result_laporan = $stmt_laporan->get_result();


// Sertakan header setelah semua logika selesai
require_once 'templates/header.php';
?>

<main class="container mx-auto p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Laporan Masuk</h1>

    <?= $pesan ?>

    <div class="bg-white p-6 rounded-xl shadow-md mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Filter Laporan</h3>
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label for="praktikum_id" class="block text-sm font-medium text-gray-700">Praktikum</label>
                <select name="praktikum_id" id="praktikum_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">Semua</option>
                    <?php while($p = $praktikums->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= ($filter_praktikum_id == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_praktikum']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label for="modul_id" class="block text-sm font-medium text-gray-700">Modul</label>
                <select name="modul_id" id="modul_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">Semua</option>
                     <?php while($m = $moduls->fetch_assoc()): ?>
                        <option value="<?= $m['id'] ?>" <?= ($filter_modul_id == $m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['judul_modul']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label for="mahasiswa_id" class="block text-sm font-medium text-gray-700">Mahasiswa</label>
                <select name="mahasiswa_id" id="mahasiswa_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">Semua</option>
                     <?php while($mhs = $mahasiswas->fetch_assoc()): ?>
                        <option value="<?= $mhs['id'] ?>" <?= ($filter_mahasiswa_id == $mhs['id']) ? 'selected' : '' ?>><?= htmlspecialchars($mhs['nama']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">Semua</option>
                    <option value="Menunggu" <?= ($filter_status == 'Menunggu') ? 'selected' : '' ?>>Menunggu</option>
                    <option value="Selesai" <?= ($filter_status == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full bg-blue-600 text-white font-bold px-4 py-2 rounded-lg hover:bg-blue-700">Filter</button>
                <a href="laporan.php" class="w-full text-center bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-lg hover:bg-gray-400">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-md">
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left">Praktikum & Modul</th>
                        <th class="py-2 px-4 text-left">Mahasiswa</th>
                        <th class="py-2 px-4 text-left">Tgl Kumpul</th>
                        <th class="py-2 px-4 text-center">Status</th>
                        <th class="py-2 px-4 text-center">Nilai</th>
                        <th class="py-2 px-4 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_laporan->num_rows > 0): ?>
                        <?php while($row = $result_laporan->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="py-3 px-4">
                                    <p class="font-bold"><?= htmlspecialchars($row['nama_praktikum']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($row['judul_modul']) ?></p>
                                </td>
                                <td class="py-3 px-4"><?= htmlspecialchars($row['nama_mahasiswa']) ?></td>
                                <td class="py-3 px-4"><?= date('d M Y, H:i', strtotime($row['tanggal_kumpul'])) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <?php if($row['status'] == 'Selesai'): ?>
                                        <span class="bg-green-200 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Selesai</span>
                                    <?php else: ?>
                                        <span class="bg-yellow-200 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center font-bold"><?= htmlspecialchars($row['nilai'] ?? '-') ?></td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-3">
                                        <a href="../<?= htmlspecialchars($row['file_laporan']) ?>" target="_blank" class="text-blue-600 hover:underline text-sm">Unduh</a>
                                        <?php if($row['status'] == 'Menunggu'): ?>
                                            <form action="" method="POST" class="flex items-center space-x-2">
                                                <input type="hidden" name="action" value="nilai">
                                                <input type="hidden" name="submission_id" value="<?= $row['id'] ?>">
                                                <input type="number" name="nilai" placeholder="Nilai" class="w-20 border rounded px-2 py-1 text-sm" required min="0" max="100" step="0.5">
                                                <input type="text" name="feedback" placeholder="Feedback singkat" class="w-32 border rounded px-2 py-1 text-sm">
                                                <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">Simpan</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-4 px-4 text-center text-gray-500">Tidak ada laporan yang cocok dengan filter yang diterapkan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
// Sertakan Footer
require_once 'templates/footer.php';
$conn->close();
?>