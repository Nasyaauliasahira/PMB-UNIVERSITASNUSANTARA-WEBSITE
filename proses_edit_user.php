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
    header("Location: register_login_admin.php");
    exit;
}

// Validasi parameter
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['alert_message'] = "ID user tidak valid!";
    $_SESSION['alert_type'] = "danger";
    header("Location: register_login_admin.php");
    exit;
}

// Ambil dan validasi data dari form
$id = intval($_POST['id']);
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$password = trim($_POST['password']);

// Validasi data tidak boleh kosong (kecuali password)
if (empty($first_name) || empty($last_name) || empty($email)) {
    $_SESSION['alert_message'] = "Semua field wajib diisi (kecuali password)!";
    $_SESSION['alert_type'] = "danger";
    header("Location: edit_user_admin.php?id=$id");
    exit;
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['alert_message'] = "Format email tidak valid!";
    $_SESSION['alert_type'] = "danger";
    header("Location: edit_user_admin.php?id=$id");
    exit;
}

// Cek apakah email sudah digunakan oleh user lain
$stmt_check = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? AND id != ?");
mysqli_stmt_bind_param($stmt_check, "si", $email, $id);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) > 0) {
    $_SESSION['alert_message'] = "Email sudah digunakan oleh user lain!";
    $_SESSION['alert_type'] = "danger";
    mysqli_stmt_close($stmt_check);
    header("Location: edit_user_admin.php?id=$id");
    exit;
}
mysqli_stmt_close($stmt_check);

// Proses update data
if (!empty($password)) {
    // Jika password diisi, update termasuk password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($koneksi, "UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssssi", $first_name, $last_name, $email, $hashed_password, $id);
} else {
    // Jika password kosong, update tanpa password
    $stmt = mysqli_prepare($koneksi, "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "sssi", $first_name, $last_name, $email, $id);
}

// Eksekusi query
if (mysqli_stmt_execute($stmt)) {
    $_SESSION['alert_message'] = "Data user berhasil diperbarui!";
    $_SESSION['alert_type'] = "success";
    mysqli_stmt_close($stmt);
    header("Location: register_login_admin.php");
    exit;
} else {
    $_SESSION['alert_message'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
    $_SESSION['alert_type'] = "danger";
    mysqli_stmt_close($stmt);
    header("Location: edit_user_admin.php?id=$id");
    exit;
}
?>
