<?php
// Re-registration flow has been merged into pendaftaran.php
// This file is kept for backward compatibility
// Redirect to pendaftaran.php

session_start();
header("Location: pendaftaran.php");
exit;
?>

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

$exam_result = null;
$can_register = false;
$error_message = "";

// Check exam results jika user sudah login
if ($is_logged_in) {
    $query = "SELECT * FROM hasil_ujian WHERE siswa_id = ? ORDER BY waktu_submit DESC LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exam_result = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Tentukan passing score (misalnya 60)
    $passing_score = 60;
    
    if ($exam_result) {
        $can_register = ($exam_result['nilai'] >= $passing_score);
        if (!$can_register) {
            $error_message = "Anda belum lulus ujian. Nilai Anda: " . $exam_result['nilai'] . "/100. Minimum kelulusan: " . $passing_score . "/100";
        }
    } else {
        $error_message = "Anda belum mengikuti ujian. Silakan ikuti ujian terlebih dahulu.";
        $can_register = false;
    }
}

// Ambil foto lama dari users atau pendaftaran
$foto_lama = '';
if ($is_logged_in) {
  $qfoto = mysqli_query($koneksi, "SELECT photo FROM users WHERE id = '".$user_id."' LIMIT 1");
  if ($qfoto && $rfoto = mysqli_fetch_assoc($qfoto)) {
    $foto_lama = $rfoto['photo'];
  }
  // Jika belum ada di users, cek di pendaftaran terakhir
  if (!$foto_lama) {
    $qfoto2 = mysqli_query($koneksi, "SELECT photo FROM pendaftaran WHERE email = (SELECT email FROM users WHERE id = '".$user_id."') ORDER BY id DESC LIMIT 1");
    if ($qfoto2 && $rfoto2 = mysqli_fetch_assoc($qfoto2)) {
      $foto_lama = $rfoto2['photo'];
    }
  }
}

// Handle registration
if ($is_logged_in && $can_register && isset($_POST['submit'])) {
  $first_name   = htmlspecialchars($_POST['first_name']);
  $last_name    = htmlspecialchars($_POST['last_name']);
  $email        = htmlspecialchars($_POST['email']);
  $jurusan      = htmlspecialchars($_POST['jurusan']);
  $phone        = htmlspecialchars($_POST['phone']);
  $school_level = htmlspecialchars($_POST['school_level']);

  // validasi email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Email tidak valid');</script>";
  } else {
    $foto_name = $_FILES['photo']['name'] ?? '';
    $tmp_name  = $_FILES['photo']['tmp_name'] ?? '';
    $new_name = $foto_lama;
    if ($foto_name && $tmp_name) {
      // validasi tipe file
      $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
      $file_type = mime_content_type($tmp_name);
      if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF');</script>";
      } else {
        $folder = "uploads/foto/";
        $new_name = time() . "_" . $foto_name;
        if (move_uploaded_file($tmp_name, $folder . $new_name)) {
          // update juga ke tabel users
          mysqli_query($koneksi, "UPDATE users SET photo = '".$new_name."' WHERE id = '".$user_id."'");
        } else {
          echo "<script>alert('Gagal upload foto');</script>";
        }
      }
    }
    // simpan ke database menggunakan prepared statement
    $query = "INSERT INTO pendaftaran 
      (first_name, last_name, email, jurusan, phone, school_level, photo, user_id)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "sssssssi", $first_name, $last_name, $email, $jurusan, $phone, $school_level, $new_name, $user_id);
    if (mysqli_stmt_execute($stmt)) {
      echo "<script>alert('Pendaftaran ulang berhasil!'); window.location.href='dashboard.php';</script>";
    } else {
      echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
    }
    mysqli_stmt_close($stmt);
  }
}

