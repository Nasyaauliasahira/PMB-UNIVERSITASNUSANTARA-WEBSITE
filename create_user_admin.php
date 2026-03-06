<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

$alert_message = $alert_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $first_name       = mysqli_real_escape_string($koneksi, $_POST['first_name']);
    $last_name        = mysqli_real_escape_string($koneksi, $_POST['last_name']);
    $email            = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password         = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    $errors = [];
    if (empty($first_name))                                   $errors[] = "Nama depan tidak boleh kosong";
    if (empty($last_name))                                    $errors[] = "Nama belakang tidak boleh kosong";
    if (empty($email))                                        $errors[] = "Email tidak boleh kosong";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors[] = "Format email tidak valid";
    if (empty($password))                                     $errors[] = "Password tidak boleh kosong";
    elseif (strlen($password) < 6)                            $errors[] = "Password minimal 6 karakter";
    if ($password !== $password_confirm)                      $errors[] = "Konfirmasi password tidak sesuai";

    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0)                            $errors[] = "Email sudah terdaftar";

    if (empty($errors)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $first_name, $last_name, $email, $password);
        if (mysqli_stmt_execute($stmt)) {
            $alert_message = "User <strong>" . htmlspecialchars($first_name . ' ' . $last_name) . "</strong> berhasil dibuat!";
            $alert_type    = "success";
            $_POST         = [];
        } else {
            $alert_message = "Gagal membuat user: " . mysqli_error($koneksi);
            $alert_type    = "danger";
        }
        mysqli_stmt_close($stmt);
    } else {
        $alert_message = implode("<br>• ", array_map('htmlspecialchars', $errors));
        $alert_type    = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buat User — Admin Panel</title>
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
      --green:       #1a6e3c;
      --green-light: #27ae60;
      --sidebar-w:   260px;
      --shadow-card: 0 8px 32px rgba(13,31,53,0.10);
      --radius-xl:   26px;
      --transition:  all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    }
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    html{scroll-behavior:smooth;}
    body{font-family:'DM Sans',sans-serif;background:var(--off-white);min-height:100vh;}

    /* ── SIDEBAR ── */
    .sidebar{width:var(--sidebar-w);height:100vh;background:linear-gradient(175deg,var(--navy) 0%,#0a1828 100%);position:fixed;left:0;top:0;z-index:200;display:flex;flex-direction:column;border-right:1px solid rgba(255,255,255,0.06);overflow:hidden;}
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
    .sidebar-logout{display:flex;align-items:center;gap:10px;padding:11px 16px;border-radius:12px;background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.2);color:#f87171;text-decoration:none;font-size:13px;font-weight:600;transition:var(--transition);}
    .sidebar-logout:hover{background:rgba(220,53,69,0.2);color:#fca5a5;}

    /* ── MAIN ── */
    .main-wrap{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;}

    /* Topbar */
    .topbar{background:white;border-bottom:1px solid rgba(13,31,53,0.07);padding:18px 36px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(13,31,53,0.06);}
    .topbar-left{display:flex;flex-direction:column;gap:2px;}
    .topbar-eyebrow{font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--orange);}
    .topbar-title{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--navy);line-height:1;}
    .btn-back{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:50px;background:rgba(13,31,53,0.06);color:var(--navy);border:1px solid rgba(13,31,53,0.12);font-size:13px;font-weight:600;text-decoration:none;transition:var(--transition);}
    .btn-back:hover{background:var(--navy);color:white;}

    /* Page content */
    .page-content{padding:32px 36px 60px;display:flex;flex-direction:column;align-items:center;}

    /* Split layout */
    .form-layout{display:grid;grid-template-columns:1fr 1.6fr;gap:24px;width:100%;max-width:900px;align-items:start;}

    /* Info panel */
    .info-panel{background:linear-gradient(160deg,var(--navy) 0%,var(--navy-mid) 100%);border-radius:var(--radius-xl);padding:36px 32px;position:sticky;top:100px;overflow:hidden;}
    .info-panel::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(255,152,0,0.1) 0%,transparent 70%);}
    .info-eyebrow{font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
    .info-eyebrow::before{content:'';width:20px;height:2px;background:var(--orange);}
    .info-heading{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:white;line-height:1.2;margin-bottom:14px;}
    .info-heading em{color:var(--gold);font-style:italic;}
    .info-desc{font-size:13px;color:rgba(255,255,255,0.6);line-height:1.75;margin-bottom:28px;}

    .info-rules{display:flex;flex-direction:column;gap:12px;}
    .info-rule{display:flex;align-items:flex-start;gap:12px;}
    .rule-dot{width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--orange);flex-shrink:0;font-weight:700;}
    .rule-text{font-size:12px;color:rgba(255,255,255,0.65);line-height:1.6;padding-top:4px;}
    .rule-text strong{color:white;display:block;margin-bottom:1px;}

    /* Form card */
    .form-card{background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-card);overflow:hidden;animation:fadeUp 0.5s ease-out both;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

    .form-card-header{background:linear-gradient(135deg,var(--navy),var(--navy-mid));padding:24px 32px;}
    .form-card-eyebrow{font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:4px;display:flex;align-items:center;gap:8px;}
    .form-card-eyebrow::before{content:'';width:20px;height:2px;background:var(--orange);}
    .form-card-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:white;}

    .form-body{padding:32px;}

    /* Alerts */
    .alert-c{border-radius:14px;padding:14px 18px;margin-bottom:22px;display:flex;align-items:flex-start;gap:12px;font-size:13px;line-height:1.6;animation:fadeUp 0.4s ease-out;}
    .alert-error-c{background:rgba(139,26,26,0.07);border:1px solid rgba(220,53,69,0.2);color:#8b1a1a;}
    .alert-success-c{background:rgba(39,174,96,0.07);border:1px solid rgba(39,174,96,0.2);color:#1a6e3c;}
    .alert-c i{font-size:18px;flex-shrink:0;margin-top:1px;}
    .alert-error-c i{color:#dc3545;}
    .alert-success-c i{color:#27ae60;}

    /* Section */
    .section-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--navy-mid);margin-bottom:14px;display:flex;align-items:center;gap:10px;}
    .section-label::after{content:'';flex:1;height:1px;background:rgba(13,31,53,0.07);}

    /* Field group */
    .field-group{margin-bottom:20px;}
    .field-group.two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .field-label{display:block;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--navy-mid);margin-bottom:7px;}
    .field-required{color:var(--orange);}
    .field-input{width:100%;padding:12px 16px;border:1px solid rgba(13,31,53,0.15);border-radius:12px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--navy);background:white;transition:var(--transition);}
    .field-input::placeholder{color:#aab4be;}
    .field-input:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,152,0,0.12);}
    .field-input.valid{border-color:var(--green-light);background:rgba(39,174,96,0.03);}
    .field-input.invalid{border-color:#dc3545;background:rgba(220,53,69,0.03);}

    /* Password input wrapper */
    .pwd-wrap{position:relative;}
    .pwd-wrap .field-input{padding-right:44px;}
    .pwd-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#aab4be;font-size:15px;transition:var(--transition);padding:4px;}
    .pwd-toggle:hover{color:var(--orange);}

    /* Strength bar */
    .pwd-strength{margin-top:8px;}
    .pwd-strength-track{height:4px;background:rgba(13,31,53,0.08);border-radius:2px;overflow:hidden;}
    .pwd-strength-fill{height:100%;width:0;border-radius:2px;transition:all 0.4s ease;}
    .pwd-strength-label{font-size:11px;margin-top:4px;color:#aab4be;}

    /* Buttons */
    .btn-row{display:flex;gap:12px;margin-top:8px;}
    .btn-submit{flex:1;padding:14px 24px;border-radius:50px;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:white;border:none;font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;transition:var(--transition);box-shadow:0 4px 16px rgba(255,152,0,0.3);display:flex;align-items:center;justify-content:center;gap:8px;}
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,152,0,0.45);}
    .btn-cancel{padding:14px 24px;border-radius:50px;background:rgba(13,31,53,0.06);color:var(--navy);border:1px solid rgba(13,31,53,0.12);font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;text-decoration:none;transition:var(--transition);display:flex;align-items:center;gap:8px;}
    .btn-cancel:hover{background:rgba(13,31,53,0.12);color:var(--navy);}

    @media(max-width:900px){.form-layout{grid-template-columns:1fr;}.info-panel{position:static;}}
    @media(max-width:768px){
      :root{--sidebar-w:0px;}
      .sidebar{transform:translateX(-100%);}
      .main-wrap{margin-left:0;}
      .page-content{padding:24px 16px 48px;}
      .topbar{padding:16px 20px;}
      .form-body{padding:24px 20px;}
      .field-group.two-col{grid-template-columns:1fr;}
      .btn-row{flex-direction:column;}
    }
  </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
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
    <a href="bank_soal_admin.php" class="sidebar-link">
      <span class="link-icon"><i class="bi bi-journal-bookmark"></i></span> Bank Soal
    </a>
    <a href="register_login_admin.php" class="sidebar-link active">
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

