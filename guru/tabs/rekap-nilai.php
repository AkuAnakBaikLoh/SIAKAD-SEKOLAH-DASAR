<?php
// guru/tabs/rekap-nilai.php
$kelasIds     = array_column($kelasDiajar, 'id');
$kelasIdPilih = (int)($_GET['kelas_id'] ?? ($kelasIds[0] ?? 0));
$semester     = $_GET['semester'] ?? '1';
$tahunAjaran  = $_GET['ta'] ?? '2024/2025';
$mapelPilih   = $_GET['mapel'] ?? '';

// Mapel yang diajar di kelas yang dipilih
$mapelTersedia = $kelasIdPilih && isset($mapelPerKelas[$kelasIdPilih])
    ? $mapelPerKelas[$kelasIdPilih]
    : [];

if ($mapelPilih === '' && !empty($mapelTersedia)) {
    $mapelPilih = $mapelTersedia[0];
}

$siswaList = [];
$nilaiMap  = [];
$namaKelas = '';

if ($kelasIdPilih && in_array($kelasIdPilih, $kelasIds)) {
    $idx = array_search($kelasIdPilih, array_column($kelasDiajar, 'id'));
    $namaKelas = $kelasDiajar[$idx]['nama_kelas'] ?? '';

    $sq = $db->prepare("SELECT s.* FROM siswa s WHERE s.kelas_id=? AND s.aktif=1 ORDER BY s.nama");
    $sq->execute([$kelasIdPilih]);
    $siswaList = $sq->fetchAll();

    if ($siswaList && $mapelPilih) {
        $sids = array_column($siswaList, 'id');
        $ph   = implode(',', array_fill(0, count($sids), '?'));
        // Ambil nilai_harian sebagai nilai akhir
        $qn = $db->prepare("SELECT siswa_id, nilai_harian FROM nilai
                             WHERE siswa_id IN ($ph) AND kelas_id=? AND guru_id=? AND mapel=? AND semester=? AND tahun_ajaran=?");
        $qn->execute([...$sids, $kelasIdPilih, $guruData['id'], $mapelPilih, $semester, $tahunAjaran]);
        foreach ($qn->fetchAll() as $n) $nilaiMap[$n['siswa_id']] = $n;
    }
}
$set = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();
?>
<div class="cetak-header">
  <h2><?= e($set['nama_sekolah'] ?? '') ?></h2>
  <p><?= e($set['alamat'] ?? '') ?></p>
  <hr style="border:1px solid #ccc;margin:.5rem 0;">
  <h3>REKAP NILAI SISWA</h3>
  <p>Kelas: <?= e($namaKelas) ?> | Mapel: <?= e($mapelPilih) ?> | Semester <?= $semester ?> | TA <?= e($tahunAjaran) ?></p>
</div>

<div class="dash-header no-print">
  <div class="dash-breadcrumb">Guru <span>/ Rekap Nilai</span></div>
  <h2>📈 Rekap Nilai</h2>
  <p>Lihat rekap nilai akhir siswa per mata pelajaran yang Anda ajarkan.</p>
</div>

<!-- FILTER -->
<div class="kartu no-print" style="margin-bottom:1.5rem;">
  <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
    <input type="hidden" name="tab" value="rekap-nilai">
    <div class="form-grup" style="flex:1;min-width:150px;margin:0;">
      <label>Kelas</label>
      <select name="kelas_id" class="form-kontrol" onchange="this.form.submit()">
        <?php foreach ($kelasDiajar as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $kelasIdPilih == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (!empty($mapelTersedia)): ?>
    <div class="form-grup" style="flex:1;min-width:160px;margin:0;">
      <label>Mata Pelajaran</label>
      <select name="mapel" class="form-kontrol" onchange="this.form.submit()">
        <?php foreach ($mapelTersedia as $m): ?>
        <option value="<?= e($m) ?>" <?= $mapelPilih === $m ? 'selected' : '' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-grup" style="min-width:120px;margin:0;">
      <label>Semester</label>
      <select name="semester" class="form-kontrol" onchange="this.form.submit()">
        <option value="1" <?= $semester === '1' ? 'selected' : '' ?>>Semester 1</option>
        <option value="2" <?= $semester === '2' ? 'selected' : '' ?>>Semester 2</option>
      </select>
    </div>
    <div class="form-grup" style="min-width:140px;margin:0;">
      <label>Tahun Ajaran</label>
      <select name="ta" class="form-kontrol" onchange="this.form.submit()">
        <?php foreach (['2024/2025', '2025/2026', '2023/2024'] as $ta2): ?>
        <option value="<?= $ta2 ?>" <?= $tahunAjaran === $ta2 ? 'selected' : '' ?>><?= $ta2 ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if ($siswaList && $mapelPilih):
  // Hitung statistik kelas dari nilai_harian (= nilai akhir)
  $allNilai = [];
  foreach ($siswaList as $s) {
      $na = $nilaiMap[$s['id']]['nilai_harian'] ?? null;
      if ($na !== null) $allNilai[] = (float)$na;
  }
  $rataKelas      = count($allNilai) ? round(array_sum($allNilai) / count($allNilai), 1) : null;
  $nilaiTertinggi = count($allNilai) ? max($allNilai) : null;
  $nilaiTerendah  = count($allNilai) ? min($allNilai) : null;
?>
<!-- Statistik kelas -->
<div class="stat-grid no-print" style="margin-bottom:1.5rem;">
  <div class="stat-kartu"><div class="angka"><?= $rataKelas !== null ? number_format($rataKelas, 1) : '-' ?></div><div class="label">Rata-rata Kelas</div></div>
  <div class="stat-kartu orange"><div class="angka"><?= $nilaiTertinggi !== null ? number_format($nilaiTertinggi, 1) : '-' ?></div><div class="label">Nilai Tertinggi</div></div>
  <div class="stat-kartu"><div class="angka"><?= $nilaiTerendah !== null ? number_format($nilaiTerendah, 1) : '-' ?></div><div class="label">Nilai Terendah</div></div>
  <div class="stat-kartu orange"><div class="angka"><?= count($allNilai) ?>/<?= count($siswaList) ?></div><div class="label">Sudah Dinilai</div></div>
</div>

<div class="tabel-wrap">
  <div class="tabel-header">
    <h3>Nilai <?= e($mapelPilih) ?> — <?= e($namaKelas) ?> | Sem.<?= $semester ?> (<?= e($tahunAjaran) ?>)</h3>
    <div class="tabel-aksi no-print">
      <a href="cetak-pdf.php?jenis=nilai&kelas_id=<?= $kelasIdPilih ?>&semester=<?= $semester ?>&ta=<?= urlencode($tahunAjaran) ?>&mapel=<?= urlencode($mapelPilih) ?>"
         target="_blank" class="btn btn-orange btn-sm">🖨️ Cetak</a>
    </div>
  </div>
  <div class="tabel-scroll">
    <table>
      <thead>
        <tr>
          <th>No</th><th>NIS</th><th>Nama Siswa</th>
          <th>Nilai Akhir</th>
          <th>Predikat</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($siswaList as $i => $s):
          $naFloat = isset($nilaiMap[$s['id']]['nilai_harian']) && $nilaiMap[$s['id']]['nilai_harian'] !== null
                     ? (float)$nilaiMap[$s['id']]['nilai_harian'] : null;
          $pred    = predikat($naFloat);
          $predCls = match(true) {
              $naFloat !== null && $naFloat >= 88 => 'badge-hijau',
              $naFloat !== null && $naFloat >= 82 => 'badge-biru',
              $naFloat !== null && $naFloat >= 75 => 'badge-orange',
              $naFloat !== null                   => 'badge-merah',
              default                             => 'badge-abu',
          };
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($s['nis']) ?></td>
          <td><strong><?= e($s['nama']) ?></strong></td>
          <td style="text-align:center;">
            <?php if ($naFloat !== null): ?>
              <strong class="<?= $naFloat >= 80 ? 'nilai-bagus' : ($naFloat >= 70 ? 'nilai-cukup' : 'nilai-kurang') ?>">
                <?= number_format($naFloat, 1) ?>
              </strong>
            <?php else: echo '<span style="color:var(--abu)">—</span>'; endif; ?>
          </td>
          <td style="text-align:center;">
            <?= $pred !== '-' ? "<span class='badge $predCls'>$pred</span>" : '<span style="color:var(--abu)">—</span>' ?>
          </td>
          <td style="font-size:12px;color:var(--abu);">
            <?php if ($naFloat !== null) echo $naFloat >= 75 ? 'Tuntas' : '<span style="color:var(--merah)">Remedi</span>'; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if ($rataKelas !== null): ?>
      <tfoot>
        <tr style="background:var(--hijau-light);font-weight:700;">
          <td colspan="3" style="text-align:right;padding:10px 1rem;">Rata-rata Kelas:</td>
          <td style="text-align:center;color:var(--hijau);"><?= number_format($rataKelas, 1) ?></td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
  <div style="padding:1rem 1.5rem;font-size:13px;color:var(--abu);border-top:1px solid var(--abu-muda);">
    Guru: <?= e($guruData['nama']) ?> | Dicetak: <?= date('d F Y H:i') ?>
  </div>
</div>
<?php elseif ($kelasIdPilih && empty($mapelTersedia)): ?>
  <div style="text-align:center;padding:3rem;color:var(--abu);">
    <div style="font-size:2rem;margin-bottom:.5rem;">⚠️</div>
    <p>Anda belum ditugaskan mengajar mapel apapun di kelas ini.</p>
  </div>
<?php else: ?>
  <div style="text-align:center;padding:3rem;color:var(--abu);">Pilih kelas dan mata pelajaran untuk melihat rekap nilai.</div>
<?php endif; ?>
