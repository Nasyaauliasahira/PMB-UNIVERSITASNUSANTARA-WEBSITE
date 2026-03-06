<?php
session_start();
include "koneksi.php";

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek apakah form disubmit dengan method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil data dari form
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $nomor_hp = mysqli_real_escape_string($koneksi, $_POST['nomor_hp']);
    $nomor_orang_terdekat = mysqli_real_escape_string($koneksi, $_POST['nomor_orang_terdekat']);
    $school_level = mysqli_real_escape_string($koneksi, $_POST['school_level']);
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Format email tidak valid!";
        header("Location: daftar_ulang.php");
        exit;
    }

    if (empty($nomor_orang_terdekat)) {
        $_SESSION['error_message'] = "Nomor orang terdekat wajib diisi!";
        header("Location: daftar_ulang.php");
        exit;
    }

    // Validasi persetujuan privacy policy & terms
    if (!isset($_POST['terms_checkbox'])) {
        $_SESSION['error_message'] = "Anda harus menyetujui Privacy Policy dan Terms of Service.";
        header("Location: daftar_ulang.php");
        exit;
    }
    
    // Cek apakah sudah pernah daftar ulang
    $query_check = "SELECT * FROM daftar_ulang WHERE user_id = ?";
    $stmt_check = mysqli_prepare($koneksi, $query_check);
    mysqli_stmt_bind_param($stmt_check, "i", $user_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $existing_data = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);
    
    // Handle upload foto
    $photo_name = '';
    $photo_uploaded = false;
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $file_type = $_FILES['photo']['type'];
        $file_size = $_FILES['photo']['size'];
        
        // Validasi tipe file
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Format file tidak valid! Hanya JPG, JPEG, dan PNG yang diperbolehkan.";
            header("Location: daftar_ulang.php");
            exit;
        }
        
        // Validasi ukuran file (max 2MB)
        if ($file_size > 2 * 1024 * 1024) {
            $_SESSION['error_message'] = "Ukuran file terlalu besar! Maksimal 2MB.";
            header("Location: daftar_ulang.php");
            exit;
        }
        
        // Generate nama file unik
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = time() . '_' . uniqid() . '.' . $file_extension;
        
        // Tentukan folder upload
        $upload_dir = 'uploads/daftar_ulang/';
        
        // Buat folder jika belum ada
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $upload_path = $upload_dir . $photo_name;
        
        // Upload file
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            $photo_uploaded = true;
            
            // Hapus foto lama jika ada (saat update)
            if ($existing_data && $existing_data['photo']) {
                $old_photo = $upload_dir . $existing_data['photo'];
                if (file_exists($old_photo)) {
                    unlink($old_photo);
                }
            }
        } else {
            $_SESSION['error_message'] = "Gagal mengupload foto!";
            header("Location: daftar_ulang.php");
            exit;
        }
    } else {
        // Jika tidak ada foto baru diupload
        if ($existing_data) {
            // Gunakan foto lama
            $photo_name = $existing_data['photo'];
        } else {
            // Foto wajib untuk pendaftaran baru
            $_SESSION['error_message'] = "Foto wajib diupload!";
            header("Location: daftar_ulang.php");
            exit;
        }
    }

    // Handle upload foto KTP
    $ktp_name = '';
    $ktp_uploaded = false;
    $ktp_upload_dir = 'uploads/daftar_ulang/ktp/';

    if (isset($_FILES['ktp_photo']) && $_FILES['ktp_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $file_type = $_FILES['ktp_photo']['type'];
        $file_size = $_FILES['ktp_photo']['size'];

        // Validasi tipe file
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Format file KTP tidak valid! Hanya JPG, JPEG, dan PNG yang diperbolehkan.";
            header("Location: daftar_ulang.php");
            exit;
        }

        // Validasi ukuran file (max 2MB)
        if ($file_size > 2 * 1024 * 1024) {
            $_SESSION['error_message'] = "Ukuran file KTP terlalu besar! Maksimal 2MB.";
            header("Location: daftar_ulang.php");
            exit;
        }

        // Generate nama file unik
        $file_extension = pathinfo($_FILES['ktp_photo']['name'], PATHINFO_EXTENSION);
        $ktp_name = 'ktp_' . time() . '_' . uniqid() . '.' . $file_extension;

        // Buat folder jika belum ada
        if (!file_exists($ktp_upload_dir)) {
            mkdir($ktp_upload_dir, 0777, true);
        }

        $upload_path = $ktp_upload_dir . $ktp_name;

        // Upload file
        if (move_uploaded_file($_FILES['ktp_photo']['tmp_name'], $upload_path)) {
            $ktp_uploaded = true;

            // Hapus foto KTP lama jika ada (saat update)
            if ($existing_data && !empty($existing_data['ktp_photo'])) {
                $old_ktp = $ktp_upload_dir . $existing_data['ktp_photo'];
                if (file_exists($old_ktp)) {
                    unlink($old_ktp);
                }
            }
        } else {
            $err = isset($_FILES['ktp_photo']['error']) ? $_FILES['ktp_photo']['error'] : 'unknown';
            $_SESSION['error_message'] = "Gagal mengupload foto KTP! Error code: $err";
            header("Location: daftar_ulang.php");
            exit;
        }
    } else {
        // Jika tidak ada file baru diupload
        if ($existing_data && !empty($existing_data['ktp_photo'])) {
            // Update: gunakan file lama
            $ktp_name = $existing_data['ktp_photo'];
        } else if (!$existing_data) {
            // Insert baru: wajib upload
            $_SESSION['error_message'] = "Foto KTP wajib diupload!";
            header("Location: daftar_ulang.php");
            exit;
        } else {
            // Update tapi tidak ada file lama, error
            $_SESSION['error_message'] = "Foto KTP tidak ditemukan. Silakan upload ulang.";
            header("Location: daftar_ulang.php");
            exit;
        }
    }
    
    // Jika sudah pernah daftar, lakukan update
    if ($existing_data) {
        // Ambil NIM lama, jika kosong generate ulang
        $nim = $existing_data['nim'];
        if (empty($nim)) {
            $tahun_masuk = date('Y');
            $nim = $tahun_masuk . str_pad($user_id, 6, '0', STR_PAD_LEFT);
        }
        $query_update = "UPDATE daftar_ulang SET 
                        nim = ?,
                        nama_lengkap = ?,
                        email = ?,
                        nomor_hp = ?,
                        nomor_orang_terdekat = ?,
                        school_level = ?,
                        photo = ?,
                        ktp_photo = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = ?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "ssssssssi", 
            $nim,
            $nama_lengkap, 
            $email, 
            $nomor_hp, 
            $nomor_orang_terdekat,
            $school_level, 
            $photo_name, 
            $ktp_name,
            $user_id
        );
        
        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['success_message'] = "Data daftar ulang berhasil diupdate!";
        } else {
            $_SESSION['error_message'] = "Gagal mengupdate data: " . mysqli_error($koneksi);
        }
        
        mysqli_stmt_close($stmt_update);
        
    } else {
        // Jika belum pernah daftar, lakukan insert
        $tahun_masuk = date('Y');
        $nim = $tahun_masuk . str_pad($user_id, 6, '0', STR_PAD_LEFT);
        $query_insert = "INSERT INTO daftar_ulang (user_id, nim, nama_lengkap, email, nomor_hp, nomor_orang_terdekat, school_level, photo, ktp_photo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_insert = mysqli_prepare($koneksi, $query_insert);
        mysqli_stmt_bind_param($stmt_insert, "issssssss", 
            $user_id, 
            $nim, 
            $nama_lengkap, 
            $email, 
            $nomor_hp, 
            $nomor_orang_terdekat,
            $school_level, 
            $photo_name,
            $ktp_name
        );
        
        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION['success_message'] = "Pendaftaran ulang berhasil! Data Anda telah tersimpan.";
        } else {
            $_SESSION['error_message'] = "Gagal menyimpan data: " . mysqli_error($koneksi);
            
            // Hapus foto yang sudah diupload jika query gagal
            if ($photo_uploaded) {
                $upload_path = 'uploads/daftar_ulang/' . $photo_name;
                if (file_exists($upload_path)) {
                    unlink($upload_path);
                }
            }

            if ($ktp_uploaded) {
                $upload_path = $ktp_upload_dir . $ktp_name;
                if (file_exists($upload_path)) {
                    unlink($upload_path);
                }
            }
        }
        
        mysqli_stmt_close($stmt_insert);
    }
    
    header("Location: daftar_ulang.php");
    exit;
    
} else {
    // Jika bukan POST request, redirect ke form
    header("Location: daftar_ulang.php");
    exit;
}
?>
