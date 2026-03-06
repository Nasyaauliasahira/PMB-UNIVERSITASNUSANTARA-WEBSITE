<?php
session_start();
include "koneksi.php";

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

// Ambil ID soal dari URL
$soal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data soal dari database
$query = "SELECT * FROM soal WHERE id = $soal_id";
$result = mysqli_query($koneksi, $query);
$soal = mysqli_fetch_assoc($result);

if (!$soal) {
    header("Location: bank_soal_admin.php");
    exit;
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $pertanyaan = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
    $pilihan_a = mysqli_real_escape_string($koneksi, $_POST['pilihan_a']);
    $pilihan_b = mysqli_real_escape_string($koneksi, $_POST['pilihan_b']);
    $pilihan_c = mysqli_real_escape_string($koneksi, $_POST['pilihan_c']);
    $pilihan_d = mysqli_real_escape_string($koneksi, $_POST['pilihan_d']);
    $jawaban_benar = mysqli_real_escape_string($koneksi, $_POST['jawaban_benar']);
    $gambar_name = $soal['gambar'];

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengupload gambar. Silakan coba lagi.";
        } else {
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $max_size = 2 * 1024 * 1024;
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_ext, true)) {
                $error = "Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
            } elseif ($_FILES['gambar']['size'] > $max_size) {
                $error = "Ukuran gambar terlalu besar. Maksimal 2MB.";
            } else {
                $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'soal' . DIRECTORY_SEPARATOR;
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $new_name = 'soal_' . $soal_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $new_name;

                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_path)) {
                    if (!empty($soal['gambar']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $soal['gambar'])) {
                        unlink(__DIR__ . DIRECTORY_SEPARATOR . $soal['gambar']);
                    }
                    $gambar_name = 'uploads/soal/' . $new_name;
                } else {
                    $error = "Gagal menyimpan gambar. Periksa izin folder upload.";
                }
            }
        }
    }

    if (empty($error)) {
        $gambar_sql = $gambar_name ? "'" . mysqli_real_escape_string($koneksi, $gambar_name) . "'" : "NULL";
        $update_query = "UPDATE soal SET pertanyaan = '$pertanyaan', gambar = $gambar_sql, pilihan_a = '$pilihan_a', pilihan_b = '$pilihan_b', pilihan_c = '$pilihan_c', pilihan_d = '$pilihan_d', jawaban_benar = '$jawaban_benar' WHERE id = $soal_id";

        if (mysqli_query($koneksi, $update_query)) {
            header("Location: bank_soal_admin.php?success=1");
            exit;
        } else {
            $error = "Gagal mengupdate soal: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Soal — Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    /* =============================================
       ROOT & BASE — selaras index.php
       ============================================= */
    :root {
      --navy:        #0d1f35;
      --navy-mid:    #1e3a5f;
      --navy-light:  #2d5a7b;
      --orange:      #ff9800;
      --orange-dark: #e68900;
      --gold:        #f0c060;
      --white:       #ffffff;
      --off-white:   #f7f5f0;
      --text-muted:  rgba(255,255,255,0.6);
      --glass:       rgba(255,255,255,0.06);
      --glass-border:rgba(255,255,255,0.12);
      --shadow-deep: 0 24px 64px rgba(13,31,53,0.35);
      --shadow-card: 0 8px 32px rgba(13,31,53,0.18);
      --radius-lg:   20px;
      --radius-xl:   32px;
      --transition:  all 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      color: var(--navy);
      overflow-x: hidden;
      display: flex;
      min-height: 100vh;
    }

    /* =============================================
       SIDEBAR
       ============================================= */
    .sidebar{width:260px;height:100vh;background:linear-gradient(175deg,var(--navy) 0%,#0a1828 100%);position:fixed;left:0;top:0;z-index:200;display:flex;flex-direction:column;border-right:1px solid rgba(255,255,255,0.06);overflow:hidden;}
    .sidebar::before{content:'';position:absolute;top:-100px;right:-80px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(255,152,0,0.08) 0%,transparent 70%);pointer-events:none;}
    .sidebar-brand{padding:28px 24px 24px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,0.06);flex-shrink:0;}
    .sidebar-brand img{width:46px;height:46px;object-fit:contain;}
    .sidebar-brand-text h3{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:700;color:white;line-height:1.2;}
    .sidebar-brand-text span{font-size:10px;color:var(--orange);font-weight:700;letter-spacing:2px;text-transform:uppercase;}
    .sidebar-nav{padding:20px 0;flex:1;overflow-y:auto;}
    .nav-section-label{padding:6px 24px 10px;font-size:9px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,0.25);}
    .sidebar-link{display:flex;align-items:center;gap:13px;padding:12px 24px;color:rgba(255,255,255,0.55);text-decoration:none;font-size:13px;font-weight:500;transition:var(--transition);border-left:3px solid transparent;}
    .sidebar-link:hover{background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.9);border-left-color:rgba(255,152,0,0.4);padding-left:28px;}
    .sidebar-link.active{background:rgba(255,152,0,0.1);color:white;border-left-color:var(--orange);}
    .sidebar-link.active .link-icon{background:var(--orange);color:white;}
    .link-icon{width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,0.07);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;transition:var(--transition);}
    .sidebar-link:hover .link-icon{background:rgba(255,152,0,0.2);color:var(--orange);}
    .sidebar-footer{padding:20px 24px;border-top:1px solid rgba(255,255,255,0.06);flex-shrink:0;}
    .sidebar-logout{display:flex;align-items:center;gap:10px;padding:11px 16px;border-radius:12px;background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.2);color:#f87171;text-decoration:none;font-size:13px;font-weight:600;transition:var(--transition);cursor:pointer;}
    .sidebar-logout:hover{background:rgba(220,53,69,0.2);color:#fca5a5;}

    /* =============================================
       MAIN CONTENT
       ============================================= */
    .main-content {
      margin-left: 260px;
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* =============================================
       TOPBAR
       ============================================= */
    .topbar {
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(13,31,53,0.08);
      padding: 0 36px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 16px rgba(13,31,53,0.06);
    }

    .topbar-breadcrumb {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
    }

    .topbar-breadcrumb a {
      color: var(--navy-mid);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
    }

    .topbar-breadcrumb a:hover { color: var(--orange); }

    .topbar-breadcrumb .sep {
      color: #ccc;
      font-size: 11px;
    }

    .topbar-breadcrumb .current {
      color: var(--orange);
      font-weight: 600;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .topbar-badge {
      background: linear-gradient(135deg, var(--navy), var(--navy-mid));
      color: white;
      padding: 6px 16px;
      border-radius: 50px;
      font-size: 12px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .topbar-badge i { color: var(--orange); }

    /* =============================================
       PAGE BODY
       ============================================= */
    .page-body {
      padding: 36px;
      flex: 1;
    }

    /* Page Header */
    .page-header {
      margin-bottom: 32px;
    }

    .page-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--orange);
      margin-bottom: 10px;
    }

    .page-eyebrow::before {
      content: '';
      width: 28px;
      height: 2px;
      background: var(--orange);
    }

    .page-header h1 {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 34px;
      color: var(--navy);
      line-height: 1.2;
    }

    .page-header p {
      color: #8898aa;
      font-size: 14px;
      margin-top: 6px;
    }

    /* =============================================
       ALERT
       ============================================= */
    .alert-error {
      background: #fff5f5;
      color: #c0392b;
      padding: 14px 20px;
      border-radius: 12px;
      margin-bottom: 24px;
      border: 1px solid rgba(192,57,43,0.2);
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      font-weight: 500;
    }

    /* =============================================
       FORM CARDS
       ============================================= */
    .form-card {
      background: white;
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-card);
      border: 1px solid rgba(13,31,53,0.06);
      margin-bottom: 24px;
      overflow: hidden;
      transition: var(--transition);
    }

    .form-card:hover {
      box-shadow: var(--shadow-deep);
    }

    .form-card-header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 22px 28px;
      border-bottom: 1px solid rgba(13,31,53,0.07);
      background: var(--off-white);
    }

    .form-card-header-icon {
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, var(--navy), var(--navy-light));
      color: var(--orange);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
    }

    .form-card-header h5 {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 20px;
      color: var(--navy);
      margin: 0;
    }

    .form-card-header p {
      font-size: 12px;
      color: #9aabba;
      margin: 2px 0 0;
    }

    .form-card-body {
      padding: 28px;
    }

    /* =============================================
       PERTANYAAN BLOCK
       ============================================= */
    .question-block {
      display: flex;
      gap: 18px;
      align-items: flex-start;
    }

    .question-number {
      width: 56px;
      height: 56px;
      background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
      color: white;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Cormorant Garamond', serif;
      font-size: 26px;
      font-weight: 700;
      flex-shrink: 0;
      box-shadow: 0 6px 20px rgba(255,152,0,0.35);
    }

    .question-textarea-wrap {
      flex: 1;
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
      border-radius: 14px;
      padding: 16px;
    }

    .question-textarea-wrap textarea {
      width: 100%;
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.12);
      color: white;
      padding: 14px 16px;
      border-radius: 10px;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      line-height: 1.7;
      resize: vertical;
      min-height: 110px;
      transition: var(--transition);
    }

    .question-textarea-wrap textarea::placeholder {
      color: rgba(255,255,255,0.45);
    }

    .question-textarea-wrap textarea:focus {
      outline: none;
      background: rgba(255,255,255,0.1);
      border-color: var(--orange);
      box-shadow: 0 0 0 3px rgba(255,152,0,0.18);
    }

    /* =============================================
       FILE UPLOAD
       ============================================= */
    .upload-zone {
      border: 2px dashed rgba(13,31,53,0.18);
      border-radius: 14px;
      padding: 28px;
      text-align: center;
      transition: var(--transition);
      cursor: pointer;
      position: relative;
    }

    .upload-zone:hover {
      border-color: var(--orange);
      background: rgba(255,152,0,0.04);
    }

    .upload-zone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }

    .upload-zone-icon {
      width: 52px;
      height: 52px;
      background: linear-gradient(135deg, var(--navy), var(--navy-light));
      color: var(--orange);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin: 0 auto 14px;
    }

    .upload-zone h6 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 18px;
      font-weight: 700;
      color: var(--navy);
      margin-bottom: 6px;
    }

    .upload-zone p {
      font-size: 12px;
      color: #9aabba;
      margin: 0;
    }

    .image-preview-wrap {
      margin-top: 18px;
      border-radius: 14px;
      overflow: hidden;
      border: 1px solid rgba(13,31,53,0.1);
      background: white;
      box-shadow: var(--shadow-card);
    }

    .image-preview-wrap img {
      width: 100%;
      max-height: 300px;
      object-fit: contain;
      display: block;
    }

    /* =============================================
       JAWABAN OPTIONS
       ============================================= */
    .jawaban-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .jawaban-item {
      display: flex;
      align-items: center;
      gap: 14px;
      background: var(--off-white);
      border: 2px solid transparent;
      border-radius: 16px;
      padding: 14px 16px;
      transition: var(--transition);
      cursor: pointer;
    }

    .jawaban-item:hover {
      border-color: rgba(255,152,0,0.25);
      background: rgba(255,152,0,0.03);
    }

    .jawaban-item.is-correct {
      background: rgba(255,152,0,0.06);
      border-color: var(--orange);
    }

    /* Badge huruf */
    .jawaban-badge {
      width: 40px;
      height: 40px;
      background: var(--navy);
      color: white;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 15px;
      flex-shrink: 0;
      transition: var(--transition);
      font-family: 'Cormorant Garamond', serif;
      letter-spacing: 0.5px;
    }

    .jawaban-item.is-correct .jawaban-badge {
      background: var(--orange);
      color: white;
      box-shadow: 0 4px 14px rgba(255,152,0,0.4);
    }

    /* Input teks jawaban — borderless, blend dengan background */
    .jawaban-item input[type="text"] {
      flex: 1;
      padding: 8px 4px;
      border: none;
      background: transparent;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      color: var(--navy);
      transition: var(--transition);
    }

    .jawaban-item input[type="text"]:focus {
      outline: none;
      color: var(--navy);
    }

    .jawaban-item input[type="text"]::placeholder {
      color: #aab4be;
    }

    /* Radio as checkmark toggle */
    .jawaban-radio-wrap {
      position: relative;
      flex-shrink: 0;
    }

    .jawaban-radio-wrap input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
    }

    .radio-check {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: white;
      border: 2px solid rgba(13,31,53,0.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 17px;
      color: #ccc;
      cursor: pointer;
      transition: var(--transition);
    }

    .radio-check:hover {
      border-color: rgba(255,152,0,0.4);
      color: rgba(255,152,0,0.5);
    }

    .jawaban-radio-wrap input[type="radio"]:checked + .radio-check {
      background: #27ae60;
      border-color: #27ae60;
      color: white;
      box-shadow: 0 4px 14px rgba(39,174,96,0.35);
    }

    /* =============================================
       ACTION BUTTONS
       ============================================= */
    .action-bar {
      display: flex;
      gap: 14px;
      justify-content: flex-end;
      padding-top: 8px;
    }

    .btn-cancel {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 13px 28px;
      background: transparent;
      color: var(--navy-mid);
      border: 2px solid rgba(13,31,53,0.15);
      border-radius: 50px;
      font-size: 13px;
      font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      text-decoration: none;
      cursor: pointer;
      letter-spacing: 0.3px;
      transition: var(--transition);
    }

    .btn-cancel:hover {
      border-color: rgba(13,31,53,0.3);
      background: rgba(13,31,53,0.05);
      color: var(--navy);
    }

    .btn-save {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 13px 32px;
      background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
      color: white;
      border: none;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      letter-spacing: 0.3px;
      box-shadow: 0 6px 20px rgba(255,152,0,0.35);
      transition: var(--transition);
    }

    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(255,152,0,0.5);
      color: white;
    }

    /* =============================================
       RESPONSIVE
       ============================================= */
    @media (max-width: 991px) {
      .sidebar { width: 220px; }
      .main-content { margin-left: 220px; }
      .page-body { padding: 24px; }
    }

    @media (max-width: 768px) {
      .sidebar { display: none; }
      .main-content { margin-left: 0; }
      .topbar { padding: 0 20px; }
      .page-body { padding: 20px; }
      .question-block { flex-direction: column; }
      .action-bar { flex-direction: column; }
      .btn-cancel, .btn-save { width: 100%; justify-content: center; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
    }
  </style>
</head>
<body>

  <!-- ============ SIDEBAR ============ -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="LOGORBG.png" alt="Logo">
      <div class="sidebar-brand-text">
        <h3>Universitas<br>Nusantara</h3>
        <span>Admin Panel</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Menu Utama</div>
      <a href="dahboardadmin.php" class="sidebar-link">
        <span class="link-icon"><i class="bi bi-speedometer2"></i></span> Dashboard
      </a>
      <a href="pendaftaran_admin.php" class="sidebar-link">
        <span class="link-icon"><i class="bi bi-clipboard-check"></i></span> Pendaftaran
      </a>
      <a href="bank_soal_admin.php" class="sidebar-link active">
        <span class="link-icon"><i class="bi bi-journal-bookmark"></i></span> Bank Soal
      </a>
      <a href="register_login_admin.php" class="sidebar-link">
        <span class="link-icon"><i class="bi bi-person-check"></i></span> Register & Login
      </a>
      <a href="status_admin.php" class="sidebar-link">
        <span class="link-icon"><i class="bi bi-card-checklist"></i></span> Status
      </a>
      <a href="daftar_ulang_admin.php" class="sidebar-link">
        <span class="link-icon"><i class="bi bi-person-badge"></i></span> Daftar Ulang
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="dashboard.php" class="sidebar-logout" onclick="return confirm('Yakin ingin logout?');">
        <i class="bi bi-box-arrow-left"></i> Logout
      </a>
    </div>
  </aside>
  <!-- ============ MAIN CONTENT ============ -->
  <div class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-breadcrumb">
        <a href="dahboardadmin.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right"></i></span>
        <a href="bank_soal_admin.php">Bank Soal</a>
        <span class="sep"><i class="bi bi-chevron-right"></i></span>
        <span class="current">Edit Soal</span>
      </div>
      <div class="topbar-right">
        <div class="topbar-badge">
          <i class="bi bi-pencil-square"></i>
          Soal #<?php echo $soal_id; ?>
        </div>
      </div>
    </div>

    <!-- PAGE BODY -->
    <div class="page-body">

      <!-- Page Header -->
      <div class="page-header">
        <div class="page-eyebrow">Bank Soal</div>
        <h1>Edit Soal Ujian</h1>
        <p>Perbarui pertanyaan, pilihan jawaban, dan kunci jawaban yang benar.</p>
      </div>

      <?php if (isset($error)): ?>
        <div class="alert-error">
          <i class="bi bi-exclamation-circle-fill"></i>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">

        <!-- CARD: PERTANYAAN -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-header-icon"><i class="bi bi-question-circle"></i></div>
            <div>
              <h5>Pertanyaan</h5>
              <p>Masukkan teks soal yang akan ditampilkan kepada peserta ujian</p>
            </div>
          </div>
          <div class="form-card-body">
            <div class="question-block">
              <div class="question-number"><?php echo $soal_id; ?></div>
              <div class="question-textarea-wrap">
                <textarea
                  name="pertanyaan"
                  placeholder="Tulis pertanyaan soal di sini..."
                  required
                ><?php echo htmlspecialchars($soal['pertanyaan']); ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- CARD: GAMBAR -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-header-icon"><i class="bi bi-image"></i></div>
            <div>
              <h5>Gambar Soal <span style="font-size:13px; color:#9aabba; font-weight:400;">(Opsional)</span></h5>
              <p>Format: JPG, PNG, GIF, WEBP &mdash; Maksimal 2MB</p>
            </div>
          </div>
          <div class="form-card-body">
            <div class="upload-zone">
              <input type="file" name="gambar" accept="image/*" id="gambarInput">
              <div class="upload-zone-icon"><i class="bi bi-cloud-arrow-up"></i></div>
              <h6>Klik atau Seret Gambar ke Sini</h6>
              <p>Gambar saat ini akan diganti jika Anda memilih file baru</p>
            </div>
            <?php if (!empty($soal['gambar'])): ?>
              <div class="image-preview-wrap" id="imagePreview">
                <img src="<?php echo htmlspecialchars($soal['gambar']); ?>" alt="Gambar soal" id="previewImg">
              </div>
            <?php else: ?>
              <div class="image-preview-wrap" id="imagePreview" style="display:none;">
                <img src="" alt="Preview" id="previewImg">
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- CARD: JAWABAN -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-header-icon"><i class="bi bi-list-check"></i></div>
            <div>
              <h5>Pilihan Jawaban</h5>
              <p>Isi keempat pilihan dan centang kunci jawaban yang benar</p>
            </div>
          </div>
          <div class="form-card-body">
            <div class="jawaban-list">

              <?php foreach (['A','B','C','D'] as $opt): ?>
              <?php $field = 'pilihan_' . strtolower($opt); ?>
              <?php $isCorrect = $soal['jawaban_benar'] === $opt; ?>
              <div class="jawaban-item <?php echo $isCorrect ? 'is-correct' : ''; ?>" id="item-<?php echo $opt; ?>">
                <div class="jawaban-badge"><?php echo $opt; ?></div>
                <input
                  type="text"
                  name="<?php echo $field; ?>"
                  placeholder="Pilihan <?php echo $opt; ?>"
                  value="<?php echo htmlspecialchars($soal[$field]); ?>"
                  required
                >
                <label class="jawaban-radio-wrap">
                  <input
                    type="radio"
                    name="jawaban_benar"
                    value="<?php echo $opt; ?>"
                    <?php echo $isCorrect ? 'checked' : ''; ?>
                  >
                  <div class="radio-check">
                    <i class="bi bi-check2"></i>
                  </div>
                </label>
              </div>
              <?php endforeach; ?>

            </div>
          </div>
        </div>

        <!-- ACTION BAR -->
        <div class="action-bar">
          <a href="bank_soal_admin.php" class="btn-cancel">
            <i class="bi bi-arrow-left"></i> Batal
          </a>
          <button type="submit" class="btn-save">
            <i class="bi bi-check-circle"></i> Simpan Perubahan
          </button>
        </div>

      </form>
    </div><!-- /page-body -->
  </div><!-- /main-content -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Update jawaban-item highlight saat radio berubah
    document.querySelectorAll('input[name="jawaban_benar"]').forEach(radio => {
      radio.addEventListener('change', function() {
        document.querySelectorAll('.jawaban-item').forEach(item => {
          item.classList.remove('is-correct');
        });
        this.closest('.jawaban-item').classList.add('is-correct');
      });
    });

    // Preview gambar saat file dipilih
    document.getElementById('gambarInput').addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
          const preview = document.getElementById('imagePreview');
          const img = document.getElementById('previewImg');
          img.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });

    // Upload zone label update
    document.getElementById('gambarInput').addEventListener('change', function() {
      const zone = this.closest('.upload-zone');
      if (this.files[0]) {
        zone.querySelector('h6').textContent = this.files[0].name;
        zone.querySelector('p').textContent = (this.files[0].size / 1024).toFixed(1) + ' KB';
      }
    });
  </script>
</body>
</html>
