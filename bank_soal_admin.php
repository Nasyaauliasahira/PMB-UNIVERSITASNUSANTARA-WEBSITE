<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

$query  = "SELECT * FROM soal ORDER BY id ASC";
$result = mysqli_query($koneksi, $query);
$soal   = mysqli_fetch_all($result, MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];
    mysqli_query($koneksi, "DELETE FROM soal WHERE id = $id");
    header("Location: bank_soal_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Soal — Admin Panel</title>
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
      --shadow-card: 0 8px 32px rgba(13,31,53,0.12);
      --radius-lg:   18px;
      --radius-xl:   26px;
      --transition:  all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    }

    *,*::before,*::after { margin:0; padding:0; box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); min-height:100vh; }

    /* ── SIDEBAR ── */
    .sidebar {
      width: var(--sidebar-w);
      height: 100vh;
      background: linear-gradient(175deg, var(--navy) 0%, #0a1828 100%);
      position: fixed; left:0; top:0; z-index:200;
      display: flex; flex-direction:column;
      border-right: 1px solid rgba(255,255,255,0.06);
      overflow: hidden;
    }
    .sidebar::before {
      content:'';
      position:absolute; top:-100px; right:-80px;
      width:260px; height:260px; border-radius:50%;
      background: radial-gradient(circle, rgba(255,152,0,0.08) 0%, transparent 70%);
      pointer-events:none;
    }

    .sidebar-brand {
      padding: 28px 24px 24px;
      display: flex; align-items:center; gap:12px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      flex-shrink: 0;
    }
    .sidebar-brand img { width:46px; height:46px; object-fit:contain; }
    .sidebar-brand-text h3 {
      font-family:'Cormorant Garamond',serif;
      font-size:16px; font-weight:700; color:white; line-height:1.2;
    }
    .sidebar-brand-text span { font-size:10px; color:var(--orange); font-weight:700; letter-spacing:2px; text-transform:uppercase; }

    .sidebar-nav { padding:20px 0; flex:1; overflow-y:auto; }

    .nav-section-label {
      padding: 6px 24px 10px;
      font-size: 9px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase;
      color: rgba(255,255,255,0.25);
    }

    .sidebar-link {
      display: flex; align-items:center; gap:13px;
      padding: 12px 24px;
      color: rgba(255,255,255,0.55);
      text-decoration: none;
      font-size: 13px; font-weight:500;
      transition: var(--transition);
      border-left: 3px solid transparent;
      position: relative;
    }
    .sidebar-link:hover {
      background: rgba(255,255,255,0.05);
      color: rgba(255,255,255,0.9);
      border-left-color: rgba(255,152,0,0.4);
      padding-left: 28px;
    }
    .sidebar-link.active {
      background: rgba(255,152,0,0.1);
      color: white;
      border-left-color: var(--orange);
    }
    .sidebar-link.active .link-icon {
      background: var(--orange);
      color: white;
    }
    .link-icon {
      width:34px; height:34px; border-radius:9px;
      background: rgba(255,255,255,0.07);
      display:flex; align-items:center; justify-content:center;
      font-size:15px; flex-shrink:0;
      transition: var(--transition);
    }
    .sidebar-link:hover .link-icon { background:rgba(255,152,0,0.2); color:var(--orange); }

    .sidebar-footer {
      padding: 20px 24px;
      border-top: 1px solid rgba(255,255,255,0.06);
      flex-shrink: 0;
    }
    .sidebar-logout {
      display:flex; align-items:center; gap:10px;
      padding:11px 16px; border-radius:12px;
      background: rgba(220,53,69,0.1); border:1px solid rgba(220,53,69,0.2);
      color:#f87171; text-decoration:none; font-size:13px; font-weight:600;
      transition:var(--transition); cursor:pointer;
    }
    .sidebar-logout:hover { background:rgba(220,53,69,0.2); color:#fca5a5; }

    /* ── MAIN ── */
    .main-wrap {
      margin-left: var(--sidebar-w);
      min-height: 100vh;
      display: flex; flex-direction:column;
    }

    /* Topbar */
    .topbar {
      background: white;
      border-bottom: 1px solid rgba(13,31,53,0.07);
      padding: 18px 36px;
      display: flex; align-items:center; justify-content:space-between;
      position: sticky; top:0; z-index:100;
      box-shadow: 0 2px 12px rgba(13,31,53,0.06);
    }
    .topbar-left { display:flex; flex-direction:column; gap:2px; }
    .topbar-eyebrow {
      font-size:10px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase; color:var(--orange);
    }
    .topbar-title {
      font-family:'Cormorant Garamond',serif;
      font-size:26px; font-weight:700; color:var(--navy); line-height:1;
    }
    .topbar-right { display:flex; align-items:center; gap:12px; }
    .topbar-count {
      background:var(--off-white); border:1px solid rgba(13,31,53,0.1);
      border-radius:50px; padding:7px 16px;
      font-size:12px; font-weight:700; color:var(--navy-mid);
      display:flex; align-items:center; gap:6px;
    }
    .topbar-count i { color:var(--orange); }

    /* Add button */
    .btn-add {
      display:inline-flex; align-items:center; gap:8px;
      padding:11px 22px; border-radius:50px;
      background:linear-gradient(135deg,var(--orange),var(--orange-dark));
      color:white; border:none; cursor:pointer;
      font-weight:700; font-size:13px; font-family:'DM Sans',sans-serif;
      transition:var(--transition);
      box-shadow:0 4px 16px rgba(255,152,0,0.3);
      text-decoration:none;
    }
    .btn-add:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(255,152,0,0.45); color:white; }

    /* ── PAGE CONTENT ── */
    .page-content { padding:32px 36px 60px; }

    /* ── SOAL CARD ── */
    .soal-list { display:flex; flex-direction:column; gap:20px; }

    .soal-card {
      background:white;
      border-radius:var(--radius-xl);
      box-shadow:var(--shadow-card);
      overflow:hidden;
      transition:var(--transition);
      animation:fadeUp 0.5s ease-out both;
    }
    .soal-card:hover { transform:translateY(-3px); box-shadow:0 16px 48px rgba(13,31,53,0.16); }

    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

    /* Card header band */
    .soal-card-header {
      background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);
      padding:18px 24px;
      display:flex; align-items:center; gap:16px;
    }
    .soal-num-badge {
      width:44px; height:44px; flex-shrink:0;
      background:linear-gradient(135deg,var(--orange),var(--orange-dark));
      border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      font-family:'Cormorant Garamond',serif;
      font-size:22px; font-weight:700; color:white;
    }
    .soal-meta { flex:1; }
    .soal-meta-label { font-size:9px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.4); margin-bottom:2px; }
    .soal-pertanyaan-text { font-size:15px; font-weight:600; color:white; line-height:1.5; }

    /* Card body */
    .soal-card-body { padding:24px; }

    /* Image */
    .soal-img { margin-bottom:20px; border-radius:14px; overflow:hidden; border:1px solid rgba(13,31,53,0.08); }
    .soal-img img { width:100%; max-height:280px; object-fit:contain; display:block; background:var(--off-white); }

    /* Options grid */
    .options-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px; }
    .option-item {
      display:flex; align-items:flex-start; gap:10px;
      padding:12px 14px;
      background:var(--off-white);
      border:1px solid rgba(13,31,53,0.07);
      border-radius:12px;
      transition:var(--transition);
    }
    .option-item:hover { background:rgba(255,152,0,0.05); border-color:rgba(255,152,0,0.2); }
    .option-letter {
      width:30px; height:30px; flex-shrink:0;
      border-radius:8px;
      background:linear-gradient(135deg,var(--navy),var(--navy-mid));
      display:flex; align-items:center; justify-content:center;
      font-size:12px; font-weight:700; color:var(--orange);
    }
    .option-text { font-size:13px; color:var(--navy); line-height:1.5; padding-top:5px; flex:1; }

    /* Card footer */
    .soal-card-footer {
      padding:16px 24px;
      background:rgba(13,31,53,0.02);
      border-top:1px solid rgba(13,31,53,0.06);
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .kunci-badge {
      display:inline-flex; align-items:center; gap:8px;
      padding:8px 16px; border-radius:50px;
      background:rgba(39,174,96,0.1);
      border:1px solid rgba(39,174,96,0.25);
      font-size:12px; font-weight:700; color:#1a6e3c;
    }
    .kunci-badge i { color:#27ae60; }

    .card-actions { display:flex; gap:8px; }
    .btn-icon {
      width:38px; height:38px; border-radius:10px;
      border:none; cursor:pointer;
      display:flex; align-items:center; justify-content:center;
      font-size:15px; transition:var(--transition);
    }
    .btn-icon-edit {
      background:rgba(255,152,0,0.1); color:var(--orange);
      border:1px solid rgba(255,152,0,0.2);
    }
    .btn-icon-edit:hover { background:var(--orange); color:white; transform:scale(1.08); }
    .btn-icon-del {
      background:rgba(220,53,69,0.1); color:#dc3545;
      border:1px solid rgba(220,53,69,0.2);
    }
    .btn-icon-del:hover { background:#dc3545; color:white; transform:scale(1.08); }

    /* Empty state */
    .empty-state {
      text-align:center; padding:100px 40px;
      background:white; border-radius:var(--radius-xl);
      box-shadow:var(--shadow-card);
    }
    .empty-icon {
      width:80px; height:80px; border-radius:20px;
      background:rgba(13,31,53,0.05);
      display:flex; align-items:center; justify-content:center;
      font-size:36px; color:rgba(13,31,53,0.25);
      margin:0 auto 20px;
    }
    .empty-state h3 { font-family:'Cormorant Garamond',serif; font-size:26px; color:var(--navy); margin-bottom:8px; }
    .empty-state p  { font-size:14px; color:#8898aa; }

    /* Responsive */
    @media (max-width:1024px) {
      .options-grid { grid-template-columns:1fr; }
    }
    @media (max-width:768px) {
      :root { --sidebar-w: 0px; }
      .sidebar { transform:translateX(-100%); }
      .sidebar.open { transform:translateX(0); width:260px; }
      .main-wrap { margin-left:0; }
      .page-content { padding:24px 20px 48px; }
      .topbar { padding:16px 20px; }
    }
  </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
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
      <span class="link-icon"><i class="bi bi-speedometer2"></i></span>
      Dashboard
    </a>
    <a href="pendaftaran_admin.php" class="sidebar-link">
      <span class="link-icon"><i class="bi bi-clipboard-check"></i></span>
      Pendaftaran
    </a>
    <a href="bank_soal_admin.php" class="sidebar-link active">
      <span class="link-icon"><i class="bi bi-journal-bookmark"></i></span>
      Bank Soal
    </a>
    <a href="register_login_admin.php" class="sidebar-link">
      <span class="link-icon"><i class="bi bi-person-check"></i></span>
      Register & Login
    </a>
    <a href="status_admin.php" class="sidebar-link">
      <span class="link-icon"><i class="bi bi-card-checklist"></i></span>
      Status
    </a>
    <a href="daftar_ulang_admin.php" class="sidebar-link">
      <span class="link-icon"><i class="bi bi-person-badge"></i></span>
      Daftar Ulang
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
      <div class="topbar-eyebrow">Manajemen Konten</div>
      <div class="topbar-title">Bank Soal Ujian</div>
    </div>
    <div class="topbar-right">
      <div class="topbar-count">
        <i class="bi bi-journal-bookmark-fill"></i>
        <?php echo count($soal); ?> Soal
      </div>
      <a href="tambah_soal_admin.php" class="btn-add">
        <i class="bi bi-plus-lg"></i> Tambah Soal
      </a>
    </div>
  </div>

  <!-- Content -->
  <div class="page-content">

    <?php if (count($soal) > 0): ?>
    <div class="soal-list">
      <?php foreach ($soal as $idx => $d): ?>
      <div class="soal-card" style="animation-delay:<?php echo $idx * 0.06; ?>s">

        <!-- Header -->
        <div class="soal-card-header">
          <div class="soal-num-badge"><?php echo $idx + 1; ?></div>
          <div class="soal-meta">
            <div class="soal-meta-label">Pertanyaan</div>
            <div class="soal-pertanyaan-text"><?php echo htmlspecialchars($d['pertanyaan']); ?></div>
          </div>
        </div>

        <!-- Body -->
        <div class="soal-card-body">
          <?php if (!empty($d['gambar'])): ?>
          <div class="soal-img">
            <img src="<?php echo htmlspecialchars($d['gambar']); ?>" alt="Gambar soal">
          </div>
          <?php endif; ?>

          <div class="options-grid">
            <?php
            $opts = ['A'=>'pilihan_a','B'=>'pilihan_b','C'=>'pilihan_c','D'=>'pilihan_d'];
            foreach ($opts as $huruf => $key):
            ?>
            <div class="option-item">
              <div class="option-letter"><?php echo $huruf; ?></div>
              <div class="option-text"><?php echo htmlspecialchars($d[$key]); ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Footer -->
        <div class="soal-card-footer">
          <div class="kunci-badge">
            <i class="bi bi-check-circle-fill"></i>
            Kunci Jawaban: <?php echo strtoupper($d['jawaban_benar']); ?>
          </div>
          <div class="card-actions">
            <a href="edit_soal_admin.php?id=<?php echo $d['id']; ?>" class="btn-icon btn-icon-edit" title="Edit soal">
              <i class="bi bi-pencil"></i>
            </a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus soal ini?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
              <button type="submit" class="btn-icon btn-icon-del" title="Hapus soal">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
      <h3>Belum Ada Soal</h3>
      <p>Mulai tambahkan soal ujian untuk ditampilkan di sini.</p>
    </div>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>