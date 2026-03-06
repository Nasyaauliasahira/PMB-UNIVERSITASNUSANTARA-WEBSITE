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
    header("Location: pendaftaran_admin.php");
    exit;
}

// Validasi parameter
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['alert_message'] = "ID pendaftaran tidak valid!";
    $_SESSION['alert_type'] = "danger";
    header("Location: pendaftaran_admin.php");
    exit;
}

// Ambil dan validasi data dari form
 $id = intval($_POST['id']);
 $nomor_ujian = trim($_POST['nomor_ujian']);
 $first_name = trim($_POST['first_name']);
 $last_name = trim($_POST['last_name']);
 $date_of_birth = trim($_POST['date_of_birth']);
 $email = trim($_POST['email']);
 $phone = trim($_POST['phone']);
 $jurusan = trim($_POST['jurusan']);
 $school_level = trim($_POST['school_level']);

// Validasi data tidak boleh kosong
if (empty($nomor_ujian) || empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($jurusan) || empty($school_level)) {
    $_SESSION['alert_message'] = "Semua field wajib diisi!";
    $_SESSION['alert_type'] = "danger";
    header("Location: edit_pendaftaran_admin.php?id=$id");
    exit;
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['alert_message'] = "Format email tidak valid!";
    $_SESSION['alert_type'] = "danger";
    header("Location: edit_pendaftaran_admin.php?id=$id");
    exit;
}

// Cek apakah email sudah digunakan oleh pendaftar lain
$stmt_check = mysqli_prepare($koneksi, "SELECT id FROM pendaftaran WHERE email = ? AND id != ?");
mysqli_stmt_bind_param($stmt_check, "si", $email, $id);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) > 0) {
    $_SESSION['alert_message'] = "Email sudah digunakan oleh pendaftar lain!";
    $_SESSION['alert_type'] = "danger";
    mysqli_stmt_close($stmt_check);
    header("Location: edit_pendaftaran_admin.php?id=$id");
    exit;
}
mysqli_stmt_close($stmt_check);

// Update data pendaftaran
 $stmt = mysqli_prepare($koneksi, "UPDATE pendaftaran SET nomor_ujian = ?, first_name = ?, last_name = ?, date_of_birth = ?, email = ?, jurusan = ?, phone = ?, school_level = ? WHERE id = ?");
 mysqli_stmt_bind_param($stmt, "ssssssssi", $nomor_ujian, $first_name, $last_name, $date_of_birth, $email, $jurusan, $phone, $school_level, $id);

// Eksekusi query
if (mysqli_stmt_execute($stmt)) {
    $_SESSION['alert_message'] = "Data pendaftaran berhasil diperbarui!";
    $_SESSION['alert_type'] = "success";
    mysqli_stmt_close($stmt);
    header("Location: pendaftaran_admin.php");
    exit;
} else {
    $_SESSION['alert_message'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
    $_SESSION['alert_type'] = "danger";
    mysqli_stmt_close($stmt);
    header("Location: edit_pendaftaran_admin.php?id=$id");
    exit;
}
?>
