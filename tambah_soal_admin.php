<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $pertanyaan  = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
    $pilihan_a   = mysqli_real_escape_string($koneksi, $_POST['pilihan_a']);
    $pilihan_b   = mysqli_real_escape_string($koneksi, $_POST['pilihan_b']);
    $pilihan_c   = mysqli_real_escape_string($koneksi, $_POST['pilihan_c']);
    $pilihan_d   = mysqli_real_escape_string($koneksi, $_POST['pilihan_d']);
    $jawaban_benar = mysqli_real_escape_string($koneksi, $_POST['jawaban_benar']);
    $gambar_name = null;

    if (empty($pertanyaan) || empty($pilihan_a) || empty($pilihan_b) || empty($pilihan_c) || empty($pilihan_d) || empty($jawaban_benar)) {
        $error = "Semua field wajib diisi!";
    } else {
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
                $error = "Gagal mengupload gambar.";
            } else {
                $allowed = ['jpg','jpeg','png','gif','webp'];
                $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = "Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
                } elseif ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
                    $error = "Ukuran gambar terlalu besar. Maksimal 2MB.";
                } else {
                    $upload_dir = __DIR__ . '/uploads/soal/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $new_name = 'soal_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $new_name)) {
                        $gambar_name = 'uploads/soal/' . $new_name;
                    } else {
                        $error = "Gagal menyimpan gambar.";
                    }
                }
            }
        }

        if (empty($error)) {
            $gambar_sql = $gambar_name ? "'" . mysqli_real_escape_string($koneksi, $gambar_name) . "'" : "NULL";
            $q = "INSERT INTO soal (pertanyaan, gambar, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar)
                  VALUES ('$pertanyaan', $gambar_sql, '$pilihan_a', '$pilihan_b', '$pilihan_c', '$pilihan_d', '$jawaban_benar')";
            if (mysqli_query($koneksi, $q)) {
                $success = "Soal berhasil ditambahkan!";
                $_POST = [];
            } else {
                $error = "Gagal menyimpan: " . mysqli_error($koneksi);
            }
        }
    }
}

