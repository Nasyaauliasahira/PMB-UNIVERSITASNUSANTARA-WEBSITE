

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `daftar_ulang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama_lengkap` varchar(200) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `nomor_hp` varchar(20) NOT NULL,
  `nomor_orang_terdekat` varchar(20) NOT NULL,
  `school_level` enum('sma','smk') NOT NULL,
  `photo` varchar(255) NOT NULL,
  `ktp_photo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `daftar_ulang` (`id`, `user_id`, `nim`, `nama_lengkap`, `date_of_birth`, `email`, `nomor_hp`, `nomor_orang_terdekat`, `school_level`, `photo`, `ktp_photo`, `created_at`, `updated_at`) VALUES
(1, 1, '2026000001', 'hana hani', NULL, 'hani@gmail.com', '8889098090', '09876', 'sma', '1772004516_699ea4a47b276.jpg', 'ktp_1772004516_699ea4a47b977.jpg', '2026-02-25 07:28:36', '2026-02-25 07:28:36'),
(2, 2, '2026000002', 'bola boli', NULL, 'bola@gmail.com', '3993933', '0987979987897', 'sma', '1772021768_699ee8084f7d9.jpg', 'ktp_1772021768_699ee808503a1.png', '2026-02-25 12:16:08', '2026-02-25 12:16:08'),
(3, 4, '2026000004', 'kim jihoon', NULL, 'jihoon@gmail.com', '5676567', '22222', 'sma', '1772022573_699eeb2d057e0.jpg', 'ktp_1772022573_699eeb2d05da9.jpg', '2026-02-25 12:29:33', '2026-02-25 12:29:33');


CREATE TABLE `hasil_ujian` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) DEFAULT NULL,
  `jumlah_benar` int(11) DEFAULT NULL,
  `nilai` int(11) DEFAULT NULL,
  `durasi` int(11) DEFAULT NULL,
  `waktu_submit` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `hasil_ujian` (`id`, `siswa_id`, `jumlah_benar`, `nilai`, `durasi`, `waktu_submit`) VALUES
(1, 1, 9, 90, 0, '2026-02-25 07:28:03'),
(2, 2, 10, 100, 0, '2026-02-25 09:40:20'),
(3, 4, 10, 100, 0, '2026-02-25 12:28:29'),
(4, 5, 1, 10, 0, '2026-02-25 13:20:11');


CREATE TABLE `jawaban_siswa` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) DEFAULT NULL,
  `soal_id` int(11) DEFAULT NULL,
  `jawaban` char(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `pendaftaran` (
  `id` int(11) NOT NULL,
  `nomor_ujian` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `jurusan` enum('business','it_software','design') NOT NULL,
  `phone` varchar(20) NOT NULL,
  `school_level` enum('sma','smk') NOT NULL,
  `photo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `pendaftaran` (`id`, `nomor_ujian`, `first_name`, `last_name`, `date_of_birth`, `email`, `jurusan`, `phone`, `school_level`, `photo`, `created_at`) VALUES
(1, 'PMB-2026-0001', 'hana', 'hani', '2026-02-13', 'hani@gmail.com', 'it_software', '8889098090', 'sma', '1772004444_dua.jpg', '2026-02-25 07:27:24'),
(2, 'PMB-2026-0002', 'bola', 'boli', '2026-02-12', 'bola@gmail.com', 'it_software', '3993933', 'sma', '1772012358_tiga.jpg', '2026-02-25 09:39:18'),
(3, 'PMB-2026-0003', 'kim', 'minju', '2026-02-19', 'minju@gmail.com', 'business', '0987654', 'sma', '1772012676_keonho.jpg', '2026-02-25 09:44:36'),
(4, 'PMB-2026-0004', 'kim', 'jihoon', '2026-02-20', 'jihoon@gmail.com', 'it_software', '5676567', 'sma', '1772022407_dua.jpg', '2026-02-25 12:26:47'),
(6, 'PMB-2026-0006', 'nasya', 'aulia', '2026-03-11', 'nasya@gmail.com', 'it_software', '0900090', 'smk', '1772023117_download (2).jpg', '2026-02-25 12:38:37');

