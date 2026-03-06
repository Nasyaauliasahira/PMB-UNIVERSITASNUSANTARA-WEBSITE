<?php
session_start();
include "koneksi.php";

$login_error = "";
$registration_error = "";
$registration_success = "";

// Handle AJAX request untuk ambil nomor ujian dan fix jika NULL
if (isset($_POST['action']) && $_POST['action'] == 'get_nomor_ujian') {
    header('Content-Type: application/json');
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    
    $query = "SELECT id, nomor_ujian FROM pendaftaran WHERE email = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $response = ['success' => false, 'nomor_ujian' => null, 'message' => ''];
    
    if ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id'];
        $nomor_ujian = $row['nomor_ujian'];
        
        // Jika nomor_ujian NULL atau kosong, generate baru
        if (empty($nomor_ujian)) {
            $nomor_ujian_baru = 'PMB-2026-' . str_pad($id, 4, '0', STR_PAD_LEFT);
            
            // Update database
            $update_query = "UPDATE pendaftaran SET nomor_ujian = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($koneksi, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $nomor_ujian_baru, $id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $response['success'] = true;
                $response['nomor_ujian'] = $nomor_ujian_baru;
                $response['message'] = 'Nomor ujian berhasil di-generate!';
            } else {
                $response['success'] = false;
                $response['message'] = 'Gagal generate nomor ujian';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            // Sudah ada, tinggal return
            $response['success'] = true;
            $response['nomor_ujian'] = $nomor_ujian;
            $response['message'] = 'Nomor ujian ditemukan';
        }
    } else {
        $response['message'] = 'Email tidak ditemukan di database';
    }
    
    mysqli_stmt_close($stmt);
    echo json_encode($response);
    exit;
}

// Handle registration
if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);
    $jurusan = htmlspecialchars($_POST['jurusan']);
    $phone = htmlspecialchars($_POST['phone']);
    $school_level = htmlspecialchars($_POST['school_level']);
    
    // validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = "Email tidak valid";
    } else {
        // upload foto
        $foto_name = $_FILES['photo']['name'];
        $tmp_name = $_FILES['photo']['tmp_name'];
        
        // validasi tipe file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($tmp_name);
        
        if (!in_array($file_type, $allowed_types)) {
            $registration_error = "Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF";
        } else {
            $folder = "uploads/foto/";
            $new_name = time() . "_" . $foto_name;

            if (move_uploaded_file($tmp_name, $folder . $new_name)) {
                // Generate nomor ujian terlebih dahulu dengan format placeholder
                // Kita akan update dengan ID yang sebenarnya setelah INSERT
                $placeholder_nomor = 'TEMP-' . time();
                
                // simpan ke database menggunakan prepared statement
                $query = "INSERT INTO pendaftaran 
                    (first_name, last_name, email, jurusan, phone, school_level, nomor_ujian, photo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($koneksi, $query);
                mysqli_stmt_bind_param($stmt, "ssssssss", $first_name, $last_name, $email, $jurusan, $phone, $school_level, $placeholder_nomor, $new_name);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Ambil ID yang baru diinsert
                    $id = mysqli_insert_id($koneksi);
                    
                    // Generate nomor ujian dengan format PMB-2026-{id dengan padding 4 digit}
                    $nomor_ujian_baru = 'PMB-2026-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                    
                    // Update nomor ujian dengan nilai yang benar
                    $update_query = "UPDATE pendaftaran SET nomor_ujian = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($koneksi, $update_query);
                    
                    if (!$update_stmt) {
                        $registration_error = "Error prepare update: " . mysqli_error($koneksi);
                    } else {
                        mysqli_stmt_bind_param($update_stmt, "si", $nomor_ujian_baru, $id);
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            // Redirect ke halaman login setelah berhasil daftar
                            echo "<script>
                                alert('Pendaftaran berhasil! Nomor ujian Anda: " . $nomor_ujian_baru . "\\n\\nSilakan login dengan email dan nomor ujian Anda untuk mengikuti ujian.');
                                window.location.href = 'login_pendaftaran.php';
                            </script>";
                            exit;
                        } else {
                            $registration_error = "Error update nomor ujian: " . mysqli_error($koneksi);
                        }
                        mysqli_stmt_close($update_stmt);
                    }
                } else {
                    $registration_error = "Error: " . mysqli_error($koneksi);
                }
                mysqli_stmt_close($stmt);
            } else {
                $registration_error = "Gagal upload foto";
            }
        }
    }
}

