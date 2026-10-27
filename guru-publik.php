<?php
// guru-publik.php — Halaman Data Guru untuk Publik
require_once __DIR__ . '/config/database.php';
$db       = getDB();
$guruList = $db->query("SELECT * FROM guru WHERE aktif=1 ORDER BY nama")->fetchAll();
$halamanAktif = 'guru';
$pageTitle    = 'Data Guru Pengajar';
include __DIR__ . '/includes/header.php';
?>
<section class="seksi">
  <div class="container">
    <div class="seksi-judul">
      <div class="label-atas">👨‍🏫 Tenaga Pendidik</div>
      <h2>Data Guru Pengajar</h2>
      <p>Pendidik profesional dan berdedikasi di <?= e($set['nama_sekolah'] ?? '') ?></p>
      <div class="garis"></div>
    </div>
    <?php if(empty($guruList)): ?>
      <p style="text-align:center;color:var(--abu);">Belum ada data guru.</p>
    <?php else: ?>
    <div class="guru-grid">
      <?php foreach($guruList as $g):
        $inisial = strtoupper(implode('', array_map(fn($w)=>$w[0], array_slice(explode(' ',$g['nama']),0,2))));
      ?>
      <div class="guru-kartu">
        <div class="guru-avatar-wrap">
          <?php if(!empty($g['foto'])): ?>
            <img src="<?= fotoUrl($g['foto']) ?>" alt="<?= e($g['nama']) ?>" class="guru-foto">
          <?php else: ?>
            <div class="guru-inisial" style="background:<?= e($g['warna']??'#1a5c3a') ?>"><?= $inisial ?></div>
          <?php endif; ?>
        </div>
        <h4><?= e($g['nama']) ?></h4>
        <div class="guru-mapel"><?= e($g['mapel']) ?></div>

      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
