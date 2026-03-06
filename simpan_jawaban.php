<?php
session_start();
include "koneksi.php";

// VALIDASI: Session user_id harus ada dari tabel users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu'
    ]);
    exit();
}

$siswa_id = $_SESSION['user_id'];

// VALIDASI: Pastikan siswa_id valid di tabel users
$cek_user = "SELECT id FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($koneksi, $cek_user);

if (!$stmt_user) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($koneksi)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt_user, "i", $siswa_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);

if (mysqli_num_rows($result_user) == 0) {
    mysqli_stmt_close($stmt_user);
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User tidak ditemukan di database'
    ]);
    exit();
}
mysqli_stmt_close($stmt_user);

// PROSES: Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Hanya POST request yang diterima'
    ]);
    exit();
}

// AMBIL: Jawaban dari POST (dalam bentuk ARRAY)
$jawaban_array = isset($_POST['jawaban']) ? $_POST['jawaban'] : [];

// VALIDASI: Pastikan jawaban adalah array
if (!is_array($jawaban_array)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Format jawaban harus berupa array'
    ]);
    exit();
}

// VALIDASI: Jawaban tidak boleh kosong
if (empty($jawaban_array)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Jawaban tidak boleh kosong'
    ]);
    exit();
}

// SIAPKAN: Query insert jawaban dengan prepared statement
// Gunakan ON DUPLICATE KEY UPDATE untuk cegah duplikat
$query_insert = "INSERT INTO jawaban_siswa (siswa_id, soal_id, jawaban, created_at) 
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE jawaban = ?, created_at = NOW()";

$stmt_insert = mysqli_prepare($koneksi, $query_insert);

if (!$stmt_insert) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($koneksi)
    ]);
    exit();
}

// LOOP: Proses setiap jawaban dari array
$berhasil_disimpan = 0;
$error_list = [];

foreach ($jawaban_array as $soal_id => $pilihan) {
    // VALIDASI: soal_id harus numeric
    $soal_id = intval($soal_id);
    
    if ($soal_id <= 0) {
        $error_list[] = "soal_id tidak valid: " . $soal_id;
        continue;
    }
    
    // TRIM: Hanya trim ke value string, bukan ke array
    $pilihan = is_string($pilihan) ? trim($pilihan) : '';
    
    if (empty($pilihan)) {
        $error_list[] = "Jawaban soal $soal_id kosong";
        continue;
    }
    
    // VALIDASI: Cek apakah soal_id ada di tabel soal
    $cek_soal = "SELECT id FROM soal WHERE id = ?";
    $stmt_cek = mysqli_prepare($koneksi, $cek_soal);
    
    if (!$stmt_cek) {
        $error_list[] = "Database error pada soal $soal_id";
        continue;
    }
    
    mysqli_stmt_bind_param($stmt_cek, "i", $soal_id);
    mysqli_stmt_execute($stmt_cek);
    $result_soal = mysqli_stmt_get_result($stmt_cek);
    
    if (mysqli_num_rows($result_soal) == 0) {
        mysqli_stmt_close($stmt_cek);
        $error_list[] = "Soal dengan ID $soal_id tidak ditemukan";
        continue;
    }
    mysqli_stmt_close($stmt_cek);
    
    // BIND PARAMETER: Untuk insert dan update
    // Parameter: siswa_id, soal_id, jawaban, jawaban(update)
    mysqli_stmt_bind_param($stmt_insert, "iiss", $siswa_id, $soal_id, $pilihan, $pilihan);
    
    // EXECUTE: Simpan jawaban
    if (mysqli_stmt_execute($stmt_insert)) {
        $berhasil_disimpan++;
    } else {
        $error = mysqli_stmt_error($stmt_insert);
        
        // CEK: Apakah error berkaitan dengan foreign key
        if (strpos($error, 'foreign key') !== false || strpos($error, 'FOREIGN') !== false) {
            $error_list[] = "Foreign Key Error pada soal $soal_id: siswa_id atau soal_id tidak valid";
        } else {
            $error_list[] = "Error soal $soal_id: " . $error;
        }
    }
}

mysqli_stmt_close($stmt_insert);

// RESPONSE: Berikan feedback hasil proses
if ($berhasil_disimpan > 0 || empty($error_list)) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "$berhasil_disimpan jawaban berhasil disimpan",
        'data' => [
            'siswa_id' => $siswa_id,
            'total_disimpan' => $berhasil_disimpan,
            'total_request' => count($jawaban_array)
        ]
    ]);
} else {
    // Ada beberapa yang gagal
    if ($berhasil_disimpan > 0) {
        http_response_code(207); // Multi-Status
        echo json_encode([
            'success' => true,
            'message' => "$berhasil_disimpan jawaban berhasil, " . count($error_list) . " ada error",
            'errors' => $error_list,
            'data' => [
                'siswa_id' => $siswa_id,
                'total_disimpan' => $berhasil_disimpan,
                'total_error' => count($error_list)
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Semua jawaban gagal disimpan',
            'errors' => $error_list
        ]);
    }
}

mysqli_close($koneksi);
?>



