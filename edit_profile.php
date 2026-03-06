<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$success_message = "";
$error_message   = "";

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt  = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = htmlspecialchars($_POST['first_name'] ?? '');
    $last_name  = htmlspecialchars($_POST['last_name']  ?? '');
    $about      = htmlspecialchars($_POST['about']      ?? '');

    $photo_name = $user['photo'] ?? null;

    if ($_FILES['photo']['name']) {
        $foto_name  = $_FILES['photo']['name'];
        $tmp_name   = $_FILES['photo']['tmp_name'];
        $file_type  = mime_content_type($tmp_name);
        $allowed    = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($file_type, $allowed)) {
            $error_message = "Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF.";
        } else {
            $folder   = "uploads/foto/";
            $new_name = time() . "_" . $foto_name;
            if (move_uploaded_file($tmp_name, $folder . $new_name)) {
                if ($user['photo'] && file_exists($folder . $user['photo'])) {
                    unlink($folder . $user['photo']);
                }
                $photo_name = $new_name;
            } else {
                $error_message = "Gagal upload foto.";
            }
        }
    }

    if (!$error_message) {
        $upd = "UPDATE users SET first_name=?, last_name=?, about=?, photo=? WHERE id=?";
        $upd_stmt = mysqli_prepare($koneksi, $upd);
        mysqli_stmt_bind_param($upd_stmt, "ssssi", $first_name, $last_name, $about, $photo_name, $user_id);

        if (mysqli_stmt_execute($upd_stmt)) {
            $success_message = "Profil berhasil diperbarui!";
            $_SESSION['user_name'] = $first_name;
            $user['first_name'] = $first_name;
            $user['last_name']  = $last_name;
            $user['about']      = $about;
            $user['photo']      = $photo_name;
        } else {
            $error_message = "Gagal memperbarui profil: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($upd_stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Profil — Universitas Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    /* =============================================
       ROOT & BASE
       ============================================= */
    :root {
      --navy:         #0d1f35;
      --navy-mid:     #1e3a5f;
      --navy-light:   #2d5a7b;
      --orange:       #ff9800;
      --orange-dark:  #e68900;
      --gold:         #f0c060;
      --white:        #ffffff;
      --off-white:    #f7f5f0;
      --text-muted:   rgba(255,255,255,0.6);
      --glass:        rgba(255,255,255,0.06);
      --glass-border: rgba(255,255,255,0.12);
      --shadow-deep:  0 24px 64px rgba(13,31,53,0.35);
      --shadow-card:  0 8px 32px rgba(13,31,53,0.18);
      --radius-lg:    20px;
      --radius-xl:    32px;
      --transition:   all 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off-white);
      color: var(--navy);
      overflow-x: hidden;
    }

    /* =============================================
       NAVBAR
       ============================================= */
    .navbar {
      background: rgba(13,31,53,0.92);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--glass-border);
      padding: 14px 0;
      position: sticky;
      top: 0;
      z-index: 1000;
      transition: var(--transition);
    }

    .navbar.scrolled {
      background: rgba(13,31,53,0.98);
      padding: 10px 0;
    }

    .navbar-brand {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 22px;
      color: white !important;
      display: flex;
      align-items: center;
      gap: 4px;
      letter-spacing: 0.5px;
      text-decoration: none;
    }

    .navbar-logo {
      width: 52px;
      height: 52px;
      object-fit: contain;
    }

    .profile-dropdown { position: relative; display: inline-block; }

    .profile-btn {
      background: var(--glass);
      border: 1px solid var(--glass-border);
      color: white;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      padding: 8px 16px 8px 8px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-radius: 50px;
      transition: var(--transition);
      font-family: 'DM Sans', sans-serif;
    }

    .profile-btn:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.25); }

    .profile-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--orange);
    }

    .profile-icon-circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--navy-light), var(--orange));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }

    .dropdown-menu-custom {
      position: absolute;
      top: calc(100% + 10px);
      right: 0;
      background: var(--white);
      border: 1px solid rgba(13,31,53,0.1);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-deep);
      min-width: 220px;
      z-index: 1001;
      display: none;
      overflow: hidden;
      animation: dropDown 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    @keyframes dropDown {
      from { opacity:0; transform:translateY(-8px); }
      to   { opacity:1; transform:translateY(0); }
    }

    .dropdown-menu-custom.show { display: block; }

    .dropdown-menu-custom a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 13px 18px;
      color: var(--navy);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: var(--transition);
      border-bottom: 1px solid rgba(13,31,53,0.06);
    }

    .dropdown-menu-custom a:last-child { border-bottom: none; }
    .dropdown-menu-custom a:hover { background: var(--off-white); padding-left: 22px; }
    .dropdown-menu-custom a i { color: var(--navy-mid); font-size: 16px; }

    .dropdown-menu-custom .user-email {
      padding: 14px 18px;
      color: #888;
      font-size: 12px;
      border-bottom: 1px solid rgba(13,31,53,0.08);
      background: var(--off-white);
    }

    /* =============================================
       PAGE LAYOUT
       ============================================= */
    .page-wrapper {
      max-width: 860px;
      margin: 48px auto;
      padding: 0 20px 60px;
    }

    /* Breadcrumb / back */
    .page-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--navy-mid);
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 28px;
      padding: 9px 18px;
      background: white;
      border-radius: 50px;
      border: 1px solid rgba(13,31,53,0.1);
      box-shadow: var(--shadow-card);
      transition: var(--transition);
    }

    .page-back:hover {
      background: var(--navy);
      color: white;
      border-color: var(--navy);
      transform: translateX(-4px);
    }

    /* =============================================
       PROFILE CARD
       ============================================= */
    .profile-card {
      background: white;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-card);
      overflow: hidden;
      animation: fadeUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Card Header — dark band */
    .profile-card-header {
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
      padding: 40px 48px 36px;
      position: relative;
      overflow: hidden;
    }

    .profile-card-header::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--navy-light), var(--orange), var(--gold), var(--orange), var(--navy-light));
    }

    .profile-card-header::after {
      content: '';
      position: absolute;
      top: -80px; right: -80px;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255,152,0,0.1) 0%, transparent 70%);
      pointer-events: none;
    }

    .header-inner {
      display: flex;
      align-items: center;
      gap: 28px;
      position: relative;
      z-index: 1;
    }

    /* Photo upload area */
    .photo-upload-area {
      position: relative;
      flex-shrink: 0;
    }

    .photo-preview {
      width: 110px;
      height: 110px;
      border-radius: 20px;
      object-fit: cover;
      border: 3px solid rgba(255,255,255,0.2);
      display: block;
      box-shadow: 0 8px 28px rgba(0,0,0,0.3);
      transition: var(--transition);
    }

    .photo-preview:hover { transform: scale(1.03); }

    .photo-placeholder {
      width: 110px;
      height: 110px;
      border-radius: 20px;
      background: linear-gradient(135deg, var(--navy-light), var(--orange));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 44px;
      border: 3px solid rgba(255,255,255,0.15);
      box-shadow: 0 8px 28px rgba(0,0,0,0.3);
    }

    .photo-upload-btn {
      position: absolute;
      bottom: -10px;
      right: -10px;
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, var(--orange), var(--orange-dark));
      color: white;
      border: none;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(255,152,0,0.45);
      transition: var(--transition);
    }

    .photo-upload-btn:hover {
      transform: scale(1.15);
      box-shadow: 0 6px 20px rgba(255,152,0,0.6);
    }

    input[type="file"] { display: none; }

    .header-text { color: white; }

    .header-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--orange);
      margin-bottom: 10px;
    }

    .header-eyebrow::before {
      content: '';
      width: 24px;
      height: 2px;
      background: var(--orange);
    }

    .header-text h1 {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
      font-size: 34px;
      line-height: 1.15;
      color: white;
      margin-bottom: 6px;
    }

    .header-text p {
      font-size: 13px;
      color: rgba(255,255,255,0.55);
      line-height: 1.6;
    }

    /* =============================================
       FORM BODY
       ============================================= */
    .profile-card-body {
      padding: 44px 48px 48px;
    }

    /* Alert banners */
    .alert-banner {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      border-radius: var(--radius-lg);
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 28px;
      border: 1px solid transparent;
    }

    .alert-banner i { font-size: 18px; flex-shrink: 0; }

    .alert-success {
      background: #edfbf3;
      color: #1a6e3c;
      border-color: #b2e8cc;
    }

    .alert-error {
      background: #fef2f2;
      color: #9b1c1c;
      border-color: #fecaca;
    }

    /* Section divider */
    .form-section-label {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: var(--navy-light);
      margin-bottom: 22px;
    }

    .form-section-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(13,31,53,0.1);
    }

    /* Form groups */
    .form-group {
      margin-bottom: 22px;
    }

    .form-group label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--navy-light);
      margin-bottom: 9px;
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(13,31,53,0.3);
      font-size: 15px;
      pointer-events: none;
      transition: var(--transition);
    }

    .input-icon-top {
      position: absolute;
      left: 16px;
      top: 16px;
      color: rgba(13,31,53,0.3);
      font-size: 15px;
      pointer-events: none;
      transition: var(--transition);
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 13px 18px 13px 44px;
      background: var(--off-white);
      border: 1px solid rgba(13,31,53,0.1);
      border-radius: 12px;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      color: var(--navy);
      transition: var(--transition);
    }

    .form-group textarea {
      padding: 13px 18px 13px 44px;
      resize: vertical;
      min-height: 120px;
      line-height: 1.65;
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
      color: rgba(13,31,53,0.3);
      font-size: 13px;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--orange);
      background: white;
      box-shadow: 0 0 0 3px rgba(255,152,0,0.1);
    }

    .input-wrapper:focus-within .input-icon,
    .input-wrapper:focus-within .input-icon-top { color: var(--orange); }

    /* Read-only email field */
    .form-group input[readonly] {
      opacity: 0.55;
      cursor: not-allowed;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }

    /* Divider */
    .form-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(13,31,53,0.1), transparent);
      margin: 32px 0;
    }

    /* Button group */
    .button-group {
      display: flex;
      gap: 14px;
      justify-content: flex-end;
      margin-top: 36px;
    }

    .btn-cancel {
      padding: 13px 28px;
      background: transparent;
      color: var(--navy-mid);
      border: 1px solid rgba(13,31,53,0.15);
      border-radius: 50px;
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-cancel:hover {
      background: var(--off-white);
      border-color: rgba(13,31,53,0.3);
      color: var(--navy);
    }

    .btn-save {
      padding: 13px 36px;
      background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
      color: white;
      border: none;
      border-radius: 50px;
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 9px;
      box-shadow: 0 8px 28px rgba(255,152,0,0.35);
    }

    .btn-save:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 36px rgba(255,152,0,0.5);
    }

    .btn-save:active { transform: translateY(-1px); }

    /* =============================================
       RESPONSIVE
       ============================================= */
    @media (max-width: 680px) {
      .profile-card-header { padding: 32px 24px 28px; }
      .header-inner { flex-direction: column; align-items: flex-start; gap: 20px; }
      .profile-card-body { padding: 32px 24px; }
      .form-row { grid-template-columns: 1fr; }
      .button-group { flex-direction: column-reverse; }
      .btn-cancel, .btn-save { width: 100%; justify-content: center; }
      .page-wrapper { padding: 0 14px 40px; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
    }
  </style>
</head>
<body>

<!-- ============ NAVBAR ============ -->
<nav class="navbar navbar-expand-lg" id="mainNav">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="index.php">
      <img src="LOGORBG.png" alt="Logo" class="navbar-logo">
      <span>Universitas Nusantara</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <div class="profile-dropdown">
        <button class="profile-btn" onclick="toggleDropdown()">
          <?php if (!empty($user['photo'])): ?>
            <img src="uploads/foto/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile" class="profile-avatar">
          <?php else: ?>
            <span class="profile-icon-circle"><i class="bi bi-person-fill"></i></span>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($user_name); ?></span>
          <i class="bi bi-chevron-down" style="font-size:11px; opacity:0.7;"></i>
        </button>
        <div class="dropdown-menu-custom" id="dropdownMenu">
          <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
          <a href="edit_profile.php"><i class="bi bi-person-gear"></i> Edit Profil</a>
          <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- ============ PAGE CONTENT ============ -->
<div class="page-wrapper">

  <a href="index.php" class="page-back">
    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
  </a>

  <div class="profile-card">

    <!-- Header -->
    <div class="profile-card-header">
      <div class="header-inner">

        <!-- Photo -->
        <div class="photo-upload-area">
          <?php if (!empty($user['photo'])): ?>
            <img src="uploads/foto/<?php echo htmlspecialchars($user['photo']); ?>"
                 alt="Profile" class="photo-preview" id="photoPreview">
          <?php else: ?>
            <div class="photo-placeholder" id="photoPlaceholder">
              <i class="bi bi-person"></i>
            </div>
          <?php endif; ?>
          <button type="button" class="photo-upload-btn"
                  onclick="document.getElementById('photoInput').click()"
                  title="Ubah Foto">
            <i class="bi bi-camera"></i>
          </button>
        </div>

        <!-- Title -->
        <div class="header-text">
          <div class="header-eyebrow">Akun Mahasiswa</div>
          <h1>Edit Profil</h1>
          <p>Perbarui informasi dan foto profil Anda.</p>
        </div>

      </div>
    </div>

    <!-- Form Body -->
    <div class="profile-card-body">

      <?php if ($success_message): ?>
        <div class="alert-banner alert-success">
          <i class="bi bi-check-circle-fill"></i>
          <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="alert-banner alert-error">
          <i class="bi bi-exclamation-circle-fill"></i>
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="file" id="photoInput" name="photo" accept="image/*"
               onchange="previewPhoto(this)">

        <div class="form-section-label">Informasi Pribadi</div>

        <div class="form-row">
          <div class="form-group">
            <label for="first_name">Nama Depan</label>
            <div class="input-wrapper">
              <input type="text" id="first_name" name="first_name"
                     value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                     placeholder="Nama depan" required>
              <i class="bi bi-person input-icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="last_name">Nama Belakang</label>
            <div class="input-wrapper">
              <input type="text" id="last_name" name="last_name"
                     value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                     placeholder="Nama belakang" required>
              <i class="bi bi-person input-icon"></i>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrapper">
            <input type="email" id="email"
                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                   readonly>
            <i class="bi bi-envelope input-icon"></i>
          </div>
        </div>

        <div class="form-divider"></div>

        <div class="form-section-label">Tentang Saya</div>

        <div class="form-group">
          <label for="about">Bio Singkat</label>
          <div class="input-wrapper">
            <textarea id="about" name="about"
                      placeholder="Ceritakan sedikit tentang diri Anda..."><?php echo htmlspecialchars($user['about'] ?? ''); ?></textarea>
            <i class="bi bi-chat-square-text input-icon-top"></i>
          </div>
        </div>

        <!-- Buttons -->
        <div class="button-group">
          <a href="index.php" class="btn-cancel">
            <i class="bi bi-x-circle"></i> Batal
          </a>
          <button type="submit" class="btn-save">
            <i class="bi bi-check-circle"></i> Simpan Perubahan
          </button>
        </div>

      </form>
    </div><!-- /card-body -->
  </div><!-- /profile-card -->

</div><!-- /page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Navbar scroll effect
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav')
      .classList.toggle('scrolled', window.scrollY > 30);
  });

  // Profile dropdown
  function toggleDropdown() {
    document.getElementById('dropdownMenu')?.classList.toggle('show');
  }

  document.addEventListener('click', function(e) {
    const pd = document.querySelector('.profile-dropdown');
    const dm = document.getElementById('dropdownMenu');
    if (pd && dm && !pd.contains(e.target)) dm.classList.remove('show');
  });

  // Photo preview
  function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
      const area    = document.querySelector('.photo-upload-area');
      const preview = document.getElementById('photoPreview');
      const holder  = document.getElementById('photoPlaceholder');

      if (preview) {
        preview.src = e.target.result;
      } else {
        const img = document.createElement('img');
        img.src       = e.target.result;
        img.className = 'photo-preview';
        img.id        = 'photoPreview';
        if (holder) holder.replaceWith(img);
        else area.insertBefore(img, area.firstChild);
      }
    };
    reader.readAsDataURL(input.files[0]);
  }
</script>
</body>
</html>