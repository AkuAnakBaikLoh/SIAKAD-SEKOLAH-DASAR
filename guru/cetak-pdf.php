<?php
// guru/cetak-pdf.php
// Generate halaman HTML yang dioptimalkan untuk cetak/save PDF via browser
require_once __DIR__ . '/../config/database.php';
$guruData = cekGuru();
$db = getDB();

$jenis        = $_GET['jenis'] ?? 'nilai';
$kelasIdPilih = (int)($_GET['kelas_id'] ?? 0);
$semester     = $_GET['semester'] ?? '1';
$tahunAjaran  = $_GET['ta'] ?? '2024/2025';
$bulanPilih   = $_GET['bulan'] ?? date('Y-m');
$mapelPilih   = trim($_GET['mapel'] ?? '');

// Validasi akses kelas (cek apakah guru mengajar di kelas ini)
$kelasCheck = $db->prepare("SELECT k.id, k.nama_kelas, k.tingkat FROM kelas k WHERE k.id=? AND k.id IN (SELECT kelas_id FROM guru_kelas WHERE guru_id=? UNION SELECT id FROM kelas WHERE wali_kelas=?)");
$kelasCheck->execute([$kelasIdPilih, $guruData['id'], $guruData['id']]);
$kelasInfo = $kelasCheck->fetch();
if (!$kelasInfo) { die('Akses tidak diizinkan.'); }

// Jika mapel tidak di-pass, gunakan mapel pertama yang diajar di kelas ini
if ($mapelPilih === '') {
    $qm = $db->prepare("SELECT mapel FROM guru_kelas WHERE guru_id=? AND kelas_id=? ORDER BY mapel LIMIT 1");
    $qm->execute([$guruData['id'], $kelasIdPilih]);
    $mapelPilih = $qm->fetchColumn();
    if (!$mapelPilih) {
        $mapels = array_map('trim', explode(',', $guruData['mapel'] ?? ''));
        $mapelPilih = $mapels[0] ?? '';
    }
}

$set = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();

// Ambil siswa
$sq = $db->prepare("SELECT s.* FROM siswa s WHERE s.kelas_id=? AND s.aktif=1 ORDER BY s.nama");
$sq->execute([$kelasIdPilih]);
$siswaList = $sq->fetchAll();

