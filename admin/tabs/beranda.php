<?php
// admin/tabs/beranda.php
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Admin <span>/ Beranda</span></div>
  <h2>Dashboard Admin</h2>
  <p>📅 <?= date('l, d F Y') ?> — Selamat datang, Administrator!</p>
</div>

<div class="stat-grid">
  <div class="stat-kartu">
    <div class="ikon-bg">👩‍🏫</div>
    <div class="angka"><?= $jmlGuru ?></div>
    <div class="label">Guru Aktif</div>
  </div>
  <div class="stat-kartu orange">
    <div class="ikon-bg">👥</div>
    <div class="angka"><?= $jmlSiswa ?></div>
    <div class="label">Siswa Aktif</div>
  </div>
  <div class="stat-kartu">
    <div class="ikon-bg">🖼️</div>
    <div class="angka"><?= $jmlBerita ?></div>
    <div class="label">Galeri Publik</div>
  </div>
  <div class="stat-kartu orange">
    <div class="ikon-bg">📩</div>
    <div class="angka"><?= $jmlPesan ?></div>
    <div class="label">Pesan Baru</div>
  </div>
</div>

<?php
$guruList   = $db->query("SELECT * FROM guru WHERE aktif = 1 ORDER BY created_at DESC LIMIT 5")->fetchAll();
$beritaList = $db->query("SELECT * FROM galeri ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;">
  <div class="tabel-wrap">
    <div class="tabel-header">
      <h3>Guru Terbaru</h3>
      <a href="?tab=guru" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <div class="tabel-scroll">
    <table>
      <thead><tr><th>Nama</th><th>Mapel</th></tr></thead>
      <tbody>
        <?php foreach($guruList as $g): ?>
        <tr><td><?= e($g['nama']) ?></td><td><?= e($g['mapel']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <div class="tabel-wrap">
    <div class="tabel-header">
      <h3>Galeri Terbaru</h3>
      <a href="?tab=galeri" class="btn btn-outline btn-sm">Kelola</a>
    </div>
    <div class="tabel-scroll">
    <table>
      <thead><tr><th>Judul</th><th>Kategori</th></tr></thead>
      <tbody>
        <?php foreach($beritaList as $b): ?>
        <tr><td><?= e(mb_strimwidth($b['judul'],0,30,'...')) ?></td><td><span class="badge badge-orange"><?= e($b['kategori']) ?></span></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
