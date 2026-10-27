<?php
// siswa/tabs/nilai.php
$siswaId     = $siswaData['id'];
$semester    = $_GET['semester'] ?? '1';
$tahunAjaran = $_GET['ta'] ?? '2024/2025';

$qNilai = $db->prepare("
    SELECT n.*, g.nama as nama_guru
    FROM nilai n
    JOIN guru g ON g.id = n.guru_id
    WHERE n.siswa_id = ? AND n.semester = ? AND n.tahun_ajaran = ?
    ORDER BY n.mapel
");
$qNilai->execute([$siswaId, $semester, $tahunAjaran]);
$nilaiList = $qNilai->fetchAll();

// Hitung rata-rata per mapel menggunakan nilai_akhir_final (atau fallback ke nilai_akhir/nilai_harian)
$rataAll = [];
foreach($nilaiList as $n) {
    // Prioritaskan nilai_akhir_final (Tahap 2), fallback ke nilai_akhir (Tahap 1), lalu nilai_harian
    $nilaiAkhir = $n['nilai_akhir_final'] ?? $n['nilai_akhir'] ?? $n['nilai_harian'] ?? null;
    if($nilaiAkhir !== null) $rataAll[] = (float)$nilaiAkhir;
}
$rataTotal    = count($rataAll) ? round(array_sum($rataAll)/count($rataAll),1) : null;
$nilaiMax     = count($rataAll) ? max($rataAll) : null;
$nilaiMin     = count($rataAll) ? min($rataAll) : null;
$jmlTuntas    = count(array_filter($rataAll, fn($v) => $v >= 75));

// Predikat keseluruhan
$predikatTotal = predikat($rataTotal);
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Siswa <span>/ Nilai Saya</span></div>
  <h2>📊 Nilai Saya</h2>
</div>

<!-- FILTER -->
<div class="filter-bar">
  <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;width:100%;">
    <input type="hidden" name="tab" value="nilai">
    <div class="form-grup" style="min-width:140px;margin:0;">
      <label>Semester</label>
      <select name="semester" class="form-kontrol" onchange="this.form.submit()">
        <option value="1" <?= $semester==='1'?'selected':'' ?>>Semester 1</option>
        <option value="2" <?= $semester==='2'?'selected':'' ?>>Semester 2</option>
      </select>
    </div>
    <div class="form-grup" style="min-width:150px;margin:0;">
      <label>Tahun Ajaran</label>
      <select name="ta" class="form-kontrol" onchange="this.form.submit()">
        <?php foreach(['2024/2025','2025/2026','2023/2024'] as $ta2): ?>
        <option value="<?= $ta2 ?>" <?= $tahunAjaran===$ta2?'selected':'' ?>><?= $ta2 ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<!-- STATISTIK -->
<div class="stat-grid" style="margin-bottom:1.5rem;">
  <div class="stat-kartu">
    <div class="angka"><?= $rataTotal !== null ? number_format($rataTotal,1) : '-' ?></div>
    <div class="label">Rata-rata Keseluruhan</div>
    <?php if($predikatTotal !== '-'): ?>
    <div style="margin-top:6px;font-size:.78rem;font-weight:600;color:<?= warnaPredikat($rataTotal) ?>;"><?= $predikatTotal ?></div>
    <?php endif; ?>
  </div>
  <div class="stat-kartu orange">
    <div class="angka"><?= $nilaiMax !== null ? number_format($nilaiMax,1) : '-' ?></div>
    <div class="label">Nilai Tertinggi</div>
  </div>
  <div class="stat-kartu">
    <div class="angka"><?= $nilaiMin !== null ? number_format($nilaiMin,1) : '-' ?></div>
    <div class="label">Nilai Terendah</div>
  </div>
  <div class="stat-kartu orange">
    <div class="angka"><?= $jmlTuntas ?>/<?= count($nilaiList) ?></div>
    <div class="label">Mata Pelajaran Tuntas</div>
  </div>
</div>

<?php if($nilaiList): ?>
<!-- KARTU NILAI PER MAPEL -->
<div class="nilai-card-grid">
  <?php foreach($nilaiList as $n):
    // Ambil nilai dari kolom baru
    $nilaiHarian = $n['nilai_akhir'] ?? $n['nilai_harian'] ?? null;
    $asts = $n['asts'] ?? null;
    $asas = $n['asas'] ?? null;
    $nilaiAkhirFinal = $n['nilai_akhir_final'] ?? null;

    // Nilai akhir yang ditampilkan: prioritas nilai_akhir_final, lalu nilai harian
    $r = $nilaiAkhirFinal !== null ? (float)$nilaiAkhirFinal : ($nilaiHarian !== null ? (float)$nilaiHarian : null);
    $p  = predikat($r);
    $warna = warnaPredikat($r);
    $pcls = match(true) {
        $r !== null && $r >= 88 => 'badge-hijau',
        $r !== null && $r >= 82 => 'badge-biru',
        $r !== null && $r >= 75 => 'badge-orange',
        $r !== null             => 'badge-merah',
        default                 => 'badge-abu',
    };
    $tuntas = ($r !== null && $r >= 75);
  ?>
  <div class="nilai-card">
    <div class="nilai-card-mapel"><?= e($n['mapel']) ?></div>
    <div class="nilai-card-guru">👨‍🏫 Guru-  <?= e($n['nama_guru']) ?></div>

    <!-- Nilai Harian -->
    <div class="nilai-bar-row">
      <div class="nilai-bar-label">Nilai Harian</div>
      <div class="nilai-bar-wrap">
        <div class="nilai-bar-fill" style="width:<?= $nilaiHarian!==null ? min($nilaiHarian,100) : 0 ?>%;background:<?= $warna ?>;"></div>
      </div>
      <div class="nilai-bar-val"><?= $nilaiHarian!==null ? number_format($nilaiHarian,1) : '<span style="color:var(--abu)">-</span>' ?></div>
    </div>
    <!-- ASTS -->
    <div class="nilai-bar-row">
      <div class="nilai-bar-label">ASTS</div>
      <div class="nilai-bar-wrap">
        <div class="nilai-bar-fill" style="width:<?= $asts!==null ? min($asts,100) : 0 ?>%;background:<?= $warna ?>;opacity:.8;"></div>
      </div>
      <div class="nilai-bar-val"><?= $asts!==null ? number_format($asts,1) : '<span style="color:var(--abu)">-</span>' ?></div>
    </div>
    <!-- ASAS -->
    <div class="nilai-bar-row">
      <div class="nilai-bar-label">ASAS</div>
      <div class="nilai-bar-wrap">
        <div class="nilai-bar-fill" style="width:<?= $asas!==null ? min($asas,100) : 0 ?>%;background:<?= $warna ?>;opacity:.6;"></div>
      </div>
      <div class="nilai-bar-val"><?= $asas!==null ? number_format($asas,1) : '<span style="color:var(--abu)">-</span>' ?></div>
    </div>

    <div class="nilai-card-rata">
      <div>
        <div style="font-size:.75rem;color:var(--abu);margin-bottom:2px;">Nilai Akhir</div>
        <div class="rata-angka" style="color:<?= $warna ?>;">
          <?= $r !== null ? number_format($r,1) : '–' ?>
        </div>
      </div>
      <div style="text-align:right;">
        <?php if($p !== '-'): ?>
          <span class="badge <?= $pcls ?>" style="font-size:.8rem;padding:5px 12px;"><?= $p ?></span>
        <?php endif; ?>
        <div style="font-size:.75rem;margin-top:4px;color:<?= $tuntas?'var(--hijau)':'var(--merah)' ?>;">
          <?= $r!==null ? ($tuntas ? '✅ Tuntas' : '⚠️ Remedi') : '' ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- TABEL REKAP -->
<div class="tabel-wrap">
  <div class="tabel-header">
    <h3>📋 Rekap Nilai — Semester <?= $semester ?> (<?= e($tahunAjaran) ?>)</h3>
    <a href="<?= BASE_URL ?>/siswa/cetak-pdf.php?jenis=nilai&semester=<?= urlencode($semester) ?>&ta=<?= urlencode($tahunAjaran) ?>" target="_blank" class="btn btn-outline btn-sm">🖨️ Cetak PDF</a>
  </div>

  <!-- Info Rumus -->
  <div style="padding:.75rem 1.5rem;background:#f0f9f4;border-bottom:1px solid #c3e6d4;font-size:.8rem;color:#1a5c3a;display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
    <strong>Rumus Nilai Akhir:</strong>
    <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:.72rem;font-weight:700;color:#fff;background:#71717a;">Nilai Harian × 60%</span> +
    <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:.72rem;font-weight:700;color:#fff;background:#7c3aed;">ASTS × 20%</span> +
    <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:.72rem;font-weight:700;color:#fff;background:#0f766e;">ASAS × 20%</span>
  </div>

  <div class="tabel-scroll">
    <table>
      <thead>
        <tr>
          <th>No</th><th>Mata Pelajaran</th><th>Guru</th>
          <th>Nilai Harian</th><th>ASTS</th><th>ASAS</th>
          <th>Nilai Akhir</th><th>Predikat</th><th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($nilaiList as $i=>$n):
          $nilaiHarian = $n['nilai_akhir'] ?? $n['nilai_harian'] ?? null;
          $asts = $n['asts'] ?? null;
          $asas = $n['asas'] ?? null;
          $nilaiAkhirFinal = $n['nilai_akhir_final'] ?? null;
          $r = $nilaiAkhirFinal !== null ? (float)$nilaiAkhirFinal : ($nilaiHarian !== null ? (float)$nilaiHarian : null);
          $p  = predikat($r);
          $pcls = match(true) {
              $r !== null && $r >= 88 => 'badge-hijau',
              $r !== null && $r >= 82 => 'badge-biru',
              $r !== null && $r >= 75 => 'badge-orange',
              $r !== null             => 'badge-merah',
              default                 => 'badge-abu',
          };
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><strong><?= e($n['mapel']) ?></strong></td>
          <td style="font-size:.82rem;color:var(--abu);"><?= e($n['nama_guru']) ?></td>
          <td style="text-align:center;"><?= $nilaiHarian!==null?number_format($nilaiHarian,1):'-' ?></td>
          <td style="text-align:center;"><?= $asts!==null?number_format($asts,1):'-' ?></td>
          <td style="text-align:center;"><?= $asas!==null?number_format($asas,1):'-' ?></td>
          <td style="text-align:center;">
            <?php if($r!==null): ?>
              <strong class="<?= $r>=80?'nilai-bagus':($r>=70?'nilai-cukup':'nilai-kurang') ?>"><?= number_format($r,1) ?></strong>
            <?php else: echo '<span style="color:var(--abu)">-</span>'; endif; ?>
          </td>
          <td style="text-align:center;">
            <?= $p!=='-' ? "<span class='badge $pcls' style='font-size:.72rem;padding:4px 10px;'>$p</span>" : '-' ?>
          </td>
          <td style="font-size:.8rem;color:<?= ($r!==null&&$r>=75)?'var(--hijau)':'var(--merah)' ?>;">
            <?php if($r!==null) echo $r>=75 ? 'Tuntas' : 'Remedi'; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if($rataTotal): ?>
      <tfoot>
        <tr style="background:var(--hijau-light);font-weight:700;">
          <td colspan="6" style="text-align:right;padding:10px 1rem;">Rata-rata Keseluruhan:</td>
          <td style="text-align:center;color:var(--hijau);"><?= number_format($rataTotal,1) ?></td>
          <td style="text-align:center;">
            <span class="badge <?= match(true) {
                $rataTotal >= 88 => 'badge-hijau',
                $rataTotal >= 82 => 'badge-biru',
                $rataTotal >= 75 => 'badge-orange',
                default          => 'badge-merah',
            } ?>" style="font-size:.72rem;padding:4px 10px;"><?= $predikatTotal ?></span>
          </td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
  <div style="padding:1rem 1.5rem;font-size:13px;color:var(--abu);border-top:1px solid var(--abu-muda);">
    Siswa: <?= e($siswaData['nama']) ?> | NIS: <?= e($siswaData['nis']) ?> | Kelas: <?= e($kelas['nama_kelas']??'-') ?> | Dicetak: <?= date('d F Y H:i') ?>
  </div>
</div>

<?php else: ?>
<div style="text-align:center;padding:4rem 2rem;color:var(--abu);">
  <div style="font-size:3rem;margin-bottom:1rem;">📭</div>
  <h3>Belum Ada Data Nilai</h3>
  <p>Data nilai untuk Semester <?= $semester ?> (<?= e($tahunAjaran) ?>) belum tersedia.<br>Hubungi guru mata pelajaran untuk informasi lebih lanjut.</p>
</div>
<?php endif; ?>
