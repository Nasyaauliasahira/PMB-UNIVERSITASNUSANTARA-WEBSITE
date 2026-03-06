<?php
session_start();
include "koneksi.php";

$has_session = isset($_SESSION['user_id']);
$user = [];
$user_name = 'Tamu';
$is_lulus = false;
$sudah_ujian = false;

if ($has_session) {
  $user_id = $_SESSION['user_id'];
  $user_name = $_SESSION['user_name'] ?? 'User';

  $query = "SELECT * FROM users WHERE id = ?";
  $stmt = mysqli_prepare($koneksi, $query);
  mysqli_stmt_bind_param($stmt, "i", $user_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $user = mysqli_fetch_assoc($result) ?: [];
  mysqli_stmt_close($stmt);

  $query_lulus = "SELECT nilai FROM hasil_ujian WHERE siswa_id = ? ORDER BY waktu_submit DESC LIMIT 1";
  $stmt_lulus = mysqli_prepare($koneksi, $query_lulus);
  mysqli_stmt_bind_param($stmt_lulus, "i", $user_id);
  mysqli_stmt_execute($stmt_lulus);
  $result_lulus = mysqli_stmt_get_result($stmt_lulus);
  $hasil_lulus = mysqli_fetch_assoc($result_lulus);
  mysqli_stmt_close($stmt_lulus);

  $is_lulus = ($hasil_lulus && $hasil_lulus['nilai'] >= 70);
  $sudah_ujian = ($hasil_lulus !== null);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>PMB Universitas Nusantara</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* =============================================
   ROOT & BASE
   ============================================= */
:root {
  --navy:       #0d1f35;
  --navy-mid:   #1e3a5f;
  --navy-light: #2d5a7b;
  --orange:     #ff9800;
  --orange-dark:#e68900;
  --gold:       #f0c060;
  --white:      #ffffff;
  --off-white:  #f7f5f0;
  --text-muted: rgba(255,255,255,0.6);
  --glass:      rgba(255,255,255,0.06);
  --glass-border: rgba(255,255,255,0.12);
  --shadow-deep: 0 24px 64px rgba(13,31,53,0.35);
  --shadow-card: 0 8px 32px rgba(13,31,53,0.18);
  --radius-lg:  20px;
  --radius-xl:  32px;
  --transition: all 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

* { margin:0; padding:0; box-sizing:border-box; }

html { scroll-behavior:smooth; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--off-white);
  color: var(--navy);
  overflow-x: hidden;
}

/* =============================================
   NAVBAR — Glassmorphism Premium
   ============================================= */
.navbar {
  background: rgba(13, 31, 53, 0.92);
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
  background: rgba(13, 31, 53, 0.98);
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
}

.navbar-logo {
  width: 52px;
  height: 52px;
  object-fit: contain;
}

.nav-btn {
  padding: 9px 20px;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  gap: 7px;
  letter-spacing: 0.3px;
}

.nav-btn-login {
  background: transparent;
  color: rgba(255,255,255,0.85);
  border: 1px solid var(--glass-border);
}

.nav-btn-login:hover {
  background: var(--glass);
  color: white;
  border-color: rgba(255,255,255,0.3);
}

.nav-btn-register {
  background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
  color: white;
  border: none;
  box-shadow: 0 4px 16px rgba(255,152,0,0.35);
}

.nav-btn-register:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(255,152,0,0.45);
  color: white;
}

/* Profile Dropdown */
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
   HERO — Carousel Premium
   ============================================= */
.hero-section {
  position: relative;
  overflow: hidden;
}

.carousel-item {
  height: 580px;
  position: relative;
}

.carousel-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  animation: heroZoom 14s ease-in-out infinite;
  will-change: transform;
}

@keyframes heroZoom {
  0%   { transform: scale(1); }
  50%  { transform: scale(1.05); }
  100% { transform: scale(1); }
}