// Handle form login submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $nomor_ujian = mysqli_real_escape_string($koneksi, $_POST['nomor_ujian']);

    $query = "SELECT * FROM pendaftaran WHERE email = ? AND nomor_ujian = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $nomor_ujian);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student_data = mysqli_fetch_assoc($result);

    if ($student_data) {
        $_SESSION['student_id'] = $student_data['id'];
        $_SESSION['student_name'] = $student_data['first_name'];
        $_SESSION['student_email'] = $student_data['email'];
        $_SESSION['nomor_ujian'] = $student_data['nomor_ujian'];
        header("Location: ujian.php");
        exit;
    } else {
        $login_error = "Email atau Nomor Ujian tidak ditemukan";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Ujian - SMK Negeri 65 Jakarta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary-color: #1e3a5f;
      --secondary-color: #2d5a7b;
      --accent-orange: #ff9800;
      --teal-color: #1a4d5c;
      --light-bg: #f8f9fa;
      --dark-bg: #2c3e50;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: var(--light-bg);
    }


    .registration-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      background: linear-gradient(135deg, var(--light-bg) 0%, #ffffff 100%);
      padding: 20px 0;
    }

    .registration-wrapper {
      background: var(--dark-bg);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
      max-width: 1400px;
      margin: 0 auto;
    }

    .registration-content {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: 0;
      align-items: center;
    }

    /* Left Side - Image */
    .registration-image {
      position: relative;
      height: 100%;
      min-height: 600px;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      overflow: hidden;
    }

    .registration-image::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: url('image.png');
      background-size: cover;
      background-position: center;
      opacity: 0.85;
    }

    .registration-image-overlay {
      position: relative;
      z-index: 2;
      text-align: left;
      padding-left: 0;
      width: 100%;
    }

    .registration-image-text {
      font-size: 60px;
      font-weight: 700;
      color: var(--accent-orange);
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
      line-height: 1.2;
      margin-left: 20px;
    }

    /* Right Side - Form */
    .registration-form-section {
      padding: 70px 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .registration-title {
      font-size: 38px;
      font-weight: 700;
      color: #ffff;
      margin-bottom: 40px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      font-size: 14px;
      color: #ffff;
      margin-bottom: 8px;
    }

    .form-control {
      padding: 14px 18px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: white;
      color: #333;
    }

    .form-control::placeholder {
      color: #999;
    }

    .form-control:focus {
      border-color: var(--accent-orange);
      box-shadow: 0 0 0 3px rgba(255, 140, 43, 0.1);
      background: white;
      color: #333;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .form-row-full {
      grid-column: 1 / -1;
    }

    /* Upload File */
    .upload-wrapper {
      position: relative;
      margin-bottom: 20px;
    }

    .upload-file {
      display: none;
    }

    .upload-label {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
      color: #666;
    }

    .upload-label:hover {
      border-color: var(--accent-orange);
      background: rgba(255, 140, 43, 0.05);
    }

    .upload-label i {
      font-size: 20px;
      color: var(--accent-orange);
    }

    .upload-filename {
      display: none;
      font-size: 13px;
      color: #999;
      margin-top: 8px;
    }

    /* Submit Button */
    .btn-register {
      width: 100%;
      padding: 14px 20px;
      background: var(--accent-orange);
      color: white;
      font-weight: 700;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 25px;
    }

    .btn-register:hover {
      background: #ff7a0f;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(255, 140, 43, 0.3);
    }

    .btn-register:active {
      transform: translateY(0);
    }

    .tab-content-custom {
      display: block;
    }

    /* Alert */
    .alert-custom {
      background: rgba(244, 67, 54, 0.1);
      color: #c62828;
      border: 1px solid rgba(244, 67, 54, 0.3);
      border-radius: 6px;
      padding: 12px 14px;
      font-size: 13px;
      margin-bottom: 20px;
    }

    .alert-success-custom {
      background: rgba(76, 175, 80, 0.1);
      color: #2e7d32;
      border: 1px solid rgba(76, 175, 80, 0.3);
    }

    /* Button Load Nomor Ujian */
    .nomor-ujian-wrapper {
      position: relative;
      display: flex;
      gap: 8px;
      align-items: flex-end;
    }

    .nomor-ujian-wrapper .form-control {
      flex: 1;
    }

    .btn-load-nomor {
      padding: 10px 12px;
      background: var(--accent-orange);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 44px;
      width: 44px;
    }

    .btn-load-nomor:hover {
      background: #ff7a0f;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 140, 43, 0.3);
    }

    .btn-load-nomor:active {
      transform: translateY(0);
    }

    .btn-load-nomor:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }

    .btn-load-nomor.loading i {
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      from {
        transform: rotate(0deg);
      }
      to {
        transform: rotate(360deg);
      }
    }

    /* Responsive */
    @media (max-width: 768px) {
      .registration-content {
        grid-template-columns: 1fr;
      }

      .registration-image {
        min-height: 300px;
      }

      .registration-form-section {
        padding: 40px 30px;
      }

      .registration-title {
        font-size: 26px;
        margin-bottom: 25px;
      }

      .registration-image-text {
        font-size: 36px;
      }

      .form-row {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 480px) {
      .registration-form-section {
        padding: 30px 20px;
      }

      .registration-title {
        font-size: 22px;
      }

      .registration-image-text {
        font-size: 28px;
      }
    }
  </style>
</head>
<body>
  <!-- Registration Section -->
  <div class="registration-container">
    <div class="registration-wrapper">
      <div class="registration-content">
        <!-- Left Side - Image -->
        <div class="registration-image">
          <div class="registration-image-overlay">
            <div class="registration-image-text">Join<br>The Test!</div>
          </div>
        </div>

        <!-- Right Side - Form -->
        <div class="registration-form-section">
          <h1 class="registration-title">Login Ujian</h1>

          <!-- Login Form -->
          <div id="login" class="tab-content-custom active">
            <?php if (!empty($login_error)): ?>
              <div class="alert-custom">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($login_error); ?>
              </div>
            <?php endif; ?>

            <form id="loginForm" method="POST">
              <div class="form-group form-row-full">
                <label for="loginEmail">Email</label>
                <input type="email" class="form-control" id="loginEmail" name="email" placeholder="Masukkan email Anda" 
                       value="<?php echo isset($_SESSION['student_email']) ? htmlspecialchars($_SESSION['student_email']) : ''; ?>" required>
                <?php if (isset($_SESSION['student_email'])): ?>
                <small style="display: block; margin-top: 8px; padding: 8px 12px; background: #e7f3ff; color: #0066cc; border-left: 4px solid #0066cc; border-radius: 4px;">
                  <i class="bi bi-info-circle"></i> Data diambil dari data pendaftaran Anda sebelumnya
                </small>
                <?php endif; ?>
              </div>

              <div class="form-group form-row-full">
                <label for="loginNomorUjian">Nomor Ujian</label>
                <div class="nomor-ujian-wrapper">
                  <input type="text" class="form-control" id="loginNomorUjian" name="nomor_ujian" placeholder="Masukkan nomor ujian (PMB-2026-XXXX)" 
                         value="<?php echo isset($_SESSION['nomor_ujian']) ? htmlspecialchars($_SESSION['nomor_ujian']) : ''; ?>" required>
                  <button type="button" class="btn-load-nomor" id="btnLoadNomor" title="Muat nomor ujian dari email">
                    <i class="bi bi-arrow-clockwise"></i>
                  </button>
                </div>
                <?php if (isset($_SESSION['nomor_ujian'])): ?>
                <small style="display: block; margin-top: 8px; padding: 8px 12px; background: #e7f3ff; color: #0066cc; border-left: 4px solid #0066cc; border-radius: 4px;">
                  <i class="bi bi-info-circle"></i> Data diambil dari data pendaftaran Anda sebelumnya
                </small>
                <?php endif; ?>
              </div>

              <button type="submit" class="btn-register">Login</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Handle file upload display
    const uploadFile = document.getElementById('uploadFile');
    const uploadFilename = document.getElementById('uploadFilename');

    if (uploadFile) {
      uploadFile.addEventListener('change', function() {
        if (this.files && this.files[0]) {
          uploadFilename.textContent = this.files[0].name;
          uploadFilename.style.display = 'block';
        }
      });
    }

    // Auto-fill nomor ujian based on email
    const loginEmail = document.getElementById('loginEmail');
    const loginNomorUjian = document.getElementById('loginNomorUjian');
    const btnLoadNomor = document.getElementById('btnLoadNomor');

    function loadNomorUjian() {
      const email = loginEmail.value.trim();
      
      if (!email) {
        alert('Silakan masukkan email terlebih dahulu');
        return;
      }

      // Disable button dan tampilkan loading
      btnLoadNomor.disabled = true;
      btnLoadNomor.classList.add('loading');

      // Send AJAX request to get nomor ujian
      fetch('login_pendaftaran.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_nomor_ujian&email=' + encodeURIComponent(email)
      })
      .then(response => response.json())
      .then(data => {
        console.log('Data received:', data);
        if (data.success && data.nomor_ujian) {
          loginNomorUjian.value = data.nomor_ujian;
          console.log('Nomor ujian:', data.nomor_ujian);
          // Tampilkan pesan sukses
          alert(data.message || 'Nomor ujian berhasil dimuat!');
        } else {
          alert(data.message || 'Nomor ujian tidak ditemukan untuk email ini');
          loginNomorUjian.value = '';
          console.log('No nomor ujian found');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Terjadi error saat memuat nomor ujian');
      })
      .finally(() => {
        // Re-enable button dan hapus loading
        btnLoadNomor.disabled = false;
        btnLoadNomor.classList.remove('loading');
      });
    }

    if (btnLoadNomor) {
      btnLoadNomor.addEventListener('click', function(e) {
        e.preventDefault();
        loadNomorUjian();
      });
    }

    // Juga load otomatis saat user keluar dari field email
    if (loginEmail) {
      loginEmail.addEventListener('change', function() {
        const email = this.value.trim();
        if (email && !loginNomorUjian.value) {
          loadNomorUjian();
        }
      });
    }
  </script>
</body>
</html>

