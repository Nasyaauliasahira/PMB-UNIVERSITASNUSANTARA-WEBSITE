<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = "SELECT u.*, p.date_of_birth FROM users u LEFT JOIN pendaftaran p ON u.email = p.email WHERE u.id = ?";
$stmt = mysqli_prepare($koneksi, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);
mysqli_stmt_close($stmt);

// Cek hasil ujian
$query_hasil = "SELECT h.*, p.jurusan, p.nomor_ujian, p.phone, p.school_level, p.photo
                FROM hasil_ujian h
                LEFT JOIN users u ON h.siswa_id = u.id
                LEFT JOIN pendaftaran p ON u.email = p.email
                WHERE h.siswa_id = ?
                ORDER BY h.waktu_submit DESC LIMIT 1";
$stmt_hasil = mysqli_prepare($koneksi, $query_hasil);
mysqli_stmt_bind_param($stmt_hasil, "i", $user_id);
mysqli_stmt_execute($stmt_hasil);
$result_hasil = mysqli_stmt_get_result($stmt_hasil);
$hasil_ujian = mysqli_fetch_assoc($result_hasil);
mysqli_stmt_close($stmt_hasil);

if (!$hasil_ujian || $hasil_ujian['nilai'] < 70) {
    $_SESSION['error_message'] = "Anda belum memenuhi syarat untuk daftar ulang.";
    header("Location: dashboard.php");
    exit;
}