CREATE TABLE `soal` (
  `id` int(11) NOT NULL,
  `pertanyaan` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `pilihan_a` varchar(255) DEFAULT NULL,
  `pilihan_b` varchar(255) DEFAULT NULL,
  `pilihan_c` varchar(255) DEFAULT NULL,
  `pilihan_d` varchar(255) DEFAULT NULL,
  `jawaban_benar` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `soal` (`id`, `pertanyaan`, `gambar`, `pilihan_a`, `pilihan_b`, `pilihan_c`, `pilihan_d`, `jawaban_benar`) VALUES
(1, 'Ibukota Indonesia adalah...', NULL, 'Bandung', 'Jakarta', 'Surabaya', 'Medan', 'B'),
(2, 'Planet terdekat dengan Matahari adalah...', NULL, 'Merkurius', 'Venus', 'Bumi', 'Mars', 'A'),
(3, 'Lambang kimia untuk air adalah...', NULL, 'O2', 'CO2', 'H2O', 'NaCl', 'C'),
(4, 'Benua terbesar di dunia adalah...', NULL, 'Afrika', 'Asia', 'Eropa', 'Australia', 'B'),
(5, 'Alat untuk mengukur suhu adalah...', NULL, 'Barometer', 'Termometer', 'Higrometer', 'Anemometer', 'B'),
(6, 'Bahasa resmi negara Jepang adalah...', NULL, 'Mandarin', 'Korea', 'Jepang', 'Thailand', 'C'),
(7, 'Hasil dari 7 x 8 adalah...', NULL, '54', '56', '58', '60', 'B'),
(8, 'Organ yang berfungsi memompa darah adalah...', NULL, 'Paru-paru', 'Hati', 'Jantung', 'Ginjal', 'C'),
(9, 'Bendera Indonesia terdiri dari warna...', NULL, 'Merah dan Putih', 'Biru dan Putih', 'Merah dan Kuning', 'Hijau dan Putih', 'A'),
(10, 'Alat musik tradisional dari Jawa Barat adalah...', NULL, 'Angklung', 'Sasando', 'Tifa', 'Kolintang', 'A');


CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `about` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `photo`, `about`, `created_at`) VALUES
(1, 'hana', 'hani', 'hani@gmail.com', 'hani', NULL, NULL, '2026-02-25 07:27:01'),
(2, 'bola', 'boli', 'bola@gmail.com', '123', '1772021554_download (2).jpg', '', '2026-02-25 08:12:25'),
(3, 'kim', 'minju', 'minju@gmail.com', '123', NULL, NULL, '2026-02-25 09:43:54'),
(4, 'kim', 'jihoon', 'jihoon@gmail.com', '123', NULL, NULL, '2026-02-25 12:26:09'),
(5, 'nasya', 'aulia', 'nasya@gmail.com', 'nasya', NULL, NULL, '2026-02-25 12:38:02'),
(6, 'carmen', 'h2h', 'carmen@gmail.com', 'carmen12345', NULL, NULL, '2026-02-27 12:05:11');


ALTER TABLE `daftar_ulang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `email` (`email`);

ALTER TABLE `hasil_ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

ALTER TABLE `jawaban_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `siswa_id` (`siswa_id`,`soal_id`),
  ADD KEY `soal_id` (`soal_id`);

ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `soal`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `daftar_ulang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `hasil_ujian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `jawaban_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pendaftaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `soal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `hasil_ujian`
  ADD CONSTRAINT `hasil_ujian_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `jawaban_siswa`
  ADD CONSTRAINT `jawaban_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jawaban_siswa_ibfk_2` FOREIGN KEY (`soal_id`) REFERENCES `soal` (`id`) ON DELETE CASCADE;
COMMIT;
