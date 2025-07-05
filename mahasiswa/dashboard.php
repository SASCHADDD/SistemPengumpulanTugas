<?php
// Atur judul halaman dan halaman aktif untuk navigasi
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Mulai session dan sertakan file konfigurasi
session_start();
require_once '../config.php';

// --- Keamanan: Cek otentikasi dan role pengguna ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

// Ambil data penting dari session
$current_user_id = $_SESSION['user_id'];
$current_user_nama = $_SESSION['nama'];


// === MENGAMBIL DATA UNTUK KARTU STATISTIK ===

// 1. Hitung jumlah praktikum yang diikuti
$stmt_praktikum = $conn->prepare("SELECT COUNT(id) AS jumlah_praktikum FROM pendaftaran_praktikum WHERE mahasiswa_id = ?");
$stmt_praktikum->bind_param("i", $current_user_id);
$stmt_praktikum->execute();
$stats_praktikum = $stmt_praktikum->get_result()->fetch_assoc();
$stmt_praktikum->close();

// 2. Hitung jumlah tugas selesai dan menunggu
$stmt_tugas = $conn->prepare("SELECT 
                                COUNT(CASE WHEN status = 'Selesai' THEN 1 END) AS tugas_selesai, 
                                COUNT(CASE WHEN status = 'Menunggu' THEN 1 END) AS tugas_menunggu 
                            FROM pengumpulan_laporan WHERE mahasiswa_id = ?");
$stmt_tugas->bind_param("i", $current_user_id);
$stmt_tugas->execute();
$stats_tugas = $stmt_tugas->get_result()->fetch_assoc();
$stmt_tugas->close();


// === MENGAMBIL DATA UNTUK NOTIFIKASI TERBARU ===
$notifications = [];

// 1. Ambil notifikasi: Laporan yang baru dinilai (status 'Selesai')
$sql_nilai = "SELECT 
                pl.id, m.judul_modul, pl.tanggal_kumpul 
              FROM 
                pengumpulan_laporan pl 
              JOIN 
                modul m ON pl.modul_id = m.id
              WHERE 
                pl.mahasiswa_id = ? AND pl.status = 'Selesai'
              ORDER BY 
                pl.tanggal_kumpul DESC LIMIT 3";
$stmt_nilai = $conn->prepare($sql_nilai);
$stmt_nilai->bind_param("i", $current_user_id);
$stmt_nilai->execute();
$result_nilai = $stmt_nilai->get_result();
while ($row = $result_nilai->fetch_assoc()) {
    $notifications[] = [
        'type' => 'nilai',
        'text' => "Nilai untuk " . htmlspecialchars($row['judul_modul']) . " telah diberikan.",
        'icon' => '🔔',
        'date' => $row['tanggal_kumpul']
    ];
}
$stmt_nilai->close();

// 2. Ambil notifikasi: Pendaftaran praktikum baru
$sql_daftar = "SELECT 
                p.id, mp.nama_praktikum, p.tanggal_daftar 
              FROM 
                pendaftaran_praktikum p 
              JOIN 
                mata_praktikum mp ON p.praktikum_id = mp.id
              WHERE 
                p.mahasiswa_id = ?
              ORDER BY 
                p.tanggal_daftar DESC LIMIT 2";
$stmt_daftar = $conn->prepare($sql_daftar);
$stmt_daftar->bind_param("i", $current_user_id);
$stmt_daftar->execute();
$result_daftar = $stmt_daftar->get_result();
while ($row = $result_daftar->fetch_assoc()) {
    $notifications[] = [
        'type' => 'daftar',
        'text' => "Anda berhasil mendaftar pada mata praktikum " . htmlspecialchars($row['nama_praktikum']) . ".",
        'icon' => '✅',
        'date' => $row['tanggal_daftar']
    ];
}
$stmt_daftar->close();

// Urutkan semua notifikasi berdasarkan tanggal (yang terbaru di atas)
usort($notifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Ambil 3 notifikasi teratas
$notifications = array_slice($notifications, 0, 3);


// Sertakan header setelah semua logika selesai
require_once 'templates/header_mahasiswa.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold">Selamat Datang Kembali, <?php echo htmlspecialchars($current_user_nama); ?>!</h1>
    <p class="mt-2 opacity-90">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-blue-600"><?= htmlspecialchars($stats_praktikum['jumlah_praktikum'] ?? 0) ?></div>
        <div class="mt-2 text-lg text-gray-600">Praktikum Diikuti</div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-green-500"><?= htmlspecialchars($stats_tugas['tugas_selesai'] ?? 0) ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Selesai</div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-yellow-500"><?= htmlspecialchars($stats_tugas['tugas_menunggu'] ?? 0) ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Menunggu</div>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Notifikasi Terbaru</h3>
    <?php if (empty($notifications)): ?>
        <p class="text-gray-500">Tidak ada notifikasi terbaru untuk Anda.</p>
    <?php else: ?>
        <ul class="space-y-4">
            <?php foreach ($notifications as $notification): ?>
                <li class="flex items-start p-3 border-b border-gray-100 last:border-b-0">
                    <span class="text-xl mr-4"><?= $notification['icon'] ?></span>
                    <div>
                        <?= $notification['text'] ?>
                        <span class="text-xs text-gray-400 block mt-1"><?= date('d F Y, H:i', strtotime($notification['date'])) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
// Sertakan Footer
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>