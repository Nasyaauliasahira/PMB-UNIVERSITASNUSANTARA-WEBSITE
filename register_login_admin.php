<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

$alert_message = $alert_type = "";
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    $alert_type    = $_SESSION['alert_type'];
    unset($_SESSION['alert_message'], $_SESSION['alert_type']);
}

$search = "";
$query  = "SELECT * FROM users ORDER BY created_at DESC";

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = mysqli_real_escape_string($koneksi, $_GET['search']);
    $query  = "SELECT * FROM users WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%' ORDER BY created_at DESC";
}

$result = mysqli_query($koneksi, $query);
$users  = mysqli_fetch_all($result, MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = $id");
    header("Location: register_login_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register & Login — Admin Panel</title>
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
      --sidebar-w:   260px;
      --glass:       rgba(255,255,255,0.06);
      --glass-border:rgba(255,255,255,0.12);
      --shadow-deep: 0 24px 64px rgba(13,31,53,0.35);
      --shadow-card: 0 8px 32px rgba(13,31,53,0.10);
      --radius-lg:   18px;
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
    .sidebar-logout{display:flex;align-items:center;gap:10px;padding:11px 16px;border-radius:12px;background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.2);color:#f87171;text-decoration:none;font-size:13px;font-weight:600;transition:var(--transition);cursor:pointer;}
    .sidebar-logout:hover{background:rgba(220,53,69,0.2);color:#fca5a5;}

    /* ── MAIN ── */
    .main-wrap{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;}

    /* Topbar */
    .topbar{background:white;border-bottom:1px solid rgba(13,31,53,0.07);padding:18px 36px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(13,31,53,0.06);}
    .topbar-left{display:flex;flex-direction:column;gap:2px;}
    .topbar-eyebrow{font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--orange);}
    .topbar-title{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--navy);line-height:1;}
    .topbar-right{display:flex;align-items:center;gap:12px;}
    .topbar-count{background:var(--off-white);border:1px solid rgba(13,31,53,0.1);border-radius:50px;padding:7px 16px;font-size:12px;font-weight:700;color:var(--navy-mid);display:flex;align-items:center;gap:6px;}
    .topbar-count i{color:var(--orange);}
    .btn-add-user{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:50px;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:white;border:none;cursor:pointer;font-weight:700;font-size:13px;font-family:'DM Sans',sans-serif;transition:var(--transition);box-shadow:0 4px 16px rgba(255,152,0,0.3);text-decoration:none;}
    .btn-add-user:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,152,0,0.45);color:white;}

    /* Page content */
    .page-content{padding:32px 36px 60px;}

    /* Alert */
    .alert-custom{border-radius:14px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;font-size:13px;font-weight:500;animation:fadeUp 0.4s ease-out;}
    .alert-success-c{background:rgba(39,174,96,0.08);border:1px solid rgba(39,174,96,0.2);color:#1a6e3c;}
    .alert-danger-c {background:rgba(220,53,69,0.08); border:1px solid rgba(220,53,69,0.2); color:#8b1a1a;}
    .alert-custom i{font-size:18px;flex-shrink:0;}
    .alert-success-c i{color:#27ae60;}
    .alert-danger-c  i{color:#dc3545;}

    /* Search bar */
    .search-bar-wrap{display:flex;gap:12px;align-items:center;margin-bottom:24px;flex-wrap:wrap;}
    .search-box{position:relative;flex:1;min-width:260px;}
    .search-box input{width:100%;padding:12px 16px 12px 42px;border:1px solid rgba(13,31,53,0.15);border-radius:12px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--navy);background:white;transition:var(--transition);}
    .search-box input::placeholder{color:#aab4be;}
    .search-box input:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,152,0,0.12);}
    .search-box i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#aab4be;font-size:16px;pointer-events:none;}
    .btn-search{padding:12px 22px;border-radius:12px;background:var(--navy);color:white;border:none;cursor:pointer;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;transition:var(--transition);display:flex;align-items:center;gap:7px;}
    .btn-search:hover{background:var(--navy-mid);transform:translateY(-1px);}

    /* Table card */
    .table-card{background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-card);overflow:hidden;animation:fadeUp 0.5s ease-out both;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

    /* Table */
    .data-table{width:100%;border-collapse:collapse;}
    .data-table thead{background:linear-gradient(135deg,var(--navy),var(--navy-mid));}
    .data-table thead th{padding:16px 20px;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.7);border:none;white-space:nowrap;}
    .data-table thead th:first-child{color:var(--orange);}
    .data-table tbody tr{border-bottom:1px solid rgba(13,31,53,0.05);transition:var(--transition);}
    .data-table tbody tr:last-child{border-bottom:none;}
    .data-table tbody tr:hover td{background:rgba(255,152,0,0.03);}
    .data-table tbody td{padding:16px 20px;font-size:13px;color:var(--navy);vertical-align:middle;}

    /* Avatar cell */
    .user-cell{display:flex;align-items:center;gap:12px;}
    .user-avatar{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:700;color:var(--orange);flex-shrink:0;}
    .user-fullname{font-weight:600;color:var(--navy);font-size:14px;}

    /* Email pill */
    .email-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:rgba(13,31,53,0.05);border-radius:50px;font-size:12px;color:var(--navy-mid);}
    .email-pill i{font-size:11px;color:var(--orange);}

    /* Password cell */
    .pwd-wrap{display:flex;align-items:center;gap:8px;}
    .pwd-text{font-family:'Courier New',monospace;font-size:12px;color:#8898aa;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .btn-toggle-pwd{background:none;border:none;cursor:pointer;color:#aab4be;font-size:13px;padding:0;transition:var(--transition);}
    .btn-toggle-pwd:hover{color:var(--orange);}

    /* Action btns */
    .action-btns{display:flex;gap:7px;}
    .btn-icon{width:36px;height:36px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:var(--transition);}
    .btn-icon-edit{background:rgba(255,152,0,0.1);color:var(--orange);border:1px solid rgba(255,152,0,0.2);text-decoration:none;}
    .btn-icon-edit:hover{background:var(--orange);color:white;transform:scale(1.08);}
    .btn-icon-del{background:rgba(220,53,69,0.1);color:#dc3545;border:1px solid rgba(220,53,69,0.2);}
    .btn-icon-del:hover{background:#dc3545;color:white;transform:scale(1.08);}

    /* Empty state */
    .empty-state{text-align:center;padding:80px 40px;}
    .empty-icon{width:72px;height:72px;border-radius:18px;background:rgba(13,31,53,0.05);display:flex;align-items:center;justify-content:center;font-size:32px;color:rgba(13,31,53,0.2);margin:0 auto 18px;}
    .empty-state h3{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);margin-bottom:6px;}
    .empty-state p{font-size:13px;color:#8898aa;}

    /* Result info */
    .result-info{font-size:12px;color:#8898aa;margin-bottom:16px;display:flex;align-items:center;gap:6px;}
    .result-info strong{color:var(--navy);}
    .result-info .search-tag{background:rgba(255,152,0,0.1);color:var(--orange);border-radius:6px;padding:2px 8px;font-weight:700;}

    @media(max-width:768px){
      :root{--sidebar-w:0px;}
      .sidebar{transform:translateX(-100%);}
      .main-wrap{margin-left:0;}
      .page-content{padding:24px 20px 48px;}
      .topbar{padding:16px 20px;}
      .data-table thead th:nth-child(4){display:none;}
      .data-table tbody td:nth-child(4){display:none;}
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
      <div class="topbar-eyebrow">Manajemen Akun</div>
      <div class="topbar-title">Register & Login</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-count">
        <i class="bi bi-people-fill"></i>
        <?php echo count($users); ?> User
      </div>
      <a href="create_user_admin.php" class="btn-add-user">
        <i class="bi bi-person-plus"></i> Buat User
      </a>
    </div>
  </div>

  <!-- Content -->
  <div class="page-content">

    <?php if (!empty($alert_message)): ?>
    <div class="alert-custom <?php echo $alert_type === 'success' ? 'alert-success-c' : 'alert-danger-c'; ?>">
      <i class="bi bi-<?php echo $alert_type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?>"></i>
      <?php echo htmlspecialchars($alert_message); ?>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" action="">
      <div class="search-bar-wrap">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="search" placeholder="Cari nama atau email..."
            value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="btn-search"><i class="bi bi-search"></i> Cari</button>
        <?php if ($search): ?>
        <a href="register_login_admin.php" class="btn-search" style="background:rgba(13,31,53,0.08);color:var(--navy);">
          <i class="bi bi-x"></i> Reset
        </a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($search): ?>
    <div class="result-info">
      <i class="bi bi-info-circle"></i>
      Menampilkan hasil untuk <span class="search-tag">"<?php echo htmlspecialchars($search); ?>"</span>
      — ditemukan <strong><?php echo count($users); ?></strong> user
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-card">
      <?php if (count($users) > 0): ?>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Password</th>
              <th>Dibuat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $idx => $u): ?>
            <tr style="animation:fadeUp 0.4s ease-out <?php echo $idx * 0.04; ?>s both;">
              <td>
                <div class="user-cell">
                  <div class="user-avatar">
                    <?php echo strtoupper(substr($u['first_name'], 0, 1)); ?>
                  </div>
                  <div class="user-fullname">
                    <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                  </div>
                </div>
              </td>
              <td>
                <div class="email-pill">
                  <i class="bi bi-envelope"></i>
                  <?php echo htmlspecialchars($u['email']); ?>
                </div>
              </td>
              <td>
                <div class="pwd-wrap">
                  <span class="pwd-text" id="pwd_<?php echo $u['id']; ?>">
                    <?php echo str_repeat('•', min(16, strlen($u['password']))); ?>
                  </span>
                  <button class="btn-toggle-pwd" onclick="togglePwd(<?php echo $u['id']; ?>, '<?php echo addslashes(htmlspecialchars($u['password'])); ?>')" title="Lihat password">
                    <i class="bi bi-eye" id="eye_<?php echo $u['id']; ?>"></i>
                  </button>
                </div>
              </td>
              <td style="color:#8898aa;font-size:12px;">
                <?php echo isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '—'; ?>
              </td>
              <td>
                <div class="action-btns">
                  <a href="edit_user_admin.php?id=<?php echo $u['id']; ?>" class="btn-icon btn-icon-edit" title="Edit user">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus user <?php echo htmlspecialchars($u['first_name']); ?>?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                    <button type="submit" class="btn-icon btn-icon-del" title="Hapus user">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-people"></i></div>
        <h3><?php echo $search ? 'Tidak Ditemukan' : 'Belum Ada User'; ?></h3>
        <p><?php echo $search ? 'Tidak ada user yang cocok dengan pencarian "' . htmlspecialchars($search) . '".' : 'Belum ada akun user yang terdaftar.'; ?></p>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle password visibility
  const pwdStore = {};
  function togglePwd(id, plain) {
    const span = document.getElementById('pwd_' + id);
    const eye  = document.getElementById('eye_' + id);
    if (!pwdStore[id]) {
      pwdStore[id] = plain;
      span.textContent = plain;
      eye.className = 'bi bi-eye-slash';
    } else {
      delete pwdStore[id];
      span.textContent = '•'.repeat(Math.min(16, plain.length));
      eye.className = 'bi bi-eye';
    }
  }

  // Enter key search
  document.querySelector('input[name="search"]')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') this.form.submit();
  });
</script>
</body>
</html>