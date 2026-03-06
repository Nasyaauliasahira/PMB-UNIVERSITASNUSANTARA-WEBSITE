<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt_hasil = mysqli_prepare($koneksi, "SELECT h.*, p.jurusan, p.nomor_ujian, p.phone 
    FROM hasil_ujian h
    LEFT JOIN users u ON h.siswa_id = u.id
    LEFT JOIN pendaftaran p ON u.email = p.email
    WHERE h.siswa_id = ? ORDER BY h.waktu_submit DESC LIMIT 1");
mysqli_stmt_bind_param($stmt_hasil, "i", $user_id);
mysqli_stmt_execute($stmt_hasil);
$hasil_ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_hasil));
mysqli_stmt_close($stmt_hasil);

if (!$hasil_ujian) { header("Location: dashboard.php"); exit; }

$is_lulus  = ($hasil_ujian['nilai'] >= 70);
$tahun_masuk = date('Y');

$nim = null;
$stmt_nim = mysqli_prepare($koneksi, "SELECT nim FROM daftar_ulang WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_nim, "i", $user_id);
mysqli_stmt_execute($stmt_nim);
if ($row_nim = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_nim))) { $nim = $row_nim['nim']; }
mysqli_stmt_close($stmt_nim);

$program_studi = ['business'=>'Manajemen Bisnis','it_software'=>'Teknik Informatika','design'=>'Desain Komunikasi Visual'];
$prodi = isset($hasil_ujian['jurusan']) && isset($program_studi[$hasil_ujian['jurusan']])
    ? $program_studi[$hasil_ujian['jurusan']] : 'Belum Ditentukan';

