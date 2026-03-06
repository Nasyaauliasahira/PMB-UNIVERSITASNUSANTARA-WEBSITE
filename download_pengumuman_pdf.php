<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($koneksi, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);
mysqli_stmt_close($stmt);

// Cek hasil ujian
$query_hasil = "SELECT h.*, p.jurusan, p.nomor_ujian, p.phone 
                FROM hasil_ujian h
                LEFT JOIN users u ON h.siswa_id = u.id
                LEFT JOIN pendaftaran p ON u.email = p.email
                WHERE h.siswa_id = ?
                ORDER BY h.waktu_submit DESC
                LIMIT 1";
$stmt_hasil = mysqli_prepare($koneksi, $query_hasil);
mysqli_stmt_bind_param($stmt_hasil, "i", $user_id);
mysqli_stmt_execute($stmt_hasil);
$result_hasil = mysqli_stmt_get_result($stmt_hasil);
$hasil_ujian = mysqli_fetch_assoc($result_hasil);
mysqli_stmt_close($stmt_hasil);

// Cek apakah user sudah ujian
if (!$hasil_ujian) {
    header("Location: dashboard.php");
    exit;
}

// Cek apakah user lulus (nilai >= 70)
$is_lulus = ($hasil_ujian['nilai'] >= 70);

// Ambil NIM dari daftar ulang (NIM dibuat setelah daftar ulang)
$tahun_masuk = date('Y');
$nim = null;
$query_nim = "SELECT nim FROM daftar_ulang WHERE user_id = ?";
$stmt_nim = mysqli_prepare($koneksi, $query_nim);
mysqli_stmt_bind_param($stmt_nim, "i", $user_id);
mysqli_stmt_execute($stmt_nim);
$result_nim = mysqli_stmt_get_result($stmt_nim);
if ($row_nim = mysqli_fetch_assoc($result_nim)) {
    $nim = $row_nim['nim'];
}
mysqli_stmt_close($stmt_nim);

// Mapping jurusan ke nama program studi
$program_studi = [
    'business' => 'Manajemen Bisnis',
    'it_software' => 'Teknik Informatika',
    'design' => 'Desain Komunikasi Visual'
];

$prodi = isset($hasil_ujian['jurusan']) && isset($program_studi[$hasil_ujian['jurusan']]) 
    ? $program_studi[$hasil_ujian['jurusan']] 
    : 'Belum Ditentukan';

// Set PDF headers
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Pengumuman_Kelulusan_' . $user['first_name'] . '_' . ($nim ? $nim : 'tanpa_nim') . '.pdf"');

// Gunakan library mPDF atau DomPDF jika tersedia, jika tidak gunakan fallback HTML
// Check if mPDF is available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        
        $html = generatePDFContent($user, $hasil_ujian, $is_lulus, $nim, $tahun_masuk, $prodi);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Pengumuman_Kelulusan_' . $user['first_name'] . '_' . ($nim ? $nim : 'tanpa_nim') . '.pdf', 'D');
        exit;
    } catch (Exception $e) {
        // Fallback to HTML
    }
}

