<?php
// 1. Definisi Variabel & Panggil Header
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Mulai session dan sertakan file konfigurasi
session_start();
require_once '../config.php';

// --- Keamanan: Cek otentikasi dan role pengguna ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$asisten_id = $_SESSION['user_id'];


// --- QUERIES UNTUK DATA DINAMIS ---

// 1. Ambil Data Statistik
$stmt_stats = $conn->prepare(
    "SELECT
        (SELECT COUNT(m.id) FROM modul m JOIN mata_praktikum mp_m ON m.praktikum_id = mp_m.id WHERE mp_m.asisten_id = ?) AS total_modul,
        (SELECT COUNT(pl.id) FROM pengumpulan_laporan pl JOIN modul m_pl ON pl.modul_id = m_pl.id JOIN mata_praktikum mp_pl ON m_pl.praktikum_id = mp_pl.id WHERE mp_pl.asisten_id = ?) AS total_laporan,
        (SELECT COUNT(pl.id) FROM pengumpulan_laporan pl JOIN modul m_pl2 ON pl.modul_id = m_pl2.id JOIN mata_praktikum mp_pl2 ON m_pl2.praktikum_id = mp_pl2.id WHERE mp_pl2.asisten_id = ? AND pl.status = 'Menunggu') AS laporan_menunggu"
);
$stmt_stats->bind_param("iii", $asisten_id, $asisten_id, $asisten_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();


// 2. Ambil Aktivitas Laporan Terbaru (5 terakhir)
$sql_activity = "SELECT u.nama, m.judul_modul, pl.tanggal_kumpul 
                 FROM pengumpulan_laporan pl 
                 JOIN users u ON pl.mahasiswa_id = u.id 
                 JOIN modul m ON pl.modul_id = m.id 
                 JOIN mata_praktikum mp ON m.praktikum_id = mp.id 
                 WHERE mp.asisten_id = ? 
                 ORDER BY pl.tanggal_kumpul DESC LIMIT 5";
$stmt_activity = $conn->prepare($sql_activity);
$stmt_activity->bind_param("i", $asisten_id);
$stmt_activity->execute();
$activities = $stmt_activity->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_activity->close();


// 3. Ambil Daftar Praktikum yang Diampu
$sql_praktikum = "SELECT id, nama_praktikum, deskripsi FROM mata_praktikum WHERE asisten_id = ?";
$stmt_praktikum = $conn->prepare($sql_praktikum);
$stmt_praktikum->bind_param("i", $asisten_id);
$stmt_praktikum->execute();
$list_praktikum = $stmt_praktikum->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_praktikum->close();


// Panggil Header setelah semua logika selesai
require_once 'templates/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
            <p class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($stats['total_modul'] ?? 0) ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Laporan Masuk</p>
            <p class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($stats['total_laporan'] ?? 0) ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
            <p class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($stats['laporan_menunggu'] ?? 0) ?></p>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Praktikum yang Anda Ampu</h3>
    <?php if (empty($list_praktikum)): ?>
        <p class="text-gray-500">Anda belum ditugaskan untuk mengelola mata praktikum manapun.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($list_praktikum as $praktikum): ?>
                <div class="border p-4 rounded-lg flex justify-between items-center flex-wrap gap-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($praktikum['nama_praktikum']) ?></h4>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($praktikum['deskripsi']) ?></p>
                    </div>
                    <div class="flex space-x-2 flex-shrink-0">
                        <a href="modul.php?praktikum_id=<?= $praktikum['id'] ?>" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">Kelola Modul</a>
                        <a href="laporan.php?praktikum_id=<?= $praktikum['id'] ?>" class="bg-gray-200 text-gray-800 font-semibold px-4 py-2 rounded-lg hover:bg-gray-300 text-sm">Lihat Laporan</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
    <div class="space-y-4">
        <?php if (empty($activities)): ?>
            <p class="text-gray-500">Belum ada aktivitas laporan terbaru.</p>
        <?php else: ?>
            <?php foreach ($activities as $activity): ?>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-4 flex-shrink-0">
                        <span class="font-bold text-gray-500"><?= strtoupper(substr($activity['nama'], 0, 2)) ?></span>
                    </div>
                    <div>
                        <p class="text-gray-800">
                            <strong><?= htmlspecialchars($activity['nama']) ?></strong> mengumpulkan laporan untuk <strong><?= htmlspecialchars($activity['judul_modul']) ?></strong>
                        </p>
                        <p class="text-sm text-gray-500"><?= date('d F Y, H:i', strtotime($activity['tanggal_kumpul'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<?php
// 3. Panggil Footer
require_once 'templates/footer.php';
$conn->close();
?>