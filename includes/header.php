<?php
// includes/header.php — Header umum untuk halaman publik
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// Ambil pengaturan sekolah
$set = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();
$namaSekolah = $set['nama_sekolah'] ?? 'SDIT BANI LUQMAN';
$fotoSekolah = $set['foto_sekolah'] ? fotoUrl($set['foto_sekolah']) : '';

$halamanAktif = $halamanAktif ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? $namaSekolah) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css?v=<?= filemtime(__DIR__ . "/../assets/css/main.css") ?>">
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/favicon.ico">
  <?php if ($fotoSekolah): ?>
  <style>
    .hero { --hero-bg-img: url('<?= $fotoSekolah ?>'); }
  </style>
  <?php endif; ?>
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">
    <a href="<?= BASE_URL ?>/index.php" class="nav-logo">
      <img src="<?= BASE_URL ?>/assets/logo.jpeg" alt="Logo Sekolah" class="nav-logo-ikon" style="object-fit: cover; background: transparent;">
      <div class="nav-logo-teks">
        <strong><?= e($namaSekolah) ?></strong>
        <span>Portal Sekolah</span>
      </div>
    </a>
    <button type="button" class="nav-toggle" aria-label="Buka menu" onclick="document.getElementById('navMenu').classList.toggle('terbuka')">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-menu" id="navMenu">
      <a href="<?= BASE_URL ?>/index.php" class="<?= $halamanAktif === 'beranda' ? 'aktif' : '' ?>">Beranda</a>
      <a href="<?= BASE_URL ?>/guru-publik.php" class="<?= $halamanAktif === 'guru' ? 'aktif' : '' ?>">Data Guru</a>
      <a href="<?= BASE_URL ?>/kontak.php" class="<?= $halamanAktif === 'kontak' ? 'aktif' : '' ?>">Kontak</a>
      <?php if (!empty($_SESSION['guru_id'])): ?>
        <a href="<?= BASE_URL ?>/guru/dashboard.php" class="btn-login">Dashboard Guru</a>
      <?php elseif (!empty($_SESSION['admin_id'])): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn-login">Dashboard Admin</a>
      <?php elseif (!empty($_SESSION['siswa_id'])): ?>
        <a href="<?= BASE_URL ?>/siswa/dashboard.php" class="btn-login">📚 Portal Siswa</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="btn-login">🔑 Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Notifikasi Toast -->
<div class="notif-toast" id="notifToast"></div>

<script>
function tampilNotif(pesan, tipe = 'sukses') {
  const el = document.getElementById('notifToast');
  const ikon = tipe === 'sukses' ? '✅' : tipe === 'error' ? '❌' : 'ℹ️';
  el.innerHTML = ikon + ' ' + pesan;
  el.className = 'notif-toast notif-' + tipe + ' muncul';
  setTimeout(() => el.classList.remove('muncul'), 4000);
}
function bukaModal(id) { document.getElementById(id).classList.add('terbuka'); }
function tutupModal(id) { document.getElementById(id).classList.remove('terbuka'); }
document.querySelectorAll('.modal-bg').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('terbuka'); });
});
</script>
