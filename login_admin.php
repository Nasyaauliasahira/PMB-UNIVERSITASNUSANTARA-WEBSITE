<?php
session_start();
include "koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    if ($email === "admin@gmail.com" && $password === "admin123") {
        $_SESSION['admin_id']   = 1;
        $_SESSION['admin_name'] = "Admin";
        $_SESSION['is_admin']   = true;
        header("Location: dahboardadmin.php");
        exit;
    } else {
        $error = "Email atau password admin salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin — Panel Universitas</title>
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
      --transition:  all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    }
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    html{scroll-behavior:smooth;}

    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      background: var(--navy);
      display: flex;
      align-items: stretch;
      overflow: hidden;
    }

    /* ── LEFT PANEL ── */
    .left-panel {
      flex: 1;
      background: linear-gradient(160deg, var(--navy) 0%, #0a1828 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 56px;
      position: relative;
      overflow: hidden;
    }

    .left-panel::before {
      content: '';
      position: absolute;
      top: -120px; right: -100px;
      width: 400px; height: 400px; border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.08) 0%, transparent 70%);
    }
    .left-panel::after {
      content: '';
      position: absolute;
      bottom: -80px; left: -60px;
      width: 300px; height: 300px; border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.05) 0%, transparent 70%);
    }

    .brand-logo {
      width: 60px; height: 60px;
      object-fit: contain;
      margin-bottom: 36px;
    }

    .left-eyebrow {
      font-size: 10px; font-weight: 700; letter-spacing: 3px;
      text-transform: uppercase; color: var(--orange);
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 16px;
    }
    .left-eyebrow::before {
      content: ''; width: 24px; height: 2px; background: var(--orange);
    }

    .left-heading {
      font-family: 'Cormorant Garamond', serif;
      font-size: 46px; font-weight: 700; color: white;
      line-height: 1.1; margin-bottom: 18px;
    }
    .left-heading em { color: var(--gold); font-style: italic; }

    .left-desc {
      font-size: 14px; color: rgba(255,255,255,0.55);
      line-height: 1.8; max-width: 320px; margin-bottom: 40px;
    }

    /* Security badges */
    .security-badges {
      display: flex; flex-direction: column; gap: 12px;
    }
    .sec-badge {
      display: flex; align-items: center; gap: 14px;
      padding: 13px 16px; border-radius: 14px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
    }
    .sec-icon {
      width: 36px; height: 36px; border-radius: 10px;
      background: rgba(255,152,0,0.12);
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; color: var(--orange); flex-shrink: 0;
    }
    .sec-text strong { font-size: 13px; color: white; display: block; margin-bottom: 2px; }
    .sec-text span { font-size: 11px; color: rgba(255,255,255,0.45); }

    /* Geometric decoration */
    .geo-deco {
      position: absolute; right: -2px; top: 50%;
      transform: translateY(-50%);
      width: 40px; display: flex; flex-direction: column; gap: 0;
    }
    .geo-deco span {
      display: block; height: 40px;
      background: var(--off-white);
      opacity: 0.06;
    }
    .geo-deco span:nth-child(odd) { opacity: 0.03; }

    /* ── RIGHT PANEL ── */
    .right-panel {
      width: 480px; flex-shrink: 0;
      background: var(--off-white);
      display: flex; flex-direction: column;
      justify-content: center; align-items: center;
      padding: 48px 52px;
      position: relative;
    }

    .right-panel::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--orange), var(--gold), var(--orange));
    }

    .form-header { width: 100%; margin-bottom: 32px; }
    .form-eyebrow {
      font-size: 10px; font-weight: 700; letter-spacing: 3px;
      text-transform: uppercase; color: var(--orange);
      margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
    }
    .form-eyebrow i { font-size: 14px; }
    .form-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 32px; font-weight: 700; color: var(--navy);
      line-height: 1.1; margin-bottom: 6px;
    }
    .form-subtitle { font-size: 13px; color: #8898aa; }

    /* Admin badge */
    .admin-badge {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 7px 14px; border-radius: 50px;
      background: rgba(13,31,53,0.08);
      border: 1px solid rgba(13,31,53,0.12);
      font-size: 11px; font-weight: 700;
      color: var(--navy-mid); letter-spacing: 1px;
      text-transform: uppercase; margin-top: 10px;
    }
    .admin-badge i { color: var(--orange); font-size: 13px; }

    /* Alert */
    .alert-c {
      width: 100%;
      border-radius: 14px; padding: 13px 16px;
      margin-bottom: 22px;
      display: flex; align-items: flex-start; gap: 11px;
      font-size: 13px; line-height: 1.5;
      background: rgba(139,26,26,0.07);
      border: 1px solid rgba(220,53,69,0.2); color: #8b1a1a;
      animation: shakeX 0.5s ease;
    }
    .alert-c i { font-size: 16px; color: #dc3545; flex-shrink: 0; margin-top: 1px; }
    @keyframes shakeX {
      0%,100%{transform:translateX(0)}
      20%{transform:translateX(-8px)}
      40%{transform:translateX(8px)}
      60%{transform:translateX(-5px)}
      80%{transform:translateX(5px)}
    }

    /* Form */
    .login-form { width: 100%; }

    .field-wrap { margin-bottom: 18px; }
    .field-label {
      display: block; font-size: 10px; font-weight: 700;
      letter-spacing: 1.5px; text-transform: uppercase;
      color: var(--navy-mid); margin-bottom: 8px;
    }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 14px; top: 50%;
      transform: translateY(-50%);
      font-size: 16px; color: #aab4be; pointer-events: none;
    }
    .field-input {
      width: 100%; padding: 13px 16px 13px 44px;
      border: 1px solid rgba(13,31,53,0.15);
      border-radius: 14px;
      font-size: 14px; font-family: 'DM Sans', sans-serif;
      color: var(--navy); background: white;
      transition: var(--transition);
    }
    .field-input::placeholder { color: #aab4be; }
    .field-input:focus {
      outline: none;
      border-color: var(--orange);
      box-shadow: 0 0 0 3px rgba(255,152,0,0.12);
    }
    .pwd-toggle {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: #aab4be; font-size: 15px; padding: 4px;
      transition: var(--transition);
    }
    .pwd-toggle:hover { color: var(--orange); }

    /* Submit */
    .btn-login {
      width: 100%; padding: 14px 24px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--navy), var(--navy-mid));
      color: white; border: none; cursor: pointer;
      font-size: 15px; font-weight: 700;
      font-family: 'DM Sans', sans-serif;
      transition: var(--transition);
      display: flex; align-items: center;
      justify-content: center; gap: 9px;
      margin-top: 8px;
      box-shadow: 0 4px 20px rgba(13,31,53,0.25);
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 32px rgba(13,31,53,0.35);
      background: linear-gradient(135deg, var(--navy-mid), var(--navy-light));
    }

    /* Back link */
    .back-link {
      margin-top: 28px;
      text-align: center;
      font-size: 13px; color: #8898aa;
    }
    .back-link a {
      color: var(--orange); text-decoration: none;
      font-weight: 600; transition: var(--transition);
      display: inline-flex; align-items: center; gap: 5px;
    }
    .back-link a:hover { color: var(--orange-dark); }

    /* Divider */
    .divider {
      display: flex; align-items: center; gap: 14px;
      margin: 22px 0; color: #c5ccd5; font-size: 12px;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px;
      background: rgba(13,31,53,0.1);
    }

    /* Responsive */
    @media (max-width: 900px) {
      .left-panel { display: none; }
      .right-panel { width: 100%; padding: 48px 32px; }
    }
    @media (max-width: 480px) {
      .right-panel { padding: 40px 24px; }
    }
  </style>
