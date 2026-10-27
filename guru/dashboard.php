<?php
// guru/dashboard.php
require_once __DIR__ . '/../config/database.php';
$guruData = cekGuru();
$db  = getDB();
$tab = $_GET['tab'] ?? 'beranda';
$flash = getFlash();

// Ambil kelas yang diajar guru ini beserta mapel yang diajarkan
// (dari tabel guru_kelas yang sudah include kolom mapel)
$kelasDiajar = $db->prepare("
    SELECT k.id, k.nama_kelas, k.tingkat, COUNT(DISTINCT s.id) as jml_siswa
    FROM kelas k
    LEFT JOIN siswa s ON s.kelas_id = k.id AND s.aktif = 1
    WHERE k.id IN (
        SELECT kelas_id FROM guru_kelas WHERE guru_id = ?
        UNION
        SELECT id FROM kelas WHERE wali_kelas = ?
    )
    GROUP BY k.id
    ORDER BY k.tingkat, k.nama_kelas
");
$kelasDiajar->execute([$guruData['id'], $guruData['id']]);
$kelasDiajar = $kelasDiajar->fetchAll();

// Ambil semua mapel yang diajarkan guru ini per kelas
$qMapelDiajar = $db->prepare("
    SELECT kelas_id, mapel
    FROM guru_kelas
    WHERE guru_id = ?
    ORDER BY mapel
");
$qMapelDiajar->execute([$guruData['id']]);
// $mapelPerKelas[kelas_id] = ['Matematika', 'IPA', ...]
$mapelPerKelas = [];
foreach ($qMapelDiajar->fetchAll() as $row) {
    $mapelPerKelas[$row['kelas_id']][] = $row['mapel'];
}
// Fallback: jika guru sebagai wali kelas tapi tidak ada di guru_kelas, gunakan mapel dari profil
foreach ($kelasDiajar as $kd) {
    if (empty($mapelPerKelas[$kd['id']])) {
        $mapels = array_map('trim', explode(',', $guruData['mapel'] ?? ''));
        foreach ($mapels as $pm) {
            if ($pm !== '') {
                $mapelPerKelas[$kd['id']][] = $pm;
            }
        }
    }
}

$set       = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();
$pageTitle = 'Dashboard Guru — ' . $guruData['nama'];
$inisial   = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $guruData['nama']), 0, 2))));
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
        <span>Portal Guru</span>
      </div>
    </div>
    <button type="button" class="nav-toggle" aria-label="Buka menu" onclick="document.getElementById('navMenu').classList.toggle('terbuka')">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-menu" id="navMenu">
      <a href="<?= BASE_URL ?>/index.php">🌐 Beranda</a>
      <a href="<?= BASE_URL ?>/logout.php" style="background:var(--merah);color:#fff;padding:7px 16px;border-radius:var(--radius-sm);">🚪 Keluar</a>
    </div>
  </div>
</nav>

<div class="notif-toast" id="notifToast"></div>

<div class="dash-layout">
  <!-- SIDEBAR -->
  <aside class="dash-sidebar">
    <div class="sidebar-user">
      <?php if(!empty($guruData['foto'])): ?>
        <img src="<?= fotoUrl($guruData['foto']) ?>" class="sidebar-avatar" alt="Foto">
      <?php else: ?>
        <div class="sidebar-avatar-inisial" style="background:<?= e($guruData['warna']??'var(--orange)') ?>"><?= $inisial ?></div>
      <?php endif; ?>
      <div class="sidebar-nama"><?= e(explode(',', $guruData['nama'])[0]) ?></div>
      <?php
        $allMapelGuru = [];
        foreach($mapelPerKelas as $mList) foreach($mList as $m) $allMapelGuru[$m] = true;
        $allMapelGuru = array_keys($allMapelGuru);
      ?>
      <div class="sidebar-role" title="<?= e(implode(', ', $allMapelGuru) ?: $guruData['mapel']) ?>">
        <?= e(count($allMapelGuru) > 1 ? count($allMapelGuru).' Mata Pelajaran' : ($allMapelGuru[0] ?? $guruData['mapel'])) ?>
      </div>
    </div>
    <ul class="sidebar-menu">
      <li class="menu-judul">DASHBOARD</li>
      <li><a href="?tab=beranda" class="<?= $tab==='beranda'?'aktif':'' ?>"><span class="menu-ikon">📊</span> Beranda</a></li>
      <li><a href="?tab=kelas" class="<?= $tab==='kelas'?'aktif':'' ?>"><span class="menu-ikon">🏫</span> Kelas Saya</a></li>
      <li><a href="?tab=siswa" class="<?= $tab==='siswa'?'aktif':'' ?>"><span class="menu-ikon">👥</span> Data Siswa</a></li>
      <li class="menu-judul">INPUT DATA</li>
      <li><a href="?tab=absensi" class="<?= $tab==='absensi'?'aktif':'' ?>"><span class="menu-ikon">✅</span> Input Absensi</a></li>
      <li><a href="?tab=nilai" class="<?= $tab==='nilai'?'aktif':'' ?>"><span class="menu-ikon">📝</span> Input Nilai</a></li>
      <li class="menu-judul">LAPORAN</li>
      <li><a href="?tab=rekap-absensi" class="<?= $tab==='rekap-absensi'?'aktif':'' ?>"><span class="menu-ikon">📋</span> Rekap Absensi</a></li>
      <li><a href="?tab=rekap-nilai" class="<?= $tab==='rekap-nilai'?'aktif':'' ?>"><span class="menu-ikon">📈</span> Rekap Nilai</a></li>
    </ul>
  </aside>

  <!-- KONTEN -->
  <main class="dash-konten">
    <?php if ($flash): ?>
    <div class="flash-msg flash-<?= $flash['type'] ?>">
      <?= $flash['type']==='sukses'?'✅':'❌' ?> <?= e($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <?php
    switch($tab) {
        case 'kelas':         include __DIR__.'/tabs/kelas.php'; break;
        case 'siswa':         include __DIR__.'/tabs/siswa.php'; break;
        case 'absensi':       include __DIR__.'/tabs/absensi.php'; break;
        case 'nilai':         include __DIR__.'/tabs/nilai.php'; break;
        case 'rekap-absensi': include __DIR__.'/tabs/rekap-absensi.php'; break;
        case 'rekap-nilai':   include __DIR__.'/tabs/rekap-nilai.php'; break;
        default:              include __DIR__.'/tabs/beranda.php'; break;
    }
    ?>
  </main>
</div>

<script>
function tampilNotif(pesan, tipe='sukses') {
  const el = document.getElementById('notifToast');
  el.innerHTML = (tipe==='sukses'?'✅':'❌') + ' ' + pesan;
  el.className = 'notif-toast notif-'+tipe+' muncul';
  setTimeout(()=>el.classList.remove('muncul'),4000);
}
function bukaModal(id) { document.getElementById(id).classList.add('terbuka'); }
function tutupModal(id) { document.getElementById(id).classList.remove('terbuka'); }
document.querySelectorAll('.modal-bg').forEach(m=>{
  m.addEventListener('click',e=>{ if(e.target===m) m.classList.remove('terbuka'); });
});
</script>
</body>
</html>
