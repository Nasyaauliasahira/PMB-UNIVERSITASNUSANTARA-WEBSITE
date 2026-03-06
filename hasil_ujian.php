<?php
include "koneksi.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jawaban'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $siswa_id      = $_SESSION['user_id'];
    $jawaban_siswa = $_POST['jawaban'];

    $result_soal = mysqli_query($koneksi, "SELECT id, jawaban_benar FROM soal ORDER BY id ASC");
    $benar       = 0;
    $total_soal  = mysqli_num_rows($result_soal);

    while ($soal = mysqli_fetch_assoc($result_soal)) {
        if (isset($jawaban_siswa[$soal['id']]) && $jawaban_siswa[$soal['id']] === $soal['jawaban_benar']) {
            $benar++;
        }
    }

    $nilai = ($benar / $total_soal) * 100;

    $_SESSION['hasil_ujian'] = [
        'benar'         => $benar,
        'total_soal'    => $total_soal,
        'nilai'         => round($nilai),
        'jawaban_siswa' => $jawaban_siswa
    ];

    $stmt = mysqli_prepare($koneksi, "INSERT INTO hasil_ujian (siswa_id, jumlah_benar, nilai, durasi, waktu_submit) VALUES (?, ?, ?, ?, NOW())");
    $durasi = 0;
    mysqli_stmt_bind_param($stmt, "iiii", $siswa_id, $benar, $nilai, $durasi);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

} elseif (!isset($_SESSION['hasil_ujian'])) {
    header("Location: ujian.php");
    exit();
}

$hasil         = $_SESSION['hasil_ujian'];
$passing_score = 60;
$is_passed     = $hasil['nilai'] >= $passing_score;