/* Multi-layer overlay */
.hero-overlay {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(to top,   rgba(13,31,53,0.85) 0%,   rgba(13,31,53,0.3) 40%, transparent 70%),
    linear-gradient(to right, rgba(13,31,53,0.5) 0%, transparent 60%);
  display: flex;
  align-items: flex-end;
  padding: 0 0 70px 70px;
  z-index: 2;
}

.hero-content {
  color: white;
  max-width: 680px;
  animation: fadeUp 1s ease-out both;
}

/* Decorative line */
.hero-label {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--orange);
  margin-bottom: 18px;
}

.hero-label::before {
  content: '';
  display: block;
  width: 40px;
  height: 2px;
  background: var(--orange);
}

.hero-content h1 {
  font-family: 'Cormorant Garamond', serif;
  font-size: 54px;
  font-weight: 700;
  line-height: 1.15;
  margin-bottom: 18px;
  text-shadow: 0 4px 24px rgba(0,0,0,0.3);
}

.hero-content p {
  font-size: 15px;
  line-height: 1.75;
  color: rgba(255,255,255,0.82);
  max-width: 520px;
  margin-bottom: 32px;
}

.hero-cta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, var(--orange), var(--orange-dark));
  color: white;
  padding: 14px 32px;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  box-shadow: 0 8px 32px rgba(255,152,0,0.4);
  transition: var(--transition);
}

.hero-cta:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 40px rgba(255,152,0,0.55);
  color: white;
}

/* Carousel controls — pill style */
.carousel-control-prev,
.carousel-control-next {
  width: 48px;
  height: 48px;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 50%;
  top: 50%;
  transform: translateY(-50%);
  opacity: 1;
  transition: var(--transition);
}

.carousel-control-prev { left: 24px; }
.carousel-control-next { right: 24px; }

.carousel-control-prev:hover,
.carousel-control-next:hover {
  background: var(--orange);
  border-color: var(--orange);
  transform: translateY(-50%) scale(1.1);
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
  width: 16px;
  height: 16px;
}

.carousel-indicators {
  bottom: 28px;
  gap: 6px;
}

.carousel-indicators [data-bs-target] {
  width: 28px;
  height: 4px;
  border-radius: 2px;
  background: rgba(255,255,255,0.4);
  border: none;
  transition: var(--transition);
}

.carousel-indicators .active {
  background: var(--orange);
  width: 44px;
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(20px); }
  to   { opacity:1; transform:translateY(0); }
}

/* =============================================
   ICON MENU — Glassmorphism Cards
   ============================================= */
.icon-menu-section {
  padding: 0 0 60px;
  margin-top: -70px;
  position: relative;
  z-index: 10;
}

.icon-menu-container {
  display: flex;
  gap: 18px;
  justify-content: center;
  flex-wrap: wrap;
}

.icon-menu {
  text-align: center;
  background: transparent;
  padding: 28px 22px 22px;
  border-radius: var(--radius-lg);
  box-shadow: none;
  border: none;
  transition: var(--transition);
  min-width: 130px;
  flex: 0 1 auto;
  text-decoration: none;
  color: inherit;
  display: block;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}

.icon-menu::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--navy-mid), var(--orange));
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.4s ease;
}

.icon-menu:hover::before { transform: scaleX(1); }

.icon-menu:hover {
  transform: translateY(-8px);
  box-shadow: none;
  text-decoration: none;
  color: inherit;
}

.icon-menu-icon {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
  color: var(--orange);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin: 0 auto 14px;
  transition: var(--transition);
  box-shadow: 0 6px 20px rgba(13,31,53,0.25);
}

.icon-menu:hover .icon-menu-icon {
  background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
  color: white;
  box-shadow: 0 8px 24px rgba(255,152,0,0.4);
  transform: scale(1.08) rotate(-4deg);
}

.icon-menu-text {
  font-size: 13px;
  font-weight: 600;
  color: var(--navy);
  letter-spacing: 0.2px;
  line-height: 1.4;
}