// If user not logged in, redirect to login
if (!$is_logged_in) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Ulang - SMK Negeri 65 Jakarta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary-color: #1e3a5f;
      --secondary-color: #2d5a7b;
      --accent-orange: #ff8c2b;
      --teal-color: #1a4d5c;
      --light-bg: #f8f9fa;
      --dark-bg: #2c3e50;
      --success-color: #ff9800;
      --danger-color: #dc3545;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: var(--light-bg);
    }

    .navbar {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
      padding: 12px 0;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 22px;
      color: white !important;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .navbar-logo {
      width: 60px;
      height: 60px;
      object-fit: contain;
    }

    .navbar-icons {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .profile-dropdown {
      position: relative;
      display: inline-block;
    }

    .profile-btn {
      background: transparent;
      border: none;
      color: white;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      padding: 8px 15px;
      display: flex;
      align-items: center;
      gap: 8px;
      border-radius: 6px;
      transition: all 0.3s;
    }

    .profile-btn:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .registration-container {
      min-height: calc(100vh - 80px);
      display: flex;
      align-items: center;
      background: linear-gradient(135deg, var(--light-bg) 0%, #ffffff 100%);
      padding: 40px 20px;
    }

    .registration-wrapper {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      margin: 0 auto;
      width: 100%;
    }

    .registration-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 30px;
      text-align: center;
    }

    .registration-header h2 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .registration-header p {
      font-size: 14px;
      opacity: 0.9;
    }

    .registration-form {
      padding: 40px;
    }

    .alert-box {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-danger {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .alert-info {
      background: #d1ecf1;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      font-weight: 600;
      color: var(--primary-color);
      margin-bottom: 8px;
      display: block;
      font-size: 14px;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      transition: all 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--teal-color);
      box-shadow: 0 0 0 3px rgba(45, 138, 138, 0.1);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .file-input-wrapper {
      position: relative;
      display: block;
    }

    .file-input-wrapper input[type="file"] {
      display: none;
    }

    .file-upload-btn {
      display: inline-block;
      width: 100%;
      padding: 12px;
      background: var(--teal-color);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s;
      text-align: center;
    }

    .file-upload-btn:hover {
      background: var(--primary-color);
    }

    .button-group {
      display: flex;
      gap: 15px;
      margin-top: 30px;
    }

    .btn-cancel {
      flex: 1;
      padding: 12px;
      background: #ddd;
      color: #333;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-cancel:hover {
      background: #bbb;
      color: #000;
    }

    .btn-submit {
      flex: 1;
      padding: 12px;
      background: var(--accent-orange);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-submit:hover {
      background: #ff7a00;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(255, 140, 43, 0.3);
    }

    .btn-submit:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }

    .exam-result-card {
      background: linear-gradient(135deg, #e8f4f8 0%, #f0e8f8 100%);
      border: 2px solid var(--teal-color);
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      text-align: center;
    }

    .exam-result-card.pass {
      border-color: var(--success-color);
      background: linear-gradient(135deg, #d4edda 0%, #e2f5e0 100%);
    }

    .exam-result-card.fail {
      border-color: var(--danger-color);
      background: linear-gradient(135deg, #f8d7da 0%, #fde8e8 100%);
    }

    .exam-result-score {
      font-size: 36px;
      font-weight: 700;
      color: var(--primary-color);
      margin: 10px 0;
    }

    .exam-result-card.pass .exam-result-score {
      color: var(--success-color);
    }

    .exam-result-card.fail .exam-result-score {
      color: var(--danger-color);
    }

    .exam-result-status {
      font-size: 16px;
      font-weight: 600;
      color: var(--primary-color);
      margin-top: 10px;
    }

    @media (max-width: 600px) {
      .form-row {
        grid-template-columns: 1fr;
      }

      .registration-form {
        padding: 25px;
      }

      .registration-header {
        padding: 20px;
      }

      .registration-header h2 {
        font-size: 22px;
      }
    }
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php">
        <img src="universitas_nusantara-removebg-preview.png" alt="Logo" class="navbar-logo">
        <span>Universitas Nusantara</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <div class="ms-auto navbar-icons">
          <div class="profile-dropdown">
            <button class="profile-btn" onclick="window.location.href='dashboard.php'">
              <i class="bi bi-arrow-left"></i>
              <span>Kembali</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <div class="registration-container">
    <div class="registration-wrapper">
      <div class="registration-header">
        <h2><i class="bi bi-clipboard-check"></i> Pendaftaran Ulang</h2>
        <p>Daftar kembali untuk mengikuti ujian lanjutan</p>
      </div>

      <div class="registration-form">
        <!-- Exam Result Status -->
        <?php if ($exam_result): ?>
          <div class="exam-result-card <?php echo $can_register ? 'pass' : 'fail'; ?>">
            <div style="font-size: 14px; color: #666;">Status Hasil Ujian</div>
            <div class="exam-result-status">
              <?php if ($can_register): ?>
                <i class="bi bi-check-circle" style="color: var(--success-color);"></i>
                Selamat! Anda lulus dan dapat mendaftar ulang
              <?php else: ?>
                <i class="bi bi-x-circle" style="color: var(--danger-color);"></i>
                Sayangnya, Anda belum lulus
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Error/Info Message -->
        <?php if ($error_message && !$can_register): ?>
          <div class="alert-box alert-danger">
            <i class="bi bi-exclamation-circle"></i>
            <span><?php echo $error_message; ?></span>
          </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <?php if ($can_register): ?>
          <!-- DEBUG FOTO LAMA -->
          <?php if ($foto_lama) { echo '<div style="color:green;font-weight:bold;">DEBUG: Foto ditemukan: ' . htmlspecialchars($foto_lama) . '</div>'; } else { echo '<div style="color:red;font-weight:bold;">DEBUG: Tidak ada foto lama</div>'; } ?>
          <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
              <div class="form-group">
                <label for="first_name">Nama Depan</label>
                <input type="text" id="first_name" name="first_name" required>
              </div>
              <div class="form-group">
                <label for="last_name">Nama Belakang</label>
                <input type="text" id="last_name" name="last_name" required>
              </div>
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
              <label for="phone">Nomor Telepon</label>
              <input type="tel" id="phone" name="phone" required>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="jurusan">Jurusan</label>
                <select id="jurusan" name="jurusan" required>
                  <option value="">-- Pilih Jurusan --</option>
                  <option value="business">Business</option>
                  <option value="it_software">IT Software</option>
                  <option value="design">Design</option>
                </select>
              </div>
              <div class="form-group">
                <label for="school_level">Tingkat Sekolah</label>
                <select id="school_level" name="school_level" required>
                  <option value="">-- Pilih Tingkat --</option>
                  <option value="sma">SMA</option>
                  <option value="smk">SMK</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Foto Profil</label>
              <?php if ($foto_lama): ?>
                <div style="margin-bottom:10px;">
                  <img src="uploads/foto/<?php echo htmlspecialchars($foto_lama); ?>" alt="Foto Profil" style="max-width:120px;max-height:120px;border-radius:8px;border:1px solid #ddd;">
                  <div style="font-size:13px;color:#666;">Foto sudah pernah diunggah. Anda bisa mengganti jika ingin.</div>
                </div>
              <?php endif; ?>
              <div class="file-input-wrapper">
                <button type="button" class="file-upload-btn" onclick="document.getElementById('photoInput').click()">
                  <i class="bi bi-cloud-upload"></i> Pilih Foto
                </button>
                <input type="file" id="photoInput" name="photo" accept="image/*" onchange="updateFileName(this)">
              </div>
              <small style="color: #999; margin-top: 5px; display: block;">Format: JPG, PNG, GIF (Max 5MB)</small>
              <div id="fileName" style="margin-top: 8px; font-weight: 600; color: var(--teal-color);"></div>
            </div>

            <div class="button-group">
              <a href="dashboard.php" class="btn-cancel">
                <i class="bi bi-x-circle"></i> Batal
              </a>
              <button type="submit" name="submit" class="btn-submit">
                <i class="bi bi-check-circle"></i> Daftar
              </button>
            </div>
          </form>
        <?php else: ?>
          <div class="alert-box alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
              <strong>Akses Ditolak</strong><br>
              <?php echo $error_message; ?>
            </div>
          </div>
          <a href="dashboard.php" class="btn-cancel" style="display: block; text-align: center; margin-top: 20px;">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function updateFileName(input) {
      const fileName = document.getElementById('fileName');
      if (input.files && input.files[0]) {
        fileName.textContent = 'File: ' + input.files[0].name;
      }
    }
  </script>
</body>
</html>
