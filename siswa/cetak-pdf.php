<?php
// siswa/cetak-pdf.php
// Generate halaman HTML yang dioptimalkan untuk cetak/save PDF via browser
require_once __DIR__ . '/../config/database.php';
$siswaData = cekSiswa();
$db = getDB();

$jenis       = $_GET['jenis']    ?? 'nilai';
$semester    = $_GET['semester'] ?? '1';
$tahunAjaran = $_GET['ta']       ?? '2024/2025';
$bulanPilih  = $_GET['bulan']    ?? date('Y-m');

$siswaId  = $siswaData['id'];
$set      = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();

// Ambil data kelas siswa
$qKelas = $db->prepare("SELECT k.* FROM kelas k WHERE k.id = ?");
$qKelas->execute([$siswaData['kelas_id']]);
$kelasInfo = $qKelas->fetch();
if (!$kelasInfo) { die('Data kelas tidak ditemukan.'); }

// Ambil data wali kelas
$qWali = $db->prepare("SELECT g.nama FROM guru g WHERE g.id = ?");
$qWali->execute([$kelasInfo['wali_kelas'] ?? 0]);
$waliKelas = $qWali->fetch();

// Data berdasarkan jenis
if ($jenis === 'absensi') {
    // --- ABSENSI ---
    $params = [$siswaId];
    $where  = "WHERE a.siswa_id = ?";
    if ($bulanPilih) {
        $where .= " AND DATE_FORMAT(a.tanggal,'%Y-%m') = ?";
        $params[] = $bulanPilih;
    }
    $qAbs = $db->prepare("
        SELECT a.*, g.mapel, g.nama as nama_guru
        FROM absensi a
        JOIN guru g ON g.id = a.guru_id
        $where
        ORDER BY a.tanggal ASC, g.mapel
    ");
    $qAbs->execute($params);
    $absensiList = $qAbs->fetchAll();

    // Rekap per mapel
    $rekapPerMapel = [];
    $rekapTotal    = ['Hadir'=>0,'Sakit'=>0,'Izin'=>0,'Alpa'=>0];
    foreach ($absensiList as $a) {
        $mp = $a['mapel'];
        if (!isset($rekapPerMapel[$mp])) {
            $rekapPerMapel[$mp] = ['mapel'=>$mp,'guru'=>$a['nama_guru'],'Hadir'=>0,'Sakit'=>0,'Izin'=>0,'Alpa'=>0,'total'=>0];
        }
        $rekapPerMapel[$mp][$a['status']]++;
        $rekapPerMapel[$mp]['total']++;
        $rekapTotal[$a['status']]++;
    }
    $grandTotal = array_sum($rekapTotal);
    $pctGrand   = $grandTotal ? round($rekapTotal['Hadir']/$grandTotal*100) : 0;

    $judulDokumen = "Rekap Absensi";
    $periodeStr   = $bulanPilih ? date('F Y', strtotime($bulanPilih.'-01')) : 'Semua Bulan';

} else {
    // --- NILAI ---
    $qNilai = $db->prepare("
        SELECT n.*, g.nama as nama_guru
        FROM nilai n
        JOIN guru g ON g.id = n.guru_id
        WHERE n.siswa_id = ? AND n.semester = ? AND n.tahun_ajaran = ?
        ORDER BY n.mapel
    ");
    $qNilai->execute([$siswaId, $semester, $tahunAjaran]);
    $nilaiList = $qNilai->fetchAll();

    $rataAll = [];
    foreach ($nilaiList as $n) {
        $r = $n['nilai_akhir_final'] ?? $n['nilai_akhir'] ?? $n['nilai_harian'] ?? null;
        if ($r !== null) $rataAll[] = (float)$r;
    }
    $rataTotal = count($rataAll) ? round(array_sum($rataAll)/count($rataAll),1) : null;
    $jmlTuntas = count(array_filter($rataAll, fn($v) => $v >= 75));

    $judulDokumen = "Laporan Nilai";
    $periodeStr   = "Semester $semester TA $tahunAjaran";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($judulDokumen) ?> — <?= e($siswaData['nama']) ?></title>
<style>
  @page { size: A4; margin: 15mm 12mm; }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Arial', sans-serif; font-size: 11pt; color: #222; background: #fff; }

  /* KOP */
  .kop { display:flex; align-items:center; gap:16px; border-bottom:3px solid #1a5c3a; padding-bottom:10px; margin-bottom:12px; }
  .kop-logo { width:70px; height:70px; flex-shrink:0; display:flex; align-items:center; justify-content:center; }
  .kop-logo img { width:100%; height:100%; object-fit:contain; }
  .kop-teks h1 { font-size:14pt; color:#1a5c3a; font-weight:bold; }
  .kop-teks p  { font-size:9pt; color:#555; margin-top:2px; }

  /* JUDUL */
  .judul-laporan { text-align:center; margin:10px 0 8px; }
  .judul-laporan h2 { font-size:13pt; color:#1a5c3a; text-transform:uppercase; letter-spacing:1px; }
  .judul-laporan p  { font-size:10pt; color:#444; }

  /* INFO SISWA */
  .info-siswa { border:1px solid #d0e8d8; border-radius:6px; padding:10px 14px; margin-bottom:12px; display:grid; grid-template-columns:1fr 1fr; gap:4px 20px; font-size:10pt; }
  .info-siswa .row { display:flex; gap:4px; }
  .info-siswa .lbl { color:#666; min-width:100px; }
  .info-siswa .val { font-weight:bold; color:#1a5c3a; }

  /* STATISTIK BOX */
  .stat-strip { display:flex; gap:10px; margin-bottom:12px; }
  .stat-box { flex:1; border:1px solid #d0e8d8; border-radius:6px; padding:8px 10px; text-align:center; }
  .stat-box .angka { font-size:16pt; font-weight:bold; color:#1a5c3a; }
  .stat-box .keterangan { font-size:8pt; color:#666; margin-top:2px; }

  /* TABEL */
  table { width:100%; border-collapse:collapse; margin-bottom:12px; }
  thead th { background:#1a5c3a; color:#fff; padding:8px 7px; text-align:center; font-size:10pt; font-weight:bold; }
  thead th.left { text-align:left; }
  tbody td { padding:7px; border-bottom:1px solid #e0e0e0; font-size:10pt; text-align:center; }
  tbody td.left { text-align:left; }
  tbody tr:nth-child(even) { background:#f7faf8; }
  tfoot td { padding:7px; font-weight:bold; background:#e8f5ee; font-size:10pt; }

  /* STATUS WARNA */
  .badge-hadir { color:#1a5c3a; font-weight:bold; }
  .badge-sakit { color:#d4a017; }
  .badge-izin  { color:#1a5276; }
  .badge-alpa  { color:#c0392b; font-weight:bold; }
  .pct-good    { color:#1a5c3a; font-weight:bold; }
  .pct-bad     { color:#c0392b; font-weight:bold; }

  /* NILAI WARNA */
  .nilai-good  { color:#1a5c3a; font-weight:bold; }
  .nilai-avg   { color:#e65c00; font-weight:bold; }
  .nilai-bad   { color:#c0392b; font-weight:bold; }

  /* PREDIKAT BADGE */
  .predikat-a  { background:#e8f5ee; color:#1a5c3a; padding:2px 8px; border-radius:10px; font-weight:bold; }
  .predikat-b  { background:#e8f0fb; color:#1a5276; padding:2px 8px; border-radius:10px; font-weight:bold; }
  .predikat-c  { background:#fff3e8; color:#e65c00; padding:2px 8px; border-radius:10px; font-weight:bold; }
  .predikat-d  { background:#fdecea; color:#c0392b; padding:2px 8px; border-radius:10px; font-weight:bold; }

  /* TANDA TANGAN */
  .ttd-section { margin-top:24px; display:flex; justify-content:space-between; }
  .ttd-box { text-align:center; min-width:180px; }
  .ttd-box .ttd-kota { font-size:10pt; }
  .ttd-box .ttd-ruang { height:56px; border-bottom:1px solid #555; margin:8px 0; }
  .ttd-box .ttd-nama  { font-weight:bold; font-size:11pt; }
  .ttd-box .ttd-sub   { font-size:9pt; color:#666; }

  /* FOOTER */
  .footer-cetak { margin-top:8px; font-size:9pt; color:#999; text-align:center; border-top:1px solid #ddd; padding-top:6px; }

  /* TOMBOL PRINT */
  .btn-cetak { position:fixed; top:12px; right:12px; background:#e65c00; color:#fff; border:none; padding:10px 20px; border-radius:8px; font-size:13px; cursor:pointer; font-weight:bold; z-index:999; box-shadow:0 4px 12px rgba(0,0,0,.2); }
  @media print { .btn-cetak { display:none; } }
</style>
</head>
<body>

<button class="btn-cetak" onclick="window.print()">🖨️ Print / Simpan PDF</button>

<!-- KOP SURAT -->
<div class="kop">
  <div class="kop-logo"><img src="../assets/logo.jpeg" alt="Logo Sekolah" onerror="this.style.display='none'"></div>
  <div class="kop-teks">
    <h1><?= e($set['nama_sekolah']??'') ?></h1>
    <p><?= e($set['alamat']??'') ?></p>
    <p>Telp: <?= e($set['telepon']??'') ?> | Email: <?= e($set['email']??'') ?></p>
  </div>
</div>

<!-- JUDUL -->
<div class="judul-laporan">
  <h2><?= e($judulDokumen) ?> Siswa</h2>
  <p><?= e($periodeStr) ?></p>
</div>

<!-- INFO SISWA -->
<div class="info-siswa">
  <div class="row"><span class="lbl">Nama Siswa</span><span>:</span><span class="val"><?= e($siswaData['nama']) ?></span></div>
  <div class="row"><span class="lbl">Kelas</span><span>:</span><span class="val"><?= e($kelasInfo['nama_kelas']) ?></span></div>
  <div class="row"><span class="lbl">NIS</span><span>:</span><span class="val"><?= e($siswaData['nis']??'-') ?></span></div>
  <div class="row"><span class="lbl">Wali Kelas</span><span>:</span><span class="val"><?= e($waliKelas['nama']??'-') ?></span></div>
</div>

<?php if ($jenis === 'absensi'): ?>
<!-- ===== ABSENSI ===== -->

<!-- STATISTIK KEHADIRAN -->
<div class="stat-strip">
  <?php
  $statItems = [
    ['Hadir', $rekapTotal['Hadir'], '#1a5c3a'],
    ['Sakit', $rekapTotal['Sakit'], '#d4a017'],
    ['Izin',  $rekapTotal['Izin'],  '#1a5276'],
    ['Alpa',  $rekapTotal['Alpa'],  '#c0392b'],
  ];
  foreach ($statItems as [$lbl,$val,$clr]):
  ?>
  <div class="stat-box">
    <div class="angka" style="color:<?= $clr ?>"><?= $val ?></div>
    <div class="keterangan"><?= $lbl ?></div>
  </div>
  <?php endforeach; ?>
  <div class="stat-box">
    <div class="angka" style="color:<?= $pctGrand>=75?'#1a5c3a':'#c0392b' ?>"><?= $pctGrand ?>%</div>
    <div class="keterangan">% Kehadiran</div>
  </div>
</div>

<!-- TABEL REKAP PER MAPEL -->
<table>
  <thead>
    <tr>
      <th width="4%">No</th>
      <th width="28%" class="left">Mata Pelajaran</th>
      <th width="22%" class="left">Guru</th>
      <th width="10%">Hadir</th>
      <th width="10%">Sakit</th>
      <th width="10%">Izin</th>
      <th width="10%">Alpa</th>
      <th width="6%">% Hadir</th>
    </tr>
  </thead>
  <tbody>
    <?php $no=1; foreach ($rekapPerMapel as $mp => $r):
      $tot = $r['total'];
      $pct = $tot ? round($r['Hadir']/$tot*100) : 0;
    ?>
    <tr>
      <td><?= $no++ ?></td>
      <td class="left"><strong><?= e($mp) ?></strong></td>
      <td class="left" style="font-size:9pt;color:#555"><?= e($r['guru']) ?></td>
      <td class="badge-hadir"><?= $r['Hadir'] ?></td>
      <td class="badge-sakit"><?= $r['Sakit'] ?></td>
      <td class="badge-izin"><?= $r['Izin'] ?></td>
      <td class="badge-alpa"><?= $r['Alpa'] ?></td>
      <td class="<?= $pct>=75?'pct-good':'pct-bad' ?>"><?= $pct ?>%</td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="3" style="text-align:right;">Total Keseluruhan:</td>
      <td class="badge-hadir"><?= $rekapTotal['Hadir'] ?></td>
      <td class="badge-sakit"><?= $rekapTotal['Sakit'] ?></td>
      <td class="badge-izin"><?= $rekapTotal['Izin'] ?></td>
      <td class="badge-alpa"><?= $rekapTotal['Alpa'] ?></td>
      <td class="<?= $pctGrand>=75?'pct-good':'pct-bad' ?>"><?= $pctGrand ?>%</td>
    </tr>
  </tfoot>
</table>

<!-- DETAIL ABSENSI (jika ada data per baris) -->
<?php if ($absensiList && $bulanPilih): ?>
<table style="margin-top:4px;">
  <thead>
    <tr>
      <th width="4%">No</th>
      <th width="18%">Tanggal</th>
      <th width="10%">Hari</th>
      <th width="28%" class="left">Mata Pelajaran</th>
      <th width="22%" class="left">Guru</th>
      <th width="10%">Status</th>
      <th width="8%">Ket.</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
                'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
    $badgeClr= ['Hadir'=>'#1a5c3a','Sakit'=>'#d4a017','Izin'=>'#1a5276','Alpa'=>'#c0392b'];
    foreach ($absensiList as $i => $a):
      $hari = $hariMap[date('l', strtotime($a['tanggal']))] ?? '-';
      $clr  = $badgeClr[$a['status']] ?? '#666';
    ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><?= formatTanggal($a['tanggal']) ?></td>
      <td style="font-size:9pt;color:#666"><?= $hari ?></td>
      <td class="left"><strong><?= e($a['mapel']) ?></strong></td>
      <td class="left" style="font-size:9pt;color:#555"><?= e($a['nama_guru']) ?></td>
      <td><span style="color:<?= $clr ?>;font-weight:bold;"><?= e($a['status']) ?></span></td>
      <td style="font-size:9pt;color:#666"><?= e($a['keterangan']??'-') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php else: ?>
<!-- ===== NILAI ===== -->

<!-- STATISTIK NILAI -->
<?php if ($nilaiList):
  $nilaiMax = count($rataAll) ? max($rataAll) : null;
  $nilaiMin = count($rataAll) ? min($rataAll) : null;
?>
<div class="stat-strip">
  <div class="stat-box">
    <div class="angka"><?= $rataTotal !== null ? number_format($rataTotal,1) : '-' ?></div>
    <div class="keterangan">Rata-rata Semua Mapel</div>
  </div>
  <div class="stat-box">
    <div class="angka" style="color:#e65c00"><?= $nilaiMax !== null ? number_format($nilaiMax,1) : '-' ?></div>
    <div class="keterangan">Nilai Tertinggi</div>
  </div>
  <div class="stat-box">
    <div class="angka" style="color:#c0392b"><?= $nilaiMin !== null ? number_format($nilaiMin,1) : '-' ?></div>
    <div class="keterangan">Nilai Terendah</div>
  </div>
  <div class="stat-box">
    <div class="angka"><?= $jmlTuntas ?>/<?= count($nilaiList) ?></div>
    <div class="keterangan">Mapel Tuntas</div>
  </div>
</div>
<?php endif; ?>

<!-- TABEL NILAI -->
<table>
  <thead>
    <tr>
      <th width="4%">No</th>
      <th width="22%" class="left">Mata Pelajaran</th>
      <th width="16%" class="left">Guru</th>
      <th width="10%">Harian</th>
      <th width="10%">ASTS</th>
      <th width="10%">ASAS</th>
      <th width="10%">Nilai Akhir</th>
      <th width="10%">Pred.</th>
      <th width="8%">Ket.</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($nilaiList as $i => $n):
      $nilaiHarian = $n['nilai_akhir'] ?? $n['nilai_harian'] ?? null;
      $asts = $n['asts'] ?? null;
      $asas = $n['asas'] ?? null;
      $r    = $n['nilai_akhir_final'] ?? $nilaiHarian ?? null;
      if ($r !== null) $r = (float)$r;
      $p    = predikat($r);
      $ncls = $r !== null ? ($r>=80?'nilai-good':($r>=70?'nilai-avg':'nilai-bad')) : '';
      $tuntas = ($r !== null && $r >= 75);
    ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td class="left"><strong><?= e($n['mapel']) ?></strong></td>
      <td class="left" style="font-size:9pt;color:#555"><?= e($n['nama_guru']) ?></td>
      <td><?= $nilaiHarian !== null ? number_format($nilaiHarian,1) : '-' ?></td>
      <td><?= $asts !== null ? number_format($asts,1) : '-' ?></td>
      <td><?= $asas !== null ? number_format($asas,1) : '-' ?></td>
      <td class="<?= $ncls ?>">
        <?= $r !== null ? number_format($r, 1) : '-' ?>
      </td>
      <td style="font-size:8pt;">
        <?= $p !== '-' ? $p : '-' ?>
      </td>
      <td style="font-size:9pt;color:<?= $r!==null?($tuntas?'#1a5c3a':'#c0392b'):'#999' ?>;">
        <?= $r !== null ? ($tuntas ? 'Tuntas' : 'Remedi') : '-' ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <?php if ($rataTotal !== null): ?>
  <tfoot>
    <tr>
      <td colspan="6" style="text-align:right;">Rata-rata Keseluruhan:</td>
      <td class="nilai-good"><?= number_format($rataTotal, 1) ?></td>
      <td style="font-size:8pt;"><?= predikat($rataTotal) ?></td>
      <td></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>

<?php if (!$nilaiList): ?>
<div style="text-align:center;padding:2rem;color:#999;border:1px dashed #ccc;border-radius:6px;">
  Belum ada data nilai untuk Semester <?= e($semester) ?> Tahun Ajaran <?= e($tahunAjaran) ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- TANDA TANGAN -->
<div class="ttd-section">
  <div class="ttd-box">
    <div class="ttd-kota"><?= date('d F Y') ?></div>
    <div class="ttd-kota">Mengetahui,</div>
    <div class="ttd-kota">Wali Kelas <?= e($kelasInfo['nama_kelas']) ?></div>
    <div class="ttd-ruang"></div>
    <div class="ttd-nama"><?= e($waliKelas['nama'] ?? '___________________') ?></div>

  </div>
  <div class="ttd-box">
    <div class="ttd-kota"><?= date('d F Y') ?></div>
    <div class="ttd-kota">Siswa yang bersangkutan,</div>
    <div class="ttd-ruang"></div>
    <div class="ttd-nama"><?= e($siswaData['nama']) ?></div>
    <div class="ttd-sub">NIS: <?= e($siswaData['nis'] ?? '-') ?></div>
  </div>
</div>

<div class="footer-cetak">
  Dicetak dari Sistem Informasi Sekolah <?= e($set['nama_sekolah']??'') ?> | <?= date('d/m/Y H:i') ?>
</div>

<script>
// Auto buka dialog print
window.addEventListener('load', () => setTimeout(()=>window.print(), 800));
</script>
</body>
</html>