// Data berdasarkan jenis
if ($jenis === 'absensi') {
    $rekapData = [];
    foreach ($siswaList as $s) {
        $qa = $db->prepare("SELECT status FROM absensi WHERE siswa_id=? AND kelas_id=? AND guru_id=? AND DATE_FORMAT(tanggal,'%Y-%m')=?");
        $qa->execute([$s['id'],$kelasIdPilih,$guruData['id'],$bulanPilih]);
        $rows=$qa->fetchAll();
        $h=$sk=$iz=$al=0;
        foreach($rows as $r){ if($r['status']==='Hadir')$h++; elseif($r['status']==='Sakit')$sk++; elseif($r['status']==='Izin')$iz++; else $al++; }
        $tot=$h+$sk+$iz+$al;
        $rekapData[$s['id']]=['hadir'=>$h,'sakit'=>$sk,'izin'=>$iz,'alpa'=>$al,'total'=>$tot,'pct'=>$tot?round($h/$tot*100):0];
    }
    $judulDokumen = "Rekap Absensi";
    $periodeStr   = date('F Y', strtotime($bulanPilih.'-01'));
} else {
    $nilaiMap = [];
    if ($siswaList) {
        $sids = array_column($siswaList,'id');
        $ph   = implode(',', array_fill(0,count($sids),'?'));
        $qn   = $db->prepare("SELECT siswa_id, nilai_harian FROM nilai WHERE siswa_id IN ($ph) AND kelas_id=? AND guru_id=? AND mapel=? AND semester=? AND tahun_ajaran=?");
        $qn->execute([...$sids,$kelasIdPilih,$guruData['id'],$mapelPilih,$semester,$tahunAjaran]);
        foreach ($qn->fetchAll() as $n) $nilaiMap[$n['siswa_id']]=$n;
    }
    $allNilai = [];
    foreach ($siswaList as $s) {
        $na = $nilaiMap[$s['id']]['nilai_harian'] ?? null;
        if ($na !== null) $allNilai[] = (float)$na;
    }
    $rataKelas    = count($allNilai) ? round(array_sum($allNilai)/count($allNilai),1) : null;
    $judulDokumen = "Rekap Nilai";
    $periodeStr   = "Semester $semester TA $tahunAjaran";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($judulDokumen) ?> — <?= e($kelasInfo['nama_kelas']) ?></title>
<style>
  @page { size: A4; margin: 15mm 12mm; }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Arial', sans-serif; font-size: 11pt; color: #222; background: #fff; }

  .kop { display:flex; align-items:center; gap:16px; border-bottom:3px solid #1a5c3a; padding-bottom:10px; margin-bottom:12px; }
  .kop-logo { width:70px; height:70px; flex-shrink:0; display:flex; align-items:center; justify-content:center; }
  .kop-logo img { width:100%; height:100%; object-fit:contain; }
  .kop-teks h1 { font-size:14pt; color:#1a5c3a; font-weight:bold; }
  .kop-teks p  { font-size:9pt; color:#555; margin-top:2px; }

  .judul-laporan { text-align:center; margin:10px 0 6px; }
  .judul-laporan h2 { font-size:13pt; color:#1a5c3a; text-transform:uppercase; letter-spacing:1px; }
  .judul-laporan p  { font-size:10pt; color:#444; }

  .info-grid { display:flex; gap:20px; margin-bottom:12px; font-size:10pt; }
  .info-item { display:flex; gap:4px; }
  .info-item .label { color:#666; min-width:90px; }
  .info-item .val   { font-weight:bold; color:#1a5c3a; }

  table { width:100%; border-collapse:collapse; margin-bottom:12px; }
  thead th { background:#1a5c3a; color:#fff; padding:8px 7px; text-align:center; font-size:10pt; font-weight:bold; }
  thead th:nth-child(3) { text-align:left; }
  tbody td { padding:7px; border-bottom:1px solid #e0e0e0; font-size:10pt; text-align:center; }
  tbody td:nth-child(3) { text-align:left; }
  tbody tr:nth-child(even) { background:#f7faf8; }
  tfoot td { padding:7px; font-weight:bold; background:#e8f5ee; font-size:10pt; }

  .badge-hadir { color:#1a5c3a; font-weight:bold; }
  .badge-sakit { color:#d4a017; }
  .badge-izin  { color:#1a5276; }
  .badge-alpa  { color:#c0392b; font-weight:bold; }
  .pct-good    { color:#1a5c3a; font-weight:bold; }
  .pct-bad     { color:#c0392b; font-weight:bold; }
  .nilai-good  { color:#1a5c3a; font-weight:bold; }
  .nilai-avg   { color:#e65c00; font-weight:bold; }
  .nilai-bad   { color:#c0392b; font-weight:bold; }
  .predikat-a  { background:#e8f5ee; color:#1a5c3a; padding:2px 8px; border-radius:10px; font-weight:bold; }
  .predikat-b  { background:#e8f0fb; color:#1a5276; padding:2px 8px; border-radius:10px; font-weight:bold; }
  .predikat-c  { background:#fff3e8; color:#e65c00; padding:2px 8px; border-radius:10px; font-weight:bold; }
  .predikat-d  { background:#fdecea; color:#c0392b; padding:2px 8px; border-radius:10px; font-weight:bold; }

  .ttd-section { margin-top:24px; display:flex; justify-content:flex-end; }
  .ttd-box { text-align:center; min-width:200px; }
  .ttd-box .ttd-kota { font-size:10pt; }
  .ttd-box .ttd-ruang { height:60px; border-bottom:1px solid #555; margin:8px 0; }
  .ttd-box .ttd-nama  { font-weight:bold; font-size:11pt; }


  .footer-cetak { margin-top:8px; font-size:9pt; color:#999; text-align:center; border-top:1px solid #ddd; padding-top:6px; }

  .btn-cetak { position:fixed; top:12px; right:12px; background:#e65c00; color:#fff; border:none; padding:10px 20px; border-radius:8px; font-size:13px; cursor:pointer; font-weight:bold; z-index:999; }
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

<!-- INFO -->
<div class="info-grid">
  <div class="info-item"><span class="label">Kelas</span><span>:</span><span class="val"><?= e($kelasInfo['nama_kelas']) ?></span></div>
  <div class="info-item"><span class="label">Mata Pelajaran</span><span>:</span><span class="val"><?= e($mapelPilih) ?></span></div>
  <div class="info-item"><span class="label">Guru</span><span>:</span><span class="val"><?= e($guruData['nama']) ?></span></div>
</div>

<?php if($jenis === 'absensi'): ?>
<!-- TABEL ABSENSI -->
<table>
  <thead>
    <tr>
      <th width="4%">No</th>
      <th width="12%">NIS</th>
      <th width="30%">Nama Siswa</th>
      <th width="10%">Hadir</th>
      <th width="10%">Sakit</th>
      <th width="10%">Izin</th>
      <th width="10%">Alpa</th>
      <th width="7%">Total</th>
      <th width="7%">% Hadir</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($siswaList as $i=>$s):
      $r=$rekapData[$s['id']]??['hadir'=>0,'sakit'=>0,'izin'=>0,'alpa'=>0,'total'=>0,'pct'=>0];
    ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><?= e($s['nis']) ?></td>
      <td style="text-align:left;"><?= e($s['nama']) ?></td>
      <td class="badge-hadir"><?= $r['hadir'] ?></td>
      <td class="badge-sakit"><?= $r['sakit'] ?></td>
      <td class="badge-izin"><?= $r['izin'] ?></td>
      <td class="badge-alpa"><?= $r['alpa'] ?></td>
      <td><?= $r['total'] ?></td>
      <td class="<?= $r['pct']>=75?'pct-good':'pct-bad' ?>"><?= $r['pct'] ?>%</td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php else: ?>
<!-- TABEL NILAI -->
<table>
  <thead>
    <tr>
      <th width="5%">No</th>
      <th width="13%">NIS</th>
      <th width="38%">Nama Siswa</th>
      <th width="18%">Nilai Akhir</th>
      <th width="12%">Predikat</th>
      <th width="14%">Keterangan</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($siswaList as $i=>$s):
      $naFloat = isset($nilaiMap[$s['id']]['nilai_harian']) && $nilaiMap[$s['id']]['nilai_harian'] !== null
                 ? (float)$nilaiMap[$s['id']]['nilai_harian'] : null;
      $p  = predikat($naFloat);
      $pcls = ['A'=>'predikat-a','B'=>'predikat-b','C'=>'predikat-c','D'=>'predikat-d','E'=>'predikat-d','-'=>''][$p] ?? '';
    ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><?= e($s['nis']) ?></td>
      <td style="text-align:left;"><?= e($s['nama']) ?></td>
      <td class="<?= $naFloat!==null?($naFloat>=80?'nilai-good':($naFloat>=70?'nilai-avg':'nilai-bad')):'' ?>">
        <?= $naFloat !== null ? number_format($naFloat, 1) : '-' ?>
      </td>
      <td><?= $p !== '-' ? "<span class='$pcls'>$p</span>" : '-' ?></td>
      <td style="font-size:9pt;color:<?= $naFloat!==null?($naFloat>=75?'#1a5c3a':'#c0392b'):'#999' ?>;">
        <?= $naFloat !== null ? ($naFloat >= 75 ? 'Tuntas' : 'Remedi') : '-' ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <?php if (isset($rataKelas) && $rataKelas): ?>
  <tfoot>
    <tr>
      <td colspan="3" style="text-align:right;">Rata-rata Kelas:</td>
      <td class="nilai-good"><?= number_format($rataKelas, 1) ?></td>
      <td colspan="2"></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>
<?php endif; ?>

<!-- TANDA TANGAN -->
<div class="ttd-section">
  <div class="ttd-box">
    <div class="ttd-kota"><?= date('d F Y') ?></div>
    <div class="ttd-kota">Guru Mata Pelajaran <?= e($mapelPilih) ?></div>
    <div class="ttd-ruang"></div>
    <div class="ttd-nama"><?= e($guruData['nama']) ?></div>

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
