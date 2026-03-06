# 🎓 Universitas Nusantara — Sistem PMB (Penerimaan Mahasiswa Baru)

Sistem pendaftaran mahasiswa baru berbasis web untuk **Universitas Nusantara**, mencakup alur lengkap dari pendaftaran peserta, ujian seleksi, pengumuman kelulusan, hingga daftar ulang — dilengkapi admin panel untuk manajemen data.

---

## 📋 Daftar File

### 🟦 User-Facing (Frontend)

| File | Deskripsi |
|---|---|
| `pendaftaran.php` | Form pendaftaran calon mahasiswa baru (data pribadi, akademik, upload foto) |
| `ujian.php` | Halaman ujian seleksi dengan timer countdown (20 menit 30 detik), navigasi soal, dan progress tracker |
| `hasil_ujian.php` | Halaman hasil setelah submit ujian — menampilkan status lulus/tidak lulus (tanpa nilai numerik) |
| `pengumuman.php` | Halaman pengumuman resmi kelulusan — untuk yang lulus: menampilkan NIM, kartu mahasiswa digital, dan download PDF |
| `dashboard.php` | Dashboard utama user setelah login |

### 🟧 Admin Panel

| File | Deskripsi |
|---|---|
| `register_login_admin.php` | Halaman manajemen akun user (register & login) di admin panel |
| `edit_user_admin.php` | Form edit data akun user (nama, email, password) |
| `edit_pendaftaran_admin.php` | Form edit data pendaftaran peserta (info dasar, kontak, akademik) |
| `edit_soal_admin.php` | Form edit soal ujian di bank soal |

---

## ✨ Fitur Utama

### Untuk Peserta
- **Form Pendaftaran** — Multi-step dengan validasi server-side; data tetap tersimpan jika ada error (form repopulation via PRG pattern)
- **Ujian Online** — Tampilan full-screen dengan sidebar timer, navigasi soal per nomor, dan auto-submit saat waktu habis
- **Hasil Ujian** — Menampilkan status **LULUS / TIDAK LULUS** tanpa memperlihatkan nilai numerik ke peserta
- **Pengumuman Kelulusan** — Bagi yang lulus: NIM, data akademik resmi, kartu mahasiswa digital (download PNG via html2canvas), dan PDF pengumuman
- **Confetti** — Animasi confetti otomatis saat peserta dinyatakan lulus 🎉

### Untuk Admin
- **Sidebar terpadu** — Desain konsisten di semua halaman admin panel
- **Edit data peserta** — Edit informasi dasar, kontak, dan akademik dengan validasi
- **Edit soal** — Manajemen bank soal ujian
- **Manajemen akun** — Edit data user dan password

---

## 🎨 Design System

Seluruh halaman menggunakan design system yang konsisten:

| Token | Nilai |
|---|---|
| `--navy` | `#0d1f35` |
| `--orange` | `#ff9800` |
| `--gold` | `#f0c060` |
| `--off-white` | `#f7f5f0` |
| Font heading | Cormorant Garamond (serif) |
| Font body | DM Sans (sans-serif) |

**Efek visual:** Glassmorphism, gradient card headers, smooth transitions (cubic-bezier), animasi `bounceIn` / `fadeUp`.

---

## 🛠️ Tech Stack

- **Backend:** PHP (native, no framework)
- **Database:** MySQL via `mysqli` (prepared statements)
- **Frontend:** Bootstrap 5.3, Bootstrap Icons 1.11
- **PDF Download:** `html2canvas` 1.4.1 (kartu mahasiswa)
- **Confetti:** `canvas-confetti` 1.5.1
- **Session:** PHP native session

---

## 🗄️ Struktur Database (Tabel Utama)

```sql
-- Akun user
users (id, first_name, last_name, email, password, photo)

-- Data pendaftaran
pendaftaran (id, first_name, last_name, date_of_birth, email, phone, jurusan, school_level, nomor_ujian, photo)

-- Bank soal
soal (id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, gambar)

-- Hasil ujian
hasil_ujian (id, siswa_id, jumlah_benar, nilai, durasi, waktu_submit)

-- Daftar ulang (mahasiswa yang lulus)
daftar_ulang (id, user_id, email, nim, nama_lengkap, photo)
```

---

## 🔑 Alur Sistem

```
Daftar (pendaftaran.php)
    ↓
Login
    ↓
Ujian Seleksi (ujian.php)
    ↓
Hasil Ujian (hasil_ujian.php)
    ↓ [jika lulus]
Daftar Ulang (daftar_ulang.php)
    ↓
Pengumuman & Kartu Mahasiswa (pengumuman.php)
```

---

## ⚙️ Setup & Instalasi

1. **Clone / copy** semua file ke direktori web server (misal: `htdocs/` atau `www/`)
2. **Buat database** MySQL dan import struktur tabel sesuai skema di atas
3. **Konfigurasi koneksi** di file `koneksi.php`:
   ```php
   $koneksi = mysqli_connect("localhost", "root", "", "nama_database");
   ```
4. **Buat folder uploads:**
   ```
   uploads/foto/          ← foto pendaftaran
   uploads/daftar_ulang/  ← foto daftar ulang
   ```
5. **Pastikan PHP session** aktif dan `file_uploads = On` di `php.ini`
6. Akses melalui browser: `http://localhost/nama-folder/`

---

## 🔒 Keamanan

- Semua query database menggunakan **prepared statements** (`mysqli_prepare`)
- Input di-sanitasi dengan `htmlspecialchars()` sebelum ditampilkan
- Session check di setiap halaman yang memerlukan autentikasi
- Admin panel menggunakan session terpisah (`admin_id`, `is_admin`)
- **Nilai ujian tidak ditampilkan ke peserta** — hanya status lulus/tidak lulus yang terlihat; nilai hanya tersimpan di database dan dapat diakses admin

---

## 📁 Struktur Folder

```
/
├── koneksi.php
├── pendaftaran.php
├── ujian.php
├── hasil_ujian.php
├── pengumuman.php
├── dashboard.php
├── login.php / logout.php
├── daftar_ulang.php
├── download_pengumuman_pdf.php
├── LOGORBG.png
├── LOGO.jpeg
├── uploads/
│   ├── foto/
│   └── daftar_ulang/
└── admin/
    ├── dahboardadmin.php
    ├── pendaftaran_admin.php
    ├── bank_soal_admin.php
    ├── register_login_admin.php
    ├── edit_user_admin.php
    ├── edit_pendaftaran_admin.php
    ├── edit_soal_admin.php
    └── login_admin.php / logout_admin.php
```

---

## 📝 Catatan

- Ujian hanya bisa diikuti **satu kali** — peserta yang sudah lulus tidak bisa mengakses halaman ujian lagi
- Passing score default: **70 / 100** (bisa diubah di variabel `$passing_score`)
- Durasi ujian default: **20 menit 30 detik** (bisa diubah di variabel `TOTAL_SECONDS` pada `ujian.php`)
- NIM dihasilkan otomatis saat peserta melakukan daftar ulang

---

*Dibuat untuk keperluan akademik — Universitas Nusantara © 2026*