// Cek apakah sudah pernah daftar ulang
$query_check = "SELECT * FROM daftar_ulang WHERE user_id = ?";
$stmt_check = mysqli_prepare($koneksi, $query_check);
mysqli_stmt_bind_param($stmt_check, "i", $user_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);
$existing_data = mysqli_fetch_assoc($result_check);
mysqli_stmt_close($stmt_check);

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message   = isset($_SESSION['error_message'])   ? $_SESSION['error_message']   : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$sudah_nim = $existing_data && !empty($existing_data['nim']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Ulang — Universitas Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    /* =============================================
       ROOT & BASE
       ============================================= */
    :root {
      --navy:         #0d1f35;
      --navy-mid:     #1e3a5f;
      --navy-light:   #2d5a7b;
      --orange:       #ff9800;
      --orange-dark:  #e68900;
      --gold:         #f0c060;
      --white:        #ffffff;
      --off-white:    #f7f5f0;
      --text-muted:   rgba(255,255,255,0.6);
      --glass:        rgba(255,255,255,0.06);
      --glass-border: rgba(255,255,255,0.12);
      --shadow-deep:  0 24px 64px rgba(13,31,53,0.35);
      --shadow-card:  0 8px 32px rgba(13,31,53,0.18);
      --radius-lg:    20px;
      --radius-xl:    32px;
      --transition:   all 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      color: var(--navy);
      overflow-x: hidden;
    }

    /* =============================================
       NAVBAR
       ============================================= */
    .navbar {
      background: rgba(13,31,53,0.92);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--glass-border);
      padding: 14px 0;
      position: sticky;
      top: 0;
      z-index: 1000;
      transition: var(--transition);
    }

    .navbar.scrolled { background: rgba(13,31,53,0.98); padding: 10px 0; }

    .navbar-brand {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 22px;
      color: white !important;
      display: flex;
      align-items: center;
      gap: 4px;
      letter-spacing: 0.5px;
      text-decoration: none;
    }

    .navbar-logo { width: 52px; height: 52px; object-fit: contain; }

    .profile-dropdown { position: relative; display: inline-block; }

    .profile-btn {
      background: var(--glass);
      border: 1px solid var(--glass-border);
      color: white;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      padding: 8px 16px 8px 8px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-radius: 50px;
      transition: var(--transition);
      font-family: 'DM Sans', sans-serif;
    }

    .profile-btn:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.25); }

    .profile-avatar {
      width: 36px; height: 36px;
      border-radius: 50%; object-fit: cover;
      border: 2px solid var(--orange);
    }

    .profile-icon-circle {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--navy-light), var(--orange));
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
    }

    .dropdown-menu-custom {
      position: absolute;
      top: calc(100% + 10px); right: 0;
      background: var(--white);
      border: 1px solid rgba(13,31,53,0.1);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-deep);
      min-width: 220px;
      z-index: 1001;
      display: none;
      overflow: hidden;
      animation: dropDown 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    @keyframes dropDown {
      from { opacity:0; transform:translateY(-8px); }
      to   { opacity:1; transform:translateY(0); }
    }

    .dropdown-menu-custom.show { display: block; }

    .dropdown-menu-custom a {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 18px;
      color: var(--navy); text-decoration: none;
      font-size: 14px; font-weight: 500;
      transition: var(--transition);
      border-bottom: 1px solid rgba(13,31,53,0.06);
    }

    .dropdown-menu-custom a:last-child { border-bottom: none; }
    .dropdown-menu-custom a:hover { background: var(--off-white); padding-left: 22px; }
    .dropdown-menu-custom a i { color: var(--navy-mid); font-size: 16px; }

    .dropdown-menu-custom .user-email {
      padding: 14px 18px; color: #888; font-size: 12px;
      border-bottom: 1px solid rgba(13,31,53,0.08);
      background: var(--off-white);
    }

    /* =============================================
       PAGE LAYOUT
       ============================================= */
    .page-wrapper {
      max-width: 820px;
      margin: 48px auto;
      padding: 0 20px 70px;
      animation: fadeUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .page-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--navy-mid);
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 28px;
      padding: 9px 18px;
      background: white;
      border-radius: 50px;
      border: 1px solid rgba(13,31,53,0.1);
      box-shadow: var(--shadow-card);
      transition: var(--transition);
    }

    .page-back:hover {
      background: var(--navy);
      color: white;
      border-color: var(--navy);
      transform: translateX(-4px);
    }

    /* =============================================
       ALERT BANNERS
       ============================================= */
    .alert-banner {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      border-radius: var(--radius-lg);
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 24px;
      border: 1px solid transparent;
    }

    .alert-banner i { font-size: 18px; flex-shrink: 0; }
    .alert-success { background: #edfbf3; color: #1a6e3c; border-color: #b2e8cc; }
    .alert-error   { background: #fef2f2; color: #9b1c1c; border-color: #fecaca; }

    /* =============================================
       NIM BADGE
       ============================================= */
    .nim-badge {
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
      border-radius: var(--radius-lg);
      padding: 28px 32px;
      margin-bottom: 28px;
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-deep);
    }

    .nim-badge::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--navy-light), var(--orange), var(--gold), var(--orange), var(--navy-light));
    }

    .nim-badge::after {
      content: '';
      position: absolute;
      top: -60px; right: -60px;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.12) 0%, transparent 70%);
      pointer-events: none;
    }

    .nim-badge-inner {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      gap: 22px;
    }

    .nim-icon {
      width: 56px; height: 56px;
      border-radius: 14px;
      background: rgba(255,152,0,0.15);
      display: flex; align-items: center; justify-content: center;
      font-size: 26px;
      color: var(--orange);
      border: 1px solid rgba(255,152,0,0.3);
      flex-shrink: 0;
    }

    .nim-info { color: white; }

    .nim-label {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: var(--orange);
      margin-bottom: 6px;
    }

    .nim-value {
      font-family: 'Cormorant Garamond', serif;
      font-size: 38px;
      font-weight: 700;
      letter-spacing: 3px;
      line-height: 1;
      color: white;
      margin-bottom: 6px;
    }

    .nim-note {
      font-size: 12px;
      color: rgba(255,255,255,0.55);
    }

    /* =============================================
       MAIN FORM CARD
       ============================================= */
    .form-card {
      background: white;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-card);
      overflow: hidden;
    }

    .form-card-header {
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
      padding: 36px 44px;
      position: relative;
      overflow: hidden;
    }

    .form-card-header::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--navy-light), var(--orange), var(--gold), var(--orange), var(--navy-light));
    }

    .form-card-header::after {
      content: '';
      position: absolute;
      top: -80px; right: -80px;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.1) 0%, transparent 70%);
      pointer-events: none;
    }

    .header-inner {
      position: relative;
      z-index: 1;
    }

    .header-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--orange);
      margin-bottom: 12px;
    }

    .header-eyebrow::before {
      content: '';
      width: 28px; height: 2px;
      background: var(--orange);
    }

    .form-card-header h1 {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 34px;
      color: white;
      line-height: 1.15;
      margin-bottom: 8px;
    }

    .form-card-header p {
      font-size: 13.5px;
      color: rgba(255,255,255,0.55);
      line-height: 1.6;
    }

    /* Form Body */
    .form-card-body {
      padding: 44px;
    }

    /* Info box */
    .info-box {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      background: rgba(13,31,53,0.04);
      border: 1px solid rgba(13,31,53,0.1);
      border-left: 4px solid var(--orange);
      border-radius: 12px;
      padding: 16px 18px;
      margin-bottom: 32px;
    }

    .info-box i { color: var(--orange); font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .info-box p { font-size: 13.5px; color: var(--navy-mid); line-height: 1.6; margin: 0; }
    .info-box strong { color: var(--navy); }

    /* Section labels */
    .form-section-label {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: var(--navy-light);
      margin-bottom: 22px;
    }

    .form-section-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(13,31,53,0.1);
    }

    .form-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(13,31,53,0.1), transparent);
      margin: 32px 0;
    }

    /* Form groups */
    .form-group { margin-bottom: 22px; }

    .form-group label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--navy-light);
      margin-bottom: 9px;
    }

    .form-group .required-star { color: var(--orange); margin-left: 3px; }

    .input-wrapper { position: relative; }

    .input-icon {
      position: absolute;
      left: 16px; top: 50%;
      transform: translateY(-50%);
      color: rgba(13,31,53,0.3);
      font-size: 15px;
      pointer-events: none;
      transition: var(--transition);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 13px 18px 13px 44px;
      background: var(--off-white);
      border: 1px solid rgba(13,31,53,0.1);
      border-radius: 12px;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      color: var(--navy);
      transition: var(--transition);
      -webkit-appearance: none;
      appearance: none;
    }

    .form-group input[type="file"] {
      padding: 11px 18px 11px 44px;
      cursor: pointer;
    }

    .form-group input::placeholder { color: rgba(13,31,53,0.28); font-size: 13px; }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--orange);
      background: white;
      box-shadow: 0 0 0 3px rgba(255,152,0,0.1);
    }

    .input-wrapper:focus-within .input-icon { color: var(--orange); }

    .form-group input:disabled,
    .form-group select:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .form-hint {
      font-size: 12px;
      color: rgba(13,31,53,0.4);
      margin-top: 7px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }

    /* Select arrow */
    .select-wrapper { position: relative; }

    .select-wrapper::after {
      content: '\F282';
      font-family: 'bootstrap-icons';
      position: absolute;
      right: 16px; top: 50%;
      transform: translateY(-50%);
      color: rgba(13,31,53,0.35);
      pointer-events: none;
      font-size: 14px;
    }

    .select-wrapper select { padding-right: 44px; }

    /* Photo previews */
    .photo-preview-wrap {
      margin-top: 14px;
      display: flex;
      align-items: flex-start;
      gap: 14px;
      flex-wrap: wrap;
    }

    .preview-item { position: relative; }

    .preview-item img {
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid rgba(13,31,53,0.1);
      box-shadow: var(--shadow-card);
      display: none;
      transition: var(--transition);
    }

    .preview-item img.show { display: block; }
    .preview-item img:hover { transform: scale(1.02); }

    .preview-label {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--navy-light);
      margin-bottom: 8px;
    }

    .current-img {
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid rgba(255,152,0,0.4);
      box-shadow: var(--shadow-card);
    }

    /* Terms */
    .terms-group {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 14px 16px;
      background: var(--off-white);
      border-radius: 12px;
      border: 1px solid rgba(13,31,53,0.08);
      margin-bottom: 28px;
      transition: var(--transition);
    }

    .terms-group:has(input:checked) {
      border-color: rgba(255,152,0,0.3);
      background: rgba(255,152,0,0.05);
    }

    .terms-group input[type="checkbox"] {
      width: 18px !important;
      height: 18px;
      min-width: 18px;
      padding: 0 !important;
      margin-top: 2px;
      cursor: pointer;
      accent-color: var(--orange);
      border-radius: 5px;
      background: white;
      border: none !important;
      box-shadow: none !important;
    }

    .terms-group label {
      margin: 0;
      font-size: 13.5px;
      line-height: 1.55;
      color: var(--navy-mid);
      cursor: pointer;
      font-weight: 400;
      text-transform: none !important;
      letter-spacing: normal !important;
    }

    .terms-group a {
      color: var(--orange);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
    }

    .terms-group a:hover { color: var(--orange-dark); text-decoration: underline; }

    /* Buttons */
    .btn-submit {
      width: 100%;
      padding: 15px 28px;
      background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
      color: white;
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      font-size: 15px;
      letter-spacing: 0.5px;
      border: none;
      border-radius: 50px;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: 0 8px 28px rgba(255,152,0,0.35);
    }

    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 36px rgba(255,152,0,0.5);
    }

    .btn-submit:active { transform: translateY(-1px); }

    .btn-submit:disabled {
      background: rgba(13,31,53,0.15);
      color: rgba(13,31,53,0.4);
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    /* =============================================
       RESPONSIVE
       ============================================= */
    @media (max-width: 680px) {
      .form-card-header { padding: 28px 24px; }
      .form-card-body { padding: 28px 24px; }
      .form-row { grid-template-columns: 1fr; }
      .nim-badge-inner { flex-direction: column; gap: 14px; }
      .nim-value { font-size: 30px; }
      .page-wrapper { padding: 0 14px 48px; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
    }
  </style>
</head>
<body>

<!-- ============ NAVBAR ============ -->
<nav class="navbar navbar-expand-lg" id="mainNav">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="index.php">
      <img src="LOGORBG.png" alt="Logo" class="navbar-logo">
      <span>Universitas Nusantara</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <div class="profile-dropdown">
        <button class="profile-btn" onclick="toggleDropdown()">
          <?php if (!empty($user['photo'])): ?>
            <img src="uploads/foto/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile" class="profile-avatar">
          <?php else: ?>
            <span class="profile-icon-circle"><i class="bi bi-person-fill"></i></span>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
          <i class="bi bi-chevron-down" style="font-size:11px; opacity:0.7;"></i>
        </button>
        <div class="dropdown-menu-custom" id="dropdownMenu">
          <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
          <a href="edit_profile.php"><i class="bi bi-person-gear"></i> Edit Profil</a>
          <a href="dashboard.php" onclick="return confirm('Apakah Anda yakin ingin logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- ============ PAGE CONTENT ============ -->
<div class="page-wrapper">

  <a href="index.php" class="page-back">
    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
  </a>

  <!-- Alerts -->
  <?php if ($success_message): ?>
    <div class="alert-banner alert-success">
      <i class="bi bi-check-circle-fill"></i>
      <?php echo htmlspecialchars($success_message); ?>
    </div>
  <?php endif; ?>

  <?php if ($error_message): ?>
    <div class="alert-banner alert-error">
      <i class="bi bi-exclamation-circle-fill"></i>
      <?php echo htmlspecialchars($error_message); ?>
    </div>
  <?php endif; ?>

  <!-- NIM Badge (jika sudah terbit) -->
  <?php if ($sudah_nim): ?>
  <div class="nim-badge">
    <div class="nim-badge-inner">
      <div class="nim-icon"><i class="bi bi-mortarboard-fill"></i></div>
      <div class="nim-info">
        <div class="nim-label">Nomor Induk Mahasiswa</div>
        <div class="nim-value"><?php echo htmlspecialchars($existing_data['nim']); ?></div>
        <div class="nim-note"><i class="bi bi-check-circle me-1"></i>NIM Anda telah terdaftar secara resmi</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Form Card -->
  <div class="form-card">

    <!-- Header -->
    <div class="form-card-header">
      <div class="header-inner">
        <div class="header-eyebrow">Penerimaan Mahasiswa Baru</div>
        <h1><i class="bi bi-clipboard-check me-2"></i>Form Daftar Ulang</h1>
        <p>Lengkapi data diri Anda sebagai mahasiswa baru Universitas Nusantara.</p>
      </div>
    </div>

    <!-- Body -->
    <div class="form-card-body">

      <!-- Info box -->
      <div class="info-box">
        <i class="bi bi-info-circle-fill"></i>
        <p>
          <strong>Perhatian:</strong>
          <?php if ($sudah_nim): ?>
            NIM Anda sudah terbit. Simpan baik-baik untuk keperluan administrasi.
          <?php else: ?>
            Pastikan semua data yang Anda masukkan sudah benar. NIM akan diberikan setelah Anda menyelesaikan daftar ulang.
          <?php endif; ?>
        </p>
      </div>

      <form action="proses_daftar_ulang.php" method="POST" enctype="multipart/form-data">

        <!-- Data Pribadi -->
        <div class="form-section-label">Data Pribadi</div>

        <div class="form-group">
          <label>Nama Lengkap <span class="required-star">*</span></label>
          <div class="input-wrapper">
            <input type="text" name="nama_lengkap"
                   value="<?php echo $existing_data ? htmlspecialchars($existing_data['nama_lengkap']) : htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                   placeholder="Nama lengkap sesuai KTP" required>
            <i class="bi bi-person input-icon"></i>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Tanggal Lahir <span class="required-star">*</span></label>
            <div class="input-wrapper">
              <input type="date" name="date_of_birth"
                     value="<?php echo $existing_data ? htmlspecialchars($existing_data['date_of_birth']) : htmlspecialchars($user['date_of_birth'] ?? ''); ?>"
                     required>
              <i class="bi bi-calendar3 input-icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label>Email <span class="required-star">*</span></label>
            <div class="input-wrapper">
              <input type="email" name="email"
                     value="<?php echo $existing_data ? htmlspecialchars($existing_data['email']) : htmlspecialchars($user['email']); ?>"
                     required>
              <i class="bi bi-envelope input-icon"></i>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Nomor HP <span class="required-star">*</span></label>
            <div class="input-wrapper">
              <input type="tel" name="nomor_hp"
                     value="<?php echo $existing_data ? htmlspecialchars($existing_data['nomor_hp']) : ($hasil_ujian['phone'] ?? ''); ?>"
                     placeholder="08xxxxxxxxxx" required>
              <i class="bi bi-telephone input-icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label>Nomor Orang Terdekat <span class="required-star">*</span></label>
            <div class="input-wrapper">
              <input type="tel" name="nomor_orang_terdekat"
                     value="<?php echo $existing_data ? htmlspecialchars($existing_data['nomor_orang_terdekat']) : ''; ?>"
                     placeholder="08xxxxxxxxxx" required>
              <i class="bi bi-people input-icon"></i>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Jenjang Sekolah Asal <span class="required-star">*</span></label>
          <div class="input-wrapper select-wrapper">
            <select name="school_level" required>
              <option value="">Pilih Jenjang Sekolah</option>
              <option value="sma" <?php echo ($existing_data && $existing_data['school_level'] == 'sma') || (!$existing_data && ($hasil_ujian['school_level'] ?? '') == 'sma') ? 'selected' : ''; ?>>SMA</option>
              <option value="smk" <?php echo ($existing_data && $existing_data['school_level'] == 'smk') || (!$existing_data && ($hasil_ujian['school_level'] ?? '') == 'smk') ? 'selected' : ''; ?>>SMK</option>
            </select>
            <i class="bi bi-building input-icon"></i>
          </div>
        </div>

        <div class="form-divider"></div>

        <!-- Dokumen -->
        <div class="form-section-label">Dokumen & Foto</div>

        <!-- Foto Diri -->
        <div class="form-group">
          <label>Foto Diri <span class="required-star">*</span></label>
          <div class="input-wrapper">
            <input type="file" name="photo" accept="image/*"
                   <?php echo !$existing_data ? 'required' : ''; ?>
                   onchange="previewFile(this, 'photoPreview')">
            <i class="bi bi-camera input-icon"></i>
          </div>
          <div class="form-hint">Format: JPG, PNG. Maksimal 2MB.</div>

          <div class="photo-preview-wrap">
            <?php if ($existing_data && $existing_data['photo']): ?>
              <div class="preview-item">
                <div class="preview-label">Foto saat ini</div>
                <img src="uploads/daftar_ulang/<?php echo htmlspecialchars($existing_data['photo']); ?>"
                     class="current-img" width="130" height="130" alt="Foto">
              </div>
            <?php endif; ?>
            <div class="preview-item">
              <?php if ($existing_data && $existing_data['photo']): ?>
                <div class="preview-label">Foto baru</div>
              <?php endif; ?>
              <img id="photoPreview" width="130" height="130" alt="Preview Foto">
            </div>
          </div>
        </div>

        <!-- Foto KTP -->
        <div class="form-group">
          <label>Foto KTP <span class="required-star">*</span></label>
          <div class="input-wrapper">
            <input type="file" name="ktp_photo" accept="image/*"
                   <?php echo (!$existing_data || empty($existing_data['ktp_photo'])) ? 'required' : ''; ?>
                   onchange="previewFile(this, 'ktpPreview')">
            <i class="bi bi-card-image input-icon"></i>
          </div>
          <div class="form-hint">Format: JPG, PNG. Maksimal 2MB.</div>

          <div class="photo-preview-wrap">
            <?php if ($existing_data && !empty($existing_data['ktp_photo'])): ?>
              <div class="preview-item">
                <div class="preview-label">KTP saat ini</div>
                <img src="uploads/daftar_ulang/ktp/<?php echo htmlspecialchars($existing_data['ktp_photo']); ?>"
                     class="current-img" width="190" height="120" alt="KTP">
              </div>
            <?php endif; ?>
            <div class="preview-item">
              <?php if ($existing_data && !empty($existing_data['ktp_photo'])): ?>
                <div class="preview-label">KTP baru</div>
              <?php endif; ?>
              <img id="ktpPreview" width="190" height="120" alt="Preview KTP">
            </div>
          </div>
        </div>

        <div class="form-divider"></div>

        <!-- Terms -->
        <div class="terms-group">
          <input type="checkbox" id="terms_checkbox" name="terms_checkbox" required>
          <label for="terms_checkbox">
            Saya menyatakan bahwa semua data yang saya isi adalah benar, dan menyetujui
            <a href="#" target="_blank">Privacy Policy</a> serta <a href="#" target="_blank">Terms of Service</a>
            Universitas Nusantara.
          </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-submit" <?php echo $sudah_nim ? 'disabled' : ''; ?>>
          <i class="bi bi-check-circle"></i>
          <?php echo $existing_data ? 'Update Data Daftar Ulang' : 'Kirim Daftar Ulang'; ?>
        </button>

      </form>
    </div>
  </div>

</div><!-- /page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Navbar scroll
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav')
      .classList.toggle('scrolled', window.scrollY > 30);
  });

  // Profile dropdown
  function toggleDropdown() {
    document.getElementById('dropdownMenu')?.classList.toggle('show');
  }
  document.addEventListener('click', function(e) {
    const pd = document.querySelector('.profile-dropdown');
    const dm = document.getElementById('dropdownMenu');
    if (pd && dm && !pd.contains(e.target)) dm.classList.remove('show');
  });

  // Unified file preview
  function previewFile(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];

    if (!file) { preview.classList.remove('show'); return; }

    if (file.size > 2 * 1024 * 1024) {
      alert('Ukuran file terlalu besar! Maksimal 2MB.');
      input.value = '';
      preview.classList.remove('show');
      return;
    }

    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.classList.add('show');
    };
    reader.readAsDataURL(file);
  }
</script>
</body>
</html>