<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

$total_users        = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users"))['total'];
$total_pendaftar    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pendaftaran"))['total'];
$total_gagal        = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM hasil_ujian WHERE nilai < 60"))['total'];
$total_lulus        = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM hasil_ujian WHERE nilai >= 60"))['total'];
$total_soal         = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM soal"))['total'];
$total_daftar_ulang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM daftar_ulang"))['total'];

$admin_name  = $_SESSION['admin_name'];
$total_ujian = $total_lulus + $total_gagal;
$pass_rate   = $total_ujian > 0 ? round($total_lulus / $total_ujian * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — Universitas Nusantara</title>
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
      --shadow-deep: 0 24px 64px rgba(13,31,53,0.32);
      --shadow-card: 0 8px 32px rgba(13,31,53,0.11);
      --radius-lg:   20px;
      --radius-xl:   28px;
      --transition:  all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    }
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    html{scroll-behavior:smooth;}
    body{font-family:'DM Sans',sans-serif;background:var(--off-white);color:var(--navy);overflow-x:hidden;}

    /* ══════════════════════════════════════════
       SIDEBAR — identik dengan register_login_admin.php
    ══════════════════════════════════════════ */
    .sidebar{width:var(--sidebar-w);height:100vh;background:linear-gradient(175deg,var(--navy) 0%,#0a1828 100%);position:fixed;left:0;top:0;z-index:200;display:flex;flex-direction:column;border-right:1px solid rgba(255,255,255,0.06);overflow:hidden;}
    .sidebar::before{content:'';position:absolute;top:-100px;right:-80px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(255,152,0,0.08) 0%,transparent 70%);pointer-events:none;}
    .sidebar-brand{padding:28px 24px 24px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,0.06);flex-shrink:0;text-decoration:none;}
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

    /* ══════════════════════════════════════════
       MAIN
    ══════════════════════════════════════════ */
    .main-wrap{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;}

    .topbar{background:white;border-bottom:1px solid rgba(13,31,53,0.07);padding:18px 36px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(13,31,53,0.06);}
    .topbar-left{display:flex;flex-direction:column;gap:1px;}
    .topbar-eyebrow{font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--orange);}
    .topbar-title{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--navy);line-height:1;}
    .topbar-date{font-size:12px;color:#8898aa;margin-top:2px;}
    .topbar-badge{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:50px;background:rgba(39,174,96,0.08);border:1px solid rgba(39,174,96,0.2);font-size:12px;font-weight:700;color:var(--green);}
    .topbar-badge .pulse{width:8px;height:8px;border-radius:50%;background:var(--green-light);animation:pulse 2s infinite;}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(1.4)}}

    .page-content{padding:32px 36px 60px;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}

    /* Stat cards */
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;}
    .stat-card{border-radius:var(--radius-lg);padding:28px 24px;position:relative;overflow:hidden;transition:var(--transition);box-shadow:var(--shadow-card);animation:fadeUp 0.5s ease-out both;}
    .stat-card:hover{transform:translateY(-7px);box-shadow:var(--shadow-deep);}
    .sc-dark  {background:linear-gradient(140deg,var(--navy) 0%,var(--navy-mid) 100%);color:white;}
    .sc-orange{background:linear-gradient(140deg,var(--orange) 0%,var(--orange-dark) 100%);color:white;}
    .sc-teal  {background:linear-gradient(140deg,#1a4d5c 0%,#0d2d36 100%);color:white;}
    .sc-green {background:linear-gradient(140deg,var(--green) 0%,#0f4022 100%);color:white;}
    .stat-card::after{content:'';position:absolute;top:-50px;right:-50px;width:160px;height:160px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,0.07) 0%,transparent 70%);pointer-events:none;}
    .stat-card::before{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(255,255,255,0.2);}
    .sc-dark::before{background:linear-gradient(90deg,var(--orange),var(--gold));}
    .sc-green::before{background:linear-gradient(90deg,var(--green-light),#a8e6c1);}
    .stat-icon{width:48px;height:48px;border-radius:13px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:18px;position:relative;z-index:1;}
    .stat-num{font-family:'Cormorant Garamond',serif;font-size:56px;font-weight:700;line-height:1;margin-bottom:6px;position:relative;z-index:1;}
    .stat-lbl{font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;opacity:.75;position:relative;z-index:1;}
    .stat-sub{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;margin-top:12px;padding:4px 11px;border-radius:50px;background:rgba(255,255,255,0.14);position:relative;z-index:1;}
    .pass-rate-wrap{display:flex;align-items:flex-end;gap:12px;margin-bottom:6px;position:relative;z-index:1;}
    .pass-rate-num{font-family:'Cormorant Garamond',serif;font-size:56px;font-weight:700;line-height:1;}
    .pass-rate-pct{font-size:20px;font-weight:700;margin-bottom:8px;opacity:.8;}
    .pass-bar-track{height:5px;background:rgba(255,255,255,0.2);border-radius:3px;overflow:hidden;position:relative;z-index:1;}
    .pass-bar-fill{height:100%;border-radius:3px;background:rgba(255,255,255,0.7);}

    /* Section heading */
    .section-head{margin-bottom:20px;}
    .section-eyebrow{font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:8px;display:flex;align-items:center;gap:10px;}
    .section-eyebrow::before{content:'';width:28px;height:2px;background:var(--orange);}
    .section-title{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:28px;color:var(--navy);}

    /* Quick cards */
    .quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
    .quick-card{background:white;border-radius:var(--radius-lg);padding:26px 22px;border:1px solid rgba(13,31,53,0.06);box-shadow:var(--shadow-card);text-decoration:none;color:inherit;display:flex;align-items:center;gap:16px;position:relative;overflow:hidden;transition:var(--transition);animation:fadeUp 0.5s ease-out both;}
    .quick-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--navy-mid),var(--orange));transform:scaleX(0);transform-origin:left;transition:transform 0.4s ease;}
    .quick-card:hover::before{transform:scaleX(1);}
    .quick-card:hover{background:var(--navy);border-color:var(--navy);transform:translateY(-5px);box-shadow:var(--shadow-deep);text-decoration:none;color:white;}
    .quick-card:hover .q-icon{background:rgba(255,152,0,0.15);color:var(--orange);}
    .quick-card:hover .q-title{color:white;}
    .quick-card:hover .q-desc{color:rgba(255,255,255,0.5);}
    .quick-card:hover .q-arrow{color:var(--orange);}
    .quick-card:hover .q-badge{background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.7);}
    .q-icon{width:52px;height:52px;border-radius:14px;background:rgba(13,31,53,0.06);display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--navy-mid);flex-shrink:0;transition:var(--transition);}
    .q-info{flex:1;min-width:0;}
    .q-title{font-weight:700;font-size:14px;color:var(--navy);margin-bottom:3px;transition:var(--transition);}
    .q-desc{font-size:12px;color:rgba(13,31,53,0.45);line-height:1.5;transition:var(--transition);}
    .q-badge{font-size:11px;font-weight:700;padding:3px 10px;border-radius:50px;background:rgba(13,31,53,0.05);color:var(--navy-mid);margin-top:7px;display:inline-block;transition:var(--transition);}
    .q-arrow{font-size:16px;color:rgba(13,31,53,0.2);flex-shrink:0;transition:var(--transition);}

    @media(max-width:1200px){.stats-grid{grid-template-columns:1fr 1fr;}.quick-grid{grid-template-columns:1fr 1fr;}}
    @media(max-width:768px){
      :root{--sidebar-w:0px;}
      .sidebar{transform:translateX(-100%);}
      .main-wrap{margin-left:0;}
      .page-content{padding:24px 20px 48px;}
      .topbar{padding:16px 20px;}
      .stats-grid{grid-template-columns:1fr 1fr;gap:14px;}
      .quick-grid{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <a href="dahboardadmin.php" class="sidebar-brand">
    <img src="LOGORBG.png" alt="Logo">
    <div class="sidebar-brand-text">
      <h3>Universitas<br>Nusantara</h3>
      <span>Admin Panel</span>
    </div>
  </a>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Menu Utama</div>
    <a href="dahboardadmin.php" class="sidebar-link active">
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

<!-- ══ MAIN ══ -->
<div class="main-wrap">

  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-eyebrow">Admin Panel</div>
      <div class="topbar-title">Dashboard</div>
      <div class="topbar-date"><?php echo date('l, d F Y'); ?></div>
    </div>
    <div class="topbar-badge">
      <span class="pulse"></span> Sistem Aktif
    </div>
  </div>

  <div class="page-content">

    <!-- Baris 1 stat -->
    <div class="stats-grid">
      <div class="stat-card sc-dark" style="animation-delay:0s">
        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        <div class="stat-num"><?php echo number_format($total_users); ?></div>
        <div class="stat-lbl">Total Pengguna</div>
        <div class="stat-sub"><i class="bi bi-person-fill"></i> Akun terdaftar</div>
      </div>
      <div class="stat-card sc-orange" style="animation-delay:0.08s">
        <div class="stat-icon"><i class="bi bi-clipboard-data-fill"></i></div>
        <div class="stat-num"><?php echo number_format($total_pendaftar); ?></div>
        <div class="stat-lbl">Total Pendaftar</div>
        <div class="stat-sub"><i class="bi bi-arrow-up-right"></i> Formulir masuk</div>
      </div>
      <div class="stat-card sc-teal" style="animation-delay:0.16s">
        <div class="stat-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div class="stat-num"><?php echo number_format($total_soal); ?></div>
        <div class="stat-lbl">Bank Soal</div>
        <div class="stat-sub"><i class="bi bi-collection"></i> Soal tersedia</div>
      </div>
      <div class="stat-card sc-green" style="animation-delay:0.24s">
        <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
        <div class="pass-rate-wrap">
          <div class="pass-rate-num"><?php echo $pass_rate; ?></div>
          <div class="pass-rate-pct">%</div>
        </div>
        <div class="stat-lbl">Tingkat Kelulusan</div>
        <div class="pass-bar-track" style="margin-top:6px;">
          <div class="pass-bar-fill" style="width:<?php echo $pass_rate; ?>%"></div>
        </div>
        <div class="stat-sub" style="margin-top:10px;"><i class="bi bi-trophy"></i> <?php echo $total_lulus; ?> / <?php echo $total_ujian; ?> lulus</div>
      </div>
    </div>

    <!-- Baris 2 stat -->
    <div class="stats-grid" style="margin-bottom:40px;">
      <div class="stat-card sc-orange" style="animation-delay:0.32s">
        <div class="stat-icon"><i class="bi bi-trophy-fill"></i></div>
        <div class="stat-num"><?php echo number_format($total_lulus); ?></div>
        <div class="stat-lbl">Peserta Lulus</div>
        <div class="stat-sub"><i class="bi bi-check-circle"></i> Nilai ≥ 60</div>
      </div>
      <div class="stat-card sc-dark" style="animation-delay:0.40s">
        <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="stat-num"><?php echo number_format($total_gagal); ?></div>
        <div class="stat-lbl">Tidak Lulus</div>
        <div class="stat-sub"><i class="bi bi-dash-circle"></i> Nilai &lt; 60</div>
      </div>
      <div class="stat-card sc-green" style="animation-delay:0.48s">
        <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
        <div class="stat-num"><?php echo number_format($total_daftar_ulang); ?></div>
        <div class="stat-lbl">Daftar Ulang</div>
        <div class="stat-sub"><i class="bi bi-check2-all"></i> Sudah daftar ulang</div>
      </div>
      <div class="stat-card sc-teal" style="animation-delay:0.56s">
        <div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div>
        <div class="stat-num"><?php echo number_format($total_ujian); ?></div>
        <div class="stat-lbl">Total Peserta Ujian</div>
        <div class="stat-sub"><i class="bi bi-pencil-square"></i> Sudah ujian</div>
      </div>
    </div>

    <!-- Quick access -->
    <div class="section-head">
      <div class="section-eyebrow">Navigasi Cepat</div>
      <div class="section-title">Kelola Data</div>
    </div>

    <div class="quick-grid">
      <a href="pendaftaran_admin.php" class="quick-card" style="animation-delay:0.10s">
        <div class="q-icon"><i class="bi bi-clipboard-check"></i></div>
        <div class="q-info">
          <div class="q-title">Pendaftaran</div>
          <div class="q-desc">Kelola data formulir pendaftaran mahasiswa baru</div>
          <div class="q-badge"><?php echo $total_pendaftar; ?> pendaftar</div>
        </div>
        <i class="bi bi-chevron-right q-arrow"></i>
      </a>
      <a href="bank_soal_admin.php" class="quick-card" style="animation-delay:0.18s">
        <div class="q-icon"><i class="bi bi-journal-bookmark"></i></div>
        <div class="q-info">
          <div class="q-title">Bank Soal</div>
          <div class="q-desc">Tambah dan kelola soal ujian masuk</div>
          <div class="q-badge"><?php echo $total_soal; ?> soal tersedia</div>
        </div>
        <i class="bi bi-chevron-right q-arrow"></i>
      </a>
      <a href="register_login_admin.php" class="quick-card" style="animation-delay:0.26s">
        <div class="q-icon"><i class="bi bi-person-check"></i></div>
        <div class="q-info">
          <div class="q-title">Register & Login</div>
          <div class="q-desc">Manajemen akun dan akses pengguna</div>
          <div class="q-badge"><?php echo $total_users; ?> user aktif</div>
        </div>
        <i class="bi bi-chevron-right q-arrow"></i>
      </a>
      <a href="status_admin.php" class="quick-card" style="animation-delay:0.34s">
        <div class="q-icon"><i class="bi bi-card-checklist"></i></div>
        <div class="q-info">
          <div class="q-title">Status Ujian</div>
          <div class="q-desc">Pantau status kelulusan hasil ujian masuk</div>
          <div class="q-badge"><?php echo $pass_rate; ?>% tingkat lulus</div>
        </div>
        <i class="bi bi-chevron-right q-arrow"></i>
      </a>
      <a href="daftar_ulang_admin.php" class="quick-card" style="animation-delay:0.42s">
        <div class="q-icon"><i class="bi bi-person-badge"></i></div>
        <div class="q-info">
          <div class="q-title">Daftar Ulang</div>
          <div class="q-desc">Verifikasi data daftar ulang mahasiswa baru</div>
          <div class="q-badge"><?php echo $total_daftar_ulang; ?> sudah daftar ulang</div>
        </div>
        <i class="bi bi-chevron-right q-arrow"></i>
      </a>
      <a href="tambah_soal_admin.php" class="quick-card" style="animation-delay:0.50s">
        <div class="q-icon"><i class="bi bi-plus-circle"></i></div>
        <div class="q-info">
          <div class="q-title">Tambah Soal</div>
          <div class="q-desc">Buat pertanyaan ujian baru untuk bank soal</div>
          <div class="q-badge">Tambah sekarang →</div>
        </div>
        <i class="bi bi-chevron-right q-arrow"></i>
      </a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
