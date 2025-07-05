<?php
// Mulai session untuk menyimpan pesan feedback
session_start();

// Sertakan file konfigurasi database
require_once '../../config.php';

// Keamanan: Pastikan hanya asisten yang bisa mengakses & request adalah POST
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak atau metode request salah.");
}

// Ambil nilai 'action' dari form
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        // Logika untuk 'create' yang sudah ada
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
        if ($stmt->execute()) {
            $_SESSION['pesan_sukses'] = "Pengguna baru berhasil ditambahkan.";
        } else {
            $_SESSION['pesan_error'] = "Gagal menambahkan pengguna.";
        }
        $stmt->close();
        break;

    // ===============================================
    // === LOGIKA BARU UNTUK PROSES UPDATE         ===
    // ===============================================
    case 'update':
        // Ambil data dari formulir edit
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $password = $_POST['password']; // Bisa kosong
        $role = $_POST['role'];

        // Cek apakah password baru diisi atau tidak
        if (!empty($password)) {
            // Jika password diisi, hash password baru dan update semua kolom
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET nama=?, email=?, password=?, role=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nama, $email, $hashed_password, $role, $id);
        } else {
            // Jika password kosong, update semua KECUALI password
            $sql = "UPDATE users SET nama=?, email=?, role=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nama, $email, $role, $id);
        }

        // Eksekusi perintah dan berikan pesan feedback
        if ($stmt->execute()) {
            $_SESSION['pesan_sukses'] = "Data pengguna berhasil diperbarui.";
        } else {
            $_SESSION['pesan_error'] = "Gagal memperbarui data pengguna.";
        }
        $stmt->close();
        break;

    case 'delete':
        // Logika untuk 'delete' yang sudah ada
        $id = $_POST['id'];
        if ($id == $_SESSION['user_id']) {
            $_SESSION['pesan_error'] = "Anda tidak dapat menghapus akun Anda sendiri.";
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['pesan_sukses'] = "Pengguna berhasil dihapus.";
            } else {
                $_SESSION['pesan_error'] = "Gagal menghapus pengguna.";
            }
            $stmt->close();
        }
        break;
}

// Setelah proses selesai, arahkan pengguna kembali ke halaman daftar
header("Location: mahasiswa.php");
exit();
?>