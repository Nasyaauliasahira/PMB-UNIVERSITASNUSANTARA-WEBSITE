<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: register_login_admin.php");
    exit;
}

$id = intval($_GET['id']);
$stmt = mysqli_prepare($koneksi, "SELECT id, first_name, last_name, email FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: register_login_admin.php");
    exit;
}

$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$alert_message = "";
$alert_type = "";
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    $alert_type    = $_SESSION['alert_type'];
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User — Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy:#0d1f35; --navy-mid:#1e3a5f; --navy-light:#2d5a7b;
      --orange:#ff9800; --orange-dark:#e68900; --gold:#f0c060;
      --off-white:#f7f5f0;
      --glass:rgba(255,255,255,0.06); --glass-border:rgba(255,255,255,0.12);
      --shadow-deep:0 24px 64px rgba(13,31,53,0.35);
      --shadow-card:0 8px 32px rgba(13,31,53,0.18);
      --radius-lg:20px;
      --transition:all 0.45s cubic-bezier(0.25,0.46,0.45,0.94);
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    html{scroll-behavior:smooth;}
    body{font-family:'DM Sans',sans-serif;background:var(--off-white);color:var(--navy);min-height:100vh;display:flex;}

    /* SIDEBAR */
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

    /* MAIN */
    .main-content{margin-left:260px;flex:1;display:flex;flex-direction:column;min-height:100vh;}
    .topbar{background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);border-bottom:1px solid rgba(13,31,53,0.08);padding:0 36px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 16px rgba(13,31,53,0.06);}
    .topbar-breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;}
    .topbar-breadcrumb a{color:var(--navy-mid);text-decoration:none;font-weight:500;transition:var(--transition);}
    .topbar-breadcrumb a:hover{color:var(--orange);}
    .topbar-breadcrumb .sep{color:#ccc;font-size:11px;}
    .topbar-breadcrumb .current{color:var(--orange);font-weight:600;}
    .topbar-badge{background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;padding:6px 16px;border-radius:50px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:7px;}
    .topbar-badge i{color:var(--orange);}

    .page-body{padding:36px;flex:1;display:flex;flex-direction:column;align-items:center;}
    .page-header{margin-bottom:32px;width:100%;max-width:600px;}
    .page-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:10px;}
    .page-eyebrow::before{content:'';width:28px;height:2px;background:var(--orange);}
    .page-header h1{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:34px;color:var(--navy);line-height:1.2;}
    .page-header p{color:#8898aa;font-size:14px;margin-top:6px;}

    /* ALERT */
    .alert-custom{padding:14px 20px;border-radius:12px;margin-bottom:24px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;border:1px solid;width:100%;max-width:600px;}
    .alert-success-custom{background:#f0fdf4;color:#166534;border-color:rgba(22,101,52,0.2);}
    .alert-danger-custom{background:#fff5f5;color:#c0392b;border-color:rgba(192,57,43,0.2);}

    /* USER CARD — avatar + nama */
    .user-card{background:white;border-radius:var(--radius-lg);box-shadow:var(--shadow-card);border:1px solid rgba(13,31,53,0.06);padding:28px;display:flex;align-items:center;gap:20px;width:100%;max-width:600px;margin-bottom:20px;transition:var(--transition);}
    .user-card:hover{box-shadow:var(--shadow-deep);}
    .user-avatar{width:72px;height:72px;border-radius:16px;background:linear-gradient(135deg,var(--navy),var(--navy-light));display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--orange);flex-shrink:0;box-shadow:0 6px 20px rgba(13,31,53,0.2);}
    .user-card-info h5{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:22px;color:var(--navy);margin-bottom:4px;}
    .user-card-info p{font-size:13px;color:#8898aa;}
    .user-id-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,152,0,0.1);color:var(--orange);border:1px solid rgba(255,152,0,0.3);padding:4px 12px;border-radius:50px;font-size:12px;font-weight:600;margin-top:8px;}

    /* FORM CARD */
    .form-card{background:white;border-radius:var(--radius-lg);box-shadow:var(--shadow-card);border:1px solid rgba(13,31,53,0.06);width:100%;max-width:600px;overflow:hidden;transition:var(--transition);}
    .form-card:hover{box-shadow:var(--shadow-deep);}
    .form-card-header{display:flex;align-items:center;gap:14px;padding:20px 28px;border-bottom:1px solid rgba(13,31,53,0.07);background:var(--off-white);}
    .form-card-header-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--navy),var(--navy-light));color:var(--orange);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
    .form-card-header h5{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:20px;color:var(--navy);margin:0;}
    .form-card-header p{font-size:12px;color:#9aabba;margin:2px 0 0;}
    .form-card-body{padding:28px;}

    /* FIELDS */
    .field-group{margin-bottom:20px;}
    .field-group:last-child{margin-bottom:0;}
    .field-label{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#8898aa;margin-bottom:8px;}
    .field-label i{font-size:14px;color:var(--navy-mid);}
    .field-input{width:100%;padding:12px 16px;border:2px solid rgba(13,31,53,0.1);border-radius:12px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--navy);background:white;transition:var(--transition);}
    .field-input:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,152,0,0.12);}
    .field-input::placeholder{color:#aab4be;}

    /* Password toggle */
    .password-wrap{position:relative;}
    .password-wrap .field-input{padding-right:48px;}
    .toggle-password{position:absolute;right:16px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aab4be;font-size:17px;transition:var(--transition);background:none;border:none;padding:0;}
    .toggle-password:hover{color:var(--orange);}

    .field-hint{display:flex;align-items:center;gap:6px;font-size:12px;color:#aab4be;margin-top:8px;}
    .field-hint i{font-size:13px;}

    /* DIVIDER between sections */
    .field-divider{height:1px;background:rgba(13,31,53,0.07);margin:24px 0;}

    /* ACTIONS */
    .action-bar{display:flex;gap:14px;justify-content:flex-end;padding-top:24px;width:100%;max-width:600px;}
    .btn-cancel{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;background:transparent;color:var(--navy-mid);border:2px solid rgba(13,31,53,0.15);border-radius:50px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;text-decoration:none;cursor:pointer;transition:var(--transition);}
    .btn-cancel:hover{border-color:rgba(13,31,53,0.3);background:rgba(13,31,53,0.05);color:var(--navy);}
    .btn-save{display:inline-flex;align-items:center;gap:8px;padding:13px 32px;background:linear-gradient(135deg,var(--orange) 0%,var(--orange-dark) 100%);color:white;border:none;border-radius:50px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;box-shadow:0 6px 20px rgba(255,152,0,0.35);transition:var(--transition);}
    .btn-save:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(255,152,0,0.5);color:white;}

    /* RESPONSIVE */
    @media(max-width:991px){.sidebar{width:220px;}.main-content{margin-left:220px;}.page-body{padding:24px;}}
    @media(max-width:768px){.sidebar{display:none;}.main-content{margin-left:0;}.topbar{padding:0 20px;}.page-body{padding:20px;}.action-bar{flex-direction:column;}.btn-cancel,.btn-save{width:100%;justify-content:center;}}
    @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important;}}
  </style>
