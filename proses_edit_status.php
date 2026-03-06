<?php
session_start();
include "koneksi.php";

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

// Validasi method POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: status_admin.php");
    exit;
}

// Validasi parameter
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['alert_message'] = "ID hasil ujian tidak valid!";
    $_SESSION['alert_type'] = "danger";
    header("Location: status_admin.php");
    exit;
}

// Ambil dan validasi data dari form
$id = intval($_POST['id']);
$nilai = floatval($_POST['nilai']);
$jumlah_benar = intval($_POST['jumlah_benar']);

// Validasi nilai
if ($nilai < 0 || $nilai > 100) {
    $_SESSION['alert_message'] = "Nilai harus antara 0 - 100!";
    $_SESSION['alert_type'] = "danger";
    header("Location: edit_status_admin.php?id=$id");
    exit;
}

// Validasi jumlah_benar
if ($jumlah_benar < 0) {
    $_SESSION['alert_message'] = "Jumlah benar tidak boleh negatif!";
    $_SESSION['alert_type'] = "danger";
    header("Location: edit_status_admin.php?id=$id");
    exit;
}

// Cek apakah data hasil ujian ada
$stmt_check = mysqli_prepare($koneksi, "SELECT id FROM hasil_ujian WHERE id = ?");
mysqli_stmt_bind_param($stmt_check, "i", $id);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) == 0) {
    $_SESSION['alert_message'] = "Data hasil ujian tidak ditemukan!";
    $_SESSION['alert_type'] = "danger";
    mysqli_stmt_close($stmt_check);
    header("Location: status_admin.php");
    exit;
}
mysqli_stmt_close($stmt_check);

// Update data hasil ujian
$stmt = mysqli_prepare($koneksi, "UPDATE hasil_ujian SET nilai = ?, jumlah_benar = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "dii", $nilai, $jumlah_benar, $id);

// Eksekusi query
if (mysqli_stmt_execute($stmt)) {
    $status = $nilai >= 70 ? 'LULUS' : 'TIDAK LULUS';
    $_SESSION['alert_message'] = "Data hasil ujian berhasil diperbarui! Status: $status";
    $_SESSION['alert_type'] = "success";
    mysqli_stmt_close($stmt);
    header("Location: status_admin.php");
    exit;
} else {
    $_SESSION['alert_message'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
    $_SESSION['alert_type'] = "danger";
    mysqli_stmt_close($stmt);
    header("Location: edit_status_admin.php?id=$id");
    exit;
}
?>
