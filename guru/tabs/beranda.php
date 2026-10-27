<?php
// guru/tabs/beranda.php
$jmlSiswa = 0;
foreach($kelasDiajar as $k) $jmlSiswa += $k['jml_siswa'];

$hariIni = date('Y-m-d');
// Absensi hari ini oleh guru ini
$absensiHariIni = $db->prepare("SELECT COUNT(*) FROM absensi WHERE guru_id=? AND tanggal=?");
$absensiHariIni->execute([$guruData['id'], $hariIni]);
$jmlAbsensiHariIni = $absensiHariIni->fetchColumn();

// Jumlah data nilai yang sudah diisi
$jmlNilai = $db->prepare("SELECT COUNT(*) FROM nilai WHERE guru_id=?");
$jmlNilai->execute([$guruData['id']]);
$jmlNilai = $jmlNilai->fetchColumn();
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Guru <span>/ Beranda</span></div>
  <h2>Selamat Datang, <?= e(explode(',', $guruData['nama'])[0]) ?>!</h2>
  <?php
    // Kumpulkan semua mapel unik yang diajarkan
    $semuaMapel = [];
    foreach($mapelPerKelas as $mList) foreach($mList as $m) $semuaMapel[$m] = true;
    $semuaMapel = array_keys($semuaMapel);
  ?>
  <p>📅 <?= date('l, d F Y') ?> &nbsp;|&nbsp; Mapel: <strong><?= e(implode(', ', $semuaMapel) ?: $guruData['mapel']) ?></strong></p>
</div>

<div class="stat-grid">
  <div class="stat-kartu">
    <div class="ikon-bg">🏫</div>
    <div class="angka"><?= count($kelasDiajar) ?></div>
    <div class="label">Kelas Diampu</div>
  </div>
  <div class="stat-kartu orange">
    <div class="ikon-bg">👥</div>
    <div class="angka"><?= $jmlSiswa ?></div>
    <div class="label">Total Siswa</div>
  </div>
  <div class="stat-kartu">
    <div class="ikon-bg">✅</div>
    <div class="angka"><?= $jmlAbsensiHariIni ?></div>
    <div class="label">Absensi Hari Ini</div>
  </div>
  <div class="stat-kartu orange">
    <div class="ikon-bg">📝</div>
    <div class="angka"><?= $jmlNilai ?></div>
    <div class="label">Data Nilai Diisi</div>
  </div>
</div>