</head>
<body>

  <!-- SIDEBAR -->
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

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-breadcrumb">
        <a href="dahboardadmin.php">Dashboard</a>
        <span class="sep"><i class="bi bi-chevron-right"></i></span>
        <a href="register_login_admin.php">Register &amp; Login</a>
        <span class="sep"><i class="bi bi-chevron-right"></i></span>
        <span class="current">Edit User</span>
      </div>
      <div class="topbar-badge">
        <i class="bi bi-person-gear"></i>
        ID #<?php echo $id; ?>
      </div>
    </div>

    <div class="page-body">

      <div class="page-header">
        <div class="page-eyebrow">Register &amp; Login</div>
        <h1>Edit Data User</h1>
        <p>Perbarui informasi akun pengguna yang terdaftar.</p>
      </div>

      <?php if (!empty($alert_message)): ?>
        <div class="alert-custom <?php echo $alert_type === 'success' ? 'alert-success-custom' : 'alert-danger-custom'; ?>">
          <i class="bi bi-<?php echo $alert_type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?>"></i>
          <?php echo htmlspecialchars($alert_message); ?>
        </div>
      <?php endif; ?>

      <!-- User Card -->
      <div class="user-card">
        <div class="user-avatar">
          <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
        </div>
        <div class="user-card-info">
          <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
          <p><?php echo htmlspecialchars($user['email']); ?></p>
          <div class="user-id-badge"><i class="bi bi-person"></i> User #<?php echo $user['id']; ?></div>
        </div>
      </div>

      <!-- Form -->
      <form action="proses_edit_user.php" method="POST" style="width:100%;max-width:600px;">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-header-icon"><i class="bi bi-person-gear"></i></div>
            <div>
              <h5>Informasi Akun</h5>
              <p>Nama, email, dan password pengguna</p>
            </div>
          </div>
          <div class="form-card-body">

            <div class="field-group">
              <div class="field-label"><i class="bi bi-person"></i> First Name</div>
              <input type="text" class="field-input" name="first_name"
                     placeholder="Nama depan"
                     value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            </div>

            <div class="field-group">
              <div class="field-label"><i class="bi bi-person"></i> Last Name</div>
              <input type="text" class="field-input" name="last_name"
                     placeholder="Nama belakang"
                     value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            </div>

            <div class="field-group">
              <div class="field-label"><i class="bi bi-envelope"></i> Email</div>
              <input type="email" class="field-input" name="email"
                     placeholder="email@domain.com"
                     value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="field-divider"></div>

            <div class="field-group">
              <div class="field-label"><i class="bi bi-lock"></i> Password Baru</div>
              <div class="password-wrap">
                <input type="password" class="field-input" id="password" name="password"
                       placeholder="Kosongkan jika tidak ingin mengubah">
                <button type="button" class="toggle-password" id="togglePassword">
                  <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
              </div>
              <div class="field-hint">
                <i class="bi bi-info-circle"></i>
                Kosongkan field ini jika tidak ingin mengubah password
              </div>
            </div>

          </div>
        </div>

        <div class="action-bar">
          <a href="register_login_admin.php" class="btn-cancel">
            <i class="bi bi-arrow-left"></i> Batal
          </a>
          <button type="submit" class="btn-save">
            <i class="bi bi-check-circle"></i> Simpan Perubahan
          </button>
        </div>

      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const toggleBtn  = document.getElementById('togglePassword');
    const passInput  = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    toggleBtn.addEventListener('click', () => {
      const isPass = passInput.getAttribute('type') === 'password';
      passInput.setAttribute('type', isPass ? 'text' : 'password');
      toggleIcon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  </script>
</body>
</html>
