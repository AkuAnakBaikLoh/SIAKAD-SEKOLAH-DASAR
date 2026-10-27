<?php
// admin/dashboard.php — Dashboard Admin
require_once __DIR__ . '/../config/database.php';
cekAdmin();

$db    = getDB();
$set   = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();
$tab   = $_GET['tab'] ?? 'beranda';
$flash = getFlash();

// Statistik
$jmlGuru    = $db->query("SELECT COUNT(*) FROM guru WHERE aktif = 1")->fetchColumn();
$jmlSiswa   = $db->query("SELECT COUNT(*) FROM siswa WHERE aktif = 1")->fetchColumn();
$jmlBerita  = $db->query("SELECT COUNT(*) FROM galeri WHERE dipublikasi = 1")->fetchColumn();
$jmlPesan   = $db->query("SELECT COUNT(*) FROM pesan_kontak WHERE sudah_dibaca = 0")->fetchColumn();

$pageTitle = 'Admin — ' . ($set['nama_sekolah'] ?? 'Sekolah');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css?v=<?= filemtime(__DIR__ . "/../assets/css/main.css") ?>">
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">
    <div class="nav-logo">
      <img src="<?= BASE_URL ?>/assets/logo.jpeg" alt="Logo Sekolah" class="nav-logo-ikon" style="object-fit: cover; background: transparent;">
      <div class="nav-logo-teks">
        <strong><?= e($set['nama_sekolah'] ?? '') ?></strong>
        <span>Panel Admin</span>
      </div>
    </div>
    <button type="button" class="nav-toggle" aria-label="Buka menu" onclick="document.getElementById('navMenu').classList.toggle('terbuka')">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-menu" id="navMenu">
      <a href="<?= BASE_URL ?>/index.php">🌐 Situs Publik</a>
      <a href="<?= BASE_URL ?>/logout.php" class="btn-login" style="background:var(--merah);">🚪 Keluar</a>
    </div>
  </div>
</nav>

<div class="notif-toast" id="notifToast"></div>

<div class="dash-layout">
  <!-- SIDEBAR -->
  <aside class="dash-sidebar">
    <div class="sidebar-user">
      <div class="sidebar-avatar-inisial">A</div>
      <div class="sidebar-nama">Administrator</div>
      <div class="sidebar-role">Super Admin</div>
    </div>
    <ul class="sidebar-menu">
      <li class="menu-judul">DASHBOARD</li>
      <li><a href="?tab=beranda" class="<?= $tab==='beranda'?'aktif':'' ?>"><span class="menu-ikon">📊</span> Beranda</a></li>
      <li class="menu-judul">MANAJEMEN</li>
      <li><a href="?tab=guru" class="<?= $tab==='guru'?'aktif':'' ?>"><span class="menu-ikon">👩‍🏫</span> Data Guru</a></li>
      <li><a href="?tab=galeri" class="<?= $tab==='galeri'?'aktif':'' ?>"><span class="menu-ikon">🖼️</span> Galeri Berita</a></li>
      <li><a href="?tab=kelas" class="<?= $tab==='kelas'?'aktif':'' ?>"><span class="menu-ikon">🏫</span> Data Kelas</a></li>
      <li><a href="?tab=siswa" class="<?= $tab==='siswa'?'aktif':'' ?>"><span class="menu-ikon">👥</span> Data Siswa</a></li>
      <li class="menu-judul">PENGATURAN</li>
      <li><a href="?tab=pengaturan" class="<?= $tab==='pengaturan'?'aktif':'' ?>"><span class="menu-ikon">⚙️</span> Pengaturan Sekolah</a></li>
      <li><a href="?tab=pesan" class="<?= $tab==='pesan'?'aktif':'' ?>"><span class="menu-ikon">📩</span> Pesan Kontak <?= $jmlPesan>0?"<span style='background:var(--orange);color:#fff;border-radius:20px;padding:1px 7px;font-size:11px;margin-left:4px;'>$jmlPesan</span>":'' ?></a></li>
    </ul>
  </aside>

  <!-- KONTEN -->
  <main class="dash-konten">
    <?php if ($flash): ?>
    <div class="flash-msg flash-<?= $flash['type'] ?>">
      <?= $flash['type']==='sukses' ? '✅' : '❌' ?> <?= e($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <?php
    switch($tab) {
        case 'guru':     include __DIR__ . '/tabs/guru.php'; break;
        case 'galeri':   include __DIR__ . '/tabs/galeri.php'; break;
        case 'kelas':    include __DIR__ . '/tabs/kelas.php'; break;
        case 'siswa':    include __DIR__ . '/tabs/siswa.php'; break;
        case 'pengaturan': include __DIR__ . '/tabs/pengaturan.php'; break;
        case 'pesan':    include __DIR__ . '/tabs/pesan.php'; break;
        default:         include __DIR__ . '/tabs/beranda.php'; break;
    }
    ?>
  </main>
</div>

<script>
function tampilNotif(pesan, tipe = 'sukses') {
  const el = document.getElementById('notifToast');
  const ikon = tipe === 'sukses' ? '✅' : '❌';
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
</body>
</html>
