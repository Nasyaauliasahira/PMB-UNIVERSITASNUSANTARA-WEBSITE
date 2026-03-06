<?php
include "koneksi.php";

// Query untuk membuat tabel daftar_ulang
$query = "CREATE TABLE IF NOT EXISTS `daftar_ulang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama_lengkap` varchar(200) NOT NULL,
  `email` varchar(150) NOT NULL,
  `nomor_hp` varchar(20) NOT NULL,
  `nomor_orang_terdekat` varchar(20) NOT NULL,
  `school_level` enum('sma','smk') NOT NULL,
  `photo` varchar(255) NOT NULL,
  `ktp_photo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `nim` (`nim`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($koneksi, $query)) {
    echo "<h2 style='color: green;'>✓ Tabel 'daftar_ulang' berhasil dibuat!</h2>";
    echo "<p>Tabel sudah siap digunakan untuk menyimpan data daftar ulang mahasiswa.</p>";
    echo "<p><a href='dashboard.php'>Kembali ke Dashboard</a></p>";
} else {
    echo "<h2 style='color: red;'>✗ Gagal membuat tabel!</h2>";
    echo "<p>Error: " . mysqli_error($koneksi) . "</p>";
}

mysqli_close($koneksi);
?>