/* =============================================
   WELCOME SECTION — Premium Dark
   ============================================= */
.welcome-section {
  background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 50%, #1a3d5e 100%);
  color: white;
  padding: 80px 60px;
  margin: 60px 0;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-deep);
  position: relative;
  overflow: hidden;
}

/* Decorative circles */
.welcome-section::before {
  content: '';
  position: absolute;
  top: -120px; right: -120px;
  width: 400px; height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,152,0,0.12) 0%, transparent 70%);
  pointer-events: none;
}

.welcome-section::after {
  content: '';
  position: absolute;
  bottom: -80px; left: -60px;
  width: 280px; height: 280px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(45,90,123,0.4) 0%, transparent 70%);
  pointer-events: none;
}

.welcome-content {
  display: grid;
  grid-template-columns: 0.85fr 1.15fr;
  gap: 60px;
  align-items: center;
  position: relative;
  z-index: 1;
}

.welcome-images {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.welcome-image-large {
  width: 100%;
  height: 260px;
  object-fit: cover;
  border-radius: 16px;
  grid-column: 1;
  grid-row: 1 / 3;
  box-shadow: 0 12px 36px rgba(0,0,0,0.3);
  border: 2px solid rgba(255,255,255,0.1);
  transition: var(--transition);
}

.welcome-image-large:hover { transform: scale(1.02); }

.welcome-image-small {
  width: 100%;
  height: 118px;
  object-fit: cover;
  border-radius: 14px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.25);
  border: 2px solid rgba(255,255,255,0.08);
  transition: var(--transition);
}

.welcome-image-small:hover { transform: scale(1.03); }

.welcome-label {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--orange);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  margin-bottom: 16px;
}

.welcome-label::before {
  content: '';
  width: 32px;
  height: 2px;
  background: var(--orange);
}

.welcome-text h2 {
  font-family: 'Cormorant Garamond', serif;
  font-weight: 700;
  font-size: 42px;
  line-height: 1.2;
  margin-bottom: 22px;
}

.welcome-text h2 em {
  color: var(--gold);
  font-style: italic;
}

.welcome-text p {
  font-size: 15px;
  line-height: 1.85;
  color: rgba(255,255,255,0.78);
  margin-bottom: 36px;
}

/* Stat Cards */
.welcome-stats {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.stat-card {
  flex: 1;
  min-width: 100px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 14px;
  padding: 18px 16px;
  text-align: center;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.stat-card::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--orange), var(--gold));
}

.stat-card:hover {
  background: rgba(255,152,0,0.1);
  border-color: rgba(255,152,0,0.3);
  transform: translateY(-4px);
}

.stat-number {
  font-family: 'Cormorant Garamond', serif;
  font-size: 44px;
  font-weight: 700;
  color: white;
  display: block;
  line-height: 1;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 12px;
  font-weight: 500;
  color: rgba(255,255,255,0.6);
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* =============================================
   PROGRAMS SECTION
   ============================================= */
.programs-section {
  padding: 70px 0;
}

.section-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--orange);
  margin-bottom: 14px;
}

.section-eyebrow::before {
  content: '';
  width: 32px;
  height: 2px;
  background: var(--orange);
}

.programs-header {
  text-align: center;
  margin-bottom: 50px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.programs-header h2 {
  font-family: 'Cormorant Garamond', serif;
  font-weight: 700;
  color: var(--navy);
  font-size: 38px;
  line-height: 1.2;
  margin-bottom: 14px;
}

.programs-subtitle {
  color: #6b7a8a;
  font-size: 15px;
  max-width: 600px;
  line-height: 1.75;
}

.program-card {
  background: white;
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-card);
  transition: var(--transition);
  text-decoration: none;
  color: inherit;
  display: block;
}

.program-card:hover {
  box-shadow: var(--shadow-deep);
  transform: translateY(-10px);
  text-decoration: none;
  color: inherit;
}