<!-- ── MAIN ── -->
<div class="main-wrap">

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-eyebrow">Register & Login</div>
      <div class="topbar-title">Buat User Baru</div>
    </div>
    <a href="register_login_admin.php" class="btn-back">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <!-- Content -->
  <div class="page-content">
    <div class="form-layout">

      <!-- Info Panel -->
      <div class="info-panel">
        <div class="info-eyebrow">Panduan</div>
        <h2 class="info-heading">Buat Akun<br>User <em>Baru</em></h2>
        <p class="info-desc">Isi data di bawah untuk membuat akun user baru secara manual. User dapat langsung login setelah akun dibuat.</p>
        <div class="info-rules">
          <div class="info-rule">
            <div class="rule-dot">1</div>
            <div class="rule-text"><strong>Nama Lengkap</strong>Isi nama depan dan belakang user</div>
          </div>
          <div class="info-rule">
            <div class="rule-dot">2</div>
            <div class="rule-text"><strong>Email Unik</strong>Email tidak boleh sudah terdaftar sebelumnya</div>
          </div>
          <div class="info-rule">
            <div class="rule-dot">3</div>
            <div class="rule-text"><strong>Password Aman</strong>Minimal 6 karakter, konfirmasi harus sama</div>
          </div>
        </div>
      </div>

      <!-- Form Card -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-eyebrow">Formulir</div>
          <div class="form-card-title">Data Akun User</div>
        </div>
        <div class="form-body">

          <?php if (!empty($alert_message)): ?>
          <div class="alert-c <?php echo $alert_type === 'success' ? 'alert-success-c' : 'alert-error-c'; ?>">
            <i class="bi bi-<?php echo $alert_type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?>"></i>
            <div>
              <?php if ($alert_type === 'success'): ?>
                <?php echo $alert_message; ?> &nbsp;<a href="register_login_admin.php" style="color:var(--green);font-weight:700;">Lihat semua user →</a>
              <?php else: ?>
                <strong>Terdapat kesalahan:</strong><br>• <?php echo $alert_message; ?>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <form method="POST" action="" id="createForm">

            <!-- Nama -->
            <div class="section-label">Nama Lengkap</div>
            <div class="field-group two-col">
              <div>
                <label class="field-label">Nama Depan <span class="field-required">*</span></label>
                <input type="text" class="field-input" name="first_name" id="first_name"
                  placeholder="Budi"
                  value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                  required>
              </div>
              <div>
                <label class="field-label">Nama Belakang <span class="field-required">*</span></label>
                <input type="text" class="field-input" name="last_name" id="last_name"
                  placeholder="Santoso"
                  value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                  required>
              </div>
            </div>

            <!-- Email -->
            <div class="section-label" style="margin-top:4px;">Email</div>
            <div class="field-group">
              <label class="field-label">Alamat Email <span class="field-required">*</span></label>
              <input type="email" class="field-input" name="email" id="emailInput"
                placeholder="user@email.com"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                required>
            </div>

            <!-- Password -->
            <div class="section-label">Password</div>
            <div class="field-group two-col">
              <div>
                <label class="field-label">Password <span class="field-required">*</span></label>
                <div class="pwd-wrap">
                  <input type="password" class="field-input" name="password" id="pwdInput"
                    placeholder="Min. 6 karakter" required>
                  <button type="button" class="pwd-toggle" onclick="togglePwd('pwdInput', this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <div class="pwd-strength">
                  <div class="pwd-strength-track"><div class="pwd-strength-fill" id="strengthFill"></div></div>
                  <div class="pwd-strength-label" id="strengthLabel">Masukkan password</div>
                </div>
              </div>
              <div>
                <label class="field-label">Konfirmasi Password <span class="field-required">*</span></label>
                <div class="pwd-wrap">
                  <input type="password" class="field-input" name="password_confirm" id="pwdConfirm"
                    placeholder="Ulangi password" required>
                  <button type="button" class="pwd-toggle" onclick="togglePwd('pwdConfirm', this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <div style="font-size:11px;margin-top:8px;color:#aab4be;" id="matchLabel">—</div>
              </div>
            </div>

            <!-- Buttons -->
            <div class="btn-row">
              <button type="submit" name="submit" class="btn-submit">
                <i class="bi bi-person-plus-fill"></i> Buat User
              </button>
              <a href="register_login_admin.php" class="btn-cancel">
                <i class="bi bi-x-lg"></i> Batal
              </a>
            </div>

          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle password visibility
  function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      input.type = 'password';
      icon.className = 'bi bi-eye';
    }
  }

  // Password strength
  const pwdInput     = document.getElementById('pwdInput');
  const pwdConfirm   = document.getElementById('pwdConfirm');
  const strengthFill = document.getElementById('strengthFill');
  const strengthLbl  = document.getElementById('strengthLabel');
  const matchLbl     = document.getElementById('matchLabel');

  pwdInput.addEventListener('input', function() {
    const v = this.value;
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const pct    = ['0%','20%','40%','60%','80%','100%'][score];
    const colors = ['','#dc3545','#fd7e14','#ffc107','#20c997','#27ae60'][score];
    const labels = ['Masukkan password','Sangat lemah','Lemah','Cukup','Kuat','Sangat kuat'][score];

    strengthFill.style.width = pct;
    strengthFill.style.background = colors;
    strengthLbl.textContent = labels;
    strengthLbl.style.color = colors || '#aab4be';

    checkMatch();
  });

  pwdConfirm.addEventListener('input', checkMatch);

  function checkMatch() {
    if (!pwdConfirm.value) { matchLbl.textContent = '—'; matchLbl.style.color = '#aab4be'; return; }
    if (pwdInput.value === pwdConfirm.value) {
      matchLbl.textContent = '✓ Password cocok';
      matchLbl.style.color = 'var(--green-light)';
      pwdConfirm.style.borderColor = 'var(--green-light)';
    } else {
      matchLbl.textContent = '✗ Password tidak cocok';
      matchLbl.style.color = '#dc3545';
      pwdConfirm.style.borderColor = '#dc3545';
    }
  }

  // Real-time email validation
  document.getElementById('emailInput').addEventListener('input', function() {
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
    this.style.borderColor = this.value ? (valid ? 'var(--green-light)' : '#dc3545') : '';
    this.style.boxShadow = '';
  });
</script>
</body>
</html>