$user_name  = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
$user_email = htmlspecialchars($user['email'] ?? '');
$user_photo = $user['photo'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengumuman Kelulusan — Universitas Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--navy:#0d1f35;--navy-mid:#1e3a5f;--navy-light:#2d5a7b;--orange:#ff9800;--orange-dark:#e68900;--gold:#f0c060;--off-white:#f7f5f0;--green:#1a6e3c;--green-light:#27ae60;--red:#8b1a1a;--red-light:#dc3545;--glass:rgba(255,255,255,0.06);--glass-border:rgba(255,255,255,0.12);--shadow-deep:0 24px 64px rgba(13,31,53,0.35);--shadow-card:0 8px 32px rgba(13,31,53,0.18);--radius-lg:20px;--radius-xl:32px;--transition:all 0.45s cubic-bezier(0.25,0.46,0.45,0.94);}
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    html{scroll-behavior:smooth;}
    body{font-family:'DM Sans',sans-serif;background:var(--off-white);min-height:100vh;}

    /* NAVBAR */
    .navbar{background:rgba(13,31,53,0.92);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--glass-border);padding:14px 0;position:sticky;top:0;z-index:1000;transition:var(--transition);}
    .navbar.scrolled{background:rgba(13,31,53,0.98);padding:10px 0;}
    .navbar-brand{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:22px;color:white!important;display:flex;align-items:center;gap:4px;}
    .navbar-logo{width:52px;height:52px;object-fit:contain;}
    .profile-dropdown{position:relative;display:inline-block;}
    .profile-btn{background:var(--glass);border:1px solid var(--glass-border);color:white;cursor:pointer;font-size:14px;font-weight:500;padding:8px 16px 8px 8px;display:flex;align-items:center;gap:10px;border-radius:50px;transition:var(--transition);font-family:'DM Sans',sans-serif;}
    .profile-btn:hover{background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.25);}
    .profile-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--orange);}
    .profile-icon-circle{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--navy-light),var(--orange));display:flex;align-items:center;justify-content:center;font-size:18px;}
    .dropdown-menu-custom{position:absolute;top:calc(100% + 10px);right:0;background:white;border:1px solid rgba(13,31,53,0.1);border-radius:var(--radius-lg);box-shadow:var(--shadow-deep);min-width:210px;z-index:1001;display:none;overflow:hidden;}
    .dropdown-menu-custom.show{display:block;}
    .dropdown-menu-custom a{display:flex;align-items:center;gap:10px;padding:13px 18px;color:var(--navy);text-decoration:none;font-size:14px;font-weight:500;transition:var(--transition);border-bottom:1px solid rgba(13,31,53,0.06);}
    .dropdown-menu-custom a:last-child{border-bottom:none;}
    .dropdown-menu-custom a:hover{background:var(--off-white);padding-left:22px;}
    .dropdown-menu-custom a i{color:var(--navy-mid);font-size:16px;}
    .dropdown-menu-custom .user-email{padding:12px 18px;color:#888;font-size:12px;border-bottom:1px solid rgba(13,31,53,0.08);background:var(--off-white);}

    /* PAGE HERO */
    .page-hero{padding:70px 0 60px;position:relative;overflow:hidden;text-align:center;}
    .page-hero-lulus{background:linear-gradient(135deg,var(--green) 0%,#145c32 50%,#0d3d20 100%);}
    .page-hero-gagal{background:linear-gradient(135deg,var(--red) 0%,#6b1414 50%,#4a0e0e 100%);}
    .page-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(255,152,0,0.12) 0%,transparent 60%);pointer-events:none;}
    .result-icon{font-size:80px;display:block;margin-bottom:20px;animation:bounceIn 0.8s cubic-bezier(0.25,0.46,0.45,0.94) both;}
    @keyframes bounceIn{0%{opacity:0;transform:scale(0.3)}50%{transform:scale(1.08)}70%{transform:scale(0.95)}100%{opacity:1;transform:scale(1)}}
    .page-hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:14px;}
    .page-hero-eyebrow::before{content:'';width:32px;height:2px;background:var(--orange);}
    .page-hero h1{font-family:'Cormorant Garamond',serif;font-size:50px;font-weight:700;color:white;line-height:1.15;margin-bottom:16px;}
    .page-hero p{color:rgba(255,255,255,0.72);font-size:16px;max-width:560px;margin:0 auto 28px;line-height:1.75;}
    /* Status badge — hanya status, tanpa angka nilai */
    .status-badge{display:inline-flex;align-items:center;gap:10px;padding:12px 30px;border-radius:50px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;}
    .status-lulus{background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:white;}
    .status-gagal{background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.25);color:white;}
    .status-dot{width:10px;height:10px;border-radius:50%;animation:pulse 2s infinite;}
    .dot-lulus{background:#4ade80;box-shadow:0 0 8px #4ade80;}
    .dot-gagal{background:#f87171;box-shadow:0 0 8px #f87171;}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.3)}}

    /* MAIN */
    .main-content{padding:60px 0 80px;}

    /* Data card */
    .data-card{background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-card);overflow:hidden;animation:fadeUp 0.6s ease-out both;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    .data-card-header{padding:32px 40px 28px;border-bottom:1px solid rgba(13,31,53,0.07);}
    .data-card-header-lulus{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);}
    .data-card-header-gagal{background:linear-gradient(135deg,var(--red) 0%,#6b1414 100%);}
    .card-header-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:6px;}
    .card-header-eyebrow::before{content:'';width:24px;height:2px;background:var(--orange);}
    .card-header-title{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:white;}
    .data-card-body{padding:36px 40px;}

    /* Data items */
    .data-item{background:var(--off-white);border-radius:14px;padding:22px 24px;border:1px solid rgba(13,31,53,0.06);transition:var(--transition);height:100%;}
    .data-item:hover{transform:translateY(-4px);box-shadow:var(--shadow-card);}
    .data-item-label{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#8898aa;margin-bottom:10px;display:flex;align-items:center;gap:8px;}
    .data-item-label i{color:var(--orange);font-size:14px;}
    .data-item-value{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--navy);line-height:1.2;}
    .data-item-value.small{font-size:18px;font-family:'DM Sans',sans-serif;}
    .nim-highlight{background:linear-gradient(135deg,var(--navy),var(--navy-mid));border-radius:14px;padding:22px 24px;height:100%;transition:var(--transition);}
    .nim-highlight:hover{transform:translateY(-4px);box-shadow:var(--shadow-deep);}
    .nim-highlight .data-item-label{color:rgba(255,255,255,0.5);}
    .nim-highlight .data-item-label i{color:var(--orange);}
    .nim-highlight .data-item-value{color:white;letter-spacing:2px;}

    /* Info box */
    .info-box{border-radius:var(--radius-lg);padding:28px 32px;margin-top:24px;display:flex;gap:20px;align-items:flex-start;animation:fadeUp 0.7s ease-out both;}
    .info-box-lulus{background:rgba(13,31,53,0.06);border:1px solid rgba(13,31,53,0.1);}
    .info-box-gagal{background:rgba(139,26,26,0.06);border:1px solid rgba(220,53,69,0.15);}
    .info-box-icon{width:48px;height:48px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:22px;}
    .info-box-icon-lulus{background:rgba(26,110,60,0.12);color:var(--green-light);}
    .info-box-icon-gagal{background:rgba(220,53,69,0.12);color:var(--red-light);}
    .info-box-title{font-weight:700;color:var(--navy);font-size:15px;margin-bottom:12px;}
    .info-box-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;}
    .info-box-list li{font-size:12px;color:#5a6a7a;padding-left:18px;position:relative;line-height:1.3;}
    .info-box-list li::before{content:'—';position:absolute;left:0;color:var(--orange);font-weight:700;}
    .info-box-list li strong{color:var(--navy);}

    /* Action buttons */
    .action-row{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:16px;}
    .btn-action{display:inline-flex;align-items:center;gap:10px;padding:12px 24px;border-radius:50px;font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;text-decoration:none;border:none;cursor:pointer;transition:var(--transition);letter-spacing:0.3px;box-shadow:0 6px 24px rgba(13,31,53,0.12);}
    .btn-green-action{background:linear-gradient(135deg,var(--green),var(--green-light));color:white;box-shadow:0 4px 16px rgba(26,110,60,0.3);}
    .btn-green-action:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(26,110,60,0.4);color:white;}
    .btn-navy-action{background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;box-shadow:0 8px 32px rgba(13,31,53,0.25);}
    .btn-navy-action:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(13,31,53,0.35);color:white;}
    .btn-muted-action{background:rgba(13,31,53,0.08);color:var(--navy);border:1px solid rgba(13,31,53,0.12);}
    .btn-muted-action:hover{background:rgba(13,31,53,0.15);transform:translateY(-2px);color:var(--navy);}

    /* Modal kartu mahasiswa */
    .modal-content{border-radius:var(--radius-xl);overflow:hidden;border:none;box-shadow:var(--shadow-deep);}
    .kartu-header{background:#17643b;padding:20px 28px;display:flex;align-items:center;gap:18px;}
    .kartu-header img{width:52px;height:52px;border-radius:10px;object-fit:contain;border:2px solid white;}
    .kartu-header-text h4{color:white;font-weight:700;font-size:18px;margin:0 0 2px;font-family:'Cormorant Garamond',serif;}
    .kartu-header-text p{color:rgba(255,255,255,0.8);font-size:12px;margin:0;}
    .kartu-body{padding:28px;display:flex;gap:24px;align-items:center;background:white;}
    .kartu-foto{width:100px;height:120px;object-fit:cover;border-radius:10px;border:3px solid var(--orange);flex-shrink:0;background:var(--off-white);}
    .kartu-info{}
    .kartu-nama{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:#17643b;line-height:1.1;margin-bottom:4px;}
    .kartu-role{font-size:13px;color:#555;margin-bottom:2px;}
    .kartu-prodi{font-size:14px;color:#333;margin-bottom:12px;}
    .kartu-nim{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:900;color:var(--navy);letter-spacing:3px;}

    /* Footer */
    footer{background:linear-gradient(160deg,var(--navy) 0%,#0d1e30 100%);color:white;padding:70px 0 28px;position:relative;overflow:hidden;}
    footer::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--navy-light),var(--orange),var(--gold),var(--orange),var(--navy-light));}
    .footer-logo-section{display:flex;gap:16px;align-items:center;margin-bottom:50px;}
    .footer-logo{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;padding:4px;}
    .footer-logo img{width:100%;height:100%;object-fit:contain;}
    .footer-brand h3{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:20px;line-height:1.3;color:white;}
    .footer-content{display:grid;grid-template-columns:1.2fr 1fr 0.8fr 0.8fr;gap:50px;margin-bottom:50px;}
    .footer-section h5{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:16px;margin-bottom:18px;color:white;}
    .footer-section p{font-size:13px;line-height:1.85;color:rgba(255,255,255,0.6);}
    .footer-links{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;}
    .footer-links a{color:rgba(255,255,255,0.6);text-decoration:none;font-size:13px;transition:var(--transition);}
    .footer-links a:hover{color:white;padding-left:4px;}
    .footer-social{display:flex;gap:12px;margin-top:20px;}
    .footer-social a{width:40px;height:40px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.6);text-decoration:none;transition:var(--transition);font-size:16px;}
    .footer-social a:hover{background:rgba(255,152,0,0.15);border-color:var(--orange);color:var(--orange);transform:translateY(-4px);}
    .footer-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);margin-bottom:28px;}
    .footer-bottom{text-align:center;font-size:12px;color:rgba(255,255,255,0.35);}
    .footer-bottom span{color:var(--orange);}

    @media(max-width:991px){.page-hero h1{font-size:36px;}.footer-content{grid-template-columns:1fr 1fr;gap:35px;}.data-card-body{padding:28px;}}
    @media(max-width:768px){.page-hero h1{font-size:28px;}.footer-content{grid-template-columns:1fr;gap:28px;}.data-card-body{padding:20px;}.action-row{flex-direction:column;align-items:stretch;}.btn-action{justify-content:center;}}
  </style>
</head>
<body>

<!-- NAVBAR -->
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
      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="profile-dropdown">
          <button class="profile-btn" onclick="toggleDropdown()">
            <?php if (!empty($user_photo)): ?>
              <img src="uploads/foto/<?php echo htmlspecialchars($user_photo); ?>" alt="Profile" class="profile-avatar">
            <?php else: ?>
              <span class="profile-icon-circle"><i class="bi bi-person-fill"></i></span>
            <?php endif; ?>
            <span><?php echo $user_name; ?></span>
            <i class="bi bi-chevron-down" style="font-size:11px;opacity:0.7;"></i>
          </button>
          <div class="dropdown-menu-custom" id="dropdownMenu">
            <div class="user-email"><?php echo $user_email; ?></div>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="edit_profile.php"><i class="bi bi-person-gear"></i> Edit Profile</a>
            <a href="logout.php" onclick="return confirm('Yakin ingin logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- PAGE HERO -->
<section class="page-hero <?php echo $is_lulus ? 'page-hero-lulus' : 'page-hero-gagal'; ?>">
  <div class="container">
    <?php if ($is_lulus): ?>
      <span class="result-icon">🎓</span>
      <div class="page-hero-eyebrow">Hasil Seleksi Ujian Masuk</div>
      <h1>Selamat, Anda<br><em style="color:var(--gold);font-style:italic;">Dinyatakan Lulus!</em></h1>
      <p>Anda telah berhasil melewati tahap seleksi. Selamat bergabung bersama keluarga besar Universitas Nusantara.</p>
      <!-- Status saja, tanpa score display -->
      <div class="status-badge status-lulus">
        <span class="status-dot dot-lulus"></span> STATUS: LULUS SELEKSI
      </div>
    <?php else: ?>
      <span class="result-icon">📋</span>
      <div class="page-hero-eyebrow">Hasil Seleksi Ujian Masuk</div>
      <h1>Maaf, Anda Belum<br><em style="color:#fca5a5;font-style:italic;">Memenuhi Nilai Minimum</em></h1>
      <p>Terima kasih telah mengikuti seleksi ujian masuk. Jangan menyerah, terus tingkatkan kemampuan Anda!</p>
      <!-- Status saja, tanpa score display -->
      <div class="status-badge status-gagal">
        <span class="status-dot dot-gagal"></span> STATUS: BELUM LULUS
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- MAIN -->
<main class="main-content">
  <div class="container" style="max-width:900px;">

    <?php if ($is_lulus):
      $stmt_card = mysqli_prepare($koneksi, "SELECT d.nim, d.nama_lengkap, d.photo AS foto_mahasiswa, p.jurusan FROM daftar_ulang d LEFT JOIN pendaftaran p ON d.email = p.email WHERE d.user_id = ? LIMIT 1");
      mysqli_stmt_bind_param($stmt_card, "i", $user_id);
      mysqli_stmt_execute($stmt_card);
      $card = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_card));
      mysqli_stmt_close($stmt_card);

      $foto_mahasiswa    = $card && $card['foto_mahasiswa'] ? 'uploads/daftar_ulang/' . htmlspecialchars($card['foto_mahasiswa']) : 'assets/images/default-user.png';
      $nama_mahasiswa    = $card && $card['nama_lengkap'] ? htmlspecialchars($card['nama_lengkap']) : $user_name;
      $nim_mahasiswa     = $card && $card['nim'] ? htmlspecialchars($card['nim']) : ($nim ? htmlspecialchars($nim) : 'Belum tersedia');
      $jurusan_mahasiswa = $card && $card['jurusan'] ? $program_studi[$card['jurusan']] : $prodi;
    ?>

    <!-- DATA AKADEMIK (LULUS) -->
    <div class="data-card mb-4">
      <div class="data-card-header data-card-header-lulus">
        <div class="card-header-eyebrow">Data Resmi</div>
        <div class="card-header-title">Data Akademik Mahasiswa Baru</div>
      </div>
      <div class="data-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="nim-highlight">
              <div class="data-item-label"><i class="bi bi-credit-card-2-front"></i> NIM</div>
              <div class="data-item-value"><?php echo $nim_mahasiswa; ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="data-item">
              <div class="data-item-label"><i class="bi bi-book"></i> Program Studi</div>
              <div class="data-item-value"><?php echo $jurusan_mahasiswa; ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="data-item">
              <div class="data-item-label"><i class="bi bi-person"></i> Nama Lengkap</div>
              <div class="data-item-value"><?php echo $nama_mahasiswa; ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="data-item">
              <div class="data-item-label"><i class="bi bi-calendar-check"></i> Tahun Masuk</div>
              <div class="data-item-value"><?php echo $tahun_masuk; ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="action-row">
      <a href="download_pengumuman_pdf.php" class="btn-action btn-green-action">
        <i class="bi bi-file-earmark-pdf"></i> Download PDF
      </a>
      <button class="btn-action btn-navy-action" data-bs-toggle="modal" data-bs-target="#modalKartuMahasiswa">
        <i class="bi bi-credit-card-2-front"></i> Lihat Kartu Mahasiswa
      </button>
    </div>

    <div class="info-box info-box-lulus">
      <div class="info-box-icon info-box-icon-lulus"><i class="bi bi-info-circle-fill"></i></div>
      <div>
        <div class="info-box-title">Informasi Penting</div>
        <ul class="info-box-list">
          <li>Simpan dan catat <strong>NIM</strong> Anda untuk keperluan administrasi selanjutnya</li>
          <li>Proses registrasi ulang akan diinformasikan melalui email</li>
          <li>Hubungi bagian akademik untuk informasi lebih lanjut</li>
        </ul>
      </div>
    </div>

    <!-- MODAL KARTU MAHASISWA -->
    <div class="modal fade" id="modalKartuMahasiswa" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div id="kartuMahasiswaArea">
            <div class="kartu-header">
              <img src="LOGO.jpeg" alt="Logo">
              <div class="kartu-header-text">
                <h4>Universitas Nusantara</h4>
                <p>Kampus Harmoni — Jl. Merdeka Raya No. 45, Kota Harmoni</p>
              </div>
            </div>
            <div class="kartu-body">
              <img src="<?php echo $foto_mahasiswa; ?>" alt="Foto" class="kartu-foto">
              <div class="kartu-info">
                <div class="kartu-nama"><?php echo $nama_mahasiswa; ?></div>
                <div class="kartu-role">Mahasiswa S1</div>
                <div class="kartu-prodi"><?php echo $jurusan_mahasiswa; ?></div>
                <div class="kartu-nim"><?php echo $nim_mahasiswa; ?></div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 px-4 pb-4">
            <button type="button" class="btn-action btn-muted-action" data-bs-dismiss="modal">Tutup</button>
            <button id="downloadKartuModalBtn" class="btn-action btn-green-action">
              <i class="bi bi-download"></i> Download
            </button>
          </div>
        </div>
      </div>
    </div>

    <?php else: ?>

    <!-- DATA HASIL (TIDAK LULUS) — tanpa kolom nilai -->
    <div class="data-card mb-4">
      <div class="data-card-header data-card-header-gagal">
        <div class="card-header-eyebrow">Detail Hasil</div>
        <div class="card-header-title">Rincian Hasil Ujian</div>
      </div>
      <div class="data-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="data-item">
              <div class="data-item-label"><i class="bi bi-person"></i> Nama Lengkap</div>
              <div class="data-item-value"><?php echo $user_name; ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="data-item">
              <div class="data-item-label"><i class="bi bi-envelope"></i> Email</div>
              <div class="data-item-value small"><?php echo $user_email; ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="data-item">
              <div class="data-item-label"><i class="bi bi-book"></i> Program Studi</div>
              <div class="data-item-value"><?php echo $prodi; ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="data-item">
              <div class="data-item-label"><i class="bi bi-calendar"></i> Waktu Submit</div>
              <div class="data-item-value small"><?php echo date('d M Y, H:i', strtotime($hasil_ujian['waktu_submit'])); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="action-row">
      <a href="download_pengumuman_pdf.php" class="btn-action btn-muted-action">
        <i class="bi bi-file-earmark-pdf"></i> Download PDF
      </a>
      <a href="dashboard.php" class="btn-action btn-navy-action">
        <i class="bi bi-house"></i> Kembali ke Dashboard
      </a>
    </div>

    <div class="info-box info-box-gagal">
      <div class="info-box-icon info-box-icon-gagal"><i class="bi bi-info-circle-fill"></i></div>
      <div>
        <div class="info-box-title" style="color:#8b1a1a;">Informasi</div>
        <ul class="info-box-list">
          <li>Silakan hubungi bagian akademik untuk informasi lebih lanjut</li>
          <li>Terima kasih telah mengikuti seleksi ujian masuk Universitas Nusantara</li>
        </ul>
      </div>
    </div>

    <?php endif; ?>
  </div>
