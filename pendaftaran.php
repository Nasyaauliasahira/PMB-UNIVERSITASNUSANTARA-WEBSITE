<?php
session_start();
include "koneksi.php";

$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['student_id']);
$user_id = $_SESSION['user_id'] ?? null;
$student_id = $_SESSION['student_id'] ?? null;
$check_id = $user_id ?? $student_id;

$exam_result = null;
$can_register = true;
$error_message = "";

$user_data = ['first_name' => '', 'last_name' => '', 'email' => '', 'photo' => ''];

if ($is_logged_in && $user_id) {
    $query = "SELECT first_name, last_name, email, photo FROM users WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) { $user_data = $row; }
    mysqli_stmt_close($stmt);
}

// Navbar session logic
$has_session = $is_logged_in;
if ($has_session) {
    if ($user_id) {
        $user_name = trim($user_data['first_name'] . ' ' . $user_data['last_name']);
        $user = ['photo' => $user_data['photo'], 'email' => $user_data['email']];
    } else if ($student_id) {
        $query = "SELECT first_name, last_name, email, photo FROM pendaftaran WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $user_name = trim($row['first_name'] . ' ' . $row['last_name']);
            $user = ['photo' => $row['photo'], 'email' => $row['email']];
        } else {
            $user_name = 'User';
            $user = ['photo' => '', 'email' => ''];
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $user_name = '';
    $user = ['photo' => '', 'email' => ''];
}

if ($is_logged_in && $check_id) {
    $query = "SELECT * FROM hasil_ujian WHERE siswa_id = ? ORDER BY waktu_submit DESC LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $check_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exam_result = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $passing_score = 60;
    if ($exam_result) {
        if ($exam_result['nilai'] < $passing_score) {
            $can_register = false;
            $error_message = "Anda belum lulus ujian dengan nilai " . $exam_result['nilai'] . "/100. Nilai minimum kelulusan adalah " . $passing_score . "/100.";
        }
    }
}

if (isset($_POST['submit'])) {
    if ($is_logged_in && !$can_register) {
        echo "<script>alert('Anda tidak dapat mendaftar ulang karena belum lulus ujian.'); window.location.href='dashboard.php';</script>";
        exit;
    }

    $first_name    = htmlspecialchars($_POST['first_name']);
    $last_name     = htmlspecialchars($_POST['last_name']);
    $date_of_birth = htmlspecialchars($_POST['date_of_birth']);
    $email         = htmlspecialchars($_POST['email']);
    $jurusan       = htmlspecialchars($_POST['jurusan']);
    $phone         = htmlspecialchars($_POST['phone']);
    $school_level  = htmlspecialchars($_POST['school_level']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_error = "Email tidak valid.";
    } else {
        $foto_name     = $_FILES['photo']['name'];
        $tmp_name      = $_FILES['photo']['tmp_name'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type     = mime_content_type($tmp_name);
        if (!in_array($file_type, $allowed_types)) {
            $form_error = "Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF.";
        } else {
            $folder   = "uploads/foto/";
            $new_name = time() . "_" . $foto_name;
            if (move_uploaded_file($tmp_name, $folder . $new_name)) {
                $query = "INSERT INTO pendaftaran (first_name, last_name, date_of_birth, email, jurusan, phone, school_level, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt  = mysqli_prepare($koneksi, $query);
                mysqli_stmt_bind_param($stmt, "ssssssss", $first_name, $last_name, $date_of_birth, $email, $jurusan, $phone, $school_level, $new_name);
                if (mysqli_stmt_execute($stmt)) {
                    $form_success = "Pendaftaran berhasil! Silakan login untuk melanjutkan ujian.";
                } else {
                    $form_error = "Error: " . mysqli_error($koneksi);
                }
                mysqli_stmt_close($stmt);
            } else {
                $form_error = "Gagal upload foto. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Ujian — Universitas Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy:        #0d1f35;
      --navy-mid:    #1e3a5f;
      --navy-light:  #2d5a7b;
      --orange:      #ff9800;
      --orange-dark: #e68900;
      --gold:        #f0c060;
      --off-white:   #f7f5f0;
      --glass:       rgba(255,255,255,0.06);
      --glass-border:rgba(255,255,255,0.12);
      --shadow-deep: 0 24px 64px rgba(13,31,53,0.35);
      --shadow-card: 0 8px 32px rgba(13,31,53,0.18);
      --radius-lg:   20px;
      --radius-xl:   32px;
      --transition:  all 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); min-height:100vh; }

    /* ── NAVBAR ── */
    .navbar {
      background: rgba(13,31,53,0.92);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--glass-border);
      padding: 14px 0;
      position: sticky; top:0; z-index:1000;
      transition: var(--transition);
    }
    .navbar.scrolled { background:rgba(13,31,53,0.98); padding:10px 0; }
    .navbar-brand {
      font-family:'Cormorant Garamond',serif;
      font-weight:700; font-size:22px;
      color:white !important;
      display:flex; align-items:center; gap:4px;
    }
    .navbar-logo { width:52px; height:52px; object-fit:contain; }

    /* Nav buttons */
    .nav-btn {
      padding:9px 20px; border-radius:50px;
      font-size:13px; font-weight:600;
      text-decoration:none; transition:var(--transition);
      display:inline-flex; align-items:center; gap:7px;
    }
    .nav-btn-back {
      background:transparent;
      color:rgba(255,255,255,0.85);
      border:1px solid var(--glass-border);
    }
    .nav-btn-back:hover { background:var(--glass); color:white; }

    /* Profile dropdown — sama persis dengan index.php */
    .profile-dropdown { position:relative; display:inline-block; }
    .profile-btn {
      background:var(--glass);
      border:1px solid var(--glass-border);
      color:white; cursor:pointer;
      font-size:14px; font-weight:500;
      padding:8px 16px 8px 8px;
      display:flex; align-items:center; gap:10px;
      border-radius:50px;
      transition:var(--transition);
      font-family:'DM Sans',sans-serif;
    }
    .profile-btn:hover { background:rgba(255,255,255,0.12); border-color:rgba(255,255,255,0.25); }
    .profile-avatar {
      width:36px; height:36px; border-radius:50%;
      object-fit:cover; border:2px solid var(--orange);
    }
    .profile-icon-circle {
      width:36px; height:36px; border-radius:50%;
      background:linear-gradient(135deg, var(--navy-light), var(--orange));
      display:flex; align-items:center; justify-content:center;
      font-size:18px;
    }
    .dropdown-menu-custom {
      position:absolute;
      top:calc(100% + 10px); right:0;
      background:white;
      border:1px solid rgba(13,31,53,0.1);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow-deep);
      min-width:210px; z-index:1001;
      display:none; overflow:hidden;
      animation:dropDown 0.3s cubic-bezier(0.25,0.46,0.45,0.94);
    }
    @keyframes dropDown {
      from { opacity:0; transform:translateY(-8px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .dropdown-menu-custom.show { display:block; }
    .dropdown-menu-custom a {
      display:flex; align-items:center; gap:10px;
      padding:13px 18px; color:var(--navy);
      text-decoration:none; font-size:14px; font-weight:500;
      transition:var(--transition);
      border-bottom:1px solid rgba(13,31,53,0.06);
    }
    .dropdown-menu-custom a:last-child { border-bottom:none; }
    .dropdown-menu-custom a:hover { background:var(--off-white); padding-left:22px; }
    .dropdown-menu-custom a i { color:var(--navy-mid); font-size:16px; }
    .dropdown-menu-custom .user-email {
      padding:12px 18px; color:#888; font-size:12px;
      border-bottom:1px solid rgba(13,31,53,0.08);
      background:var(--off-white);
    }

    /* ── PAGE HERO ── */
    .page-hero {
      background:linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
      padding:56px 0 48px; position:relative; overflow:hidden;
    }
    .page-hero::after {
      content:''; position:absolute;
      bottom:-80px; right:-80px;
      width:300px; height:300px; border-radius:50%;
      background:radial-gradient(circle, rgba(255,152,0,0.1) 0%, transparent 70%);
      pointer-events:none;
    }
    .page-hero-eyebrow {
      display:inline-flex; align-items:center; gap:10px;
      font-size:11px; font-weight:700;
      letter-spacing:3px; text-transform:uppercase;
      color:var(--orange); margin-bottom:14px;
    }
    .page-hero-eyebrow::before { content:''; width:32px; height:2px; background:var(--orange); }
    .page-hero h1 {
      font-family:'Cormorant Garamond',serif;
      font-size:46px; font-weight:700;
      color:white; line-height:1.15; margin-bottom:10px;
    }
    .page-hero p { color:rgba(255,255,255,0.65); font-size:15px; }

    /* ── MAIN ── */
    .main-content { padding:60px 0 80px; }

    /* Alerts */
    .alert-custom {
      border-radius:14px; padding:18px 22px;
      margin-bottom:28px;
      display:flex; align-items:flex-start; gap:14px;
      font-size:14px; line-height:1.6;
      animation:fadeUp 0.5s ease-out both;
    }
    .alert-error  { background:#3d0b0b; border:1px solid rgba(220,53,69,0.3); color:#ffb3b3; }
    .alert-success{ background:rgba(13,31,53,0.9); border:1px solid rgba(255,152,0,0.3); color:rgba(255,255,255,0.9); }
    .alert-custom .alert-icon { font-size:22px; flex-shrink:0; margin-top:1px; }
    .alert-error  .alert-icon { color:#ff6b6b; }
    .alert-success .alert-icon { color:var(--orange); }
    .alert-custom a { color:var(--orange); text-decoration:none; font-weight:600; }
    .alert-custom a:hover { text-decoration:underline; }

    /* Form card */
    .form-card {
      background:white; border-radius:var(--radius-xl);
      box-shadow:var(--shadow-card); overflow:hidden;
      animation:fadeUp 0.6s ease-out both;
    }
    .card-section {
      padding:36px 40px;
      border-bottom:1px solid rgba(13,31,53,0.07);
    }
    .card-section:last-child { border-bottom:none; }

    .section-eyebrow {
      display:inline-flex; align-items:center; gap:10px;
      font-size:10px; font-weight:700;
      letter-spacing:3px; text-transform:uppercase;
      color:var(--orange); margin-bottom:6px;
    }
    .section-eyebrow::before { content:''; width:24px; height:2px; background:var(--orange); }
    .section-title {
      font-family:'Cormorant Garamond',serif;
      font-size:26px; font-weight:700;
      color:var(--navy); margin-bottom:28px;
    }

    /* Fields */
    .field-label {
      display:block; font-size:10px; font-weight:700;
      letter-spacing:1.5px; text-transform:uppercase;
      color:var(--navy-mid); margin-bottom:8px;
    }
    .form-control, .form-select {
      padding:13px 16px;
      border:1px solid rgba(13,31,53,0.15);
      border-radius:12px; font-size:14px;
      font-family:'DM Sans',sans-serif;
      color:var(--navy); background:white;
      transition:var(--transition); width:100%;
    }
    .form-control::placeholder { color:#aab4be; }
    .form-control:focus, .form-select:focus {
      outline:none; border-color:var(--orange);
      box-shadow:0 0 0 3px rgba(255,152,0,0.12);
    }

    /* Upload */
    .upload-area {
      border:2px dashed rgba(13,31,53,0.2);
      border-radius:16px; padding:36px 24px;
      text-align:center; cursor:pointer;
      transition:var(--transition);
      background:var(--off-white); position:relative;
    }
    .upload-area:hover, .upload-area.dragover {
      border-color:var(--orange);
      background:rgba(255,152,0,0.04);
    }
    .upload-area input[type="file"] {
      position:absolute; inset:0; opacity:0;
      cursor:pointer; width:100%; height:100%;
    }
    .upload-icon {
      width:56px; height:56px;
      background:linear-gradient(135deg, var(--navy), var(--navy-light));
      border-radius:14px; display:flex;
      align-items:center; justify-content:center;
      font-size:24px; color:var(--orange);
      margin:0 auto 16px;
    }
    .upload-title { font-weight:600; color:var(--navy); font-size:15px; margin-bottom:6px; }
    .upload-sub   { font-size:13px; color:#8898aa; }
    .upload-sub span { color:var(--orange); font-weight:600; }
    .upload-filename {
      margin-top:14px; font-size:13px; font-weight:500;
      color:var(--navy-mid); display:none;
    }
    .upload-filename i { color:var(--orange); margin-right:6px; }
    .photo-preview {
      width:100px; height:100px; border-radius:50%;
      object-fit:cover; border:3px solid var(--orange);
      display:none; margin:14px auto 0;
    }

    /* Submit */
    .submit-section {
      padding:32px 40px 40px; background:var(--off-white);
      border-radius:var(--radius-xl);
      box-shadow:var(--shadow-card);
    }
    .btn-submit {
      width:100%; padding:16px 24px;
      background:linear-gradient(135deg, var(--orange), var(--orange-dark));
      color:white; font-weight:700; font-size:16px;
      font-family:'DM Sans',sans-serif; border:none;
      border-radius:50px; cursor:pointer;
      transition:var(--transition);
      box-shadow:0 4px 20px rgba(255,152,0,0.35);
      display:flex; align-items:center; justify-content:center; gap:10px;
    }
    .btn-submit:hover { transform:translateY(-3px); box-shadow:0 10px 32px rgba(255,152,0,0.5); }
    .btn-submit:active { transform:translateY(-1px); }

    /* ── FOOTER ── */
    footer {
      background:linear-gradient(160deg, var(--navy) 0%, #0d1e30 100%);
      color:white; padding:70px 0 28px;
      position:relative; overflow:hidden;
    }
    footer::before {
      content:''; position:absolute; top:0; left:0; right:0; height:3px;
      background:linear-gradient(90deg, var(--navy-light), var(--orange), var(--gold), var(--orange), var(--navy-light));
    }
    .footer-logo-section { display:flex; gap:16px; align-items:center; margin-bottom:50px; }
    .footer-logo {
      width:56px; height:56px; border-radius:14px;
      background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);
      display:flex; align-items:center; justify-content:center; padding:4px;
    }
    .footer-logo img { width:100%; height:100%; object-fit:contain; }
    .footer-brand h3 { font-family:'Cormorant Garamond',serif; font-weight:700; font-size:20px; line-height:1.3; color:white; }
    .footer-content { display:grid; grid-template-columns:1.2fr 1fr 0.8fr 0.8fr; gap:50px; margin-bottom:50px; }
    .footer-section h5 { font-family:'Cormorant Garamond',serif; font-weight:700; font-size:16px; margin-bottom:18px; color:white; }
    .footer-section p  { font-size:13px; line-height:1.85; color:rgba(255,255,255,0.6); }
    .footer-links { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; }
    .footer-links a { color:rgba(255,255,255,0.6); text-decoration:none; font-size:13px; transition:var(--transition); }
    .footer-links a:hover { color:white; padding-left:4px; }
    .footer-social { display:flex; gap:12px; margin-top:20px; }
    .footer-social a {
      width:40px; height:40px; background:rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.1); border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      color:rgba(255,255,255,0.6); text-decoration:none;
      transition:var(--transition); font-size:16px;
    }
    .footer-social a:hover { background:rgba(255,152,0,0.15); border-color:var(--orange); color:var(--orange); transform:translateY(-4px); }
    .footer-divider { height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent); margin-bottom:28px; }
    .footer-bottom { text-align:center; font-size:12px; color:rgba(255,255,255,0.35); }
    .footer-bottom span { color:var(--orange); }

    @keyframes fadeUp {
      from { opacity:0; transform:translateY(20px); }
      to   { opacity:1; transform:translateY(0); }
    }

    @media (max-width:991px) {
      .footer-content { grid-template-columns:1fr 1fr; gap:35px; }
      .card-section { padding:28px; }
      .submit-section { padding:24px 28px 32px; }
    }
    @media (max-width:768px) {
      .page-hero h1 { font-size:34px; }
      .card-section { padding:24px 20px; }
      .submit-section { padding:20px 20px 28px; }
      .footer-content { grid-template-columns:1fr; gap:28px; }
    }
  </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar navbar-expand-lg" id="mainNav">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="index.php">
      <img src="LOGORBG.png" alt="Logo" class="navbar-logo">
      <span>Universitas Nusantara</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="ms-auto d-flex align-items-center gap-3">
        <?php if ($has_session): ?>
          <!-- Sudah login: tampilkan profile dropdown -->
          <div class="profile-dropdown">
            <button class="profile-btn" onclick="toggleDropdown()">
              <?php if (!empty($user['photo'])): ?>
                <img src="uploads/foto/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile" class="profile-avatar">
              <?php else: ?>
                <span class="profile-icon-circle"><i class="bi bi-person-fill"></i></span>
              <?php endif; ?>
              <span><?php echo htmlspecialchars($user_name); ?></span>
              <i class="bi bi-chevron-down" style="font-size:11px; opacity:0.7;"></i>
            </button>
            <div class="dropdown-menu-custom" id="dropdownMenu">
              <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
              <a href="index.php"><i class="bi bi-house"></i> Beranda</a>
              <a href="edit_profile.php"><i class="bi bi-person-gear"></i> Edit Profile</a>
              <a href="logout.php" onclick="return confirm('Yakin ingin logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
          </div>
        <?php else: ?>
          <!-- Belum login: hanya tombol kembali -->
          <a href="index.php" class="nav-btn nav-btn-back"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- ── PAGE HERO ── -->
<section class="page-hero">
  <div class="container">
    <div class="page-hero-eyebrow">Penerimaan Mahasiswa Baru</div>
    <h1>Pendaftaran Ujian<br><em style="color:var(--gold); font-style:italic;">Masuk Universitas</em></h1>
    <p>Lengkapi data diri Anda untuk mengikuti ujian seleksi masuk.</p>
  </div>
</section>

<!-- ── MAIN ── -->
<main class="main-content">
  <div class="container" style="max-width:860px;">

    <?php if (!empty($form_error)): ?>
    <div class="alert-custom alert-error">
      <i class="bi bi-x-circle-fill alert-icon"></i>
      <div><?php echo $form_error; ?></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($form_success)): ?>
    <div class="alert-custom alert-success">
      <i class="bi bi-check-circle-fill alert-icon"></i>
      <div><?php echo $form_success; ?> <a href="login_pendaftaran.php">Login sekarang →</a></div>
    </div>
    <?php endif; ?>

    <?php if ($is_logged_in && !$can_register): ?>
    <div class="alert-custom alert-error">
      <i class="bi bi-shield-exclamation alert-icon"></i>
      <div>
        <strong style="display:block; margin-bottom:6px;">Akses Ditolak</strong>
        <?php echo $error_message; ?>
        <br><a href="dashboard.php" style="margin-top:10px; display:inline-block;">← Kembali ke Dashboard</a>
      </div>
    </div>
    <?php endif; ?>

    <form id="registrationForm" method="POST" enctype="multipart/form-data"
      <?php echo (!$can_register) ? 'style="display:none;"' : ''; ?>>

      <div class="form-card mb-4">

        <!-- Langkah 1: Data Pribadi -->
        <div class="card-section">
          <div class="section-eyebrow">Langkah 1</div>
          <div class="section-title">Data Pribadi</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Nama Depan</label>
              <input type="text" class="form-control" name="first_name" placeholder="Nama depan Anda"
                value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Nama Belakang</label>
              <input type="text" class="form-control" name="last_name" placeholder="Nama belakang Anda"
                value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Tanggal Lahir</label>
              <input type="date" class="form-control" name="date_of_birth" required>
            </div>
            <div class="col-md-6">
              <label class="field-label">Nomor Telepon</label>
              <input type="tel" class="form-control" name="phone" placeholder="08xx-xxxx-xxxx" required>
            </div>
            <div class="col-12">
              <label class="field-label">Email</label>
              <input type="email" class="form-control" name="email" placeholder="email@contoh.com"
                value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
            </div>
          </div>
        </div>

        <!-- Langkah 2: Data Akademik -->
        <div class="card-section">
          <div class="section-eyebrow">Langkah 2</div>
          <div class="section-title">Data Akademik</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Program Studi</label>
              <select class="form-select" name="jurusan" required>
                <option value="">Pilih program studi</option>
                <option value="business">Manajemen Bisnis</option>
                <option value="it_software">Teknik Informatika</option>
                <option value="design">Desain Komunikasi Visual</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="field-label">Tingkat Sekolah Sebelumnya</label>
              <select class="form-select" name="school_level" required>
                <option value="">Pilih tingkat sekolah</option>
                <option value="sma">Sekolah Menengah Atas (SMA)</option>
                <option value="smk">Sekolah Menengah Kejuruan (SMK)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Langkah 3: Upload Foto -->
        <div class="card-section">
          <div class="section-eyebrow">Langkah 3</div>
          <div class="section-title">Upload Foto</div>
          <div class="upload-area" id="uploadArea">
            <input type="file" name="photo" id="uploadFile" accept="image/*" required>
            <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
            <div class="upload-title">Seret foto ke sini</div>
            <div class="upload-sub">atau <span>klik untuk memilih file</span></div>
            <div class="upload-sub" style="margin-top:6px;">JPG, PNG, GIF — maks. 5MB</div>
            <div class="upload-filename" id="uploadFilename">
              <i class="bi bi-paperclip"></i><span id="filenameText"></span>
            </div>
            <img src="" alt="Preview" class="photo-preview" id="photoPreview">
          </div>
        </div>

      </div><!-- /form-card -->

      <!-- Submit -->
      <div class="submit-section">
        <button type="submit" class="btn-submit" name="submit">
          <i class="bi bi-send-fill"></i> Kirim Pendaftaran
        </button>
      </div>

    </form>
  </div>
</main>

<!-- ── FOOTER ── -->
<footer>
  <div class="container-fluid px-5">
    <div class="footer-logo-section">
      <div class="footer-logo"><img src="LOGORBG.png" alt="Logo"></div>
      <div class="footer-brand"><h3>Universitas<br>Nusantara</h3></div>
    </div>
    <div class="footer-content">
      <div class="footer-section">
        <h5>Kontak Kami</h5>
        <p>Direktorat Akademik — Kantor Seleksi Masuk<br>Gedung Rektorat Lt. 2, Kampus Harmoni<br>Jl. Merdeka Raya No. 45</p>
      </div>
      <div class="footer-section">
        <h5>Unit Layanan Terpadu</h5>
        <p>Gedung Rektorat Lt. 1, Kampus Harmoni<br>Jl. Merdeka Raya No. 45, Kota Harmoni</p>
      </div>
      <div class="footer-section">
        <h5>Tautan Cepat</h5>
        <ul class="footer-links">
          <li><a href="index.php">Beranda</a></li>
          <li><a href="#">Program Akademik</a></li>
          <li><a href="#">Beasiswa</a></li>
          <li><a href="#">Kontak</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h5>Informasi</h5>
        <ul class="footer-links">
          <li><a href="#">Tentang Kami</a></li>
          <li><a href="#">Kebijakan Privasi</a></li>
          <li><a href="#">FAQ</a></li>
        </ul>
        <div class="footer-social">
          <a href="#"><i class="bi bi-facebook"></i></a>
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
    </div>
    <div class="footer-divider"></div>
    <div class="footer-bottom"><p>&copy; 2026 <span>Universitas Nusantara</span> — All Rights Reserved</p></div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Navbar scroll effect
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 30);
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

  // File upload preview
  const uploadFile     = document.getElementById('uploadFile');
  const uploadArea     = document.getElementById('uploadArea');
  const uploadFilename = document.getElementById('uploadFilename');
  const filenameText   = document.getElementById('filenameText');
  const photoPreview   = document.getElementById('photoPreview');

  uploadFile.addEventListener('change', function() {
    if (this.files && this.files[0]) {
      filenameText.textContent = this.files[0].name;
      uploadFilename.style.display = 'block';
      const reader = new FileReader();
      reader.onload = e => { photoPreview.src = e.target.result; photoPreview.style.display = 'block'; };
      reader.readAsDataURL(this.files[0]);
    }
  });

  // Drag & drop
  uploadArea.addEventListener('dragover',  e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
  uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
  uploadArea.addEventListener('drop', e => {
    e.preventDefault(); uploadArea.classList.remove('dragover');
    uploadFile.files = e.dataTransfer.files;
    uploadFile.dispatchEvent(new Event('change'));
  });
</script>
</body>
</html>