// Navbar user
$user_name = $user_email = $user_photo = '';
if (isset($_SESSION['user_id'])) {
    $su = mysqli_prepare($koneksi, "SELECT first_name, last_name, email, photo FROM users WHERE id = ?");
    mysqli_stmt_bind_param($su, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($su);
    if ($u = mysqli_fetch_assoc(mysqli_stmt_get_result($su))) {
        $user_name  = htmlspecialchars(trim($u['first_name'] . ' ' . $u['last_name']));
        $user_email = htmlspecialchars($u['email']);
        $user_photo = $u['photo'] ?? '';
    }
    mysqli_stmt_close($su);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hasil Ujian — Universitas Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy:#0d1f35; --navy-mid:#1e3a5f; --navy-light:#2d5a7b;
      --orange:#ff9800; --orange-dark:#e68900; --gold:#f0c060;
      --off-white:#f7f5f0;
      --green:#1a6e3c; --green-light:#27ae60;
      --red:#8b1a1a;
      --glass:rgba(255,255,255,0.06); --glass-border:rgba(255,255,255,0.12);
      --shadow-deep:0 24px 64px rgba(13,31,53,0.35);
      --shadow-card:0 8px 32px rgba(13,31,53,0.18);
      --radius-lg:20px; --radius-xl:32px;
      --transition:all 0.45s cubic-bezier(0.25,0.46,0.45,0.94);
    }
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    html{scroll-behavior:smooth;}
    body{font-family:'DM Sans',sans-serif;background:var(--off-white);min-height:100vh;display:flex;flex-direction:column;}

    /* NAVBAR */
    .navbar{background:rgba(13,31,53,0.92);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--glass-border);padding:14px 0;position:sticky;top:0;z-index:1000;transition:var(--transition);}
    .navbar.scrolled{background:rgba(13,31,53,0.98);padding:10px 0;}
    .navbar-brand{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:22px;color:white!important;display:flex;align-items:center;gap:4px;}
    .navbar-logo{width:52px;height:52px;object-fit:contain;}
    .profile-dropdown{position:relative;display:inline-block;}
    .profile-btn{background:var(--glass);border:1px solid var(--glass-border);color:white;cursor:pointer;font-size:14px;font-weight:500;padding:8px 16px 8px 8px;display:flex;align-items:center;gap:10px;border-radius:50px;transition:var(--transition);font-family:'DM Sans',sans-serif;}
    .profile-btn:hover{background:rgba(255,255,255,0.12);}
    .profile-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--orange);}
    .profile-icon-circle{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--navy-light),var(--orange));display:flex;align-items:center;justify-content:center;font-size:18px;}
    .dropdown-menu-custom{position:absolute;top:calc(100% + 10px);right:0;background:white;border:1px solid rgba(13,31,53,0.1);border-radius:var(--radius-lg);box-shadow:var(--shadow-deep);min-width:210px;z-index:1001;display:none;overflow:hidden;}
    .dropdown-menu-custom.show{display:block;}
    .dropdown-menu-custom a{display:flex;align-items:center;gap:10px;padding:13px 18px;color:var(--navy);text-decoration:none;font-size:14px;font-weight:500;transition:var(--transition);border-bottom:1px solid rgba(13,31,53,0.06);}
    .dropdown-menu-custom a:last-child{border-bottom:none;}
    .dropdown-menu-custom a:hover{background:var(--off-white);padding-left:22px;}
    .dropdown-menu-custom a i{color:var(--navy-mid);}
    .dropdown-menu-custom .user-email{padding:12px 18px;color:#888;font-size:12px;border-bottom:1px solid rgba(13,31,53,0.08);background:var(--off-white);}

    /* HERO */
    .result-hero{padding:64px 0 56px;text-align:center;position:relative;overflow:hidden;}
    .hero-passed{background:linear-gradient(135deg,var(--green) 0%,#0f4022 100%);}
    .hero-failed{background:linear-gradient(135deg,var(--red) 0%,#5c1010 100%);}
    .result-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(255,152,0,0.1) 0%,transparent 60%);pointer-events:none;}
    .result-icon{font-size:72px;display:block;margin-bottom:16px;animation:bounceIn 0.8s cubic-bezier(0.25,0.46,0.45,0.94) both;}
    @keyframes bounceIn{0%{opacity:0;transform:scale(0.3)}55%{transform:scale(1.08)}75%{transform:scale(0.95)}100%{opacity:1;transform:scale(1)}}
    .result-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:12px;}
    .result-eyebrow::before,.result-eyebrow::after{content:'';width:28px;height:2px;background:var(--orange);}
    .result-hero h1{font-family:'Cormorant Garamond',serif;font-size:46px;font-weight:700;color:white;line-height:1.15;margin-bottom:12px;}
    .result-hero h1 em{color:var(--gold);font-style:italic;}
    .result-hero p{color:rgba(255,255,255,0.68);font-size:15px;}
    /* Status pill — hanya LULUS/BELUM LULUS, tanpa angka */
    .status-pill{display:inline-flex;align-items:center;gap:10px;padding:11px 28px;border-radius:50px;margin-top:22px;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:white;}
    .status-dot{width:9px;height:9px;border-radius:50%;animation:pulse 2s infinite;}
    .dot-passed{background:#4ade80;box-shadow:0 0 8px #4ade80;}
    .dot-failed{background:#f87171;box-shadow:0 0 8px #f87171;}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.3)}}

    /* MAIN */
    .main-content{padding:52px 0 72px;flex:1;}

    /* Score card */
    .score-card{background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-card);overflow:hidden;animation:fadeUp 0.6s ease-out both;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    .score-header{padding:28px 36px 24px;border-bottom:1px solid rgba(13,31,53,0.07);}
    .header-passed{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);}
    .header-failed{background:linear-gradient(135deg,var(--red) 0%,#6b1414 100%);}
    .card-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:4px;}
    .card-eyebrow::before{content:'';width:22px;height:2px;background:var(--orange);}
    .card-title{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:white;}
    .score-body{padding:40px 36px;}

    /* Status badge besar — pengganti ring nilai */
    .status-display{display:flex;flex-direction:column;align-items:center;margin-bottom:36px;text-align:center;}
    .status-badge-big{width:130px;height:130px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:56px;margin-bottom:18px;animation:bounceIn 0.7s ease-out both;}
    .sb-passed{background:linear-gradient(135deg,rgba(26,110,60,0.1),rgba(39,174,96,0.15));border:3px solid rgba(39,174,96,0.3);}
    .sb-failed{background:linear-gradient(135deg,rgba(139,26,26,0.1),rgba(220,53,69,0.15));border:3px solid rgba(220,53,69,0.25);}
    .status-label{font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:700;margin-bottom:8px;}
    .sl-passed{color:var(--green);}
    .sl-failed{color:#c0392b;}
    .status-sub{font-size:14px;color:#8898aa;line-height:1.7;max-width:380px;}

    /* Alert */
    .result-alert{border-radius:14px;padding:20px 24px;margin-bottom:28px;display:flex;align-items:flex-start;gap:14px;font-size:14px;line-height:1.65;animation:fadeUp 0.7s ease-out both;}
    .ra-passed{background:rgba(26,110,60,0.07);border:1px solid rgba(39,174,96,0.2);}
    .ra-failed{background:rgba(139,26,26,0.06);border:1px solid rgba(220,53,69,0.18);}
    .ra-icon{width:44px;height:44px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:20px;}
    .ri-green{background:rgba(39,174,96,0.12);color:var(--green-light);}
    .ri-red{background:rgba(220,53,69,0.12);color:#dc3545;}
    .ra-title{font-weight:700;color:var(--navy);margin-bottom:4px;}
    .ra-text{color:#5a6a7a;}

    /* Buttons */
    .action-row{display:flex;flex-direction:column;gap:12px;animation:fadeUp 0.8s ease-out both;}
    .btn-act{width:100%;padding:15px 24px;border:none;border-radius:50px;font-weight:700;font-size:15px;font-family:'DM Sans',sans-serif;cursor:pointer;transition:var(--transition);display:flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;}
    .btn-orange{background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:white;box-shadow:0 4px 20px rgba(255,152,0,0.35);}
    .btn-orange:hover{transform:translateY(-3px);box-shadow:0 10px 32px rgba(255,152,0,0.5);color:white;}
    .btn-navy{background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;box-shadow:0 4px 16px rgba(13,31,53,0.22);}
    .btn-navy:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(13,31,53,0.35);color:white;}

    /* Footer */
    footer{background:linear-gradient(160deg,var(--navy) 0%,#0d1e30 100%);color:white;padding:60px 0 24px;position:relative;overflow:hidden;}
    footer::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--navy-light),var(--orange),var(--gold),var(--orange),var(--navy-light));}
    .footer-logo-section{display:flex;gap:16px;align-items:center;margin-bottom:44px;}
    .footer-logo{width:54px;height:54px;border-radius:13px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;padding:4px;}
    .footer-logo img{width:100%;height:100%;object-fit:contain;}
    .footer-brand h3{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:20px;line-height:1.3;color:white;}
    .footer-content{display:grid;grid-template-columns:1.2fr 1fr 0.8fr 0.8fr;gap:48px;margin-bottom:44px;}
    .footer-section h5{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:16px;margin-bottom:16px;color:white;}
    .footer-section p{font-size:13px;line-height:1.85;color:rgba(255,255,255,0.6);}
    .footer-links{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:9px;}
    .footer-links a{color:rgba(255,255,255,0.6);text-decoration:none;font-size:13px;transition:var(--transition);}
    .footer-links a:hover{color:white;padding-left:4px;}
    .footer-social{display:flex;gap:11px;margin-top:18px;}
    .footer-social a{width:40px;height:40px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.6);text-decoration:none;transition:var(--transition);font-size:15px;}
    .footer-social a:hover{background:rgba(255,152,0,0.15);border-color:var(--orange);color:var(--orange);transform:translateY(-4px);}
    .footer-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);margin-bottom:24px;}
    .footer-bottom{text-align:center;font-size:12px;color:rgba(255,255,255,0.35);}
    .footer-bottom span{color:var(--orange);}

    @media(max-width:768px){.result-hero h1{font-size:34px;}.score-body{padding:32px 24px;}.footer-content{grid-template-columns:1fr 1fr;gap:28px;}}
    @media(max-width:480px){.score-body{padding:28px 20px;}.footer-content{grid-template-columns:1fr;gap:22px;}}
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
      <div class="ms-auto">
        <?php if (!empty($user_name)): ?>
        <div class="profile-dropdown">
          <button class="profile-btn" onclick="toggleDropdown()">
            <?php if (!empty($user_photo)): ?>
              <img src="uploads/foto/<?php echo htmlspecialchars($user_photo); ?>" class="profile-avatar" alt="">
            <?php else: ?>
              <span class="profile-icon-circle"><i class="bi bi-person-fill"></i></span>
            <?php endif; ?>
            <span><?php echo $user_name; ?></span>
            <i class="bi bi-chevron-down" style="font-size:11px;opacity:0.7;"></i>
          </button>
          <div class="dropdown-menu-custom" id="dropdownMenu">
            <div class="user-email"><?php echo $user_email; ?></div>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="logout.php" onclick="return confirm('Yakin ingin logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="result-hero <?php echo $is_passed ? 'hero-passed' : 'hero-failed'; ?>">
  <div class="container">
    <span class="result-icon"><?php echo $is_passed ? '🎓' : '📋'; ?></span>
    <div class="result-eyebrow">Hasil Ujian Seleksi</div>
    <?php if ($is_passed): ?>
      <h1>Ujian Selesai!<br><em>Anda Lulus!</em></h1>
      <p>Selamat! Anda berhasil mengerjakan ujian seleksi masuk Universitas Nusantara.</p>
    <?php else: ?>
      <h1>Ujian Selesai,<br><em style="color:#fca5a5;">Belum Lulus</em></h1>
      <p>Terima kasih telah mengikuti ujian. Terus semangat dan jangan menyerah!</p>
    <?php endif; ?>
    <!-- Status pill: hanya status kelulusan, tanpa angka nilai -->
    <div class="status-pill">
      <span class="status-dot <?php echo $is_passed ? 'dot-passed' : 'dot-failed'; ?>"></span>
      <?php echo $is_passed ? 'LULUS' : 'BELUM LULUS'; ?>
    </div>
  </div>
</section>

<!-- MAIN -->
<main class="main-content">
  <div class="container" style="max-width:640px;">
    <div class="score-card">

      <div class="score-header <?php echo $is_passed ? 'header-passed' : 'header-failed'; ?>">
        <div class="card-eyebrow">Status Kelulusan</div>
        <div class="card-title">Hasil Ujian Seleksi Masuk</div>
      </div>

      <div class="score-body">

        <!-- Badge status besar — tanpa ring nilai, tanpa angka -->
        <div class="status-display">
          <div class="status-badge-big <?php echo $is_passed ? 'sb-passed' : 'sb-failed'; ?>">
            <?php echo $is_passed ? '✓' : '✗'; ?>
          </div>
          <div class="status-label <?php echo $is_passed ? 'sl-passed' : 'sl-failed'; ?>">
            <?php echo $is_passed ? 'Dinyatakan Lulus' : 'Belum Memenuhi Syarat'; ?>
          </div>
          <div class="status-sub">
            <?php if ($is_passed): ?>
              Anda memenuhi syarat kelulusan ujian seleksi masuk Universitas Nusantara dan berhak melanjutkan ke tahap daftar ulang.
            <?php else: ?>
              Anda belum memenuhi syarat kelulusan yang ditetapkan. Silakan hubungi bagian akademik untuk informasi lebih lanjut.
            <?php endif; ?>
          </div>
        </div>

        <!-- Alert -->
        <div class="result-alert <?php echo $is_passed ? 'ra-passed' : 'ra-failed'; ?>">
          <div class="ra-icon <?php echo $is_passed ? 'ri-green' : 'ri-red'; ?>">
            <i class="bi bi-<?php echo $is_passed ? 'check-circle-fill' : 'exclamation-circle-fill'; ?>"></i>
          </div>
          <div>
            <div class="ra-title"><?php echo $is_passed ? 'Selamat, Anda Lulus!' : 'Belum Memenuhi Nilai Minimum'; ?></div>
            <div class="ra-text">
              <?php if ($is_passed): ?>
                Anda berhak melanjutkan ke tahap daftar ulang mahasiswa baru. Klik tombol di bawah untuk melanjutkan proses pendaftaran.
              <?php else: ?>
                Silakan hubungi bagian akademik untuk informasi lebih lanjut mengenai langkah berikutnya.
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Buttons -->
        <div class="action-row">
          <?php if ($is_passed): ?>
          <a href="daftar_ulang.php" class="btn-act btn-orange">
            <i class="bi bi-pencil-square"></i> Daftar Ulang Sekarang
          </a>
          <?php endif; ?>
          <a href="index.php" class="btn-act btn-navy">
            <i class="bi bi-house"></i> Kembali ke Beranda
          </a>
        </div>

      </div>
    </div>
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
<?php if ($is_passed): ?>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<script>
window.addEventListener('load', () => {
  const end = Date.now() + 3200;
  const rnd = (a,b) => Math.random()*(b-a)+a;
  const iv  = setInterval(() => {
    if (Date.now() > end) return clearInterval(iv);
    const c = 50 * ((end - Date.now()) / 3200);
    const d = {startVelocity:30,spread:360,ticks:60,zIndex:9999,particleCount:c};
    confetti({...d, origin:{x:rnd(0.1,0.3),y:Math.random()-0.2}});
    confetti({...d, origin:{x:rnd(0.7,0.9),y:Math.random()-0.2}});
  }, 250);
});
</script>
<?php endif; ?>
<script>
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 30);
  });
  function toggleDropdown() {
    document.getElementById('dropdownMenu')?.classList.toggle('show');
  }
  document.addEventListener('click', e => {
    const pd = document.querySelector('.profile-dropdown');
    const dm = document.getElementById('dropdownMenu');
    if (pd && dm && !pd.contains(e.target)) dm.classList.remove('show');
  });
</script>
</body>
</html>