</main>

<!-- FOOTER -->
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
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 30);
  });
  function toggleDropdown() {
    document.getElementById('dropdownMenu')?.classList.toggle('show');
  }
  document.addEventListener('click', function(e) {
    const pd = document.querySelector('.profile-dropdown');
    const dm = document.getElementById('dropdownMenu');
    if (pd && dm && !pd.contains(e.target)) dm.classList.remove('show');
  });

  <?php if ($is_lulus): ?>
  window.addEventListener('load', function() {
    const duration = 3500, end = Date.now() + duration;
    const defaults = {startVelocity:30,spread:360,ticks:60,zIndex:9999};
    const rnd = (a,b) => Math.random()*(b-a)+a;
    const iv = setInterval(() => {
      const left = end - Date.now();
      if (left <= 0) return clearInterval(iv);
      const c = 50*(left/duration);
      confetti({...defaults,particleCount:c,origin:{x:rnd(0.1,0.3),y:Math.random()-0.2}});
      confetti({...defaults,particleCount:c,origin:{x:rnd(0.7,0.9),y:Math.random()-0.2}});
    }, 250);
  });
  function doDownload() {
    const area = document.getElementById('kartuMahasiswaArea');
    html2canvas(area, {backgroundColor:'#fff',scale:2}).then(canvas => {
      const link = document.createElement('a');
      link.download = 'kartu_mahasiswa_<?php echo $nim_mahasiswa ?? "pmb"; ?>.png';
      link.href = canvas.toDataURL();
      link.click();
    });
  }
  document.getElementById('downloadKartuModalBtn')?.addEventListener('click', doDownload);
  <?php endif; ?>
</script>
</body>
</html>