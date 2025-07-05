<?php
$pageTitle = 'Praktikum Saya';
$activePage = 'praktikum_saya';
require_once 'templates/header_mahasiswa.php'; 

// Menyertakan file konfigurasi database.
// Menggunakan ../ karena file config.php berada satu level di luar folder 'mahasiswa'
require_once '../config.php';

// Cek autentikasi: Pastikan pengguna sudah login dan rolenya adalah 'mahasiswa'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    // Jika tidak, redirect ke halaman login
    header("Location: ../login.php");
    exit();
}

// Ambil ID mahasiswa yang sedang login dari session
$mahasiswa_id = $_SESSION['user_id'];

// Menyiapkan query SQL untuk mengambil daftar praktikum yang diikuti oleh mahasiswa.
// Query ini menggabungkan 3 tabel:
// 1. pendaftaran_praktikum: Untuk mencari pendaftaran berdasarkan mahasiswa_id.
// 2. mata_praktikum: Untuk mendapatkan detail praktikum (nama, deskripsi).
// 3. users: Untuk mendapatkan nama lengkap asisten penanggung jawab praktikum.
$sql = "SELECT 
            mp.id, 
            mp.nama_praktikum, 
            mp.deskripsi, 
            u.nama AS nama_asisten
        FROM 
            pendaftaran_praktikum pp
        JOIN 
            mata_praktikum mp ON pp.praktikum_id = mp.id
        JOIN 
            users u ON mp.asisten_id = u.id
        WHERE 
            pp.mahasiswa_id = ?";

// Mempersiapkan statement untuk eksekusi yang aman
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mahasiswa_id);
$stmt->execute();
$result = $stmt->get_result();

// Mengambil semua baris hasil query ke dalam array
$courses = $result->fetch_all(MYSQLI_ASSOC);

// Menutup statement
$stmt->close();

// === BAGIAN HEADER HTML ===
// Menyertakan file header dari folder templates
// File header biasanya berisi bagian <head>, <body>, dan navigasi
?>

<main class="container mx-auto p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Praktikum Saya</h1>

    <?php if (empty($courses)): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg" role="alert">
            <p class="font-bold">Informasi</p>
            <p class="text-sm">Anda belum terdaftar di mata praktikum manapun. Silakan cari dan daftar praktikum di katalog.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($courses as $course): ?>
                <a href="course_detail.php?id=<?= htmlspecialchars($course['id']) ?>" class="block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-2"><?= htmlspecialchars($course['nama_praktikum']) ?></h2>
                        <p class="text-gray-600 text-sm mb-4">
                            <?= htmlspecialchars(substr($course['deskripsi'], 0, 100)) . (strlen($course['deskripsi']) > 100 ? '...' : '') ?>
                        </p>
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-xs text-gray-500">Dosen / Asisten:</p>
                            <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($course['nama_asisten']) ?></p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php
// === BAGIAN FOOTER HTML ===
// Menyertakan file footer dari folder templates
// File footer biasanya berisi tag penutup </body> dan </html>, serta script JS
include 'templates/footer_mahasiswa.php';

// Menutup koneksi database
$conn->close();
?>