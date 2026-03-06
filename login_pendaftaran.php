<?php
session_start();
include "koneksi.php";

$login_error = "";

// Handle AJAX — ambil nomor ujian
if (isset($_POST['action']) && $_POST['action'] == 'get_nomor_ujian') {
    header('Content-Type: application/json');
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);

    $query = "SELECT id, nomor_ujian FROM pendaftaran WHERE email = ?";
    $stmt  = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result   = mysqli_stmt_get_result($stmt);
    $response = ['success' => false, 'nomor_ujian' => null, 'message' => ''];

    if ($row = mysqli_fetch_assoc($result)) {
        $id          = $row['id'];
        $nomor_ujian = $row['nomor_ujian'];

        if (empty($nomor_ujian)) {
            $nomor_ujian_baru = 'PMB-2026-' . str_pad($id, 4, '0', STR_PAD_LEFT);
            $update_stmt = mysqli_prepare($koneksi, "UPDATE pendaftaran SET nomor_ujian = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $nomor_ujian_baru, $id);
            if (mysqli_stmt_execute($update_stmt)) {
                $response = ['success' => true, 'nomor_ujian' => $nomor_ujian_baru, 'message' => 'Nomor ujian berhasil di-generate!'];
            } else {
                $response['message'] = 'Gagal generate nomor ujian';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $response = ['success' => true, 'nomor_ujian' => $nomor_ujian, 'message' => 'Nomor ujian ditemukan'];
        }
    } else {
        $response['message'] = 'Email tidak ditemukan di database';
    }

    mysqli_stmt_close($stmt);
    echo json_encode($response);
    exit;
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $email       = mysqli_real_escape_string($koneksi, $_POST['email']);
    $nomor_ujian = mysqli_real_escape_string($koneksi, $_POST['nomor_ujian']);

    $stmt = mysqli_prepare($koneksi, "SELECT * FROM pendaftaran WHERE email = ? AND nomor_ujian = ?");
    mysqli_stmt_bind_param($stmt, "ss", $email, $nomor_ujian);
    mysqli_stmt_execute($stmt);
    $student_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($student_data) {
        $_SESSION['student_id']    = $student_data['id'];
        $_SESSION['student_name']  = $student_data['first_name'];
        $_SESSION['student_email'] = $student_data['email'];
        $_SESSION['nomor_ujian']   = $student_data['nomor_ujian'];
        header("Location: ujian.php");
        exit;
    } else {
        $login_error = "Email atau Nomor Ujian tidak ditemukan. Periksa kembali data Anda.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Ujian — Universitas Nusantara</title>
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
      --transition:  all 0.45s cubic-bezier(0.25,0.46,0.45,0.94);
    }

    *,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      min-height: 100vh;
      display: flex; flex-direction: column;
    }

    /* ── NAVBAR ── */
    .navbar {
      background: rgba(13,31,53,0.92);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--glass-border);
      padding: 14px 0;
      position: sticky; top: 0; z-index: 1000;
      transition: var(--transition);
    }
    .navbar.scrolled { background: rgba(13,31,53,0.98); padding: 10px 0; }
    .navbar-brand {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700; font-size: 22px;
      color: white !important;
      display: flex; align-items: center; gap: 4px;
    }
    .navbar-logo { width: 52px; height: 52px; object-fit: contain; }
    .nav-btn {
      padding: 9px 20px; border-radius: 50px;
      font-size: 13px; font-weight: 600;
      text-decoration: none; transition: var(--transition);
      display: inline-flex; align-items: center; gap: 7px;
    }
    .nav-btn-back {
      background: transparent;
      color: rgba(255,255,255,0.85);
      border: 1px solid var(--glass-border);
    }
    .nav-btn-back:hover { background: var(--glass); color: white; }

    /* ── PAGE LAYOUT ── */
    .page-main {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 52px 20px;
    }

    /* ── SPLIT CARD ── */
    .login-card {
      display: grid;
      grid-template-columns: 1fr 1.15fr;
      max-width: 980px; width: 100%;
      background: white;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-deep);
      overflow: hidden;
      animation: fadeUp 0.6s ease-out both;
    }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(24px); }
      to   { opacity:1; transform:translateY(0); }
    }

    /* ── LEFT PANEL ── */
    .login-left {
      background: linear-gradient(160deg, var(--navy) 0%, var(--navy-mid) 60%, #162f4c 100%);
      padding: 64px 52px;
      display: flex; flex-direction: column; justify-content: space-between;
      position: relative; overflow: hidden;
    }
    .login-left::before {
      content: '';
      position: absolute; top: -120px; right: -120px;
      width: 380px; height: 380px; border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.1) 0%, transparent 70%);
      pointer-events: none;
    }
    .login-left::after {
      content: '';
      position: absolute; bottom: -80px; left: -60px;
      width: 240px; height: 240px; border-radius: 50%;
      background: radial-gradient(circle, rgba(45,90,123,0.4) 0%, transparent 70%);
      pointer-events: none;
    }

    .left-top { position: relative; z-index: 1; }

    .left-eyebrow {
      display: inline-flex; align-items: center; gap: 10px;
      font-size: 10px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase;
      color: var(--orange); margin-bottom: 18px;
    }
    .left-eyebrow::before { content:''; width:28px; height:2px; background:var(--orange); }

    .left-heading {
      font-family: 'Cormorant Garamond', serif;
      font-size: 52px; font-weight: 700;
      color: white; line-height: 1.1; margin-bottom: 18px;
    }
    .left-heading em { color: var(--gold); font-style: italic; }

    .left-desc {
      font-size: 14px; color: rgba(255,255,255,0.62);
      line-height: 1.8;
    }

    /* Steps */
    .left-steps { position: relative; z-index: 1; margin-top: 44px; }
    .step-item {
      display: flex; gap: 14px; align-items: flex-start;
      margin-bottom: 16px;
    }
    .step-dot {
      width: 32px; height: 32px; flex-shrink: 0;
      border-radius: 8px;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.14);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; color: var(--orange); font-weight: 700;
    }
    .step-text {
      font-size: 13px; color: rgba(255,255,255,0.65);
      line-height: 1.6; padding-top: 5px;
    }
    .step-text strong { color: white; display: block; margin-bottom: 1px; font-size: 13px; }

    /* ── RIGHT PANEL ── */
    .login-right {
      padding: 64px 56px;
      display: flex; flex-direction: column; justify-content: center;
    }

    .form-eyebrow {
      display: inline-flex; align-items: center; gap: 10px;
      font-size: 10px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase;
      color: var(--orange); margin-bottom: 10px;
    }
    .form-eyebrow::before { content:''; width:24px; height:2px; background:var(--orange); }

    .form-heading {
      font-family: 'Cormorant Garamond', serif;
      font-size: 38px; font-weight: 700;
      color: var(--navy); line-height: 1.15; margin-bottom: 32px;
    }

    /* Alert */
    .alert-error {
      border-radius: 12px; padding: 14px 18px;
      margin-bottom: 24px;
      display: flex; align-items: flex-start; gap: 12px;
      font-size: 13px; line-height: 1.6;
      background: rgba(139,26,26,0.07);
      border: 1px solid rgba(220,53,69,0.2);
      color: #8b1a1a;
      animation: fadeUp 0.4s ease-out;
    }
    .alert-error i { color: #dc3545; font-size: 18px; flex-shrink: 0; margin-top: 1px; }

    /* Fields */
    .field-group { margin-bottom: 24px; }

    .field-label {
      display: block; font-size: 10px; font-weight: 700;
      letter-spacing: 1.5px; text-transform: uppercase;
      color: var(--navy-mid); margin-bottom: 8px;
    }

    .form-control {
      width: 100%; padding: 13px 16px;
      border: 1px solid rgba(13,31,53,0.15);
      border-radius: 12px; font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      color: var(--navy); background: white;
      transition: var(--transition);
    }
    .form-control::placeholder { color: #aab4be; }
    .form-control:focus {
      outline: none;
      border-color: var(--orange);
      box-shadow: 0 0 0 3px rgba(255,152,0,0.12);
    }

    /* Nomor ujian with reload */
    .nomor-wrap {
      display: flex; gap: 10px; align-items: center;
    }
    .nomor-wrap .form-control { flex: 1; }

    .btn-reload {
      width: 48px; height: 48px; flex-shrink: 0;
      background: linear-gradient(135deg, var(--navy), var(--navy-mid));
      border: none; border-radius: 12px;
      color: white; font-size: 18px; cursor: pointer;
      transition: var(--transition);
      display: flex; align-items: center; justify-content: center;
    }
    .btn-reload:hover {
      background: linear-gradient(135deg, var(--orange), var(--orange-dark));
      transform: scale(1.06);
    }
    .btn-reload:disabled { background: #ccc; cursor: not-allowed; transform: none; }
    .btn-reload.loading i { animation: spin 0.8s linear infinite; }
    @keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }

    /* Hints */
    .field-hint {
      margin-top: 9px; padding: 10px 14px;
      background: rgba(255,152,0,0.06);
      border-left: 3px solid var(--orange);
      border-radius: 0 8px 8px 0;
      font-size: 12px; color: #7a6040;
      display: flex; align-items: flex-start; gap: 8px; line-height: 1.55;
    }
    .field-hint i { color: var(--orange); font-size: 13px; flex-shrink: 0; margin-top: 1px; }

    .field-prefilled {
      margin-top: 9px; padding: 8px 12px;
      background: rgba(13,31,53,0.05);
      border-left: 3px solid var(--navy-light);
      border-radius: 0 8px 8px 0;
      font-size: 12px; color: var(--navy-light);
      display: flex; align-items: center; gap: 7px;
    }
    .field-prefilled i { font-size: 13px; }

    /* Submit */
    .btn-login {
      width: 100%; padding: 15px 24px;
      background: linear-gradient(135deg, var(--orange), var(--orange-dark));
      color: white; font-weight: 700; font-size: 15px;
      font-family: 'DM Sans', sans-serif;
      border: none; border-radius: 50px; cursor: pointer;
      transition: var(--transition);
      box-shadow: 0 4px 20px rgba(255,152,0,0.35);
      display: flex; align-items: center; justify-content: center; gap: 10px;
      margin-top: 8px;
    }
    .btn-login:hover { transform: translateY(-3px); box-shadow: 0 10px 32px rgba(255,152,0,0.5); }
    .btn-login:active { transform: translateY(-1px); }

    .form-footer {
      text-align: center; margin-top: 20px;
      font-size: 13px; color: #8898aa;
    }
    .form-footer a { color: var(--orange); font-weight: 600; text-decoration: none; }
    .form-footer a:hover { text-decoration: underline; }

    /* ── FOOTER ── */
    footer {
      background: linear-gradient(160deg, var(--navy) 0%, #0d1e30 100%);
      color: white; padding: 60px 0 24px;
      position: relative; overflow: hidden;
    }
    footer::before {
      content:''; position:absolute; top:0; left:0; right:0; height:3px;
      background: linear-gradient(90deg, var(--navy-light), var(--orange), var(--gold), var(--orange), var(--navy-light));
    }
    .footer-logo-section { display:flex; gap:16px; align-items:center; margin-bottom:44px; }
    .footer-logo { width:54px; height:54px; border-radius:13px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center; padding:4px; }
    .footer-logo img { width:100%; height:100%; object-fit:contain; }
    .footer-brand h3 { font-family:'Cormorant Garamond',serif; font-weight:700; font-size:20px; line-height:1.3; color:white; }
    .footer-content { display:grid; grid-template-columns:1.2fr 1fr 0.8fr 0.8fr; gap:50px; margin-bottom:44px; }
    .footer-section h5 { font-family:'Cormorant Garamond',serif; font-weight:700; font-size:16px; margin-bottom:16px; color:white; }
    .footer-section p  { font-size:13px; line-height:1.85; color:rgba(255,255,255,0.6); }
    .footer-links { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:9px; }
    .footer-links a { color:rgba(255,255,255,0.6); text-decoration:none; font-size:13px; transition:var(--transition); }
    .footer-links a:hover { color:white; padding-left:4px; }
    .footer-social { display:flex; gap:11px; margin-top:18px; }
    .footer-social a { width:40px; height:40px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.6); text-decoration:none; transition:var(--transition); font-size:15px; }
    .footer-social a:hover { background:rgba(255,152,0,0.15); border-color:var(--orange); color:var(--orange); transform:translateY(-4px); }
    .footer-divider { height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent); margin-bottom:24px; }
    .footer-bottom { text-align:center; font-size:12px; color:rgba(255,255,255,0.35); }
    .footer-bottom span { color:var(--orange); }

    /* Responsive */
    @media (max-width:900px) {
      .login-card { grid-template-columns: 1fr; }
      .login-left { padding: 48px 40px; }
      .left-heading { font-size: 40px; }
      .left-steps { display: none; }
      .login-right { padding: 44px 40px; }
      .footer-content { grid-template-columns: 1fr 1fr; gap: 32px; }
    }
    @media (max-width:540px) {
      .login-left { padding: 36px 28px; }
      .left-heading { font-size: 34px; }
      .login-right { padding: 36px 24px; }
      .footer-content { grid-template-columns: 1fr; gap: 24px; }
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
      <div class="ms-auto">
        <a href="index.php" class="nav-btn nav-btn-back"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
      </div>
    </div>
  </div>
</nav>

<!-- ── MAIN ── -->
<div class="page-main">
  <div class="login-card">

    <!-- LEFT PANEL -->
    <div class="login-left">
      <div class="left-top">
        <div class="left-eyebrow">Seleksi Ujian Masuk</div>
        <h1 class="left-heading">Siap untuk<br><em>Mengikuti<br>Ujian?</em></h1>
        <p class="left-desc">Masuk menggunakan email dan nomor ujian yang Anda terima setelah mendaftar.</p>
      </div>
      <div class="left-steps">
        <div class="step-item">
          <div class="step-dot">1</div>
          <div class="step-text">
            <strong>Masukkan Email</strong>
            Email yang digunakan saat pendaftaran
          </div>
        </div>
        <div class="step-item">
          <div class="step-dot">2</div>
          <div class="step-text">
            <strong>Muat Nomor Ujian</strong>
            Klik ikon reload untuk mengisi otomatis
          </div>
        </div>
        <div class="step-item">
          <div class="step-dot">3</div>
          <div class="step-text">
            <strong>Mulai Ujian</strong>
            Kerjakan soal dalam waktu yang ditentukan
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="login-right">
      <div class="form-eyebrow">Masuk</div>
      <h2 class="form-heading">Login Ujian<br>Seleksi Masuk</h2>

      <?php if (!empty($login_error)): ?>
      <div class="alert-error">
        <i class="bi bi-x-circle-fill"></i>
        <div><?php echo htmlspecialchars($login_error); ?></div>
      </div>
      <?php endif; ?>

      <form method="POST" id="loginForm">

        <!-- Email -->
        <div class="field-group">
          <label class="field-label">Email Pendaftaran</label>
          <input type="email" class="form-control" id="loginEmail" name="email"
            placeholder="email@contoh.com"
            value="<?php echo isset($_SESSION['student_email']) ? htmlspecialchars($_SESSION['student_email']) : ''; ?>"
            required>
          <?php if (isset($_SESSION['student_email'])): ?>
          <div class="field-prefilled"><i class="bi bi-info-circle"></i> Diisi dari data sesi sebelumnya</div>
          <?php else: ?>
          <div class="field-hint"><i class="bi bi-info-circle-fill"></i> Gunakan email yang sama seperti saat mendaftar ujian.</div>
          <?php endif; ?>
        </div>

        <!-- Nomor Ujian -->
        <div class="field-group">
          <label class="field-label">Nomor Ujian</label>
          <div class="nomor-wrap">
            <input type="text" class="form-control" id="loginNomorUjian" name="nomor_ujian"
              placeholder="PMB-2026-XXXX"
              value="<?php echo isset($_SESSION['nomor_ujian']) ? htmlspecialchars($_SESSION['nomor_ujian']) : ''; ?>"
              required>
            <button type="button" class="btn-reload" id="btnLoadNomor" title="Muat nomor ujian dari email">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
          <?php if (isset($_SESSION['nomor_ujian'])): ?>
          <div class="field-prefilled"><i class="bi bi-info-circle"></i> Diisi dari data sesi sebelumnya</div>
          <?php else: ?>
          <div class="field-hint"><i class="bi bi-info-circle-fill"></i> Klik tombol reload untuk memuat nomor ujian secara otomatis dari email Anda.</div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn-login">
          <i class="bi bi-box-arrow-in-right"></i> Masuk & Mulai Ujian
        </button>

      </form>

      <div class="form-footer">
        Belum mendaftar? <a href="pendaftaran.php">Daftar ujian di sini</a>
      </div>
    </div>

  </div>
</div>

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
  // Navbar scroll
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 30);
  });

  const loginEmail      = document.getElementById('loginEmail');
  const loginNomorUjian = document.getElementById('loginNomorUjian');
  const btnLoadNomor    = document.getElementById('btnLoadNomor');

  function loadNomorUjian() {
    const email = loginEmail.value.trim();
    if (!email) { alert('Silakan masukkan email terlebih dahulu.'); return; }

    btnLoadNomor.disabled = true;
    btnLoadNomor.classList.add('loading');

    fetch('login_pendaftaran.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=get_nomor_ujian&email=' + encodeURIComponent(email)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.nomor_ujian) {
        loginNomorUjian.value = data.nomor_ujian;
        // Subtle green flash feedback
        loginNomorUjian.style.borderColor = '#27ae60';
        loginNomorUjian.style.boxShadow   = '0 0 0 3px rgba(39,174,96,0.12)';
        setTimeout(() => {
          loginNomorUjian.style.borderColor = '';
          loginNomorUjian.style.boxShadow   = '';
        }, 2000);
      } else {
        alert(data.message || 'Nomor ujian tidak ditemukan untuk email ini.');
        loginNomorUjian.value = '';
      }
    })
    .catch(() => alert('Terjadi kesalahan. Silakan coba lagi.'))
    .finally(() => {
      btnLoadNomor.disabled = false;
      btnLoadNomor.classList.remove('loading');
    });
  }

  btnLoadNomor?.addEventListener('click', e => { e.preventDefault(); loadNomorUjian(); });

  // Auto-load saat user keluar dari field email (jika nomor belum diisi)
  loginEmail?.addEventListener('blur', function() {
    if (this.value.trim() && !loginNomorUjian.value) loadNomorUjian();
  });
</script>
</body>
</html>
