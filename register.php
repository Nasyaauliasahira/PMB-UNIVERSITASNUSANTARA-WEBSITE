<?php
include "koneksi.php";

if (isset($_POST['submit'])) {

    $first_name = mysqli_real_escape_string($koneksi, $_POST['first_name']);
    $last_name  = mysqli_real_escape_string($koneksi, $_POST['last_name']);
    $email      = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password   = $_POST['password'];

    // validasi checkbox terms
    if (!isset($_POST['terms_checkbox']) || $_POST['terms_checkbox'] !== 'on') {
        echo "<script>alert('Anda harus menyetujui Privacy Policy dan Terms of Service');</script>";
        exit;
    }

    // validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Email tidak valid');</script>";
        exit;
    }

    // cek email sudah ada atau belum
    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Email sudah terdaftar');</script>";
        exit;
    }

    $hash = $password;

    $query = "INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)";
    $stmt  = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $first_name, $last_name, $email, $hash);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
            alert('Registrasi berhasil, silakan login');
            window.location='login.php';
        </script>";
    } else {
        echo "<script>alert('Registrasi gagal');</script>";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar — Universitas Nusantara</title>
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

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      position: relative;
      overflow-x: hidden;
    }

    /* Decorative background circles */
    body::before {
      content: '';
      position: fixed;
      top: -200px; right: -200px;
      width: 600px; height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.07) 0%, transparent 70%);
      pointer-events: none;
    }

    body::after {
      content: '';
      position: fixed;
      bottom: -150px; left: -150px;
      width: 450px; height: 450px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(13,31,53,0.08) 0%, transparent 70%);
      pointer-events: none;
    }

    /* =============================================
       BACK LINK
       ============================================= */
    .back-home {
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
      align-self: flex-start;
      position: relative;
      z-index: 1;
    }

    .back-home:hover {
      background: var(--navy);
      color: white;
      border-color: var(--navy);
      transform: translateX(-4px);
    }

    /* =============================================
       REGISTER WRAPPER
       ============================================= */
    .register-container {
      width: 100%;
      max-width: 980px;
      position: relative;
      z-index: 1;
      animation: fadeUp 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .register-wrapper {
      display: grid;
      grid-template-columns: 1fr 1fr;
      border-radius: var(--radius-xl);
      overflow: hidden;
      box-shadow: var(--shadow-deep);
    }

    /* =============================================
       LEFT PANEL — Image / Brand
       ============================================= */
    .register-image {
      position: relative;
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
      display: flex;
      align-items: flex-end;
      overflow: hidden;
      min-height: 600px;
    }

    .register-image-bg {
      position: absolute;
      inset: 0;
      background-image: url('image.png');
      background-size: cover;
      background-position: center;
      opacity: 0.45;
      transition: var(--transition);
    }

    .register-image:hover .register-image-bg {
      opacity: 0.55;
      transform: scale(1.03);
    }

    .register-image-overlay {
      position: absolute;
      inset: 0;
      background:
        linear-gradient(to top,   rgba(13,31,53,0.95) 0%, rgba(13,31,53,0.4) 45%, transparent 75%),
        linear-gradient(to right, rgba(13,31,53,0.3)  0%, transparent 60%);
    }

    .register-image::after {
      content: '';
      position: absolute;
      top: -100px; right: -100px;
      width: 350px; height: 350px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.12) 0%, transparent 70%);
      pointer-events: none;
    }

    .register-image-content {
      position: relative;
      z-index: 2;
      padding: 44px 40px;
      color: white;
      width: 100%;
    }

    .register-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      position: absolute;
      top: 40px;
      left: 40px;
      text-decoration: none;
    }

    .register-brand-logo {
      width: 46px;
      height: 46px;
      object-fit: contain;
    }

    .register-brand-name {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 15px;
      color: white;
      line-height: 1.3;
      letter-spacing: 0.3px;
    }

    .register-image-label {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--orange);
      margin-bottom: 14px;
    }

    .register-image-label::before {
      content: '';
      width: 28px;
      height: 2px;
      background: var(--orange);
    }

    .register-image-content h2 {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 46px;
      line-height: 1.15;
      color: white;
      text-shadow: 0 4px 24px rgba(0,0,0,0.3);
      margin-bottom: 16px;
    }

    .register-image-content h2 em {
      color: var(--gold);
      font-style: italic;
    }

    .register-image-content p {
      font-size: 13px;
      color: rgba(255,255,255,0.65);
      line-height: 1.75;
      max-width: 300px;
    }

    /* Steps indicator */
    .steps-indicator {
      display: flex;
      gap: 10px;
      margin-top: 28px;
    }

    .step-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255,255,255,0.25);
    }

    .step-dot.active {
      background: var(--orange);
      width: 28px;
      border-radius: 4px;
    }

    /* =============================================
       RIGHT PANEL — Form
       ============================================= */
    .register-form-section {
      background: linear-gradient(160deg, var(--navy) 0%, #0d1e30 100%);
      padding: 48px 48px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    /* Top accent bar */
    .register-form-section::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--navy-light), var(--orange), var(--gold), var(--orange), var(--navy-light));
    }

    /* Decorative bottom-right circle */
    .register-form-section::after {
      content: '';
      position: absolute;
      bottom: -100px; right: -100px;
      width: 280px; height: 280px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.06) 0%, transparent 70%);
      pointer-events: none;
    }

    .form-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--orange);
      margin-bottom: 14px;
    }

    .form-eyebrow::before {
      content: '';
      width: 28px;
      height: 2px;
      background: var(--orange);
    }

    .register-title {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 40px;
      color: white;
      line-height: 1.15;
      margin-bottom: 8px;
    }

    .register-subtitle {
      font-size: 13.5px;
      color: rgba(255,255,255,0.5);
      margin-bottom: 30px;
      line-height: 1.6;
    }

    /* Form Groups */
    .form-group {
      margin-bottom: 18px;
      position: relative;
    }

    .form-group label {
      display: block;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: rgba(255,255,255,0.5);
      margin-bottom: 9px;
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255,255,255,0.3);
      font-size: 15px;
      pointer-events: none;
      transition: var(--transition);
    }

    .form-group input {
      width: 100%;
      padding: 13px 18px 13px 44px;
      background: var(--glass);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      color: white;
      transition: var(--transition);
    }

    .form-group input::placeholder {
      color: rgba(255,255,255,0.22);
      font-size: 13px;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--orange);
      background: rgba(255,255,255,0.1);
      box-shadow: 0 0 0 3px rgba(255,152,0,0.12);
    }

    .input-wrapper:focus-within .input-icon { color: var(--orange); }

    /* Two-column row */
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    /* Terms checkbox */
    .terms-group {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-top: 4px;
      margin-bottom: 22px;
      padding: 14px 16px;
      background: var(--glass);
      border-radius: 12px;
      border: 1px solid var(--glass-border);
      transition: var(--transition);
    }

    .terms-group:has(input:checked) {
      border-color: rgba(255,152,0,0.3);
      background: rgba(255,152,0,0.06);
    }

    .terms-group input[type="checkbox"] {
      width: 18px !important;
      height: 18px;
      min-width: 18px;
      padding: 0 !important;
      margin-top: 2px;
      cursor: pointer;
      accent-color: var(--orange);
      background: white;
      border-radius: 5px;
      border: none !important;
      box-shadow: none !important;
    }

    .terms-group label {
      margin: 0 !important;
      text-transform: none !important;
      letter-spacing: normal !important;
      font-size: 13px !important;
      line-height: 1.55;
      color: rgba(255,255,255,0.65) !important;
      cursor: pointer;
      font-weight: 400 !important;
    }

    .terms-group a {
      color: var(--orange);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
    }

    .terms-group a:hover { color: var(--gold); text-decoration: underline; }

    /* Already have account */
    .login-link {
      text-align: center;
      margin-bottom: 18px;
    }

    .login-link p {
      color: rgba(255,255,255,0.5);
      font-size: 13.5px;
      margin: 0;
    }

    .login-link a {
      color: var(--orange);
      text-decoration: none;
      font-weight: 600;
      font-size: 13.5px;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .login-link a:hover { color: var(--gold); gap: 10px; }

    /* Submit button */
    .btn-register {
      width: 100%;
      padding: 15px 24px;
      background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
      color: white;
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      font-size: 14px;
      letter-spacing: 0.5px;
      border: none;
      border-radius: 50px;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      box-shadow: 0 8px 28px rgba(255,152,0,0.35);
    }

    .btn-register:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 36px rgba(255,152,0,0.5);
    }

    .btn-register:active { transform: translateY(-1px); }

    .btn-register:disabled {
      background: rgba(255,255,255,0.15);
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    /* =============================================
       RESPONSIVE
       ============================================= */
    @media (max-width: 820px) {
      .register-wrapper {
        grid-template-columns: 1fr;
        border-radius: var(--radius-lg);
      }

      .register-image { min-height: 260px; }
      .register-image-content { padding: 36px 32px; }
      .register-image-content h2 { font-size: 34px; }
      .register-brand { top: 28px; left: 28px; }

      .register-form-section { padding: 42px 36px; }

      .form-row { grid-template-columns: 1fr; gap: 0; }
    }

    @media (max-width: 480px) {
      body { padding: 20px 14px; }
      .register-form-section { padding: 36px 24px; }
      .register-title { font-size: 32px; }
      .register-image-content h2 { font-size: 28px; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
    }
  </style>
</head>
<body>

  <!-- Back to home -->
  <a href="index.php" class="back-home">
    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
  </a>

  <div class="register-container">
    <div class="register-wrapper">

      <!-- LEFT: Image / Brand Panel -->
      <div class="register-image">
        <div class="register-image-bg"></div>
        <div class="register-image-overlay"></div>

        <a href="index.php" class="register-brand">
          <img src="LOGORBG.png" alt="Logo" class="register-brand-logo">
          <span class="register-brand-name">Universitas<br>Nusantara</span>
        </a>

        <div class="register-image-content">
          <div class="register-image-label">PMB 2025 / 2026</div>
          <h2>Mari<br>Kita <em>Mulai!</em></h2>
          <p>Daftarkan diri Anda dan jadilah bagian dari ribuan mahasiswa berprestasi di Universitas Nusantara.</p>
          <div class="steps-indicator">
            <div class="step-dot active"></div>
            <div class="step-dot"></div>
            <div class="step-dot"></div>
          </div>
        </div>
      </div>

      <!-- RIGHT: Form Panel -->
      <div class="register-form-section">
        <div class="form-eyebrow">Pendaftaran Akun</div>
        <h1 class="register-title">Buat Akun</h1>
        <p class="register-subtitle">Isi data berikut untuk membuat akun baru Anda.</p>

        <form id="registerForm" method="POST" action="register.php">

          <!-- Nama Depan & Belakang -->
          <div class="form-row">
            <div class="form-group">
              <label for="firstName">Nama Depan</label>
              <div class="input-wrapper">
                <input type="text" id="firstName" name="first_name"
                       placeholder="Nama depan" required>
                <i class="bi bi-person input-icon"></i>
              </div>
            </div>
            <div class="form-group">
              <label for="lastName">Nama Belakang</label>
              <div class="input-wrapper">
                <input type="text" id="lastName" name="last_name"
                       placeholder="Nama belakang" required>
                <i class="bi bi-person input-icon"></i>
              </div>
            </div>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label for="email">Email</label>
            <div class="input-wrapper">
              <input type="email" id="email" name="email"
                     placeholder="nama@email.com" required>
              <i class="bi bi-envelope input-icon"></i>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label for="password">Kata Sandi</label>
            <div class="input-wrapper">
              <input type="password" id="password" name="password"
                     placeholder="Buat kata sandi" required>
              <i class="bi bi-lock input-icon"></i>
            </div>
          </div>

          <!-- Terms -->
          <div class="terms-group">
            <input type="checkbox" id="terms_checkbox" name="terms_checkbox" required>
            <label for="terms_checkbox">
              Saya menyetujui <a href="#" target="_blank">Privacy Policy</a> dan <a href="#" target="_blank">Terms of Service</a> Universitas Nusantara
            </label>
          </div>

          <!-- Already have account -->
          <div class="login-link">
            <p>Sudah punya akun?
              <a href="login.php">
                <i class="bi bi-box-arrow-in-right"></i> Masuk di sini
              </a>
            </p>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-register" name="submit">
            <i class="bi bi-person-check"></i> Buat Akun
          </button>

        </form>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>