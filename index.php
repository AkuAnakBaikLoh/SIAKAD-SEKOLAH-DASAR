<?php
// index.php — Halaman Beranda Publik
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db       = getDB();
$set      = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();
$berita   = $db->query("SELECT * FROM galeri WHERE dipublikasi = 1 ORDER BY created_at DESC LIMIT 6")->fetchAll();
$guru     = $db->query("SELECT * FROM guru WHERE aktif = 1 ORDER BY nama LIMIT 8")->fetchAll();
$jmlGuru  = $db->query("SELECT COUNT(*) FROM guru WHERE aktif = 1")->fetchColumn();
$jmlSiswa = $db->query("SELECT COUNT(*) FROM siswa WHERE aktif = 1")->fetchColumn();
$jmlKelas = $db->query("SELECT COUNT(*) FROM kelas")->fetchColumn();

$halamanAktif = 'beranda';
$pageTitle    = ($set['nama_sekolah'] ?? 'SMP') . ' — Beranda';
$fotoSekolah  = $set['foto_sekolah'] ? fotoUrl($set['foto_sekolah']) : '';
include __DIR__ . '/includes/header.php';
?>

<!-- ==================== HERO ==================== -->
<section class="hero">
  <div class="hero-bg" <?= $fotoSekolah ? "style=\"background-image:url('$fotoSekolah')\"" : '' ?>></div>
  <div class="hero-deko1"></div>
  <div class="hero-deko2"></div>
  <div class="hero-inner">
    <div>
      <h1><?= e($set['nama_sekolah'] ?? 'SDIT BANI LUQMAN') ?></h1>
      <p class="hero-sub" style="color: white;font-size: 18px;">
        "Membentuk generasi cerdas, berkarakter, dan berdaya saing global melalui
        pendidikan berkualitas dengan fondasi nilai-nilai Pancasila"
      </p>
      <div class="hero-actions">
        <a href="#profil" class="btn btn-orange btn-lg">📖 Profil Sekolah</a>
        <a href="#galeri" class="btn btn-outline-putih btn-lg">🖼️ Galeri Kegiatan</a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="angka" style="color: white;"><?= $jmlGuru ?></div>
          <div class="label" style="color: white;">Guru Pengajar</div>
        </div>
        <div class="hero-stat">
          <div class="angka" style="color: white;"><?= $jmlSiswa ?></div>
          <div class="label" style="color: white;">Siswa Aktif</div>
        </div>
        <div class="hero-stat">
          <div class="angka" style="color: white;"><?= $jmlKelas ?></div>
          <div class="label" style="color: white;">Kelas</div>
        </div>
        <div class="hero-stat">
          <div class="angka" style="color: white;"><?= e($set['tahun_berdiri'] ?? '1984') ?></div>
          <div class="label" style="color: white;">Berdiri Sejak</div>
        </div>
      </div>
    </div>
    <div class="hero-kartu">
      <h3>⏰ Jadwal Sekolah</h3>
      <div class="jam-item"><span class="hari" style="color: white;">Senin – Kamis</span><span class="waktu" style="color: white;">07.00 – 13.10</span></div>
      <div class="jam-item"><span class="hari" style="color: white;">Jum'at</span><span class="waktu" style="color: white;">07.00 – 11.30</span></div>
      <div class="jam-item"><span class="hari" style="color: white;">Upacara</span><span class="waktu" style="color: white;">Senin, 07.00</span></div>
      <div class="jam-item"><span class="hari" style="color: white;">BTQ</span><span class="waktu" style="color: white;">Selasa, 07.00</span></div>
      <div class="jam-item"><span class="hari" style="color: white;">Hafalan Do'a Harian</span><span class="waktu" style="color: white;">Rabu, 07.00</span></div>
      <div class="jam-item"><span class="hari" style="color: white;">Hafalan Hadist</span><span class="waktu" style="color: white;">Kamis, 07.00</span></div>
      <div class="jam-item"><span class="hari" style="color: white;">Senam Indonesia Hebat</span><span class="waktu" style="color: white;">Jum'at, 07.00</span></div>
      <div class="skor-akre">
        <div class="nilai" style="color: white;"><?= e($set['akreditasi'] ?? 'A') ?></div>
        <div class="label">Akreditasi</div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== PROFIL & VISI MISI ==================== -->