.program-card-image {
  width: 100%;
  aspect-ratio: 3/4;
  background-size: cover;
  background-position: center;
  position: relative;
  overflow: hidden;
  transition: transform 0.6s ease;
}

.program-card:hover .program-card-image {
  transform: scale(1.05);
}

.program-card-overlay {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  background: linear-gradient(to top, rgba(13,31,53,0.95) 0%, rgba(13,31,53,0.4) 50%, transparent 100%);
  padding: 28px 24px 24px;
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  height: 100%;
}

.program-card-tag {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: var(--orange);
  margin-bottom: 8px;
}

.program-card-title {
  font-family: 'Cormorant Garamond', serif;
  font-weight: 600;
  color: white;
  font-size: 26px;
}

/* =============================================
   HELPDESK SECTION — Elevated Cards
   ============================================= */
.helpdesk-section {
  background: white;
  padding: 70px 50px;
  margin: 60px 0;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-card);
  position: relative;
  overflow: hidden;
}

.helpdesk-section::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--navy), var(--navy-light), var(--orange));
}

.helpdesk-section .section-header {
  text-align: center;
  margin-bottom: 50px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.helpdesk-section h2 {
  font-family: 'Cormorant Garamond', serif;
  font-weight: 700;
  color: var(--navy);
  font-size: 36px;
  margin-bottom: 12px;
}

.helpdesk-subtitle {
  color: #8898aa;
  font-size: 15px;
  max-width: 500px;
  line-height: 1.7;
}

.helpdesk-card {
  background: var(--off-white);
  border-radius: var(--radius-lg);
  padding: 36px 28px;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
  height: 100%;
  border: 1px solid rgba(13,31,53,0.06);
}

/* Top gradient bar replacing left border */
.helpdesk-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--navy), var(--orange));
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.5s ease;
}

.helpdesk-card:hover::before { transform: scaleX(1); }

/* Hover: navy background, white text */
.helpdesk-card:hover {
  background: var(--navy);
  border-color: var(--navy);
  transform: translateY(-6px);
  box-shadow: var(--shadow-deep);
}

.helpdesk-card:hover .helpdesk-icon { background: rgba(255,152,0,0.15); color: var(--orange); }
.helpdesk-card:hover .helpdesk-card-title { color: white; }
.helpdesk-card:hover .helpdesk-card-text { color: rgba(255,255,255,0.7); }
.helpdesk-card:hover .helpdesk-detail-label { color: rgba(255,255,255,0.5); }
.helpdesk-card:hover .helpdesk-detail-value { color: white; }

.helpdesk-icon {
  width: 60px;
  height: 60px;
  background: rgba(13,31,53,0.08);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  color: var(--navy-mid);
  margin-bottom: 22px;
  transition: var(--transition);
}

.helpdesk-card-title {
  font-family: 'Cormorant Garamond', serif;
  font-weight: 700;
  color: var(--navy);
  margin-bottom: 18px;
  font-size: 20px;
  transition: var(--transition);
}

.helpdesk-card-text { transition: var(--transition); }

.helpdesk-detail {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.helpdesk-detail-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.helpdesk-detail-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: #9aabba;
  transition: var(--transition);
}

.helpdesk-detail-value {
  font-size: 14px;
  color: var(--navy);
  font-weight: 500;
  transition: var(--transition);
}

/* =============================================
   FOOTER — Dark Navy Premium
   ============================================= */
footer {
  background: linear-gradient(160deg, var(--navy) 0%, #0d1e30 100%);
  color: white;
  padding: 70px 0 28px;
  margin-top: 60px;
  position: relative;
  overflow: hidden;
}

footer::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--navy-light), var(--orange), var(--gold), var(--orange), var(--navy-light));
}

/* Decorative circle */
footer::after {
  content: '';
  position: absolute;
  bottom: -200px; right: -150px;
  width: 500px; height: 500px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,152,0,0.05) 0%, transparent 70%);
  pointer-events: none;
}

