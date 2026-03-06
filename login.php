<?php
session_start();
include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt  = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);

    if ($user && $password === $user['password']) {

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['first_name'];

        header("Location: dashboard.php");
        exit;

    } else {
        echo "<script>alert('Email atau password salah');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Universitas Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    /* =============================================
       ROOT & BASE
       ============================================= */
    :root {
      --navy:       #0d1f35;
      --navy-mid:   #1e3a5f;
      --navy-light: #2d5a7b;
      --orange:     #ff9800;
      --orange-dark:#e68900;
      --gold:       #f0c060;
      --white:      #ffffff;
      --off-white:  #f7f5f0;
      --text-muted: rgba(255,255,255,0.6);
      --glass:      rgba(255,255,255,0.06);
      --glass-border: rgba(255,255,255,0.12);
      --shadow-deep: 0 24px 64px rgba(13,31,53,0.35);
      --shadow-card: 0 8px 32px rgba(13,31,53,0.18);
      --radius-lg:  20px;
      --radius-xl:  32px;
      --transition: all 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
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
       BACK TO HOME LINK
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
       LOGIN WRAPPER
       ============================================= */
    .login-container {
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

    .login-wrapper {
      display: grid;
      grid-template-columns: 1fr 1fr;
      border-radius: var(--radius-xl);
      overflow: hidden;
      box-shadow: var(--shadow-deep);
    }

    /* =============================================
       LEFT PANEL — Image / Brand
       ============================================= */
    .login-image {
      position: relative;
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
      display: flex;
      align-items: flex-end;
      overflow: hidden;
      min-height: 560px;
    }

    .login-image-bg {
      position: absolute;
      inset: 0;
      background-image: url('image.png');
      background-size: cover;
      background-position: center;
      opacity: 0.45;
      transition: var(--transition);
    }

    .login-image:hover .login-image-bg {
      opacity: 0.55;
      transform: scale(1.03);
    }

    /* Multi-layer overlay, same technique as hero */
    .login-image-overlay {
      position: absolute;
      inset: 0;
      background:
        linear-gradient(to top,   rgba(13,31,53,0.95) 0%,  rgba(13,31,53,0.4) 45%, transparent 75%),
        linear-gradient(to right, rgba(13,31,53,0.3) 0%, transparent 60%);
    }

    /* Decorative glow */
    .login-image::after {
      content: '';
      position: absolute;
      top: -100px; right: -100px;
      width: 350px; height: 350px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.12) 0%, transparent 70%);
      pointer-events: none;
    }

    .login-image-content {
      position: relative;
      z-index: 2;
      padding: 44px 40px;
      color: white;
      width: 100%;
    }

    /* University badge on top */
    .login-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: auto;
      position: absolute;
      top: 40px;
      left: 40px;
    }

    .login-brand-logo {
      width: 46px;
      height: 46px;
      object-fit: contain;
    }

    .login-brand-name {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 15px;
      color: white;
      line-height: 1.3;
      letter-spacing: 0.3px;
    }

    .login-image-label {
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

    .login-image-label::before {
      content: '';
      width: 28px;
      height: 2px;
      background: var(--orange);
    }

    .login-image-content h2 {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 46px;
      line-height: 1.15;
      color: white;
      text-shadow: 0 4px 24px rgba(0,0,0,0.3);
      margin-bottom: 16px;
    }

    .login-image-content h2 em {
      color: var(--gold);
      font-style: italic;
    }

    .login-image-content p {
      font-size: 13px;
      color: rgba(255,255,255,0.65);
      line-height: 1.75;
      max-width: 300px;
    }

    /* =============================================
       RIGHT PANEL — Form
       ============================================= */
    .login-form-section {
      background: linear-gradient(160deg, var(--navy) 0%, #0d1e30 100%);
      padding: 52px 50px 52px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    /* Top accent bar */
    .login-form-section::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--navy-light), var(--orange), var(--gold), var(--orange), var(--navy-light));
    }

    /* Decorative bottom-right circle */
    .login-form-section::after {
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

    .login-title {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 42px;
      color: white;
      line-height: 1.15;
      margin-bottom: 8px;
    }

    .login-subtitle {
      font-size: 14px;
      color: rgba(255,255,255,0.55);
      margin-bottom: 36px;
      font-weight: 400;
      line-height: 1.6;
    }

    /* Form Groups */
    .form-group {
      margin-bottom: 22px;
      position: relative;
    }

    .form-group label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: rgba(255,255,255,0.5);
      margin-bottom: 10px;
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
      font-size: 16px;
      pointer-events: none;
      transition: var(--transition);
    }

    .form-group input {
      width: 100%;
      padding: 14px 18px 14px 46px;
      background: var(--glass);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      color: white;
      transition: var(--transition);
    }

    .form-group input::placeholder {
      color: rgba(255,255,255,0.25);
      font-size: 13px;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--orange);
      background: rgba(255,255,255,0.1);
      box-shadow: 0 0 0 3px rgba(255,152,0,0.12);
    }

    .form-group input:focus + .input-icon,
    .input-wrapper:focus-within .input-icon {
      color: var(--orange);
    }

    /* Register link */
    .register-link {
      text-align: center;
      margin-bottom: 20px;
    }

    .register-link p {
      color: rgba(255,255,255,0.5);
      font-size: 13.5px;
      margin: 0;
    }

    .register-link a {
      color: var(--orange);
      text-decoration: none;
      font-weight: 600;
      font-size: 13.5px;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .register-link a:hover {
      color: var(--gold);
      gap: 10px;
    }

    /* Primary Button */
    .btn-login {
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
      margin-bottom: 12px;
    }

    .btn-login:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 36px rgba(255,152,0,0.5);
    }

    .btn-login:active { transform: translateY(-1px); }

    /* Admin Button */
    .btn-admin {
      width: 100%;
      padding: 13px 24px;
      background: transparent;
      color: rgba(255,255,255,0.7);
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      font-size: 13px;
      letter-spacing: 0.3px;
      border: 1px solid var(--glass-border);
      border-radius: 50px;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      text-decoration: none;
    }

    .btn-admin:hover {
      background: var(--glass);
      border-color: rgba(255,255,255,0.25);
      color: white;
      transform: translateY(-2px);
    }

    /* Divider */
    .form-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--glass-border), transparent);
      margin: 20px 0;
    }

    /* =============================================
       RESPONSIVE
       ============================================= */
    @media (max-width: 820px) {
      .login-wrapper {
        grid-template-columns: 1fr;
        border-radius: var(--radius-lg);
      }

      .login-image {
        min-height: 260px;
      }

      .login-image-content {
        padding: 36px 32px;
      }

      .login-image-content h2 { font-size: 34px; }
      .login-brand { top: 28px; left: 28px; }

      .login-form-section {
        padding: 42px 36px;
      }
    }

    @media (max-width: 480px) {
      body { padding: 20px 14px; }

      .login-form-section {
        padding: 36px 24px;
      }

      .login-title { font-size: 34px; }
      .login-image-content h2 { font-size: 28px; }
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

  <div class="login-container">
    <div class="login-wrapper">

      <!-- LEFT: Image / Brand Panel -->
      <div class="login-image">
        <div class="login-image-bg"></div>
        <div class="login-image-overlay"></div>

        <a href="index.php" class="login-brand" style="text-decoration:none;">
          <img src="LOGORBG.png" alt="Logo" class="login-brand-logo">
          <span class="login-brand-name">Universitas<br>Nusantara</span>
        </a>

        <div class="login-image-content">
          <div class="login-image-label">PMB 2025 / 2026</div>
          <h2>Selamat<br>Datang<br><em>Kembali!</em></h2>
          <p>Masuk untuk melanjutkan proses pendaftaran dan cek status penerimaan Anda.</p>
        </div>
      </div>

      <!-- RIGHT: Form Panel -->
      <div class="login-form-section">
        <div class="form-eyebrow">Akun Mahasiswa</div>
        <h1 class="login-title">Masuk</h1>
        <p class="login-subtitle">Silakan masukkan detail Anda untuk melanjutkan.</p>

        <form id="loginForm" method="POST" action="login.php">

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
                     placeholder="Masukkan kata sandi Anda" required>
              <i class="bi bi-lock input-icon"></i>
            </div>
          </div>

          <!-- Register link -->
          <div class="register-link">
            <p>Belum punya akun?
              <a href="register.php">
                <i class="bi bi-person-plus"></i> Daftar Akun Baru
              </a>
            </p>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Masuk
          </button>

          <div class="form-divider"></div>

          <!-- Admin -->
          <a href="login_admin.php" class="btn-admin">
            <i class="bi bi-shield-lock"></i> Masuk Sebagai Admin
          </a>

        </form>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>