$opts = ['A', 'B', 'C', 'D'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Soal — Admin Panel</title>
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

    /* Form card */
    .form-card{background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-card);overflow:hidden;width:100%;max-width:820px;animation:fadeUp 0.5s ease-out both;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

    .form-card-header{background:linear-gradient(135deg,var(--navy),var(--navy-mid));padding:26px 36px;}
    .form-card-eyebrow{font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--orange);margin-bottom:4px;display:flex;align-items:center;gap:8px;}
    .form-card-eyebrow::before{content:'';width:20px;height:2px;background:var(--orange);}
    .form-card-title{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:white;}

    .form-body{padding:36px;}

    /* Alerts */
    .alert-c{border-radius:14px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:12px;font-size:13px;line-height:1.6;}
    .alert-error-c{background:rgba(139,26,26,0.07);border:1px solid rgba(220,53,69,0.2);color:#8b1a1a;}
    .alert-success-c{background:rgba(39,174,96,0.07);border:1px solid rgba(39,174,96,0.2);color:#1a6e3c;}
    .alert-c i{font-size:18px;flex-shrink:0;margin-top:1px;}
    .alert-error-c i{color:#dc3545;}
    .alert-success-c i{color:#27ae60;}

    /* Section label */
    .section-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--navy-mid);margin-bottom:12px;display:flex;align-items:center;gap:10px;}
    .section-label::after{content:'';flex:1;height:1px;background:rgba(13,31,53,0.07);}

    /* Pertanyaan area */
    .pertanyaan-wrap{display:flex;gap:16px;align-items:flex-start;margin-bottom:32px;}
    .pertanyaan-badge{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--orange),var(--orange-dark));display:flex;align-items:center;justify-content:center;font-size:24px;color:white;flex-shrink:0;}
    .pertanyaan-box{flex:1;background:linear-gradient(135deg,var(--navy),var(--navy-mid));border-radius:16px;padding:4px;}
    .pertanyaan-textarea{width:100%;background:rgba(255,255,255,0.07);border:none;border-radius:12px;color:white;padding:16px 18px;font-size:14px;font-family:'DM Sans',sans-serif;line-height:1.7;resize:vertical;min-height:110px;}
    .pertanyaan-textarea::placeholder{color:rgba(255,255,255,0.4);}
    .pertanyaan-textarea:focus{outline:none;background:rgba(255,255,255,0.11);box-shadow:0 0 0 2px rgba(255,152,0,0.3);}

    /* Image upload */
    .upload-zone{border:2px dashed rgba(13,31,53,0.15);border-radius:14px;padding:24px;text-align:center;cursor:pointer;transition:var(--transition);margin-bottom:32px;position:relative;}
    .upload-zone:hover{border-color:var(--orange);background:rgba(255,152,0,0.03);}
    .upload-zone.has-file{border-color:var(--green-light);background:rgba(39,174,96,0.04);}
    .upload-zone input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
    .upload-icon{font-size:32px;color:rgba(13,31,53,0.2);margin-bottom:8px;}
    .upload-text{font-size:13px;color:#8898aa;}
    .upload-text strong{color:var(--navy-mid);}
    .upload-preview{max-height:160px;border-radius:10px;margin-top:12px;object-fit:contain;display:none;}

    /* Options */
    .options-list{display:flex;flex-direction:column;gap:12px;margin-bottom:32px;}
    .option-row{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--off-white);border-radius:14px;border:2px solid rgba(13,31,53,0.07);transition:var(--transition);}
    .option-row:hover{border-color:rgba(255,152,0,0.3);background:rgba(255,152,0,0.03);}
    .option-row.selected{border-color:var(--orange);background:rgba(255,152,0,0.06);}

    .option-letter{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--orange);flex-shrink:0;}
    .option-row.selected .option-letter{background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:white;}

    .option-input{flex:1;border:none;background:transparent;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--navy);padding:0;}
    .option-input::placeholder{color:#aab4be;}
    .option-input:focus{outline:none;}

    .option-radio{display:none;}
    .option-check-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid rgba(13,31,53,0.12);background:white;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition);flex-shrink:0;font-size:16px;color:rgba(13,31,53,0.2);}
    .option-row.selected .option-check-btn{background:var(--green-light);border-color:var(--green-light);color:white;}

    /* Buttons */
    .btn-row{display:flex;gap:12px;justify-content:flex-end;}
    .btn-cancel{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:50px;background:rgba(13,31,53,0.06);color:var(--navy);border:1px solid rgba(13,31,53,0.12);font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;text-decoration:none;transition:var(--transition);}
    .btn-cancel:hover{background:rgba(13,31,53,0.12);color:var(--navy);}
    .btn-submit{display:inline-flex;align-items:center;gap:8px;padding:13px 32px;border-radius:50px;background:linear-gradient(135deg,var(--orange),var(--orange-dark));color:white;border:none;font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;transition:var(--transition);box-shadow:0 4px 16px rgba(255,152,0,0.3);}
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,152,0,0.45);}

    /* Upload hint */
    .upload-hint{font-size:11px;color:#aab4be;margin-top:8px;}

    @media(max-width:768px){
      :root{--sidebar-w:0px;}
      .sidebar{transform:translateX(-100%);}
      .main-wrap{margin-left:0;}
      .page-content{padding:24px 16px 48px;}
      .topbar{padding:16px 20px;}
      .form-body{padding:24px 20px;}
      .pertanyaan-wrap{flex-direction:column;}
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
    <a href="bank_soal_admin.php" class="sidebar-link active">
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
    <a href="logout_admin.php" class="sidebar-logout" onclick="return confirm('Yakin ingin logout?');">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</aside>

<!-- ── MAIN ── -->
<div class="main-wrap">

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-eyebrow">Bank Soal</div>
      <div class="topbar-title">Tambah Soal Baru</div>
    </div>
    <a href="bank_soal_admin.php" class="btn-back">
      <i class="bi bi-arrow-left"></i> Kembali ke Bank Soal
    </a>
  </div>

  <!-- Content -->
  <div class="page-content">
    <div class="form-card">

      <div class="form-card-header">
        <div class="form-card-eyebrow">Input Soal</div>
        <div class="form-card-title">Buat Pertanyaan Ujian Baru</div>
      </div>

      <div class="form-body">

        <?php if ($error): ?>
        <div class="alert-c alert-error-c">
          <i class="bi bi-exclamation-circle-fill"></i>
          <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert-c alert-success-c">
          <i class="bi bi-check-circle-fill"></i>
          <div><?php echo htmlspecialchars($success); ?> <a href="bank_soal_admin.php" style="color:var(--green);font-weight:700;">Lihat semua soal →</a></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" id="soalForm">
          <input type="hidden" name="action" value="tambah">

          <!-- Pertanyaan -->
          <div class="section-label">Pertanyaan</div>
          <div class="pertanyaan-wrap">
            <div class="pertanyaan-badge"><i class="bi bi-question-lg"></i></div>
            <div class="pertanyaan-box">
              <textarea class="pertanyaan-textarea" name="pertanyaan"
                placeholder="Tulis pertanyaan soal di sini..." required><?php echo isset($_POST['pertanyaan']) ? htmlspecialchars($_POST['pertanyaan']) : ''; ?></textarea>
            </div>
          </div>

          <!-- Gambar -->
          <div class="section-label">Gambar Soal <span style="font-weight:400;opacity:0.5;text-transform:none;letter-spacing:0;">(opsional)</span></div>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="gambar" id="gambarInput" accept="image/*">
            <div id="uploadPlaceholder">
              <div class="upload-icon"><i class="bi bi-image"></i></div>
              <div class="upload-text"><strong>Klik untuk upload</strong> atau drag & drop gambar</div>
              <div class="upload-hint">JPG, PNG, GIF, WEBP • Maks. 2MB</div>
            </div>
            <img id="uploadPreview" class="upload-preview" alt="Preview">
          </div>

          <!-- Pilihan Jawaban -->
          <div class="section-label">Pilihan Jawaban <span style="font-weight:400;opacity:0.5;text-transform:none;letter-spacing:0;">(pilih yang benar)</span></div>
          <div class="options-list" id="optionsList">
            <?php foreach ($opts as $huruf): ?>
            <?php
              $key     = 'pilihan_' . strtolower($huruf);
              $val     = isset($_POST[$key]) ? htmlspecialchars($_POST[$key]) : '';
              $checked = isset($_POST['jawaban_benar']) && $_POST['jawaban_benar'] === $huruf;
            ?>
            <div class="option-row <?php echo $checked ? 'selected' : ''; ?>" id="row_<?php echo $huruf; ?>" onclick="selectOption('<?php echo $huruf; ?>')">
              <div class="option-letter"><?php echo $huruf; ?></div>
              <input type="text" class="option-input" name="<?php echo $key; ?>"
                placeholder="Pilihan <?php echo $huruf; ?>..."
                value="<?php echo $val; ?>" required
                onclick="event.stopPropagation()">
              <input type="radio" class="option-radio" name="jawaban_benar" value="<?php echo $huruf; ?>"
                id="radio_<?php echo $huruf; ?>" <?php echo $checked ? 'checked' : ''; ?>>
              <div class="option-check-btn" title="Tandai sebagai jawaban benar">
                <i class="bi bi-check-lg"></i>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Buttons -->
          <div class="btn-row">
            <a href="bank_soal_admin.php" class="btn-cancel">
              <i class="bi bi-x-lg"></i> Batal
            </a>
            <button type="submit" class="btn-submit">
              <i class="bi bi-send-fill"></i> Simpan Soal
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Select option
  function selectOption(huruf) {
    document.querySelectorAll('.option-row').forEach(r => r.classList.remove('selected'));
    document.getElementById('row_' + huruf).classList.add('selected');
    document.getElementById('radio_' + huruf).checked = true;
  }

  // Image preview
  const gambarInput   = document.getElementById('gambarInput');
  const uploadPreview = document.getElementById('uploadPreview');
  const uploadZone    = document.getElementById('uploadZone');
  const placeholder   = document.getElementById('uploadPlaceholder');

  gambarInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = e => {
        uploadPreview.src = e.target.result;
        uploadPreview.style.display = 'block';
        placeholder.style.display  = 'none';
        uploadZone.classList.add('has-file');
      };
      reader.readAsDataURL(this.files[0]);
    }
  });

  // Drag & drop
  uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.style.borderColor = 'var(--orange)'; });
  uploadZone.addEventListener('dragleave',  () => uploadZone.style.borderColor = '');
  uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.style.borderColor = '';
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
      const dt = new DataTransfer();
      dt.items.add(file);
      gambarInput.files = dt.files;
      gambarInput.dispatchEvent(new Event('change'));
    }
  });
</script>
</body>
</html>