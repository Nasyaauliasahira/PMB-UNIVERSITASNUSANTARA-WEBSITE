<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$query_check = "SELECT nilai FROM hasil_ujian WHERE siswa_id = ? ORDER BY waktu_submit DESC LIMIT 1";
$stmt = mysqli_prepare($koneksi, $query_check);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$hasil_ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt_u = mysqli_prepare($koneksi, "SELECT first_name, last_name, email, photo FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_u, "i", $user_id);
mysqli_stmt_execute($stmt_u);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_u));
mysqli_stmt_close($stmt_u);
$user_name  = htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
$user_email = htmlspecialchars($user['email'] ?? '');
$user_photo = $user['photo'] ?? '';

// Sudah lulus — halaman akses ditolak
if ($hasil_ujian && $hasil_ujian['nilai'] >= 70) {
    // $nilai_user dihapus — tidak ditampilkan ke user
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sudah Lulus — Universitas Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--navy:#0d1f35;--navy-mid:#1e3a5f;--navy-light:#2d5a7b;--orange:#ff9800;--orange-dark:#e68900;--gold:#f0c060;--off-white:#f7f5f0;--green:#1a6e3c;--green-light:#27ae60;--glass:rgba(255,255,255,0.06);--glass-border:rgba(255,255,255,0.12);--shadow-deep:0 24px 64px rgba(13,31,53,0.35);--shadow-card:0 8px 32px rgba(13,31,53,0.18);--radius-lg:20px;--radius-xl:32px;--transition:all 0.45s cubic-bezier(0.25,0.46,0.45,0.94);}
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'DM Sans',sans-serif;background:var(--off-white);min-height:100vh;display:flex;flex-direction:column;}
    .navbar{background:rgba(13,31,53,0.92);backdrop-filter:blur(20px);border-bottom:1px solid var(--glass-border);padding:14px 0;position:sticky;top:0;z-index:1000;}
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
    .dropdown-menu-custom a i{color:var(--navy-mid);font-size:16px;}
    .dropdown-menu-custom .user-email{padding:12px 18px;color:#888;font-size:12px;border-bottom:1px solid rgba(13,31,53,0.08);background:var(--off-white);}
    .denied-page{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
    .denied-card{background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-deep);overflow:hidden;max-width:900px;width:100%;display:grid;grid-template-columns:1fr 1.2fr;}
    .denied-left{background:linear-gradient(135deg,var(--green) 0%,#0d3d20 100%);padding:70px 50px;display:flex;flex-direction:column;justify-content:center;color:white;position:relative;overflow:hidden;}
    .denied-left::before{content:'';position:absolute;top:-100px;right:-100px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(255,152,0,0.15) 0%,transparent 70%);}
    .denied-left-eyebrow{font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
    .denied-left-eyebrow::before{content:'';width:24px;height:2px;background:var(--orange);}
    .denied-left h1{font-family:'Cormorant Garamond',serif;font-size:52px;font-weight:700;line-height:1.1;color:white;}
    .denied-left h1 em{color:var(--gold);font-style:italic;}
    /* Ganti score-pill dengan lulus-badge tanpa angka */
    .lulus-badge{display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);border-radius:50px;padding:10px 22px;margin-top:28px;font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:white;}
    .lulus-dot{width:9px;height:9px;border-radius:50%;background:#4ade80;box-shadow:0 0 8px #4ade80;animation:pulse 2s infinite;}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.3)}}
    .denied-right{padding:60px 50px;display:flex;flex-direction:column;justify-content:center;}
    .denied-right-eyebrow{font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:10px;display:flex;align-items:center;gap:10px;}
    .denied-right-eyebrow::before{content:'';width:24px;height:2px;background:var(--orange);}
    .denied-right h2{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--navy);margin-bottom:28px;}
    .info-block{background:var(--off-white);border-radius:14px;padding:22px 24px;margin-bottom:24px;border-left:4px solid var(--green-light);}
    .info-block-title{font-weight:700;color:var(--navy);font-size:14px;margin-bottom:10px;display:flex;align-items:center;gap:8px;}
    .info-block-title i{color:var(--green-light);}
    .info-block p{font-size:14px;color:#5a6a7a;line-height:1.7;margin:0;}
    .info-note{background:rgba(255,152,0,0.07);border:1px solid rgba(255,152,0,0.2);border-radius:14px;padding:18px 22px;margin-bottom:28px;font-size:14px;color:var(--navy);line-height:1.7;}
    .info-note i{color:var(--orange);margin-right:6px;}
    .btn-dashboard{display:inline-flex;align-items:center;gap:10px;padding:14px 32px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;text-decoration:none;border-radius:50px;font-weight:700;font-size:14px;font-family:'DM Sans',sans-serif;transition:var(--transition);box-shadow:0 4px 16px rgba(13,31,53,0.25);}
    .btn-dashboard:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(13,31,53,0.35);color:white;}
    @media(max-width:768px){.denied-card{grid-template-columns:1fr;}.denied-left{padding:40px 30px;}.denied-left h1{font-size:36px;}.denied-right{padding:36px 24px;}}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="index.php">
      <img src="LOGORBG.png" alt="Logo" class="navbar-logo">
      <span>Universitas Nusantara</span>
    </a>
    <div class="ms-auto">
      <div class="profile-dropdown">
        <button class="profile-btn" onclick="toggleDropdown()">
          <?php if (!empty($user_photo)): ?>
            <img src="uploads/foto/<?php echo htmlspecialchars($user_photo); ?>" class="profile-avatar" alt="Profile">
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
    </div>
  </div>
</nav>

<div class="denied-page">
  <div class="denied-card">
    <div class="denied-left">
      <div class="denied-left-eyebrow">Status Ujian</div>
      <h1>Selamat!<br>Anda <em>Sudah<br>Lulus</em></h1>
      <!-- Hanya badge status, tanpa angka nilai -->
      <div class="lulus-badge">
        <span class="lulus-dot"></span> DINYATAKAN LULUS
      </div>
    </div>
    <div class="denied-right">
      <div class="denied-right-eyebrow">Informasi</div>
      <h2>Akses Ujian<br>Tidak Tersedia</h2>
      <div class="info-block">
        <div class="info-block-title"><i class="bi bi-check-circle-fill"></i> Anda Sudah Dinyatakan Lulus</div>
        <p>Selamat! Anda sudah berhasil lulus ujian seleksi. Anda tidak dapat mengikuti ujian kembali karena sudah dinyatakan lulus.</p>
      </div>
      <div class="info-note">
        <i class="bi bi-pin-angle-fill"></i>
        Silakan lanjutkan proses <strong>daftar ulang</strong> mahasiswa baru melalui menu yang tersedia di dashboard.
      </div>
      <a href="dashboard.php" class="btn-dashboard">
        <i class="bi bi-speedometer2"></i> Kembali ke Dashboard
      </a>
    </div>
  </div>
</div>
<script>
  function toggleDropdown(){document.getElementById('dropdownMenu')?.classList.toggle('show');}
  document.addEventListener('click',function(e){const pd=document.querySelector('.profile-dropdown');const dm=document.getElementById('dropdownMenu');if(pd&&dm&&!pd.contains(e.target))dm.classList.remove('show');});
</script>
</body>
</html>
<?php
    exit;
}

// Ambil soal
$soal = mysqli_query($koneksi, "SELECT * FROM soal ORDER BY id ASC");
$total_soal = mysqli_num_rows($soal);
$jawaban_tersimpan = $_SESSION['jawaban'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ujian Seleksi — Universitas Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--navy:#0d1f35;--navy-mid:#1e3a5f;--navy-light:#2d5a7b;--orange:#ff9800;--orange-dark:#e68900;--gold:#f0c060;--off-white:#f7f5f0;--glass:rgba(255,255,255,0.06);--glass-border:rgba(255,255,255,0.12);--shadow-deep:0 24px 64px rgba(13,31,53,0.35);--shadow-card:0 8px 32px rgba(13,31,53,0.18);--radius-lg:20px;--radius-xl:32px;--transition:all 0.45s cubic-bezier(0.25,0.46,0.45,0.94);}
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    html,body{height:100%;overflow:hidden;}
    body{font-family:'DM Sans',sans-serif;background:var(--off-white);display:flex;flex-direction:column;}
    .exam-topbar{background:rgba(13,31,53,0.96);backdrop-filter:blur(20px);border-bottom:1px solid var(--glass-border);padding:12px 28px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;z-index:100;}
    .topbar-brand{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:20px;color:white;display:flex;align-items:center;gap:6px;}
    .topbar-brand img{width:40px;height:40px;object-fit:contain;}
    .topbar-title{font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--orange);}
    .topbar-user{font-size:13px;font-weight:500;color:rgba(255,255,255,0.7);display:flex;align-items:center;gap:8px;}
    .topbar-user-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--navy-light),var(--orange));display:flex;align-items:center;justify-content:center;font-size:14px;color:white;}
    .exam-wrapper{flex:1;display:flex;overflow:hidden;}
    .exam-content{flex:1;background:var(--off-white);padding:40px 52px;overflow-y:auto;display:flex;flex-direction:column;max-height:calc(100vh - 80px);}
    .question-section{display:none;}
    .question-section.active{display:flex;flex-direction:column;flex:1;animation:fadeIn 0.3s ease;}
    @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .question-header{display:flex;align-items:center;gap:16px;margin-bottom:28px;}
    .question-badge{width:52px;height:52px;flex-shrink:0;background:linear-gradient(135deg,var(--navy),var(--navy-mid));border-radius:14px;display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--orange);}
    .question-meta{font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#8898aa;}
    .question-text{font-size:17px;font-weight:600;color:var(--navy);line-height:1.7;margin-bottom:28px;padding:24px 28px;background:white;border-radius:var(--radius-lg);border:1px solid rgba(13,31,53,0.07);box-shadow:0 2px 12px rgba(13,31,53,0.05);}
    .question-image{margin-bottom:24px;border-radius:14px;overflow:hidden;border:1px solid rgba(13,31,53,0.08);box-shadow:var(--shadow-card);}
    .question-image img{width:100%;max-height:320px;object-fit:contain;display:block;}
    .answer-options{display:flex;flex-direction:column;gap:10px;margin-bottom:36px;}
    .answer-option{display:flex;align-items:center;gap:16px;background:white;border:2px solid rgba(13,31,53,0.1);border-radius:14px;padding:16px 20px;cursor:pointer;transition:var(--transition);font-size:15px;font-weight:500;color:var(--navy);}
    .answer-option:hover{border-color:var(--orange);background:rgba(255,152,0,0.04);transform:translateX(4px);}
    .answer-option.selected{border-color:var(--orange);background:linear-gradient(135deg,rgba(255,152,0,0.08),rgba(255,152,0,0.04));box-shadow:0 0 0 1px var(--orange);}
    .answer-option input[type="radio"]{display:none;}
    .option-letter{width:32px;height:32px;flex-shrink:0;border-radius:8px;border:2px solid rgba(13,31,53,0.15);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:var(--navy-light);transition:var(--transition);}
    .answer-option.selected .option-letter{background:var(--orange);border-color:var(--orange);color:white;}
    .nav-buttons{display:flex;gap:10px;padding:10px 0 0 0;position:sticky;bottom:0;background:var(--off-white);margin-top:auto;z-index:100;}
    .btn-nav{flex:1 1 0;min-width:0;padding:10px 0;border:none;border-radius:40px;font-weight:700;font-size:14px;font-family:'DM Sans',sans-serif;cursor:pointer;transition:var(--transition);display:flex;align-items:center;justify-content:center;gap:8px;}
    .btn-prev{background:rgba(13,31,53,0.08);color:var(--navy);border:1px solid rgba(13,31,53,0.12);}
    .btn-prev:hover{background:rgba(13,31,53,0.14);}
    .btn-prev:disabled{opacity:0.35;cursor:not-allowed;}
    .btn-next{background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;box-shadow:0 4px 16px rgba(13,31,53,0.25);}
    .btn-next:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(13,31,53,0.35);}
    .btn-submit-final{background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:white;box-shadow:0 4px 16px rgba(255,152,0,0.35);}
    .btn-submit-final:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(255,152,0,0.5);}
    .exam-sidebar{width:270px;flex-shrink:0;background:var(--navy);padding:28px 20px;overflow-y:auto;border-left:3px solid var(--orange);display:flex;flex-direction:column;gap:24px;}
    .timer-card{background:rgba(255,255,255,0.05);border:1px solid var(--glass-border);border-radius:var(--radius-lg);padding:20px;text-align:center;}
    .timer-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:10px;}
    .timer-display{font-family:'Cormorant Garamond',serif;font-size:52px;font-weight:700;color:var(--orange);line-height:1;letter-spacing:2px;}
    .timer-display.warning{color:#f87171;animation:timerPulse 1s infinite;}
    @keyframes timerPulse{0%,100%{opacity:1}50%{opacity:0.5}}
    .timer-bar-wrap{height:4px;background:rgba(255,255,255,0.1);border-radius:2px;margin-top:14px;overflow:hidden;}
    .timer-bar{height:100%;background:linear-gradient(90deg,var(--orange),var(--gold));border-radius:2px;transition:width 1s linear;}
    .progress-card{background:rgba(255,255,255,0.05);border:1px solid var(--glass-border);border-radius:var(--radius-lg);padding:18px 20px;}
    .progress-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:12px;}
    .progress-bar-wrap{height:6px;background:rgba(255,255,255,0.1);border-radius:3px;overflow:hidden;margin-bottom:8px;}
    .progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--orange),var(--gold));border-radius:3px;transition:width 0.4s ease;}
    .progress-text{font-size:12px;color:rgba(255,255,255,0.5);text-align:right;}
    .progress-text span{color:white;font-weight:700;}
    .nav-section-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.35);}
    .question-nav{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;}
    .question-nav-item{aspect-ratio:1;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:rgba(255,255,255,0.6);transition:var(--transition);}
    .question-nav-item:hover{border-color:var(--orange);color:white;background:rgba(255,152,0,0.1);}
    .question-nav-item.answered{background:rgba(255,152,0,0.15);border-color:rgba(255,152,0,0.4);color:var(--orange);}
    .question-nav-item.active{background:var(--orange);border-color:var(--orange);color:white;box-shadow:0 0 14px rgba(255,152,0,0.45);}
    ::-webkit-scrollbar{width:6px;}::-webkit-scrollbar-track{background:transparent;}::-webkit-scrollbar-thumb{background:rgba(255,152,0,0.3);border-radius:3px;}::-webkit-scrollbar-thumb:hover{background:var(--orange);}
    @media(max-width:1024px){.exam-wrapper{flex-direction:column-reverse;overflow:auto;}.exam-sidebar{width:100%;border-left:none;border-top:3px solid var(--orange);flex-direction:row;flex-wrap:wrap;gap:16px;}.question-nav{grid-template-columns:repeat(6,1fr);}.nav-buttons{position:fixed;left:270px;right:0;bottom:0;background:var(--off-white);border-top:1px solid rgba(13,31,53,0.07);padding:16px 32px;display:flex;gap:12px;z-index:2000;}}
    @media(max-width:768px){.exam-content{padding:24px 8px;}.exam-topbar{padding:12px 8px;}.topbar-title{display:none;}.question-nav{grid-template-columns:repeat(5,1fr);}.nav-buttons{padding:12px 4px;}.btn-nav{font-size:13px;padding:13px 0;}}
  </style>
