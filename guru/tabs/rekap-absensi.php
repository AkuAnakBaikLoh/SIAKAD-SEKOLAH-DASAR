<?php
// guru/tabs/rekap-absensi.php — Multi mapel per kelas
$kelasIds     = array_column($kelasDiajar,'id');
$kelasIdPilih = (int)($_GET['kelas_id'] ?? ($kelasIds[0]??0));
$bulanPilih   = $_GET['bulan'] ?? date('Y-m');
$mapelPilih   = $_GET['mapel'] ?? '';

// Mapel yang diajar di kelas yang dipilih
$mapelTersedia = $kelasIdPilih && isset($mapelPerKelas[$kelasIdPilih])
    ? $mapelPerKelas[$kelasIdPilih]
    : [];

if ($mapelPilih === '' && !empty($mapelTersedia)) {
    $mapelPilih = $mapelTersedia[0];
}

$siswaList = [];
$rekapData = [];
$namaKelas = '';

if($kelasIdPilih && in_array($kelasIdPilih,$kelasIds)){
    $idx = array_search($kelasIdPilih,array_column($kelasDiajar,'id'));
    $namaKelas = $kelasDiajar[$idx]['nama_kelas']??'';
    $sq = $db->prepare("SELECT s.* FROM siswa s WHERE s.kelas_id=? AND s.aktif=1 ORDER BY s.nama");
    $sq->execute([$kelasIdPilih]); $siswaList=$sq->fetchAll();
    foreach($siswaList as $s){
        $qa = $db->prepare("SELECT status FROM absensi WHERE siswa_id=? AND kelas_id=? AND guru_id=? ".($bulanPilih?"AND DATE_FORMAT(tanggal,'%Y-%m')=?":''));
        $params = [$s['id'],$kelasIdPilih,$guruData['id']];
        if($bulanPilih) $params[]=$bulanPilih;
        $qa->execute($params); $rows=$qa->fetchAll();
        $hadir=$sakit=$izin=$alpa=0;
        foreach($rows as $r){
            if($r['status']==='Hadir') $hadir++;
            elseif($r['status']==='Sakit') $sakit++;
            elseif($r['status']==='Izin') $izin++;
            else $alpa++;
        }
        $total=$hadir+$sakit+$izin+$alpa;
        $rekapData[$s['id']]=['hadir'=>$hadir,'sakit'=>$sakit,'izin'=>$izin,'alpa'=>$alpa,'total'=>$total,'pct'=>$total?round($hadir/$total*100):0];
    }
}
$set = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();
?>
<div class="cetak-header">
  <h2><?= e($set['nama_sekolah']??'') ?></h2>
  <p><?= e($set['alamat']??'') ?> | Telp: <?= e($set['telepon']??'') ?></p>
  <hr style="border:1px solid #ccc;margin:.5rem 0;">
  <h3>REKAP ABSENSI SISWA</h3>
  <p>Kelas: <?= e($namaKelas) ?> | Mapel: <?= e($mapelPilih ?: 'Semua') ?> | Periode: <?= $bulanPilih ? date('F Y',strtotime($bulanPilih.'-01')) : 'Semua' ?></p>
</div>

<div class="dash-header no-print">
  <div class="dash-breadcrumb">Guru <span>/ Rekap Absensi</span></div>
  <h2>📋 Rekap Absensi</h2>
  <p>Rekap kehadiran siswa per mata pelajaran yang Anda ajarkan.</p>
</div>

<!-- FILTER -->
<div class="kartu no-print" style="margin-bottom:1.5rem;">
  <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
    <input type="hidden" name="tab" value="rekap-absensi">
    <div class="form-grup" style="flex:1;min-width:150px;margin:0;">
      <label>Kelas</label>
      <select name="kelas_id" class="form-kontrol" onchange="this.form.submit()">
        <?php foreach($kelasDiajar as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $kelasIdPilih==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if(!empty($mapelTersedia)): ?>
    <div class="form-grup" style="flex:1;min-width:160px;margin:0;">
      <label>Mata Pelajaran</label>
      <select name="mapel" class="form-kontrol" onchange="this.form.submit()">
        
        <?php foreach($mapelTersedia as $m): ?>
        <option value="<?= e($m) ?>" <?= $mapelPilih===$m?'selected':'' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-grup" style="min-width:150px;margin:0;">
      <label>Bulan</label>
      <input type="month" name="bulan" class="form-kontrol" value="<?= e($bulanPilih) ?>" onchange="this.form.submit()">
    </div>
    <button type="submit" name="bulan" value="" class="btn btn-abu btn-sm">📅 Semua Periode</button>
  </form>
</div>

<?php if($siswaList): ?>
<div class="tabel-wrap">
  <div class="tabel-header">
    <h3>Rekap — <?= e($namaKelas) ?> | <?= e($mapelPilih ?: 'Semua Mapel') ?></h3>
    <div class="tabel-aksi no-print">
      <a href="cetak-pdf.php?jenis=absensi&kelas_id=<?= $kelasIdPilih ?>&bulan=<?= urlencode($bulanPilih) ?>&mapel=<?= urlencode($mapelPilih) ?>"
         target="_blank" class="btn btn-orange btn-sm">🖨️ Cetak</a>
    </div>
  </div>
  <div class="tabel-scroll">
    <table>
      <thead>
        <tr>
          <th>No</th><th>NIS</th><th>Nama Siswa</th>
          <th style="background:#1a5c3a;">Hadir</th>
          <th style="background:#d4a017;">Sakit</th>
          <th style="background:#1a5276;">Izin</th>
          <th style="background:#c0392b;">Alpa</th>
          <th>Total</th><th>% Hadir</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($siswaList as $i=>$s):
          $r=$rekapData[$s['id']]??['hadir'=>0,'sakit'=>0,'izin'=>0,'alpa'=>0,'total'=>0,'pct'=>0];
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= e($s['nis']) ?></td>
          <td><strong><?= e($s['nama']) ?></strong></td>
          <td style="text-align:center;"><span class="badge badge-hijau"><?= $r['hadir'] ?></span></td>
          <td style="text-align:center;"><span class="badge badge-kuning"><?= $r['sakit'] ?></span></td>
          <td style="text-align:center;"><span class="badge badge-biru"><?= $r['izin'] ?></span></td>
          <td style="text-align:center;"><span class="badge badge-merah"><?= $r['alpa'] ?></span></td>
          <td style="text-align:center;"><?= $r['total'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <div style="flex:1;height:8px;background:var(--abu-muda);border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:<?= $r['pct'] ?>%;background:<?= $r['pct']>=75?'var(--hijau)':'var(--merah)' ?>;border-radius:4px;"></div>
              </div>
              <strong class="<?= $r['pct']>=75?'nilai-bagus':'nilai-kurang' ?>"><?= $r['pct'] ?>%</strong>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div style="padding:1rem 1.5rem;font-size:13px;color:var(--abu);border-top:1px solid var(--abu-muda);">
    Dicetak oleh: <?= e($guruData['nama']) ?> | <?= date('d F Y H:i') ?>
  </div>
</div>
<?php else: ?>
  <div style="text-align:center;padding:3rem;color:var(--abu);">Pilih kelas untuk melihat rekap absensi.</div>
<?php endif; ?>
