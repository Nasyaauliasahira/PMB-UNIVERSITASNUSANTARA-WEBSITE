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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];
    mysqli_query($koneksi, "DELETE FROM hasil_ujian WHERE id = $id");
    header("Location: status_admin.php");
    exit;
}

$search        = $_GET['search']        ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$base_query = "SELECT h.id, h.siswa_id, h.nilai, h.jumlah_benar, h.waktu_submit,
    u.first_name, u.last_name, u.email, p.photo
    FROM hasil_ujian h
    LEFT JOIN users u ON h.siswa_id = u.id
    LEFT JOIN pendaftaran p ON u.email = p.email";

$where = [];
if ($search !== '') {
    $s = mysqli_real_escape_string($koneksi, $search);
    $where[] = "(u.first_name LIKE '%$s%' OR u.last_name LIKE '%$s%' OR u.email LIKE '%$s%')";
}
if ($filter_status === 'lulus')       $where[] = "h.nilai >= 70";
if ($filter_status === 'tidak_lulus') $where[] = "h.nilai < 70";

if ($where) $base_query .= " WHERE " . implode(" AND ", $where);
$base_query .= " ORDER BY h.waktu_submit DESC";

$result     = mysqli_query($koneksi, $base_query);
$hasil_ujian = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Stats
$all_result   = mysqli_query($koneksi, "SELECT nilai FROM hasil_ujian");
$all          = mysqli_fetch_all($all_result, MYSQLI_ASSOC);
$total_peserta= count($all);
$total_lulus  = count(array_filter($all, fn($r) => $r['nilai'] >= 70));
$total_gagal  = $total_peserta - $total_lulus;
$rata_nilai   = $total_peserta ? round(array_sum(array_column($all, 'nilai')) / $total_peserta) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Status Mahasiswa — Admin Panel</title>
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
      --glass-border:rgba(255,255,255,0.12);
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
    .filter-pill{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:50px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;transition:var(--transition);border:1px solid rgba(13,31,53,0.12);}
    .filter-pill-all{background:var(--off-white);color:var(--navy-mid);}
    .filter-pill-all.active,.filter-pill-all:hover{background:var(--navy);color:white;border-color:var(--navy);}
    .filter-pill-lulus{background:rgba(39,174,96,0.08);color:var(--green);}
    .filter-pill-lulus.active,.filter-pill-lulus:hover{background:var(--green);color:white;border-color:var(--green);}
    .filter-pill-gagal{background:rgba(220,53,69,0.08);color:#dc3545;}
    .filter-pill-gagal.active,.filter-pill-gagal:hover{background:#dc3545;color:white;border-color:#dc3545;}

    /* Page content */
    .page-content{padding:32px 36px 60px;}

    /* Stat cards */
    .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:28px;}
    .stat-card{background:white;border-radius:20px;padding:22px 24px;box-shadow:var(--shadow-card);display:flex;align-items:center;gap:16px;transition:var(--transition);animation:fadeUp 0.5s ease-out both;}
    .stat-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(13,31,53,0.14);}
    .stat-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
    .si-orange{background:rgba(255,152,0,0.12);color:var(--orange);}
    .si-green {background:rgba(39,174,96,0.12); color:var(--green-light);}
    .si-red   {background:rgba(220,53,69,0.12);  color:#dc3545;}
    .si-navy  {background:rgba(13,31,53,0.08);   color:var(--navy-mid);}
    .stat-info{}
    .stat-num{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--navy);line-height:1;}
    .stat-lbl{font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#8898aa;margin-top:2px;}

    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

    /* Alert */
    .alert-custom{border-radius:14px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;font-size:13px;font-weight:500;}
    .alert-success-c{background:rgba(39,174,96,0.08);border:1px solid rgba(39,174,96,0.2);color:#1a6e3c;}
    .alert-danger-c {background:rgba(220,53,69,0.08); border:1px solid rgba(220,53,69,0.2); color:#8b1a1a;}
    .alert-custom i{font-size:18px;flex-shrink:0;}

    /* Search */
    .search-bar-wrap{display:flex;gap:12px;align-items:center;margin-bottom:22px;flex-wrap:wrap;}
    .search-box{position:relative;flex:1;min-width:260px;}
    .search-box input{width:100%;padding:12px 16px 12px 42px;border:1px solid rgba(13,31,53,0.15);border-radius:12px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--navy);background:white;transition:var(--transition);}
    .search-box input::placeholder{color:#aab4be;}
    .search-box input:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,152,0,0.12);}
    .search-box i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#aab4be;font-size:16px;pointer-events:none;}
    .btn-search{padding:12px 22px;border-radius:12px;background:var(--navy);color:white;border:none;cursor:pointer;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;transition:var(--transition);display:flex;align-items:center;gap:7px;}
    .btn-search:hover{background:var(--navy-mid);transform:translateY(-1px);}
    .btn-reset{padding:12px 16px;border-radius:12px;background:rgba(13,31,53,0.06);color:var(--navy);border:1px solid rgba(13,31,53,0.12);cursor:pointer;font-size:13px;font-family:'DM Sans',sans-serif;transition:var(--transition);text-decoration:none;display:flex;align-items:center;gap:6px;}
    .btn-reset:hover{background:rgba(13,31,53,0.1);color:var(--navy);}

    /* Result info */
    .result-info{font-size:12px;color:#8898aa;margin-bottom:14px;display:flex;align-items:center;gap:6px;}
    .result-info .search-tag{background:rgba(255,152,0,0.1);color:var(--orange);border-radius:6px;padding:2px 8px;font-weight:700;}

    /* Table card */
    .table-card{background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-card);overflow:hidden;animation:fadeUp 0.5s ease-out 0.1s both;}

    /* Table */
    .data-table{width:100%;border-collapse:collapse;}
    .data-table thead{background:linear-gradient(135deg,var(--navy),var(--navy-mid));}
    .data-table thead th{padding:16px 20px;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.65);border:none;white-space:nowrap;}
    .data-table thead th:first-child{color:var(--orange);}
    .data-table tbody tr{border-bottom:1px solid rgba(13,31,53,0.05);transition:var(--transition);}
    .data-table tbody tr:last-child{border-bottom:none;}
    .data-table tbody tr:hover td{background:rgba(255,152,0,0.03);}
    .data-table tbody td{padding:14px 20px;font-size:13px;color:var(--navy);vertical-align:middle;}

    /* Student cell */
    .student-cell{display:flex;align-items:center;gap:12px;}
    .student-photo{width:42px;height:42px;border-radius:11px;object-fit:cover;border:2px solid rgba(13,31,53,0.08);}
    .student-avatar{width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;color:var(--orange);flex-shrink:0;}
    .student-name{font-weight:600;font-size:14px;color:var(--navy);}
    .student-time{font-size:11px;color:#8898aa;margin-top:2px;}

    /* Email pill */
    .email-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;background:rgba(13,31,53,0.05);border-radius:50px;font-size:12px;color:var(--navy-mid);}
    .email-pill i{font-size:11px;color:var(--orange);}

    /* Score */
    .score-wrap{display:flex;align-items:center;gap:8px;}
    .score-bar{width:60px;height:6px;background:rgba(13,31,53,0.08);border-radius:3px;overflow:hidden;}
    .score-fill-green{background:var(--green-light);}
    .score-fill-red  {background:#dc3545;}
    .score-num{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;}
    .score-passed{color:var(--green);}
    .score-failed{color:#dc3545;}

    /* Status badge */
    .status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:50px;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;}
    .badge-lulus   {background:rgba(39,174,96,0.1); border:1px solid rgba(39,174,96,0.25); color:var(--green);}
    .badge-gagal   {background:rgba(220,53,69,0.1);  border:1px solid rgba(220,53,69,0.2);  color:#dc3545;}
    .badge-dot{width:7px;height:7px;border-radius:50%;animation:pulse 2s infinite;}
    .dot-green{background:var(--green-light);}
    .dot-red  {background:#dc3545;}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.3)}}

    /* Action btn */
    .btn-icon-del{width:36px;height:36px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:var(--transition);background:rgba(220,53,69,0.1);color:#dc3545;border:1px solid rgba(220,53,69,0.2);}
    .btn-icon-del:hover{background:#dc3545;color:white;transform:scale(1.08);}

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
      .topbar{padding:16px 20px;flex-wrap:wrap;gap:12px;}
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
    <a href="status_admin.php" class="sidebar-link active">
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
      <div class="topbar-eyebrow">Laporan Ujian</div>
      <div class="topbar-title">Status Mahasiswa</div>
    </div>
    <div class="topbar-right">
      <a href="status_admin.php" class="filter-pill filter-pill-all <?php echo !$filter_status ? 'active' : ''; ?>">
        Semua
      </a>
      <a href="status_admin.php?filter_status=lulus<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-pill filter-pill-lulus <?php echo $filter_status === 'lulus' ? 'active' : ''; ?>">
        <i class="bi bi-check-circle"></i> Lulus
      </a>
      <a href="status_admin.php?filter_status=tidak_lulus<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-pill filter-pill-gagal <?php echo $filter_status === 'tidak_lulus' ? 'active' : ''; ?>">
        <i class="bi bi-x-circle"></i> Belum Lulus
      </a>
    </div>
  </div>

  <!-- Content -->
  <div class="page-content">

    <!-- Stat Cards -->
    <div class="stats-row">
      <div class="stat-card" style="animation-delay:0s">
        <div class="stat-icon si-orange"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info">
          <div class="stat-num"><?php echo $total_peserta; ?></div>
          <div class="stat-lbl">Total Peserta</div>
        </div>
      </div>
      <div class="stat-card" style="animation-delay:0.08s">
        <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-info">
          <div class="stat-num"><?php echo $total_lulus; ?></div>
          <div class="stat-lbl">Lulus</div>
        </div>
      </div>
      <div class="stat-card" style="animation-delay:0.16s">
        <div class="stat-icon si-red"><i class="bi bi-x-circle-fill"></i></div>
        <div class="stat-info">
          <div class="stat-num"><?php echo $total_gagal; ?></div>
          <div class="stat-lbl">Belum Lulus</div>
        </div>
      </div>
      <div class="stat-card" style="animation-delay:0.24s">
        <div class="stat-icon si-navy"><i class="bi bi-bar-chart-fill"></i></div>
        <div class="stat-info">
          <div class="stat-num"><?php echo $rata_nilai; ?></div>
          <div class="stat-lbl">Rata-rata Nilai</div>
        </div>
      </div>
    </div>

    <?php if (!empty($alert_message)): ?>
    <div class="alert-custom <?php echo $alert_type === 'success' ? 'alert-success-c' : 'alert-danger-c'; ?>">
      <i class="bi bi-<?php echo $alert_type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?>"></i>
      <?php echo htmlspecialchars($alert_message); ?>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" action="">
      <?php if ($filter_status): ?><input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($filter_status); ?>"><?php endif; ?>
      <div class="search-bar-wrap">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="search" placeholder="Cari nama atau email peserta..."
            value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="btn-search"><i class="bi bi-search"></i> Cari</button>
        <?php if ($search): ?>
        <a href="status_admin.php<?php echo $filter_status ? '?filter_status=' . $filter_status : ''; ?>" class="btn-reset">
          <i class="bi bi-x"></i> Reset
        </a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($search || $filter_status): ?>
    <div class="result-info">
      <i class="bi bi-info-circle"></i>
      <?php if ($search): ?>Hasil untuk <span class="search-tag">"<?php echo htmlspecialchars($search); ?>"</span> —<?php endif; ?>
      <strong><?php echo count($hasil_ujian); ?></strong> peserta ditemukan
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-card">
      <?php if (count($hasil_ujian) > 0): ?>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Peserta</th>
              <th>Email</th>
              <th>Nilai</th>
              <th>Status</th>
              <th>Waktu Submit</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hasil_ujian as $idx => $d):
              $is_lulus = $d['nilai'] >= 70;
              $nama = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
              $inisial = strtoupper(substr($d['first_name'] ?? 'U', 0, 1));
            ?>
            <tr style="animation:fadeUp 0.4s ease-out <?php echo $idx * 0.04; ?>s both;">
              <td>
                <div class="student-cell">
                  <?php if (!empty($d['photo'])): ?>
                    <img src="uploads/foto/<?php echo htmlspecialchars($d['photo']); ?>" class="student-photo" alt="">
                  <?php else: ?>
                    <div class="student-avatar"><?php echo $inisial; ?></div>
                  <?php endif; ?>
                  <div>
                    <div class="student-name"><?php echo htmlspecialchars($nama ?: 'N/A'); ?></div>
                    <div class="student-time">ID: <?php echo $d['siswa_id']; ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div class="email-pill"><i class="bi bi-envelope"></i><?php echo htmlspecialchars($d['email'] ?? 'N/A'); ?></div>
              </td>
              <td>
                <div class="score-wrap">
                  <span class="score-num <?php echo $is_lulus ? 'score-passed' : 'score-failed'; ?>"><?php echo $d['nilai']; ?></span>
                  <div>
                    <div class="score-bar"><div class="<?php echo $is_lulus ? 'score-fill-green' : 'score-fill-red'; ?>" style="height:100%;width:<?php echo $d['nilai']; ?>%;border-radius:3px;"></div></div>
                    <div style="font-size:10px;color:#aab4be;margin-top:2px;"><?php echo $d['jumlah_benar']; ?> benar</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="status-badge <?php echo $is_lulus ? 'badge-lulus' : 'badge-gagal'; ?>">
                  <span class="badge-dot <?php echo $is_lulus ? 'dot-green' : 'dot-red'; ?>"></span>
                  <?php echo $is_lulus ? 'Lulus' : 'Belum Lulus'; ?>
                </span>
              </td>
              <td style="font-size:12px;color:#8898aa;">
                <?php echo $d['waktu_submit'] ? date('d M Y, H:i', strtotime($d['waktu_submit'])) : '—'; ?>
              </td>
              <td>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus data ujian <?php echo htmlspecialchars($nama); ?>?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                  <button type="submit" class="btn-icon-del" title="Hapus"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
        <h3>Tidak Ada Data</h3>
        <p><?php echo ($search || $filter_status) ? 'Tidak ada peserta yang cocok dengan filter yang dipilih.' : 'Belum ada data hasil ujian peserta.'; ?></p>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelector('input[name="search"]')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') this.form.submit();
  });
</script>
</body>
</html>