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

$search        = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? 'all';

$search_q = '';
if ($search !== '') {
    $s = mysqli_real_escape_string($koneksi, $search);
    $search_q = " AND (u.first_name LIKE '%$s%' OR u.last_name LIKE '%$s%' OR u.email LIKE '%$s%' OR p.jurusan LIKE '%$s%')";
}
$status_q = '';
if ($filter_status === 'sudah') $status_q = " AND du.id IS NOT NULL";
if ($filter_status === 'belum') $status_q = " AND du.id IS NULL";

$query = "SELECT u.id as user_id, u.first_name, u.last_name, u.email,
    h.nilai, h.waktu_submit,
    p.jurusan, p.school_level, p.phone, p.photo,
    du.id as daftar_ulang_id, du.nim, du.nomor_orang_terdekat, du.ktp_photo, du.created_at as tanggal_daftar_ulang
    FROM hasil_ujian h
    INNER JOIN users u ON h.siswa_id = u.id
    LEFT JOIN pendaftaran p ON u.email = p.email
    LEFT JOIN daftar_ulang du ON u.id = du.user_id
    WHERE h.nilai >= 70
    $search_q $status_q
    ORDER BY CASE WHEN du.id IS NULL THEN 0 ELSE 1 END, h.waktu_submit DESC";

$result    = mysqli_query($koneksi, $query);
if (!$result) die("Query Error: " . mysqli_error($koneksi));
$mahasiswa = mysqli_fetch_all($result, MYSQLI_ASSOC);

$total_lulus         = count($mahasiswa);
$sudah_daftar_ulang  = count(array_filter($mahasiswa, fn($m) => $m['daftar_ulang_id']));
$belum_daftar_ulang  = $total_lulus - $sudah_daftar_ulang;
$pct_done            = $total_lulus ? round($sudah_daftar_ulang / $total_lulus * 100) : 0;

