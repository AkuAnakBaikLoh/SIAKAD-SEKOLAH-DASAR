<?php
// siswa/tabs/beranda.php
$siswaId = $siswaData['id'];

// Hitung ringkasan nilai
$qNilai = $db->prepare("SELECT n.*, g.nama as nama_guru FROM nilai n JOIN guru g ON g.id=n.guru_id WHERE n.siswa_id=? ORDER BY n.mapel");
$qNilai->execute([$siswaId]);
$allNilai = $qNilai->fetchAll();

// Hitung ringkasan absensi (semua mapel)
$qAbs = $db->prepare("SELECT status, COUNT(*) as jml FROM absensi WHERE siswa_id=? GROUP BY status");
$qAbs->execute([$siswaId]);
$absRaw = $qAbs->fetchAll();
$absSummary = ['Hadir'=>0,'Sakit'=>0,'Izin'=>0,'Alpa'=>0];
foreach($absRaw as $a) $absSummary[$a['status']] = (int)$a['jml'];
$totalAbs = array_sum($absSummary);
$pctHadir = $totalAbs ? round($absSummary['Hadir']/$totalAbs*100) : 0;

// Rata-rata nilai semua mapel
$rataAll = [];
foreach($allNilai as $n) {
    $r = $n['nilai_akhir_final'] ?? $n['nilai_akhir'] ?? $n['nilai_harian'] ?? null;
    if($r !== null) $rataAll[] = (float)$r;
}
$rataTotal = count($rataAll) ? round(array_sum($rataAll)/count($rataAll),1) : null;
?>

<!-- HERO CARD -->
<div class="siswa-hero">
  <div class="siswa-hero-avatar"><?= $inisial ?></div>
  <div class="siswa-hero-info">
    <h2>Selamat Datang, <?= e(explode(' ', $siswaData['nama'])[0]) ?>! 👋</h2>
    <p>NIS: <strong><?= e($siswaData['nis']) ?></strong></p>
    <div class="siswa-hero-badges">
      <span class="siswa-badge">🏫 Kelas <?= e($kelas['nama_kelas'] ?? '-') ?></span>
      <span class="siswa-badge"><?= $siswaData['jenis_kelamin']==='L' ? '👦 Laki-laki' : '👧 Perempuan' ?></span>
      <span class="siswa-badge">📅 <?= date('d F Y') ?></span>
    </div>
  </div>
</div>

<!-- RINGKASAN AKADEMIK -->
<div class="quick-stats">
  <div class="stat-kartu">
    <div class="angka"><?= $rataTotal !== null ? number_format($rataTotal,1) : '-' ?></div>
    <div class="label">Rata-rata Nilai</div>
  </div>
  <div class="stat-kartu orange">
    <div class="angka"><?= count($allNilai) ?></div>
    <div class="label">Mata Pelajaran</div>
  </div>
  <div class="stat-kartu">
    <div class="angka"><?= $pctHadir ?>%</div>
    <div class="label">Kehadiran</div>
  </div>
  <div class="stat-kartu orange">
    <div class="angka"><?= $absSummary['Alpa'] ?></div>
    <div class="label">Total Alpa</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;">
  <!-- NILAI TERBARU -->
  <div class="tabel-wrap">
    <div class="tabel-header">
      <h3>📊 Ringkasan Nilai</h3>
      <a href="?tab=nilai" class="btn btn-hijau btn-sm">Lihat Semua</a>
    </div>
    <div class="tabel-scroll">
      <table>
        <thead><tr><th>Mata Pelajaran</th><th>Rata-rata</th><th>Predikat</th></tr></thead>
        <tbody>
          <?php if($allNilai): foreach($allNilai as $n):
            $r = $n['nilai_akhir_final'] ?? $n['nilai_akhir'] ?? $n['nilai_harian'] ?? null;
            if($r !== null) $r = (float)$r;
            $p = predikat($r);
            $pcls = match(true) {
                $r !== null && $r >= 88 => 'badge-hijau',
                $r !== null && $r >= 82 => 'badge-biru',
                $r !== null && $r >= 75 => 'badge-orange',
                $r !== null             => 'badge-merah',
                default                 => 'badge-abu',
            };
          ?>
          <tr>
            <td><strong><?= e($n['mapel']) ?></strong></td>
            <td style="text-align:center;"><?= $r!==null ? '<strong class="'.($r>=80?'nilai-bagus':($r>=70?'nilai-cukup':'nilai-kurang')).'">'.number_format($r,1).'</strong>' : '<span style="color:var(--abu)">-</span>' ?></td>
            <td style="text-align:center;"><?= $p!=='-' ? "<span class='badge $pcls' style='font-size:.72rem;'>$p</span>" : '-' ?></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="3" style="text-align:center;color:var(--abu);padding:2rem;">Belum ada data nilai</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ABSENSI RINGKASAN -->
  <div>
    <div class="tabel-wrap">
      <div class="tabel-header">
        <h3>📅 Rekap Kehadiran</h3>
        <a href="?tab=absensi" class="btn btn-hijau btn-sm">Lihat Detail</a>
      </div>
      <div style="padding:1.5rem;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.25rem;">
          <div style="background:linear-gradient(135deg,#1a5c3a,#0d7a4e);color:#fff;border-radius:var(--radius);padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;"><?= $absSummary['Hadir'] ?></div>
            <div style="font-size:.8rem;opacity:.85;">✅ Hadir</div>
          </div>
          <div style="background:linear-gradient(135deg,#d4a017,#f0b429);color:#fff;border-radius:var(--radius);padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;"><?= $absSummary['Sakit'] ?></div>
            <div style="font-size:.8rem;opacity:.85;">🤒 Sakit</div>
          </div>
          <div style="background:linear-gradient(135deg,#1a5276,#2874a6);color:#fff;border-radius:var(--radius);padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;"><?= $absSummary['Izin'] ?></div>
            <div style="font-size:.8rem;opacity:.85;">📝 Izin</div>
          </div>
          <div style="background:linear-gradient(135deg,#c0392b,#e74c3c);color:#fff;border-radius:var(--radius);padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;"><?= $absSummary['Alpa'] ?></div>
            <div style="font-size:.8rem;opacity:.85;">❌ Alpa</div>
          </div>
        </div>
        <!-- Progress bar kehadiran -->
        <div style="font-size:.85rem;color:var(--abu-gelap);margin-bottom:.4rem;">Persentase Kehadiran</div>
        <div style="height:12px;background:var(--abu-muda);border-radius:6px;overflow:hidden;margin-bottom:.5rem;">
          <div style="height:100%;width:<?= $pctHadir ?>%;background:<?= $pctHadir>=75?'var(--hijau)':'var(--merah)' ?>;border-radius:6px;transition:width .8s ease;"></div>
        </div>
        <div style="font-weight:700;font-size:1.1rem;color:<?= $pctHadir>=75?'var(--hijau)':'var(--merah)' ?>;"><?= $pctHadir ?>% <span style="font-size:.8rem;font-weight:400;color:var(--abu);">(<?= $totalAbs ?> pertemuan)</span></div>
      </div>
    </div>
  </div>
</div>