// Fallback: Generate HTML that can be printed as PDF
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman Kelulusan - <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
            .container { 
                padding: 15px !important; 
                border: 1px solid #1e3a5f !important;
                page-break-after: avoid;
            }
        }
        
        @page {
            size: A4;
            margin: 10mm;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 10px;
            background: white;
            font-size: 11px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 2px solid #1e3a5f;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 10px;
        }
        
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 5px;
        }
        
        .university-info {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
            line-height: 1.3;
        }
        
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #ff9800;
            margin: 15px 0 10px 0;
            text-align: center;
            text-transform: uppercase;
        }
        
        .status-box {
            background-color: <?php echo $is_lulus ? '#d4edda' : '#f8d7da'; ?>;
            border: 2px solid <?php echo $is_lulus ? '#28a745' : '#dc3545'; ?>;
            padding: 12px;
            margin: 15px 0;
            text-align: center;
            border-radius: 5px;
        }
        
        .status-text {
            font-size: 15px;
            font-weight: bold;
            color: <?php echo $is_lulus ? '#28a745' : '#dc3545'; ?>;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11px;
        }
        
        .info-table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            line-height: 1.3;
        }
        
        .info-table td.label {
            background-color: #1e3a5f;
            color: white;
            font-weight: bold;
            width: 40%;
        }
        
        .info-table td.value {
            background-color: #f8f9fa;
        }
        
        .footer-note {
            margin-top: 15px;
            padding: 12px;
            background-color: <?php echo $is_lulus ? '#fff3cd' : '#f8d7da'; ?>;
            border: 1px solid <?php echo $is_lulus ? '#ffc107' : '#dc3545'; ?>;
            border-radius: 5px;
            font-size: 10px;
        }
        
        .footer-note strong {
            font-size: 11px;
        }
        
        .footer-note ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        
        .footer-note li {
            margin: 4px 0;
            line-height: 1.4;
        }
        
        .signature {
            margin-top: 25px;
            text-align: right;
            font-size: 10px;
        }
        
        .signature-box {
            margin-top: 80px;
            border-top: 1px solid #000;
            width: 180px;
            display: inline-block;
            padding-top: 3px;
            text-align: center;
            font-size: 9px;
        }
        
        .footer-info {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            line-height: 1.3;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">🖨️ Print / Save as PDF</button>
    
    <div class="container">
        <div class="header">
            <div class="logo">🎓 UNIVERSITAS NUSANTARA</div>
            <div class="university-info">
                Jl. Pendidikan No. 123, Indonesia<br>
                Email: info@universitasnusantara.ac.id | Telp: (021) 1234567<br>
                Website: www.universitasnusantara.ac.id
            </div>
        </div>

        <div class="title">Pengumuman Hasil Seleksi Ujian Masuk</div>

        <div class="status-box">
            <div class="status-text">
                <?php echo $is_lulus ? '✓ SELAMAT! ANDA DINYATAKAN LULUS' : '✗ MOHON MAAF, ANDA BELUM LULUS'; ?>
            </div>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Nama Lengkap</td>
                <td class="value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value"><?php echo htmlspecialchars($user['email']); ?></td>
            </tr>
            <tr>
                <td class="label">Program Studi</td>
                <td class="value"><?php echo htmlspecialchars($prodi); ?></td>
            </tr>
            <tr>
                <td class="label">Nilai Minimum Kelulusan</td>
                <td class="value">70 / 100</td>
            </tr>
            <?php if ($is_lulus): ?>
            <tr>
                <td class="label">NIM (Nomor Induk Mahasiswa)</td>
                <td class="value"><strong style="font-size: 14px; color: #1e3a5f;">
                    <?php echo $nim ? htmlspecialchars($nim) : 'Belum tersedia'; ?>
                </strong></td>
            </tr>
            <tr>
                <td class="label">Tahun Masuk</td>
                <td class="value"><?php echo $tahun_masuk; ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="label">Tanggal Ujian</td>
                <td class="value"><?php echo date('d F Y, H:i', strtotime($hasil_ujian['waktu_submit'])); ?> WIB</td>
            </tr>
        </table>

        <?php if ($is_lulus): ?>
        <div class="footer-note">
            <strong>📋 INFORMASI PENTING:</strong>
            <ul>
                <li>Simpan dan catat NIM Anda untuk keperluan administrasi selanjutnya</li>
                <li>Silakan melakukan <strong>daftar ulang</strong> melalui sistem online</li>
                <li>Proses registrasi ulang akan diinformasikan melalui email</li>
                <li>Hubungi bagian akademik untuk informasi lebih lanjut</li>
                <li>Dokumen ini adalah bukti resmi kelulusan Anda</li>
            </ul>
        </div>
        <?php else: ?>
        <div class="footer-note">
            <strong>📋 INFORMASI:</strong>
            <ul>
                <li>Terima kasih telah mengikuti seleksi ujian masuk Universitas Nusantara</li>
                <li>Nilai Anda: <strong><?php echo $hasil_ujian['nilai']; ?> / 100</strong></li>
                <li>Nilai minimum kelulusan: <strong>70 / 100</strong></li>
                <li>Tetap semangat dan jangan menyerah untuk mencoba lagi</li>
                <li>Hubungi bagian akademik untuk informasi lebih lanjut tentang pendaftaran ulang</li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="signature">
            <div style="margin-bottom: 5px;">
                <strong>Panitia Penerimaan Mahasiswa Baru</strong><br>
                <strong>Universitas Nusantara</strong>
            </div>
            <div class="signature-box">
                (Tanda Tangan & Cap)
            </div>
        </div>

        <div class="footer-info">
            Dokumen ini dicetak secara otomatis pada <?php echo date('d F Y, H:i:s'); ?> WIB<br>
            Halaman 1 dari 1 | Dokumen Resmi Universitas Nusantara
        </div>
    </div>

    <script>
        // Auto print dialog when page loads (optional)
        // Uncomment if you want auto print dialog
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
