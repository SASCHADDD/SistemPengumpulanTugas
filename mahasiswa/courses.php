<?php
// Atur judul halaman dan halaman aktif untuk navigasi
$pageTitle = 'Katalog Praktikum';
$activePage = 'courses';

// Mulai session dan sertakan file konfigurasi
session_start();
require_once '../config.php';

// --- Keamanan: Cek otentikasi dan role pengguna ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

// Ambil ID mahasiswa yang sedang login
$mahasiswa_id = $_SESSION['user_id'];


// --- LANGKAH 1: Ambil ID semua praktikum yang SUDAH diikuti oleh mahasiswa ---
$enrolled_courses = [];
$sql_enrolled = "SELECT praktikum_id FROM pendaftaran_praktikum WHERE mahasiswa_id = ?";
$stmt_enrolled = $conn->prepare($sql_enrolled);
$stmt_enrolled->bind_param("i", $mahasiswa_id);
$stmt_enrolled->execute();
$result_enrolled = $stmt_enrolled->get_result();
while ($row = $result_enrolled->fetch_assoc()) {
    // Masukkan semua ID praktikum yang diikuti ke dalam array
    $enrolled_courses[] = $row['praktikum_id'];
}
$stmt_enrolled->close();


// --- LANGKAH 2: Ambil SEMUA mata praktikum yang tersedia dari database ---
$sql_all_courses = "SELECT 
                        mp.id, 
                        mp.nama_praktikum, 
                        mp.deskripsi, 
                        u.nama AS nama_asisten
                    FROM 
                        mata_praktikum mp
                    JOIN 
                        users u ON mp.asisten_id = u.id
                    ORDER BY 
                        mp.nama_praktikum ASC";
$result_all_courses = $conn->query($sql_all_courses);


// Sertakan header setelah semua logika selesai
require_once 'templates/header_mahasiswa.php';
?>

<main class="container mx-auto p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Katalog Mata Praktikum</h1>
        <div class="relative">
            <input type="text" placeholder="Cari praktikum..." class="pl-4 pr-10 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="w-5 h-5 text-gray-400 absolute top-1/2 right-3 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if ($result_all_courses->num_rows > 0): ?>
            <?php while($course = $result_all_courses->fetch_assoc()): ?>
                <div class="bg-white rounded-xl shadow-md flex flex-col">
                    <div class="p-6 flex-grow">
                        <h2 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($course['nama_praktikum']) ?></h2>
                        <p class="text-gray-600 text-sm mb-4 flex-grow">
                            <?= htmlspecialchars($course['deskripsi']) ?>
                        </p>
                        <div class="text-sm text-gray-500">
                            <span>Oleh: </span>
                            <span class="font-medium text-gray-700"><?= htmlspecialchars($course['nama_asisten']) ?></span>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-b-xl">
                        <?php 
                        // Cek apakah ID praktikum saat ini ada di dalam array praktikum yang sudah diikuti
                        if (in_array($course['id'], $enrolled_courses)): 
                        ?>
                            <span class="w-full text-center inline-block bg-green-200 text-green-800 font-semibold px-4 py-2 rounded-lg text-sm">
                                Terdaftar
                            </span>
                        <?php else: ?>
                            <form action="enroll_process.php" method="POST">
                                <input type="hidden" name="praktikum_id" value="<?= $course['id'] ?>">
                                <button type="submit" class="w-full bg-blue-600 text-white font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-300 text-sm">
                                    Daftar Praktikum
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-gray-500 col-span-full">Saat ini belum ada mata praktikum yang tersedia.</p>
        <?php endif; ?>
    </div>
</main>

<?php
// Sertakan Footer
require_once 'templates/footer_mahasiswa.php';
$conn->close();