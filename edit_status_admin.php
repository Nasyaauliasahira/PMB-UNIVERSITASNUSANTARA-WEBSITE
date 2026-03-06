<?php
session_start();
include "koneksi.php";

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

// Validasi parameter ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: status_admin.php");
    exit;
}

$id = intval($_GET['id']);

// Ambil data hasil ujian berdasarkan ID
$query = "SELECT 
    h.id,
    h.siswa_id,
    h.nilai,
    h.jumlah_benar,
    h.waktu_submit,
    u.first_name,
    u.last_name,
    u.email,
    p.photo
FROM hasil_ujian h
LEFT JOIN users u ON h.siswa_id = u.id
LEFT JOIN pendaftaran p ON u.email = p.email
WHERE h.id = ?";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: status_admin.php");
    exit;
}

$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Hitung status
$status = $data['nilai'] >= 70 ? 'LULUS' : 'TIDAK LULUS';
$status_class = $data['nilai'] >= 70 ? 'lulus' : 'tidak-lulus';

// Ambil pesan alert dari session (jika ada)
$alert_message = "";
$alert_type = "";
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    $alert_type = $_SESSION['alert_type'];
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Status Mahasiswa - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .edit-container {
            max-width: 700px;
            width: 100%;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #1a4d5c 0%, #0d2d36 100%);
            color: white;
            padding: 25px;
            border: none;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 30px;
        }

        .student-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .student-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #dee2e6;
        }

        .student-info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.lulus {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.tidak-lulus {
            background: #f8d7da;
            color: #721c24;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #1a4d5c;
            box-shadow: 0 0 0 3px rgba(26, 77, 92, 0.1);
        }

        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .btn-group-custom {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn-submit {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #1a4d5c 0%, #0d2d36 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 77, 92, 0.3);
        }

        .btn-cancel {
            padding: 12px 25px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }

        .photo-preview {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid #dee2e6;
        }

        .score-info {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a7b 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .score-info h5 {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .score-info .score {
            font-size: 36px;
            font-weight: 700;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="card">
            <div class="card-header">
                <h4>
                    <i class="bi bi-pencil-square"></i>
                    Edit Status Mahasiswa
                </h4>
            </div>
            <div class="card-body">
                <?php if (!empty($alert_message)): ?>
                    <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?php echo $alert_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo $alert_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Informasi Mahasiswa -->
                <div class="student-info">
                    <h5 style="margin-bottom: 15px; color: #1a4d5c;">
                        <i class="bi bi-person-badge"></i> Informasi Mahasiswa
                    </h5>
                    
                    <?php if (!empty($data['photo'])): ?>
                        <div style="text-align: center; margin-bottom: 15px;">
                            <img src="uploads/foto/<?php echo htmlspecialchars($data['photo']); ?>" 
                                 alt="Photo" class="photo-preview">
                        </div>
                    <?php endif; ?>

                    <div class="student-info-row">
                        <span class="info-label">Nama:</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($data['first_name'] . ' ' . $data['last_name']); ?>
                        </span>
                    </div>
                    <div class="student-info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($data['email']); ?></span>
                    </div>
                    <div class="student-info-row">
                        <span class="info-label">Status Saat Ini:</span>
                        <span class="info-value">
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status; ?>
                            </span>
                        </span>
                    </div>
                    <div class="student-info-row">
                        <span class="info-label">Waktu Submit:</span>
                        <span class="info-value">
                            <?php echo date('d/m/Y H:i', strtotime($data['waktu_submit'])); ?>
                        </span>
                    </div>
                </div>

                <!-- Form Edit -->
                <form action="proses_edit_status.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">

                    <div class="score-info">
                        <h5>Nilai Saat Ini</h5>
                        <div class="score"><?php echo $data['nilai']; ?></div>
                        <small>Jumlah Benar: <?php echo $data['jumlah_benar']; ?> soal</small>
                    </div>

                    <div class="mb-3">
                        <label for="nilai" class="form-label">
                            <i class="bi bi-award"></i> Nilai Baru
                        </label>
                        <input type="number" class="form-control" id="nilai" name="nilai" 
                               value="<?php echo $data['nilai']; ?>" 
                               min="0" max="100" step="0.01" required>
                        <small class="form-text">
                            <i class="bi bi-info-circle"></i> Masukkan nilai antara 0 - 100. Passing grade: 70
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="jumlah_benar" class="form-label">
                            <i class="bi bi-check-circle"></i> Jumlah Jawaban Benar
                        </label>
                        <input type="number" class="form-control" id="jumlah_benar" name="jumlah_benar" 
                               value="<?php echo $data['jumlah_benar']; ?>" 
                               min="0" required>
                        <small class="form-text">
                            <i class="bi bi-info-circle"></i> Jumlah soal yang dijawab benar
                        </small>
                    </div>

                    <div class="btn-group-custom">
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-check-circle"></i>
                            Simpan Perubahan
                        </button>
                        <a href="status_admin.php" class="btn-cancel">
                            <i class="bi bi-x-circle"></i>
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update status preview saat nilai berubah
        const nilaiInput = document.getElementById('nilai');
        
        nilaiInput.addEventListener('input', function() {
            const nilai = parseFloat(this.value);
            if (!isNaN(nilai)) {
                // Optional: bisa tambahkan live preview status lulus/tidak lulus
                console.log('Nilai:', nilai, 'Status:', nilai >= 70 ? 'LULUS' : 'TIDAK LULUS');
            }
        });
    </script>
</body>
</html>