.footer-logo-section {
  display: flex;
  gap: 16px;
  align-items: center;
  margin-bottom: 50px;
}

.footer-logo {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
}

.footer-logo img { width: 100%; height: 100%; object-fit: contain; }

.footer-brand h3 {
  font-family: 'Cormorant Garamond', serif;
  font-weight: 700;
  font-size: 20px;
  line-height: 1.3;
  color: white;
}

.footer-content {
  display: grid;
  grid-template-columns: 1.2fr 1fr 0.8fr 0.8fr;
  gap: 50px;
  margin-bottom: 50px;
  position: relative;
  z-index: 1;
}

.footer-section h5 {
  font-family: 'Cormorant Garamond', serif;
  font-weight: 700;
  font-size: 16px;
  margin-bottom: 18px;
  color: white;
  letter-spacing: 0.5px;
}

.footer-section p {
  font-size: 13px;
  line-height: 1.85;
  color: var(--text-muted);
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.footer-links a {
  color: var(--text-muted);
  text-decoration: none;
  font-size: 13px;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.footer-links a::before {
  content: '';
  width: 0;
  height: 1px;
  background: var(--orange);
  transition: width 0.3s ease;
}

.footer-links a:hover {
  color: white;
  padding-left: 4px;
}

.footer-links a:hover::before { width: 14px; }

.footer-social {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.footer-social a {
  width: 40px;
  height: 40px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  text-decoration: none;
  transition: var(--transition);
  font-size: 16px;
}

.footer-social a:hover {
  background: rgba(255,152,0,0.15);
  border-color: var(--orange);
  color: var(--orange);
  transform: translateY(-4px);
}

.footer-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  margin-bottom: 28px;
}

.footer-bottom {
  text-align: center;
  font-size: 12px;
  color: rgba(255,255,255,0.35);
  position: relative;
  z-index: 1;
}

.footer-bottom span { color: var(--orange); }

/* =============================================
   UTILITIES & RESPONSIVE
   ============================================= */
@media (max-width: 991px) {
  .hero-overlay { padding: 0 30px 50px; }
  .hero-content h1 { font-size: 38px; }
  .welcome-content { grid-template-columns: 1fr; }
  .welcome-section { padding: 50px 30px; }
  .footer-content { grid-template-columns: 1fr 1fr; gap: 35px; }
  .helpdesk-section { padding: 50px 28px; }
}

@media (max-width: 768px) {
  .carousel-item { height: 420px; }
  .hero-content h1 { font-size: 30px; }
  .hero-overlay { padding: 0 20px 40px; }
  .hero-cta { padding: 12px 24px; font-size: 13px; }
  .icon-menu-container { gap: 10px; }
  .icon-menu { min-width: 100px; padding: 20px 14px 16px; }
  .icon-menu-icon { width: 58px; height: 58px; font-size: 22px; }
  .welcome-text h2 { font-size: 32px; }
  .stat-number { font-size: 34px; }
  .footer-content { grid-template-columns: 1fr; gap: 28px; }
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
    <a class="navbar-brand" href="#">
      <img src="LOGORBG.png" alt="Logo" class="navbar-logo">
      <span>Universitas Nusantara</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="ms-auto d-flex align-items-center gap-3">
        <?php if ($has_session): ?>
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
              <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? 'User'); ?></div>
              <a href="edit_profile.php"><i class="bi bi-person-gear"></i> Edit Profile</a>
              <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a href="login.php" class="nav-btn nav-btn-login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
          <a href="register.php" class="nav-btn nav-btn-register"><i class="bi bi-person-plus"></i> Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- ============ HERO CAROUSEL ============ -->
<div class="hero-section">
  <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img src="banner%20(1).jpg" alt="Banner 1">
      </div>
      <div class="carousel-item">
        <img src="banner%20(2).jpg" alt="Banner 2">
      </div>
      <div class="carousel-item">
        <img src="banner%20(3).jpg" alt="Banner 3">
      </div>
      <div class="carousel-item">
        <img src="banner%20(4).jpg" alt="Banner 4">
      </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="0" class="active"></button>
      <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="2"></button>
      <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="3"></button>
    </div>
  </div>

  <div class="hero-overlay">
    <div class="hero-content">
      <div class="hero-label">Penerimaan Mahasiswa Baru 2025/2026</div>
      <h1>Raih Masa Depan<br>Bersama <em style="font-style:italic; color: var(--gold);">Universitas<br>Nusantara</em></h1>
      <p>Pendaftaran program studi dilakukan secara online. Bergabunglah dengan ribuan mahasiswa berprestasi dan wujudkan impianmu bersama kami.</p>
      <?php if ($has_session): ?>
        <a href="pendaftaran.php" class="hero-cta"><i class="bi bi-arrow-right-circle"></i> Mulai Pendaftaran</a>
      <?php else: ?>
        <a href="register.php" class="hero-cta"><i class="bi bi-arrow-right-circle"></i> Daftar Sekarang</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ============ ICON MENU ============ -->
<section class="icon-menu-section">
  <div class="container">
    <div class="icon-menu-container">

      <!-- Pendaftaran / Ujian button -->
      <?php if (!$sudah_ujian): ?>
        <?php if ($has_session): ?>
        <a href="pendaftaran.php" class="icon-menu">
          <div class="icon-menu-icon"><i class="bi bi-building"></i></div>
          <div class="icon-menu-text">Pendaftaran</div>
        </a>
        <?php else: ?>
        <div class="icon-menu" onclick="requireLogin()">
          <div class="icon-menu-icon"><i class="bi bi-building"></i></div>
          <div class="icon-menu-text">Pendaftaran</div>
        </div>
        <?php endif; ?>
      <?php elseif ($is_lulus): ?>
        <a href="login_pendaftaran.php" class="icon-menu">
          <div class="icon-menu-icon"><i class="bi bi-building"></i></div>
          <div class="icon-menu-text">Pendaftaran Ujian</div>
        </a>
      <?php else: ?>
        <div class="icon-menu" style="opacity:0.45; cursor:not-allowed;">
          <div class="icon-menu-icon"><i class="bi bi-building"></i></div>
          <div class="icon-menu-text">Pendaftaran</div>
        </div>
      <?php endif; ?>

      <!-- Kelulusan / Pemberitahuan -->
      <?php if ($is_lulus): ?>
        <a href="pengumuman_lulus.php" class="icon-menu">
          <div class="icon-menu-icon" style="background:linear-gradient(135deg,#1a6e3c,#27ae60);color:white;"><i class="bi bi-trophy"></i></div>
          <div class="icon-menu-text">Pengumuman Kelulusan</div>
        </a>
      <?php elseif ($sudah_ujian): ?>
        <a href="pengumuman_lulus.php" class="icon-menu">
          <div class="icon-menu-icon" style="background:linear-gradient(135deg,#8b1a1a,#dc3545);color:white;"><i class="bi bi-x-circle"></i></div>
          <div class="icon-menu-text">Pemberitahuan Tidak Lulus</div>
        </a>
      <?php else: ?>
        <div class="icon-menu">
          <div class="icon-menu-icon"><i class="bi bi-mortarboard"></i></div>
          <div class="icon-menu-text">Pengumuman Kelulusan</div>
        </div>
      <?php endif; ?>

      <!-- Daftar Ulang -->
      <?php if ($is_lulus): ?>
        <a href="daftar_ulang.php" class="icon-menu">
          <div class="icon-menu-icon" style="background:linear-gradient(135deg,#e68900,#ff9800);color:white;"><i class="bi bi-clipboard-check"></i></div>
          <div class="icon-menu-text">Daftar Ulang</div>
        </a>
      <?php elseif ($sudah_ujian): ?>
        <div class="icon-menu" style="opacity:0.3; cursor:not-allowed;">
          <div class="icon-menu-icon" style="background:linear-gradient(135deg,#555,#777);color:white;"><i class="bi bi-clipboard-check"></i></div>
          <div class="icon-menu-text">Daftar Ulang</div>
        </div>
      <?php else: ?>
        <div class="icon-menu">
          <div class="icon-menu-icon"><i class="bi bi-file-earmark-text"></i></div>
          <div class="icon-menu-text">Daftar Ulang</div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>

<div class="container">

  <!-- ============ WELCOME SECTION ============ -->
  <div class="welcome-section">
    <div class="welcome-content">
      <div class="welcome-images">
        <img src="satu.jpg" alt="Kampus" class="welcome-image-large">
        <img src="dua.jpg" alt="Kegiatan" class="welcome-image-small">
        <img src="tiga.jpg" alt="Mahasiswa" class="welcome-image-small">
      </div>
      <div class="welcome-text">
        <div class="welcome-label">About Universitas Nusantara</div>
        <h2>Selamat Datang di<br><em>Universitas Nusantara</em></h2>
        <p>Sebagai perguruan tinggi terkemuka di Indonesia, Universitas Nusantara berkomitmen untuk mencetak generasi unggul yang berdaya saing global melalui pendidikan berkualitas, penelitian inovatif, dan pengabdian kepada masyarakat.</p>
        <div class="welcome-stats">
          <div class="stat-card">
            <span class="stat-number">2k+</span>
            <span class="stat-label">Students</span>
          </div>
          <div class="stat-card">
            <span class="stat-number">3.5</span>
            <span class="stat-label">Avg. CGPA</span>
          </div>
          <div class="stat-card">
            <span class="stat-number">95%</span>
            <span class="stat-label">Graduates</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ PROGRAMS ============ -->
  <section class="programs-section">
    <div class="programs-header">
      <div class="section-eyebrow">Program Kami</div>
      <h2>Program Unggulan<br>Universitas Nusantara</h2>
      <p class="programs-subtitle">Pilih program yang dirancang untuk kebutuhan industri modern dengan kurikulum terbarukan dan pengajar berpengalaman.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4 col-sm-6">
        <a href="#" class="program-card">
          <div class="program-card-image" style="background:url('empat.jpg') center/cover no-repeat;">
            <div class="program-card-overlay">
              <div class="program-card-tag">Fakultas Ekonomi</div>
              <h5 class="program-card-title">Business</h5>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-4 col-sm-6">
        <a href="#" class="program-card">
          <div class="program-card-image" style="background:url('lima.jpg') center/cover no-repeat;">
            <div class="program-card-overlay">
              <div class="program-card-tag">Fakultas Teknologi</div>
              <h5 class="program-card-title">IT & Software</h5>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-4 col-sm-6">
        <a href="#" class="program-card">
          <div class="program-card-image" style="background:url('enam.jpg') center/cover no-repeat;">
            <div class="program-card-overlay">
              <div class="program-card-tag">Fakultas Seni</div>
              <h5 class="program-card-title">Design</h5>
            </div>
          </div>
        </a>
      </div>
    </div>
  </section>

  <!-- ============ HELPDESK ============ -->
  <div class="helpdesk-section">
    <div class="section-header">
      <div class="section-eyebrow">Bantuan</div>
      <h2>Layanan Helpdesk</h2>
      <p class="helpdesk-subtitle">Kami siap membantu menyelesaikan masalah online dan permasalahan layanan offline Anda.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="helpdesk-card">
          <div class="helpdesk-icon"><i class="bi bi-telephone-fill"></i></div>
          <div class="helpdesk-card-title">Unit Layanan Terpadu</div>
          <div class="helpdesk-card-text">
            <div class="helpdesk-detail">
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Jam Operasional</span>
                <span class="helpdesk-detail-value">08.00 – 17.00 WIB, Hari Kerja</span>
              </div>
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Telepon</span>
                <span class="helpdesk-detail-value">(021) 1234-5678</span>
              </div>
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Email</span>
                <span class="helpdesk-detail-value">admin@universitas.ac.id</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="helpdesk-card">
          <div class="helpdesk-icon"><i class="bi bi-chat-dots-fill"></i></div>
          <div class="helpdesk-card-title">Kantorik — Chat Online</div>
          <div class="helpdesk-card-text">
            <div class="helpdesk-detail">
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Layanan</span>
                <span class="helpdesk-detail-value">Chat Online 24 Jam</span>
              </div>
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Jam Operasional</span>
                <span class="helpdesk-detail-value">Setiap Hari, 24 Jam</span>
              </div>
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Email</span>
                <span class="helpdesk-detail-value">admin@universitas.ac.id</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="helpdesk-card">
          <div class="helpdesk-icon"><i class="bi bi-briefcase-fill"></i></div>
          <div class="helpdesk-card-title">Hall dan Join Karir</div>
          <div class="helpdesk-card-text">
            <div class="helpdesk-detail">
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Jam Operasional</span>
                <span class="helpdesk-detail-value">08.00 – 17.00 WIB</span>
              </div>
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Telepon</span>
                <span class="helpdesk-detail-value">(021) 9876-5432</span>
              </div>
              <div class="helpdesk-detail-row">
                <span class="helpdesk-detail-label">Email</span>
                <span class="helpdesk-detail-value">admin@universitas.ac.id</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /container -->

<!-- ============ FOOTER ============ -->
<footer>
  <div class="container-fluid px-5">
    <div class="footer-logo-section">
      <div class="footer-logo">
        <img src="LOGORBG.png" alt="Logo">
      </div>
      <div class="footer-brand">
        <h3>Universitas<br>Nusantara</h3>
      </div>
    </div>

    <div class="footer-content">
      <div class="footer-section">
        <h5>Kontak Kami</h5>
        <p>Direktorat Akademik — Kantor Seleksi Masuk Universitas Nusantara<br><br>
        Gedung Rektorat Lt. 2, Kampus Harmoni<br>
        Jl. Merdeka Raya No. 45, Kota Harmoni</p>
      </div>
      <div class="footer-section">
        <h5>Unit Layanan Terpadu</h5>
        <p>Gedung Rektorat Universitas Nusantara Lt. 1<br>
        Kampus Harmoni, Jl. Merdeka Raya No. 45<br>
        Kota Harmoni</p>
      </div>
      <div class="footer-section">
        <h5>Tautan Cepat</h5>
        <ul class="footer-links">
          <li><a href="#">Beranda</a></li>
          <li><a href="#">Program Akademik</a></li>
          <li><a href="#">Beasiswa</a></li>
          <li><a href="#">Kontak</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h5>Informasi</h5>
        <ul class="footer-links">
          <li><a href="#">Tentang Kami</a></li>
          <li><a href="#">Kebijakan Privasi</a></li>
          <li><a href="#">Syarat & Ketentuan</a></li>
          <li><a href="#">FAQ</a></li>
        </ul>
        <div class="footer-social">
          <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" title="YouTube"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
    </div>

    <div class="footer-divider"></div>
    <div class="footer-bottom">
      <p>&copy; 2026 <span>Universitas Nusantara</span> — All Rights Reserved</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Navbar scroll effect
  window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if (window.scrollY > 30) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
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

  // Login gate
  function requireLogin() {
    alert('Silakan login terlebih dahulu untuk melanjutkan.');
  }

  // Staggered icon menu animation
  document.querySelectorAll('.icon-menu').forEach((el, i) => {
    el.style.animationDelay = `${i * 0.1}s`;
    el.style.animation = 'fadeUp 0.6s ease-out both';
    el.style.animationDelay = `${i * 0.12 + 0.3}s`;
  });
</script>
</body>
</html>
