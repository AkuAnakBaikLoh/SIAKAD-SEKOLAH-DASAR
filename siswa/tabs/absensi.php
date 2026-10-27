<?php
// siswa/tabs/absensi.php
$siswaId    = $siswaData['id'];
$bulanPilih = $_GET['bulan'] ?? date('Y-m');
$mapelPilih = $_GET['mapel'] ?? '';

// Ambil semua mapel yang pernah ada absensinya
$qMapel = $db->prepare("
    SELECT DISTINCT g.mapel, g.nama as nama_guru, g.id as guru_id
    FROM absensi a
    JOIN guru g ON g.id = a.guru_id
    WHERE a.siswa_id = ?
    ORDER BY g.mapel
");
$qMapel->execute([$siswaId]);
$mapelList = $qMapel->fetchAll();

// Query absensi detail
$params = [$siswaId];
$where  = "WHERE a.siswa_id = ?";
if ($bulanPilih) { $where .= " AND DATE_FORMAT(a.tanggal,'%Y-%m') = ?"; $params[] = $bulanPilih; }
if ($mapelPilih) { $where .= " AND g.mapel = ?"; $params[] = $mapelPilih; }

$qAbs = $db->prepare("
    SELECT a.*, g.mapel, g.nama as nama_guru
    FROM absensi a
    JOIN guru g ON g.id = a.guru_id
    $where
    ORDER BY a.tanggal DESC, g.mapel
");
$qAbs->execute($params);
$absensiList = $qAbs->fetchAll();

// Hitung summary dari data yang ditampilkan
$summaryFilt = ['Hadir'=>0,'Sakit'=>0,'Izin'=>0,'Alpa'=>0];
foreach($absensiList as $a) $summaryFilt[$a['status']]++;
$totalFilt   = array_sum($summaryFilt);
$pctFilt     = $totalFilt ? round($summaryFilt['Hadir']/$totalFilt*100) : 0;

// Hitung summary total (semua waktu)
$qTotal = $db->prepare("SELECT status, COUNT(*) as jml FROM absensi WHERE siswa_id=? GROUP BY status");
$qTotal->execute([$siswaId]);
$totalAll = ['Hadir'=>0,'Sakit'=>0,'Izin'=>0,'Alpa'=>0];
foreach($qTotal->fetchAll() as $r) $totalAll[$r['status']] = (int)$r['jml'];
$grandTotal = array_sum($totalAll);
$pctGrand   = $grandTotal ? round($totalAll['Hadir']/$grandTotal*100) : 0;
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Siswa <span>/ Absensi Saya</span></div>
  <h2>📅 Absensi Saya</h2>
</div>

<!-- STATISTIK TOTAL KEHADIRAN -->
<div class="absensi-summary-grid" style="margin-bottom:1.5rem;">
  <div class="abs-stat abs-hadir">
    <div class="abs-angka"><?= $totalAll['Hadir'] ?></div>
    <div class="abs-label">✅ Total Hadir</div>
  </div>
  <div class="abs-stat abs-sakit">
    <div class="abs-angka"><?= $totalAll['Sakit'] ?></div>
    <div class="abs-label">🤒 Total Sakit</div>
  </div>
  <div class="abs-stat abs-izin">
    <div class="abs-angka"><?= $totalAll['Izin'] ?></div>
    <div class="abs-label">📝 Total Izin</div>
  </div>
  <div class="abs-stat abs-alpa">
    <div class="abs-angka"><?= $totalAll['Alpa'] ?></div>
    <div class="abs-label">❌ Total Alpa</div>
  </div>
</div>

<!-- BAR KEHADIRAN TOTAL -->
<div class="kartu" style="margin-bottom:1.5rem;padding:1.25rem 1.5rem;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
    <div>
      <strong>Persentase Kehadiran Keseluruhan</strong>
      <div style="font-size:.8rem;color:var(--abu);"><?= $grandTotal ?> total pertemuan dari semua mata pelajaran</div>
    </div>
    <div style="font-size:1.6rem;font-weight:800;color:<?= $pctGrand>=75?'var(--hijau)':'var(--merah)' ?>;">
      <?= $pctGrand ?>%
    </div>
  </div>
  <div style="height:14px;background:var(--abu-muda);border-radius:7px;overflow:hidden;">
    <div style="height:100%;width:<?= $pctGrand ?>%;background:<?= $pctGrand>=75?'linear-gradient(90deg,#1a5c3a,#0d7a4e)':'linear-gradient(90deg,#c0392b,#e74c3c)' ?>;border-radius:7px;transition:width .8s ease;"></div>
  </div>
  <div style="margin-top:.5rem;font-size:.78rem;color:<?= $pctGrand>=75?'var(--hijau)':'var(--merah)' ?>;">
    <?= $pctGrand>=75 ? '✅ Kehadiran Anda memenuhi syarat minimum (≥75%)' : '⚠️ Kehadiran Anda di bawah syarat minimum 75%. Harap segera melapor ke wali kelas.' ?>
  </div>
</div>

<!-- FILTER -->
<div class="filter-bar">
  <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;width:100%;">
    <input type="hidden" name="tab" value="absensi">
    <div class="form-grup" style="min-width:160px;margin:0;">
      <label>Bulan</label>
      <input type="month" name="bulan" class="form-kontrol" value="<?= e($bulanPilih) ?>" onchange="this.form.submit()">
    </div>
    <a href="?tab=absensi" class="btn btn-abu btn-sm" style="align-self:flex-end;">🔄 Reset Filter</a>
  </form>
</div>

<?php if($bulanPilih || $mapelPilih): ?>
<!-- STATISTIK FILTER -->
<div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
  <?php foreach(['Hadir'=>['#1a5c3a','✅'],'Sakit'=>['#d4a017','🤒'],'Izin'=>['#1a5276','📝'],'Alpa'=>['#c0392b','❌']] as $st=>[$clr,$ikon]): ?>
  <div style="flex:1;min-width:100px;background:<?= $clr ?>;color:#fff;border-radius:var(--radius);padding:.75rem 1rem;text-align:center;">
    <div style="font-size:1.4rem;font-weight:800;"><?= $summaryFilt[$st] ?></div>
    <div style="font-size:.78rem;opacity:.9;"><?= $ikon ?> <?= $st ?></div>
  </div>
  <?php endforeach; ?>
  <div style="flex:1;min-width:100px;background:var(--putih);border:2px solid var(--hijau);border-radius:var(--radius);padding:.75rem 1rem;text-align:center;">
    <div style="font-size:1.4rem;font-weight:800;color:<?= $pctFilt>=75?'var(--hijau)':'var(--merah)' ?>;"><?= $pctFilt ?>%</div>
    <div style="font-size:.78rem;color:var(--abu);">% Kehadiran</div>
  </div>
</div>
<?php endif; ?>

<!-- TABEL DETAIL ABSENSI -->
<div class="tabel-wrap absensi-table-wrap">
  <div class="tabel-header">
    <h3>📋 Detail Absensi
      <?php if($bulanPilih) echo '— ' . date('F Y', strtotime($bulanPilih.'-01')); ?>
      <?php if($mapelPilih) echo ' — ' . e($mapelPilih); ?>
    </h3>
    <span style="font-size:.82rem;color:var(--abu);"><?= count($absensiList) ?> data</span>
  </div>
  <div class="tabel-scroll">
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Tanggal</th>
          <th>Hari</th>
          <th></th>
          <th>Guru</th>
          <th>Status</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php if($absensiList): foreach($absensiList as $i=>$a):
          $stBadge = [
            'Hadir' => 'badge-hijau',
            'Sakit' => 'badge-kuning',
            'Izin'  => 'badge-biru',
            'Alpa'  => 'badge-merah',
          ][$a['status']] ?? 'badge-abu';
          $hari = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
                   'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][date('l',strtotime($a['tanggal']))] ?? '-';
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= formatTanggal($a['tanggal']) ?></td>
          <td style="color:var(--abu);font-size:.85rem;"><?= $hari ?></td>
          <td><strong><?= e($a['mapel']) ?></strong></td>
          <td style="font-size:.82rem;color:var(--abu);"><?= e($a['nama_guru']) ?></td>
          <td><span class="badge <?= $stBadge ?>"><?= e($a['status']) ?></span></td>
          <td style="font-size:.82rem;color:var(--abu);"><?= e($a['keterangan'] ?? '-') ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:3rem;color:var(--abu);">
            <div style="font-size:2rem;margin-bottom:.5rem;">📭</div>
            Tidak ada data absensi<?= $bulanPilih ? ' untuk bulan '.date('F Y',strtotime($bulanPilih.'-01')) : '' ?><?= $mapelPilih ? ' mapel '.$mapelPilih : '' ?>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($absensiList): ?>
  <div style="padding:1rem 1.5rem;font-size:13px;color:var(--abu);border-top:1px solid var(--abu-muda);">
    Siswa: <?= e($siswaData['nama']) ?> | NIS: <?= e($siswaData['nis']) ?> | Kelas: <?= e($kelas['nama_kelas']??'-') ?> | Dicetak: <?= date('d F Y H:i') ?>
  </div>
  <?php endif; ?>
</div>