$jurusan_map = [
    'business'    => 'Manajemen Bisnis',
    'it_software' => 'Teknik Informatika',
    'design'      => 'Desain Komunikasi Visual'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Ulang — Admin Panel</title>
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
    .topbar-right{display:flex;align-items:center;gap:10px;}
    .filter-pill{display:inline-flex;align-items:center;gap:6px;padding:7px 15px;border-radius:50px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;transition:var(--transition);border:1px solid rgba(13,31,53,0.12);}
    .fp-all{background:var(--off-white);color:var(--navy-mid);}
    .fp-all.active,.fp-all:hover{background:var(--navy);color:white;border-color:var(--navy);}
    .fp-sudah{background:rgba(39,174,96,0.08);color:var(--green);}
    .fp-sudah.active,.fp-sudah:hover{background:var(--green);color:white;border-color:var(--green);}
    .fp-belum{background:rgba(220,53,69,0.08);color:#dc3545;}
    .fp-belum.active,.fp-belum:hover{background:#dc3545;color:white;border-color:#dc3545;}

    /* Page content */
    .page-content{padding:32px 36px 60px;}

    /* Stat cards */
    .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:28px;}
    .stat-card{background:white;border-radius:20px;padding:22px 24px;box-shadow:var(--shadow-card);display:flex;align-items:center;gap:16px;transition:var(--transition);animation:fadeUp 0.5s ease-out both;}
    .stat-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(13,31,53,0.14);}
    .stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
    .si-orange{background:rgba(255,152,0,0.12);color:var(--orange);}
    .si-green {background:rgba(39,174,96,0.12); color:var(--green-light);}
    .si-red   {background:rgba(220,53,69,0.12);  color:#dc3545;}
    .si-navy  {background:rgba(13,31,53,0.08);   color:var(--navy-mid);}
    .stat-num{font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:700;color:var(--navy);line-height:1;}
    .stat-lbl{font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#8898aa;margin-top:2px;}

    /* Progress bar in stat */
    .stat-prog{margin-top:10px;width:100%;}
    .stat-prog-track{height:5px;background:rgba(13,31,53,0.08);border-radius:3px;overflow:hidden;}
    .stat-prog-fill{height:100%;background:linear-gradient(90deg,var(--green),var(--green-light));border-radius:3px;transition:width 1s ease;}
    .stat-prog-label{font-size:10px;color:#8898aa;margin-top:4px;}

    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

    /* Alert */
    .alert-custom{border-radius:14px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;font-size:13px;font-weight:500;}
    .alert-success-c{background:rgba(39,174,96,0.08);border:1px solid rgba(39,174,96,0.2);color:#1a6e3c;}
    .alert-danger-c {background:rgba(220,53,69,0.08); border:1px solid rgba(220,53,69,0.2); color:#8b1a1a;}
    .alert-custom i{font-size:18px;flex-shrink:0;}

    /* Filter bar */
    .filter-wrap{background:white;border-radius:18px;box-shadow:var(--shadow-card);padding:20px 24px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;animation:fadeUp 0.5s ease-out 0.1s both;}
    .filter-group{display:flex;flex-direction:column;gap:6px;flex:1;min-width:200px;}
    .filter-label{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--navy-mid);}
    .filter-input{padding:11px 16px;border:1px solid rgba(13,31,53,0.15);border-radius:12px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--navy);background:white;transition:var(--transition);}
    .filter-input::placeholder{color:#aab4be;}
    .filter-input:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,152,0,0.12);}
    .btn-filter{padding:11px 22px;border-radius:12px;border:none;cursor:pointer;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;transition:var(--transition);display:flex;align-items:center;gap:7px;}
    .btn-filter-primary{background:var(--navy);color:white;}
    .btn-filter-primary:hover{background:var(--navy-mid);transform:translateY(-1px);}
    .btn-filter-reset{background:rgba(13,31,53,0.06);color:var(--navy);border:1px solid rgba(13,31,53,0.12);text-decoration:none;}
    .btn-filter-reset:hover{background:rgba(13,31,53,0.1);color:var(--navy);}

    /* Result info */
    .result-info{font-size:12px;color:#8898aa;margin-bottom:14px;display:flex;align-items:center;gap:6px;}
    .result-info .tag{background:rgba(255,152,0,0.1);color:var(--orange);border-radius:6px;padding:2px 8px;font-weight:700;}

    /* Table card */
    .table-card{background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-card);overflow:hidden;animation:fadeUp 0.5s ease-out 0.15s both;}

    /* Table */
    .data-table{width:100%;border-collapse:collapse;}
    .data-table thead{background:linear-gradient(135deg,var(--navy),var(--navy-mid));}
    .data-table thead th{padding:15px 18px;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.65);border:none;white-space:nowrap;}
    .data-table thead th:first-child{color:var(--orange);}
    .data-table tbody tr{border-bottom:1px solid rgba(13,31,53,0.05);transition:var(--transition);}
    .data-table tbody tr:last-child{border-bottom:none;}
    .data-table tbody tr:hover td{background:rgba(255,152,0,0.03);}
    .data-table tbody td{padding:14px 18px;font-size:13px;color:var(--navy);vertical-align:middle;}

    /* Student cell */
    .student-cell{display:flex;align-items:center;gap:12px;}
    .student-photo{width:44px;height:44px;border-radius:11px;object-fit:cover;border:2px solid rgba(13,31,53,0.08);}
    .student-avatar{width:44px;height:44px;border-radius:11px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;color:var(--orange);flex-shrink:0;}
    .student-name{font-weight:600;font-size:14px;color:var(--navy);}
    .student-email{font-size:11px;color:#8898aa;margin-top:1px;}

    /* NIM */
    .nim-chip{font-family:'Courier New',monospace;font-size:12px;font-weight:700;color:var(--navy-mid);background:rgba(13,31,53,0.06);padding:5px 12px;border-radius:8px;display:inline-block;letter-spacing:0.5px;}
    .nim-empty{color:#aab4be;font-size:12px;font-style:italic;}

    /* Jurusan */
    .jurusan-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:rgba(13,31,53,0.06);border-radius:50px;font-size:12px;color:var(--navy-mid);font-weight:500;}

    /* Nilai */
    .nilai-wrap{display:flex;align-items:center;gap:8px;}
    .nilai-num{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--green);}
    .nilai-bar{width:50px;height:5px;background:rgba(13,31,53,0.08);border-radius:3px;overflow:hidden;}
    .nilai-fill{height:100%;background:var(--green-light);border-radius:3px;}

    /* Status badge */
    .status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:50px;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;}
    .badge-sudah{background:rgba(39,174,96,0.1);border:1px solid rgba(39,174,96,0.25);color:var(--green);}
    .badge-belum{background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.2);color:#dc3545;}
    .badge-dot{width:7px;height:7px;border-radius:50%;animation:pulse 2s infinite;}
    .dot-green{background:var(--green-light);}
    .dot-red{background:#dc3545;}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.3)}}

    /* KTP button */
    .btn-ktp{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:var(--transition);background:rgba(13,31,53,0.06);color:var(--navy-mid);border:1px solid rgba(13,31,53,0.1);}
    .btn-ktp:hover{background:var(--navy);color:white;}

    /* Empty */
    .empty-state{text-align:center;padding:80px 40px;}
    .empty-icon{width:72px;height:72px;border-radius:18px;background:rgba(13,31,53,0.05);display:flex;align-items:center;justify-content:center;font-size:32px;color:rgba(13,31,53,0.2);margin:0 auto 18px;}
    .empty-state h3{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--navy);margin-bottom:6px;}
    .empty-state p{font-size:13px;color:#8898aa;}

    @media(max-width:1100px){.stats-row{grid-template-columns:1fr 1fr;}}
    @media(max-width:768px){
      :root{--sidebar-w:0px;}
      .sidebar{transform:translateX(-100%);}
      .main-wrap{margin-left:0;}
      .page-content{padding:24px 20px 48px;}
      .topbar{padding:16px 20px;flex-wrap:wrap;gap:10px;}
      .stats-row{grid-template-columns:1fr 1fr;}
    }
    @media(max-width:480px){.stats-row{grid-template-columns:1fr;}}
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
    <a href="register_login_admin.php" class="sidebar-link">
      <span class="link-icon"><i class="bi bi-person-check"></i></span> Register & Login
    </a>
    <a href="status_admin.php" class="sidebar-link">
      <span class="link-icon"><i class="bi bi-card-checklist"></i></span> Status
    </a>
    <a href="daftar_ulang_admin.php" class="sidebar-link active">
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
      <div class="topbar-eyebrow">Administrasi Mahasiswa</div>
      <div class="topbar-title">Daftar Ulang</div>
    </div>
    <div class="topbar-right">
      <?php $qs = $search ? '&search=' . urlencode($search) : ''; ?>
      <a href="daftar_ulang_admin.php" class="filter-pill fp-all <?php echo $filter_status === 'all' ? 'active' : ''; ?>">Semua</a>
      <a href="daftar_ulang_admin.php?status=sudah<?php echo $qs; ?>" class="filter-pill fp-sudah <?php echo $filter_status === 'sudah' ? 'active' : ''; ?>">
        <i class="bi bi-check-circle"></i> Sudah
      </a>
      <a href="daftar_ulang_admin.php?status=belum<?php echo $qs; ?>" class="filter-pill fp-belum <?php echo $filter_status === 'belum' ? 'active' : ''; ?>">
        <i class="bi bi-clock"></i> Belum
      </a>
    </div>
  </div>

  <!-- Content -->
  <div class="page-content">

    <!-- Stat cards -->
    <div class="stats-row">
      <div class="stat-card" style="animation-delay:0s">
        <div class="stat-icon si-orange"><i class="bi bi-people-fill"></i></div>
        <div style="flex:1">
          <div class="stat-num"><?php echo $total_lulus; ?></div>
          <div class="stat-lbl">Total Lulus</div>
        </div>
      </div>
      <div class="stat-card" style="animation-delay:0.08s">
        <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
        <div style="flex:1">
          <div class="stat-num"><?php echo $sudah_daftar_ulang; ?></div>
          <div class="stat-lbl">Sudah Daftar Ulang</div>
        </div>
      </div>
      <div class="stat-card" style="animation-delay:0.16s">
        <div class="stat-icon si-red"><i class="bi bi-hourglass-split"></i></div>
        <div style="flex:1">
          <div class="stat-num"><?php echo $belum_daftar_ulang; ?></div>
          <div class="stat-lbl">Belum Daftar Ulang</div>
        </div>
      </div>
      <div class="stat-card" style="animation-delay:0.24s;flex-direction:column;align-items:flex-start;">
        <div style="display:flex;align-items:center;gap:14px;width:100%">
          <div class="stat-icon si-navy"><i class="bi bi-graph-up"></i></div>
          <div>
            <div class="stat-num"><?php echo $pct_done; ?>%</div>
            <div class="stat-lbl">Progress</div>
          </div>
        </div>
        <div class="stat-prog" style="margin-top:12px;">
          <div class="stat-prog-track">
            <div class="stat-prog-fill" style="width:<?php echo $pct_done; ?>%"></div>
          </div>
          <div class="stat-prog-label"><?php echo $sudah_daftar_ulang; ?> dari <?php echo $total_lulus; ?> sudah mendaftar ulang</div>
        </div>
      </div>
    </div>

    <?php if (!empty($alert_message)): ?>
    <div class="alert-custom <?php echo $alert_type === 'success' ? 'alert-success-c' : 'alert-danger-c'; ?>">
      <i class="bi bi-<?php echo $alert_type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?>"></i>
      <?php echo htmlspecialchars($alert_message); ?>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <form method="GET" action="">
      <?php if ($filter_status && $filter_status !== 'all'): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>"><?php endif; ?>
      <div class="filter-wrap">
        <div class="filter-group">
          <label class="filter-label">Cari Mahasiswa</label>
          <input type="text" name="search" class="filter-input" placeholder="Nama, email, atau jurusan..."
            value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-group" style="max-width:220px;">
          <label class="filter-label">Status</label>
          <select name="status" class="filter-input">
            <option value="all"  <?php echo $filter_status === 'all'   ? 'selected' : ''; ?>>Semua Status</option>
            <option value="sudah"<?php echo $filter_status === 'sudah' ? 'selected' : ''; ?>>Sudah Daftar Ulang</option>
            <option value="belum"<?php echo $filter_status === 'belum' ? 'selected' : ''; ?>>Belum Daftar Ulang</option>
          </select>
        </div>
        <button type="submit" class="btn-filter btn-filter-primary"><i class="bi bi-search"></i> Cari</button>
        <?php if ($search || $filter_status !== 'all'): ?>
        <a href="daftar_ulang_admin.php" class="btn-filter btn-filter-reset"><i class="bi bi-x"></i> Reset</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($search || $filter_status !== 'all'): ?>
    <div class="result-info">
      <i class="bi bi-info-circle"></i>
      <?php if ($search): ?>Hasil untuk <span class="tag">"<?php echo htmlspecialchars($search); ?>"</span> —<?php endif; ?>
      <strong><?php echo count($mahasiswa); ?></strong> mahasiswa ditemukan
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-card">
      <?php if (count($mahasiswa) > 0): ?>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Mahasiswa</th>
              <th>NIM</th>
              <th>Jurusan</th>
              <th>Nilai</th>
              <th>KTP</th>
              <th>No. Terdekat</th>
              <th>Status</th>
              <th>Tgl. Daftar Ulang</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($mahasiswa as $idx => $m):
              $is_daftar  = !empty($m['daftar_ulang_id']);
              $nama       = trim($m['first_name'] . ' ' . $m['last_name']);
              $inisial    = strtoupper(substr($m['first_name'] ?? 'U', 0, 1));
              $jurusan    = $jurusan_map[$m['jurusan']] ?? $m['jurusan'];
              $photo_path = !empty($m['photo']) ? 'uploads/foto/' . $m['photo'] : '';
            ?>
            <tr style="animation:fadeUp 0.4s ease-out <?php echo $idx * 0.04; ?>s both;">
              <td style="color:#aab4be;font-size:12px;font-weight:700;"><?php echo $idx + 1; ?></td>
              <td>
                <div class="student-cell">
                  <?php if ($photo_path): ?>
                    <img src="<?php echo htmlspecialchars($photo_path); ?>" class="student-photo" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="student-avatar" style="display:none;"><?php echo $inisial; ?></div>
                  <?php else: ?>
                    <div class="student-avatar"><?php echo $inisial; ?></div>
                  <?php endif; ?>
                  <div>
                    <div class="student-name"><?php echo htmlspecialchars($nama); ?></div>
                    <div class="student-email"><?php echo htmlspecialchars($m['email']); ?></div>
                  </div>
                </div>
              </td>
              <td>
                <?php if ($m['nim']): ?>
                  <span class="nim-chip"><?php echo htmlspecialchars($m['nim']); ?></span>
                <?php else: ?>
                  <span class="nim-empty">Belum tersedia</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="jurusan-badge"><i class="bi bi-building"></i><?php echo htmlspecialchars($jurusan ?: '—'); ?></span>
              </td>
              <td>
                <div class="nilai-wrap">
                  <span class="nilai-num"><?php echo $m['nilai']; ?></span>
                  <div>
                    <div class="nilai-bar"><div class="nilai-fill" style="width:<?php echo $m['nilai']; ?>%"></div></div>
                    <div style="font-size:10px;color:#aab4be;margin-top:2px;">/100</div>
                  </div>
                </div>
              </td>
              <td>
                <?php if (!empty($m['ktp_photo'])): ?>
                  <a href="uploads/daftar_ulang/ktp/<?php echo htmlspecialchars($m['ktp_photo']); ?>" target="_blank" class="btn-ktp">
                    <i class="bi bi-card-image"></i> Lihat
                  </a>
                <?php else: ?>
                  <span style="color:#aab4be;font-size:12px;">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:13px;color:var(--navy-mid);">
                <?php echo $m['nomor_orang_terdekat'] ? htmlspecialchars($m['nomor_orang_terdekat']) : '<span style="color:#aab4be">—</span>'; ?>
              </td>
              <td>
                <span class="status-badge <?php echo $is_daftar ? 'badge-sudah' : 'badge-belum'; ?>">
                  <span class="badge-dot <?php echo $is_daftar ? 'dot-green' : 'dot-red'; ?>"></span>
                  <?php echo $is_daftar ? 'Sudah' : 'Belum'; ?>
                </span>
              </td>
              <td style="font-size:12px;color:#8898aa;">
                <?php echo $m['tanggal_daftar_ulang'] ? date('d M Y, H:i', strtotime($m['tanggal_daftar_ulang'])) : '<span style="color:#aab4be">—</span>'; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-person-x"></i></div>
        <h3>Tidak Ada Data</h3>
        <p><?php echo ($search || $filter_status !== 'all') ? 'Tidak ada mahasiswa yang cocok dengan filter yang dipilih.' : 'Belum ada mahasiswa lulus yang tercatat.'; ?></p>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelector('input[name="search"]')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') e.target.form.submit();
  });
</script>
</body>
</html>