<section class="seksi seksi-bg" id="profil">
  <div class="container">
    <div class="seksi-judul">
      <div class="label-atas">📋 Tentang Kami</div>
      <h2>Profil & Visi Misi</h2>
      <p>Mengenal lebih dekat <?= e($set['nama_sekolah'] ?? 'SMP Nusantara Bangsa') ?></p>
      <div class="garis"></div>
    </div>
    <div class="profil-grid">
      <div class="kartu">
        <h3 class="kartu-judul"><span class="kartu-ikon">🏫</span> Profil Sekolah</h3>
        <table class="info-table">
          <tr>
            <td>Kepala Sekolah</td>
            <td><?= e($set['kepala_sekolah'] ?? '-') ?></td>
          </tr>
          <tr>
            <td>NPSN</td>
            <td><?= e($set['npsn'] ?? '-') ?></td>
          </tr>
          <tr>
            <td>Akreditasi</td>
            <td><span class="badge badge-hijau">⭐ <?= e($set['akreditasi'] ?? 'A') ?> (Unggul)</span></td>
          </tr>
          <tr>
            <td>Status</td>
            <td>Swasta</td>
          </tr>
          <tr>
            <td>Berdiri</td>
            <td><?= e($set['tahun_berdiri'] ?? '1984') ?></td>
          </tr>
          <tr>
            <td>Alamat</td>
            <td><?= e($set['alamat'] ?? '-') ?></td>
          </tr>
          <tr>
            <td>Telepon</td>
            <td><?= e($set['telepon'] ?? '-') ?></td>
          </tr>
          <tr>
            <td>Email</td>
            <td><?= e($set['email'] ?? '-') ?></td>
          </tr>
        </table>
      </div>
      <div class="kartu">
        <h3 class="kartu-judul"><span class="kartu-ikon">🌟</span> Visi & Misi</h3>
        <div style="margin-bottom:1rem;">
          <strong style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--orange);">Visi</strong>
          <p style="margin-top:5px;color:var(--abu-gelap);font-size:14px;line-height:1.7;"><?= e($set['visi'] ?? '') ?></p>
        </div>
        <div>
          <strong style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--orange);">Misi</strong>
          <ul class="misi-list" style="margin-top:5px;">
            <?php foreach (explode("\n", $set['misi'] ?? '') as $m): if (trim($m)): ?>
                <li><?= e(trim($m)) ?></li>
            <?php endif;
            endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== GALERI KEGIATAN ==================== -->
<section class="seksi" id="galeri">
  <div class="container">
    <div class="seksi-judul">
      <div class="label-atas">📸 Dokumentasi</div>
      <h2>Galeri Kegiatan</h2>
      <p>Aktivitas dan prestasi warga sekolah</p>
      <div class="garis"></div>
    </div>
    <?php if (empty($berita)): ?>
      <div style="text-align:center;padding:3rem;color:var(--abu);">Belum ada galeri yang dipublikasikan.</div>
    <?php else: ?>
      <div class="berita-grid">
        <?php foreach ($berita as $b): ?>
          <div class="berita-kartu">
            <div class="berita-foto">
              <?php if (!empty($b['foto'])): ?>
                <img src="<?= fotoUrl($b['foto']) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
              <?php else: ?>
                <span><?= $b['emoji'] ?></span>
              <?php endif; ?>
            </div>
            <div class="berita-isi">
              <span class="berita-tag"><?= e($b['kategori']) ?></span>
              <h4><?= e($b['judul']) ?></h4>
              <p><?= e(mb_strimwidth($b['ringkasan'] ?? '', 0, 100, '...')) ?></p>
              <div class="berita-tgl">📅 <?= formatTanggal($b['created_at']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>