</head>
<body>

<!-- TOPBAR -->
<div class="exam-topbar">
  <div class="topbar-brand">
    <img src="LOGORBG.png" alt="Logo">
    <span>Universitas Nusantara</span>
  </div>
  <div class="topbar-title">Ujian Seleksi Masuk</div>
  <div class="topbar-user">
    <div class="topbar-user-avatar"><i class="bi bi-person-fill"></i></div>
    <?php echo $user_name; ?>
  </div>
</div>

<form id="examForm" method="POST" action="hasil_ujian.php">
<div class="exam-wrapper">

  <!-- QUESTIONS -->
  <div class="exam-content">
    <?php
    $soal_array = [];
    $no = 1;
    while ($s = mysqli_fetch_assoc($soal)):
      $soal_array[] = $s;
    ?>
    <div class="question-section <?= ($no === 1) ? 'active' : '' ?>" data-question="<?= $no ?>">
      <div class="question-header">
        <div class="question-badge"><?= $no ?></div>
        <div><div class="question-meta">Soal <?= $no ?> dari <?= $total_soal ?></div></div>
      </div>
      <div class="question-text"><?= htmlspecialchars($s['pertanyaan']) ?></div>
      <?php if (!empty($s['gambar'])): ?>
      <div class="question-image"><img src="<?= htmlspecialchars($s['gambar']) ?>" alt="Gambar soal"></div>
      <?php endif; ?>
      <div class="answer-options">
        <?php
        $opts = [['label'=>'A','key'=>'pilihan_a'],['label'=>'B','key'=>'pilihan_b'],['label'=>'C','key'=>'pilihan_c'],['label'=>'D','key'=>'pilihan_d']];
        foreach ($opts as $opt):
          $checked   = isset($jawaban_tersimpan[$s['id']]) && $jawaban_tersimpan[$s['id']] == $opt['label'];
          $sel_class = $checked ? 'selected' : '';
        ?>
        <label class="answer-option <?= $sel_class ?>" onclick="selectAnswer(this)">
          <input type="radio" name="jawaban[<?= $s['id'] ?>]" value="<?= $opt['label'] ?>" <?= $checked ? 'checked' : '' ?>>
          <span class="option-letter"><?= $opt['label'] ?></span>
          <span><?= htmlspecialchars($s[$opt['key']]) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $no++; endwhile; ?>

    <div class="nav-buttons" id="navButtons">
      <button type="button" class="btn-nav btn-prev" id="btnPrev" onclick="previousQuestion()">
        <i class="bi bi-arrow-left"></i> Sebelumnya
      </button>
      <button type="button" class="btn-nav btn-next" id="btnNext" onclick="nextQuestion()">
        Selanjutnya <i class="bi bi-arrow-right"></i>
      </button>
      <button type="submit" class="btn-nav btn-submit-final" id="btnSubmit" style="display:none;">
        <i class="bi bi-send-fill"></i> Kirim Jawaban
      </button>
    </div>
  </div>

  <!-- SIDEBAR -->
  <div class="exam-sidebar">
    <div class="timer-card">
      <div class="timer-label">Sisa Waktu</div>
      <div class="timer-display" id="timerDisplay">20:30</div>
      <div class="timer-bar-wrap"><div class="timer-bar" id="timerBar" style="width:100%;"></div></div>
    </div>
    <div class="progress-card">
      <div class="progress-label">Progress Jawaban</div>
      <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressBar" style="width:0%;"></div></div>
      <div class="progress-text"><span id="answeredCount">0</span> / <?= $total_soal ?> dijawab</div>
    </div>
    <div>
      <div class="nav-section-label" style="margin-bottom:12px;">Navigasi Soal</div>
      <div class="question-nav" id="questionNav">
        <?php for ($i = 1; $i <= count($soal_array); $i++):
          $soal_id  = $soal_array[$i-1]['id'];
          $answered = isset($jawaban_tersimpan[$soal_id]) ? 'answered' : '';
        ?>
        <div class="question-nav-item <?= $answered ?> <?= ($i===1)?'active':'' ?>"
          onclick="goToQuestion(<?= $i ?>)" data-question="<?= $i ?>"><?= $i ?></div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const totalQuestions = <?= count($soal_array) ?>;
  let currentQuestion  = 1;
  const TOTAL_SECONDS  = 20 * 60 + 30;
  let timeLeft         = TOTAL_SECONDS;

  const timerDisplay = document.getElementById('timerDisplay');
  const timerBar     = document.getElementById('timerBar');

  function startTimer() {
    setInterval(() => {
      if (timeLeft > 0) {
        timeLeft--;
        const m = Math.floor(timeLeft / 60);
        const s = timeLeft % 60;
        timerDisplay.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        timerBar.style.width = (timeLeft / TOTAL_SECONDS * 100) + '%';
        if (timeLeft <= 300) timerDisplay.classList.add('warning');
      } else {
        document.getElementById('examForm').submit();
      }
    }, 1000);
  }

  function updateButtons() {
    const btnPrev   = document.getElementById('btnPrev');
    const btnNext   = document.getElementById('btnNext');
    const btnSubmit = document.getElementById('btnSubmit');
    btnPrev.disabled = (currentQuestion === 1);
    if (currentQuestion === totalQuestions) {
      btnNext.style.display   = 'none';
      btnSubmit.style.display = 'flex';
    } else {
      btnNext.style.display   = 'flex';
      btnSubmit.style.display = 'none';
    }
  }

  function goToQuestion(n) {
    document.querySelectorAll('.question-section').forEach(el => el.classList.remove('active'));
    document.querySelector(`.question-section[data-question="${n}"]`).classList.add('active');
    document.querySelectorAll('.question-nav-item').forEach(el => el.classList.remove('active'));
    document.querySelector(`.question-nav-item[data-question="${n}"]`).classList.add('active');
    currentQuestion = n;
    updateButtons();
    document.querySelector('.exam-content').scrollTo({ top: 0, behavior: 'smooth' });
  }

  function nextQuestion()     { if (currentQuestion < totalQuestions) goToQuestion(currentQuestion + 1); }
  function previousQuestion() { if (currentQuestion > 1) goToQuestion(currentQuestion - 1); }

  function selectAnswer(label) {
    label.parentElement.querySelectorAll('.answer-option').forEach(el => el.classList.remove('selected'));
    label.classList.add('selected');
    label.querySelector('input[type="radio"]').checked = true;
    document.querySelectorAll('.question-nav-item')[currentQuestion - 1].classList.add('answered');
    updateProgress();
  }

  function updateProgress() {
    const answered = document.querySelectorAll('.question-nav-item.answered').length;
    document.getElementById('answeredCount').textContent = answered;
    document.getElementById('progressBar').style.width = (answered / totalQuestions * 100) + '%';
  }

  updateButtons();
  updateProgress();
  startTimer();
</script>
</body>
</html>