</head>
<body>

<!-- ── LEFT PANEL ── -->
<div class="left-panel">
  <div class="left-eyebrow">Akses Terbatas</div>
  <h1 class="left-heading">Panel<br>Administrasi<br><em>Universitas</em></h1>
  <p class="left-desc">Halaman ini hanya dapat diakses oleh administrator yang berwenang. Harap gunakan kredensial resmi Anda.</p>

  <div class="security-badges">
    <div class="sec-badge">
      <div class="sec-icon"><i class="bi bi-shield-lock-fill"></i></div>
      <div class="sec-text">
        <strong>Akses Terenkripsi</strong>
        <span>Sesi dilindungi dengan autentikasi admin</span>
      </div>
    </div>
    <div class="sec-badge">
      <div class="sec-icon"><i class="bi bi-person-badge-fill"></i></div>
      <div class="sec-text">
        <strong>Login Khusus Admin</strong>
        <span>Berbeda dengan akun mahasiswa biasa</span>
      </div>
    </div>
  </div>
</div>

<!-- ── RIGHT PANEL ── -->
<div class="right-panel">
  <div class="form-header">
    <div class="form-eyebrow"><i class="bi bi-shield-fill-check"></i> Admin Panel</div>
    <div class="form-title">Masuk ke<br>Dashboard Admin</div>
    <div class="form-subtitle">Gunakan kredensial admin untuk melanjutkan</div>
    <div class="admin-badge"><i class="bi bi-person-badge-fill"></i> Login Khusus Admin</div>
  </div>

  <?php if ($error): ?>
  <div class="alert-c">
    <i class="bi bi-exclamation-circle-fill"></i>
    <div><?php echo htmlspecialchars($error); ?></div>
  </div>
  <?php endif; ?>

  <form method="POST" action="" class="login-form">

    <div class="field-wrap">
      <label class="field-label">Email Admin</label>
      <div class="input-wrap">
        <i class="bi bi-envelope input-icon"></i>
        <input type="email" class="field-input" name="email"
          placeholder="admin@universitas.ac.id" required autofocus
          value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>
    </div>

    <div class="field-wrap">
      <label class="field-label">Password</label>
      <div class="input-wrap">
        <i class="bi bi-key input-icon"></i>
        <input type="password" class="field-input" name="password"
          id="adminPwd" placeholder="Masukkan password admin" required>
        <button type="button" class="pwd-toggle" onclick="togglePwd()">
          <i class="bi bi-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn-login">
      <i class="bi bi-box-arrow-in-right"></i> Masuk ke Admin Panel
    </button>

  </form>

  <div class="divider">atau</div>

  <div class="back-link">
    <a href="login_pendaftaran.php">
      <i class="bi bi-arrow-left"></i> Kembali ke Login Peserta
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function togglePwd() {
    const input = document.getElementById('adminPwd');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      input.type = 'password';
      icon.className = 'bi bi-eye';
    }
  }
</script>
</body>
</html>