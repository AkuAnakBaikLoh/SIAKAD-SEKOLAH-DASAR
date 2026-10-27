<?php
// guru/tabs/nilai.php — Sistem penilaian Tahap 1 + Tahap 2
$kelasIds     = array_column($kelasDiajar, 'id');
$kelasIdPilih = (int)($_GET['kelas_id'] ?? 0);
$semester     = $_GET['semester'] ?? '1';
$tahunAjaran  = $_GET['ta'] ?? '2024/2025';
$mapelPilih   = $_GET['mapel'] ?? '';

$mapelTersedia = $kelasIdPilih && isset($mapelPerKelas[$kelasIdPilih])
    ? $mapelPerKelas[$kelasIdPilih] : [];
if ($mapelPilih === '' && !empty($mapelTersedia)) {
    $mapelPilih = $mapelTersedia[0];
}

// ── Simpan nilai ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    $kId   = (int)($_POST['kelas_id']    ?? 0);
    $sem   = $_POST['semester']          ?? '1';
    $ta    = $_POST['tahun_ajaran']      ?? '2024/2025';
    $mapel = trim($_POST['mapel_simpan'] ?? '');
    $data  = $_POST['nilai']             ?? [];

    $kelasOk = in_array($kId, $kelasIds);
    $mapelOk = $mapel !== '' && in_array($mapel, $mapelPerKelas[$kId] ?? []);
    $ok = $kelasOk && $mapelOk;

    if ($ok) {
        foreach ($data as $siswaId => $n) {
            // ── TAHAP 1: Komponen Nilai Harian ────────────────────────────
            $u = [];
            for ($i=1;$i<=3;$i++) {
                $v = trim($n["u$i"] ?? '');
                $u[$i] = ($v !== '' && is_numeric($v)) ? (float)$v : null;
            }
            $t = [];
            for ($i=1;$i<=4;$i++) {
                $v = trim($n["t$i"] ?? '');
                $t[$i] = ($v !== '' && is_numeric($v)) ? (float)$v : null;
            }
            $keaktifan = ($v = trim($n['keaktifan'] ?? '')) !== '' && is_numeric($v) ? (float)$v : null;
            $kehadiran = ($v = trim($n['kehadiran'] ?? '')) !== '' && is_numeric($v) ? (float)$v : null;

            $uIsi  = array_filter($u, fn($x) => $x !== null);
            $rataU = count($uIsi) ? array_sum($uIsi) / count($uIsi) : null;
            $outU  = $rataU !== null ? round($rataU * 0.20, 2) : null;

            $tIsi  = array_filter($t, fn($x) => $x !== null);
            $rataT = count($tIsi) ? array_sum($tIsi) / count($tIsi) : null;
            $outT  = $rataT !== null ? round($rataT * 0.40, 2) : null;

            $outKA = $keaktifan !== null ? round($keaktifan * 0.20, 2) : null;
            $outKH = $kehadiran !== null ? round($kehadiran * 0.20, 2) : null;

            // Nilai Harian = jumlah output Tahap 1 yang tersedia
            $outArr1   = array_filter([$outU, $outT, $outKA, $outKH], fn($x) => $x !== null);
            $nilaiHarian = !empty($outArr1) ? round(array_sum($outArr1), 2) : null;

            // ── TAHAP 2: ASTS, ASAS, Nilai Akhir Final ───────────────────
            $asts = ($v = trim($n['asts'] ?? '')) !== '' && is_numeric($v) ? (float)$v : null;
            $asas = ($v = trim($n['asas'] ?? '')) !== '' && is_numeric($v) ? (float)$v : null;

            $outNH   = $nilaiHarian !== null ? round($nilaiHarian * 0.60, 2) : null;
            $outASTS = $asts !== null ? round($asts * 0.20, 2) : null;
            $outASAS = $asas !== null ? round($asas * 0.20, 2) : null;

            $outArr2 = array_filter([$outNH, $outASTS, $outASAS], fn($x) => $x !== null);
            $nilaiAkhirFinal = !empty($outArr2) ? round(array_sum($outArr2), 2) : null;

            // nilai_harian (kolom lama) = nilai akhir final jika ada, fallback ke nilai harian Tahap 1
            $simpanHarian = $nilaiAkhirFinal ?? $nilaiHarian;

            try {
                $stmt = $db->prepare("
                    INSERT INTO nilai
                        (siswa_id, kelas_id, guru_id, mapel, semester, tahun_ajaran,
                         u1, u2, u3, t1, t2, t3, t4, keaktifan, kehadiran, nilai_akhir,
                         asts, asas, nilai_akhir_final,
                         nilai_harian, uts, uas)
                    VALUES (?,?,?,?,?,?, ?,?,?,?,?,?,?,?,?,?, ?,?,?, ?,NULL,NULL)
                    ON DUPLICATE KEY UPDATE
                         u1=VALUES(u1), u2=VALUES(u2), u3=VALUES(u3),
                         t1=VALUES(t1), t2=VALUES(t2), t3=VALUES(t3), t4=VALUES(t4),
                         keaktifan=VALUES(keaktifan), kehadiran=VALUES(kehadiran),
                         nilai_akhir=VALUES(nilai_akhir),
                         asts=VALUES(asts), asas=VALUES(asas),
                         nilai_akhir_final=VALUES(nilai_akhir_final),
                         nilai_harian=VALUES(nilai_harian), uts=NULL, uas=NULL
                ");
                $stmt->execute([
                    $siswaId, $kId, $guruData['id'], $mapel, $sem, $ta,
                    $u[1], $u[2], $u[3], $t[1], $t[2], $t[3], $t[4],
                    $keaktifan, $kehadiran, $nilaiHarian,
                    $asts, $asas, $nilaiAkhirFinal,
                    $simpanHarian
                ]);
            } catch (Exception $e) { $ok = false; }
        }
    }
    setFlash($ok ? 'sukses' : 'error', $ok ? 'Nilai berhasil disimpan!' : 'Gagal menyimpan. Jalankan migrate_nilai.php terlebih dahulu.');
    header("Location: ?tab=nilai&kelas_id=$kId&semester=$sem&mapel=" . urlencode($mapel) . "&ta=" . urlencode($ta));
    exit;
}

// ── Ambil data siswa & nilai ──────────────────────────────────────────────────
$siswaList = [];
$nilaiMap  = [];
if ($kelasIdPilih && in_array($kelasIdPilih, $kelasIds) && $mapelPilih) {
    $sq = $db->prepare("SELECT s.* FROM siswa s WHERE s.kelas_id=? AND s.aktif=1 ORDER BY s.nama");
    $sq->execute([$kelasIdPilih]);
    $siswaList = $sq->fetchAll();
    if ($siswaList) {
        $sids = array_column($siswaList, 'id');
        $ph   = implode(',', array_fill(0, count($sids), '?'));
        $qn   = $db->prepare("SELECT * FROM nilai
                               WHERE siswa_id IN ($ph) AND kelas_id=? AND guru_id=? AND mapel=? AND semester=? AND tahun_ajaran=?");
        $qn->execute([...$sids, $kelasIdPilih, $guruData['id'], $mapelPilih, $semester, $tahunAjaran]);
        foreach ($qn->fetchAll() as $n) $nilaiMap[$n['siswa_id']] = $n;
    }
}
$namaKelas = '';
if ($kelasIdPilih) {
    $idx = array_search($kelasIdPilih, array_column($kelasDiajar, 'id'));
    if ($idx !== false) $namaKelas = $kelasDiajar[$idx]['nama_kelas'];
}
?>
<style>
.inp-nilai {
  width: 64px; padding: 5px 6px;
  border: 1.5px solid var(--abu-muda); border-radius: 6px;
  font-size: 13px; text-align: center;
  transition: border-color .2s;
}
.inp-nilai:focus { outline: none; border-color: var(--hijau); }
.inp-nilai.diisi { border-color: var(--hijau); background: #f0f9f4; }

.out-cell {
  font-weight: 700; font-size: .85rem; min-width: 48px;
  text-align: center; color: #fff;
}
.out-cell.kosong { color: rgba(255,255,255,.5); font-weight: 400; }

th.grup-header {
  text-align: center; border-bottom: 2px solid rgba(255,255,255,.2);
  padding: 6px 4px 4px; font-size: .78rem; color: #fff;
}
th.sub-header { font-size: .72rem; font-weight: 600; padding: 4px 3px; text-align:center; color:#fff; }
td { vertical-align: middle; }

.rumus-info {
  background: #f0f9f4; border: 1px solid #c3e6d4;
  border-radius: var(--radius-sm); padding: .75rem 1rem;
  margin-bottom: 1.25rem; font-size: .83rem; color: #1a5c3a;
  display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;
}
.rumus-badge {
  display: inline-block; padding: 2px 10px; border-radius: 999px;
  font-size: .72rem; font-weight: 700; color: #fff;
}
.section-divider {
  display: flex; align-items: center; gap: .75rem; margin: 2rem 0 1rem;
}
.section-divider .bar { width: 4px; height: 32px; border-radius: 2px; }
.section-divider h3 { margin: 0; font-size: 1rem; }
.section-divider p  { margin: 0; font-size: .82rem; color: var(--abu); }
</style>

<div class="dash-header">
  <div class="dash-breadcrumb">Guru <span>/ Input Nilai</span></div>
  <h2>📝 Input Nilai</h2>
  <p>Masukkan komponen nilai siswa — Tahap 1 (Nilai Harian) lalu Tahap 2 (ASTS, ASAS, Nilai Akhir).</p>
</div>

<!-- FILTER -->
<div class="kartu" style="margin-bottom:1.5rem;">
  <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
    <input type="hidden" name="tab" value="nilai">
    <div class="form-grup" style="flex:1;min-width:150px;margin:0;">
      <label>Kelas</label>
      <select name="kelas_id" class="form-kontrol" onchange="this.form.submit()">
        <option value="">— Pilih Kelas —</option>
        <?php foreach ($kelasDiajar as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $kelasIdPilih == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($kelasIdPilih && !empty($mapelTersedia)): ?>
    <div class="form-grup" style="flex:1;min-width:170px;margin:0;">
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
        <?php foreach (['2024/2025','2025/2026','2023/2024'] as $ta2): ?>
        <option value="<?= $ta2 ?>" <?= $tahunAjaran === $ta2 ? 'selected' : '' ?>><?= $ta2 ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if ($kelasIdPilih && empty($mapelTersedia)): ?>
<div style="text-align:center;padding:3rem;color:var(--abu);">
  <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
  <p>Anda belum ditugaskan mengajar mata pelajaran apapun di kelas ini.</p>
</div>
<?php elseif ($kelasIdPilih && $mapelPilih && $siswaList): ?>

<!-- ══════════════════════════════════════════════
     TAHAP 1 — NILAI HARIAN
══════════════════════════════════════════════ -->
<div class="section-divider">
  <div class="bar" style="background:linear-gradient(180deg,#6366f1,#0891b2);"></div>
  <div>
    <h3>Tahap 1 — Nilai Harian</h3>
  </div>
</div>

<div class="rumus-info">
  <span><strong>Nilai Harian =</strong></span>
  <span><span class="rumus-badge" style="background:#6366f1;">Total Nilai Ulangan÷n × 20%</span> +</span>
  <span><span class="rumus-badge" style="background:#0891b2;">Total Nilai Tugas÷n × 40%</span> +</span>
  <span><span class="rumus-badge" style="background:#16a34a;">Nilai Keaktifan × 20%</span> +</span>
  <span><span class="rumus-badge" style="background:#d97706;">Nilai Kehadiran × 20%</span></span>
</div>

<form method="POST" id="formNilai1">
  <input type="hidden" name="simpan_nilai" value="1">
  <input type="hidden" name="kelas_id" value="<?= $kelasIdPilih ?>">
  <input type="hidden" name="mapel_simpan" value="<?= e($mapelPilih) ?>">
  <input type="hidden" name="semester" value="<?= e($semester) ?>">
  <input type="hidden" name="tahun_ajaran" value="<?= e($tahunAjaran) ?>">
  <!-- Kirim ulang ASTS & ASAS agar tidak terhapus saat simpan Tahap 1 -->
  <?php foreach ($siswaList as $s):
    $nhd = $nilaiMap[$s['id']] ?? []; ?>
  <input type="hidden" name="nilai[<?= $s['id'] ?>][asts]" value="<?= $nhd['asts'] ?? '' ?>">
  <input type="hidden" name="nilai[<?= $s['id'] ?>][asas]" value="<?= $nhd['asas'] ?? '' ?>">
  <?php endforeach; ?>

  <div class="tabel-wrap">
    <div class="tabel-header">
      <h3><?= e($mapelPilih) ?> — <?= e($namaKelas) ?> | Semester <?= $semester ?> (<?= e($tahunAjaran) ?>)</h3>
    </div>
    <div class="tabel-scroll" style="overflow-x:auto;">
      <table style="min-width:900px;">
        <thead>
          <tr>
            <th rowspan="2" style="width:32px;">No</th>
            <th rowspan="2">Nama Siswa</th>
            <th colspan="5" class="grup-header" style="background:#6366f1;">
              Nilai Ulangan <small style="font-weight:400;opacity:.85;">(bobot 20%)</small>
            </th>
            <th colspan="6" class="grup-header" style="background:#0891b2;">
              Nilai Tugas <small style="font-weight:400;opacity:.85;">(bobot 40%)</small>
            </th>
            <th colspan="2" class="grup-header" style="background:#16a34a;">
              Keaktifan <small style="font-weight:400;opacity:.85;">(20%)</small>
            </th>
            <th colspan="2" class="grup-header" style="background:#d97706;">
              Kehadiran <small style="font-weight:400;opacity:.85;">(20%)</small>
            </th>
            <th rowspan="2" class="grup-header" style="background:#71717a;">
              Nilai<br>Harian
            </th>
          </tr>
          <tr>
            <th class="sub-header" style="background:#6366f1;">U1</th>
            <th class="sub-header" style="background:#6366f1;">U2</th>
            <th class="sub-header" style="background:#6366f1;">U3</th>
            <th class="sub-header" style="background:#6366f1;">Rata²</th>
            <th class="sub-header" style="background:#6366f1;">×20%</th>
            <th class="sub-header" style="background:#0891b2;">T1</th>
            <th class="sub-header" style="background:#0891b2;">T2</th>
            <th class="sub-header" style="background:#0891b2;">T3</th>
            <th class="sub-header" style="background:#0891b2;">T4</th>
            <th class="sub-header" style="background:#0891b2;">Rata²</th>
            <th class="sub-header" style="background:#0891b2;">×40%</th>
            <th class="sub-header" style="background:#16a34a;">Nilai</th>
            <th class="sub-header" style="background:#16a34a;">×20%</th>
            <th class="sub-header" style="background:#d97706;">Nilai</th>
            <th class="sub-header" style="background:#d97706;">×20%</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($siswaList as $i => $s):
            $n = $nilaiMap[$s['id']] ?? [];
            $sid = $s['id'];
          ?>
          <tr id="row_<?= $sid ?>">
            <td><?= $i+1 ?></td>
            <td><strong><?= e($s['nama']) ?></strong><br><small style="color:var(--abu);font-size:11px;"><?= e($s['nis']) ?></small></td>

            <?php for($u=1;$u<=3;$u++): ?>
            <td style="padding:4px;">
              <input type="number" class="inp-nilai" id="u<?= $u ?>_<?= $sid ?>"
                     name="nilai[<?= $sid ?>][u<?= $u ?>]"
                     min="0" max="100" step="0.5"
                     value="<?= $n["u$u"] ?? '' ?>"
                     oninput="hitung1(<?= $sid ?>)" placeholder="—">
            </td>
            <?php endfor; ?>
            <td style="background:#6366f1;" class="out-cell kosong" id="rata_u_<?= $sid ?>">—</td>
            <td style="background:#6366f1;" class="out-cell kosong" id="out_u_<?= $sid ?>">—</td>

            <?php for($t=1;$t<=4;$t++): ?>
            <td style="padding:4px;">
              <input type="number" class="inp-nilai" id="t<?= $t ?>_<?= $sid ?>"
                     name="nilai[<?= $sid ?>][t<?= $t ?>]"
                     min="0" max="100" step="0.5"
                     value="<?= $n["t$t"] ?? '' ?>"
                     oninput="hitung1(<?= $sid ?>)" placeholder="—">
            </td>
            <?php endfor; ?>
            <td style="background:#0891b2;" class="out-cell kosong" id="rata_t_<?= $sid ?>">—</td>
            <td style="background:#0891b2;" class="out-cell kosong" id="out_t_<?= $sid ?>">—</td>

            <td style="padding:4px;">
              <input type="number" class="inp-nilai" id="ka_<?= $sid ?>"
                     name="nilai[<?= $sid ?>][keaktifan]"
                     min="0" max="100" step="0.5"
                     value="<?= $n['keaktifan'] ?? '' ?>"
                     oninput="hitung1(<?= $sid ?>)" placeholder="—">
            </td>
            <td style="background:#16a34a;" class="out-cell kosong" id="out_ka_<?= $sid ?>">—</td>

            <td style="padding:4px;">
              <input type="number" class="inp-nilai" id="kh_<?= $sid ?>"
                     name="nilai[<?= $sid ?>][kehadiran]"
                     min="0" max="100" step="0.5"
                     value="<?= $n['kehadiran'] ?? '' ?>"
                     oninput="hitung1(<?= $sid ?>)" placeholder="—">
            </td>
            <td style="background:#d97706;" class="out-cell kosong" id="out_kh_<?= $sid ?>">—</td>

            <td style="background:#71717a;" class="out-cell kosong" id="nh_<?= $sid ?>">—</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:1rem 1.5rem;border-top:1px solid var(--abu-muda);display:flex;justify-content:flex-end;">
      <button type="submit" class="btn btn-hijau">💾 Simpan Semua Nilai Harian</button>
    </div>
  </div>
</form>

<!-- ══════════════════════════════════════════════
     TAHAP 2 — NILAI AKHIR (ASTS + ASAS)
══════════════════════════════════════════════ -->
<div class="section-divider" style="margin-top:2.5rem;">
  <div class="bar" style="background:linear-gradient(180deg,#7c3aed,#0f766e);"></div>
  <div>
    <h3>Tahap 2 — Nilai Akhir</h3>
  </div>
</div>

<div class="rumus-info" style="background:#faf5ff;border-color:#e9d5ff;">
  <span><strong>Nilai Akhir =</strong></span>
  <span><span class="rumus-badge" style="background:#71717a;">Nilai Harian × 60%</span> +</span>
  <span><span class="rumus-badge" style="background:#7c3aed;">ASTS × 20%</span> +</span>
  <span><span class="rumus-badge" style="background:#0f766e;">ASAS × 20%</span></span>
</div>

<form method="POST" id="formNilai2">
  <input type="hidden" name="simpan_nilai" value="1">
  <input type="hidden" name="kelas_id" value="<?= $kelasIdPilih ?>">
  <input type="hidden" name="mapel_simpan" value="<?= e($mapelPilih) ?>">
  <input type="hidden" name="semester" value="<?= e($semester) ?>">
  <input type="hidden" name="tahun_ajaran" value="<?= e($tahunAjaran) ?>">
  <!-- Kirim ulang semua nilai Tahap 1 agar tidak terhapus saat simpan Tahap 2 -->
  <?php foreach ($siswaList as $s):
    $nd = $nilaiMap[$s['id']] ?? [];
    $sid2 = $s['id'];
    for ($ui=1;$ui<=3;$ui++) echo "<input type='hidden' name='nilai[{$sid2}][u{$ui}]' value='" . htmlspecialchars($nd["u$ui"] ?? '') . "'>";
    for ($ti=1;$ti<=4;$ti++) echo "<input type='hidden' name='nilai[{$sid2}][t{$ti}]' value='" . htmlspecialchars($nd["t$ti"] ?? '') . "'>";
    echo "<input type='hidden' name='nilai[{$sid2}][keaktifan]' value='" . htmlspecialchars($nd['keaktifan'] ?? '') . "'>";
    echo "<input type='hidden' name='nilai[{$sid2}][kehadiran]'  value='" . htmlspecialchars($nd['kehadiran']  ?? '') . "'>";
  endforeach; ?>

  <div class="tabel-wrap">
    <div class="tabel-header">
      <h3><?= e($mapelPilih) ?> — <?= e($namaKelas) ?> | Semester <?= $semester ?> (<?= e($tahunAjaran) ?>)</h3>
    </div>
    <div class="tabel-scroll" style="overflow-x:auto;">
      <table style="min-width:680px;">
        <thead>
          <tr>
            <th rowspan="2" style="width:32px;">No</th>
            <th rowspan="2">Nama Siswa</th>
            <th colspan="2" class="grup-header" style="background:#71717a;">
              Nilai Harian <small style="font-weight:400;opacity:.85;">(60%)</small>
            </th>
            <th colspan="2" class="grup-header" style="background:#7c3aed;">
              ASTS <small style="font-weight:400;opacity:.85;">(20%)</small>
            </th>
            <th colspan="2" class="grup-header" style="background:#0f766e;">
              ASAS <small style="font-weight:400;opacity:.85;">(20%)</small>
            </th>
            <th rowspan="2" class="grup-header" style="background:#1a3a5c;">
              Nilai<br>Akhir
            </th>
          </tr>
          <tr>
            <th class="sub-header" style="background:#71717a;">Nilai</th>
            <th class="sub-header" style="background:#71717a;">×60%</th>
            <th class="sub-header" style="background:#7c3aed;">Input</th>
            <th class="sub-header" style="background:#7c3aed;">×20%</th>
            <th class="sub-header" style="background:#0f766e;">Input</th>
            <th class="sub-header" style="background:#0f766e;">×20%</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($siswaList as $i => $s):
            $nd2  = $nilaiMap[$s['id']] ?? [];
            $sid2 = $s['id'];
            $nhDb = $nd2['nilai_akhir'] ?? null; // Nilai Harian tersimpan dari Tahap 1
          ?>
          <tr id="row2_<?= $sid2 ?>">
            <td><?= $i+1 ?></td>
            <td><strong><?= e($s['nama']) ?></strong><br><small style="color:var(--abu);font-size:11px;"><?= e($s['nis']) ?></small></td>

            <!-- NILAI HARIAN (dari Tahap 1 — read-only) -->
            <td style="background:#71717a;text-align:center;">
              <strong id="nh2_disp_<?= $sid2 ?>" style="color:#fff;font-size:.9rem;">
                <?= $nhDb !== null ? number_format($nhDb, 2) : '—' ?>
              </strong>
              <input type="hidden" id="nh2_val_<?= $sid2 ?>" value="<?= $nhDb ?? '' ?>">
            </td>
            <td style="background:#71717a;" class="out-cell kosong" id="out_nh2_<?= $sid2 ?>">—</td>

            <!-- ASTS -->
            <td style="padding:4px;">
              <input type="number" class="inp-nilai" id="asts_<?= $sid2 ?>"
                     name="nilai[<?= $sid2 ?>][asts]"
                     min="0" max="100" step="0.5"
                     value="<?= $nd2['asts'] ?? '' ?>"
                     oninput="hitung2(<?= $sid2 ?>)" placeholder="—">
            </td>
            <td style="background:#7c3aed;" class="out-cell kosong" id="out_asts_<?= $sid2 ?>">—</td>

            <!-- ASAS -->
            <td style="padding:4px;">
              <input type="number" class="inp-nilai" id="asas_<?= $sid2 ?>"
                     name="nilai[<?= $sid2 ?>][asas]"
                     min="0" max="100" step="0.5"
                     value="<?= $nd2['asas'] ?? '' ?>"
                     oninput="hitung2(<?= $sid2 ?>)" placeholder="—">
            </td>
            <td style="background:#0f766e;" class="out-cell kosong" id="out_asas_<?= $sid2 ?>">—</td>

            <!-- NILAI AKHIR FINAL -->
            <td style="background:#1a3a5c;" class="out-cell kosong" id="akhir2_<?= $sid2 ?>">—</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:1rem 1.5rem;border-top:1px solid var(--abu-muda);display:flex;justify-content:flex-end;">
      <button type="submit" class="btn btn-hijau">💾 Simpan Semua Nilai Akhir</button>
    </div>
  </div>
</form>

<?php elseif ($kelasIdPilih && $mapelPilih && !$siswaList): ?>
  <div style="text-align:center;padding:3rem;color:var(--abu);">Tidak ada siswa aktif di kelas ini.</div>
<?php else: ?>
  <div style="text-align:center;padding:3rem;color:var(--abu);">
    <div style="font-size:3rem;margin-bottom:1rem;">📝</div>
    <p>Pilih kelas dan mata pelajaran untuk mulai memasukkan nilai.</p>
  </div>
<?php endif; ?>

<script>
function getVal(id) {
  const el = document.getElementById(id);
  if (!el) return null;
  const v = el.value.trim();
  return (v !== '' && !isNaN(v)) ? parseFloat(v) : null;
}

function setCell(id, val, decimals = 2) {
  const el = document.getElementById(id);
  if (!el) return;
  if (val === null) {
    el.textContent = '—';
    el.classList.add('kosong');
  } else {
    el.textContent = val.toFixed(decimals);
    el.classList.remove('kosong');
  }
}

// ── TAHAP 1 ─────────────────────────────────────────────────────────────────
function hitung1(sid) {
  // Ulangan
  const uVals = [1,2,3].map(i => getVal(`u${i}_${sid}`)).filter(v => v !== null);
  const rataU = uVals.length ? uVals.reduce((a,b) => a+b, 0) / uVals.length : null;
  const outU  = rataU !== null ? rataU * 0.20 : null;
  setCell(`rata_u_${sid}`, rataU);
  setCell(`out_u_${sid}`, outU);

  // Tugas
  const tVals = [1,2,3,4].map(i => getVal(`t${i}_${sid}`)).filter(v => v !== null);
  const rataT = tVals.length ? tVals.reduce((a,b) => a+b, 0) / tVals.length : null;
  const outT  = rataT !== null ? rataT * 0.40 : null;
  setCell(`rata_t_${sid}`, rataT);
  setCell(`out_t_${sid}`, outT);

  // Keaktifan
  const ka    = getVal(`ka_${sid}`);
  const outKA = ka !== null ? ka * 0.20 : null;
  setCell(`out_ka_${sid}`, outKA);

  // Kehadiran
  const kh    = getVal(`kh_${sid}`);
  const outKH = kh !== null ? kh * 0.20 : null;
  setCell(`out_kh_${sid}`, outKH);

  // Nilai Harian = jumlah output yang tersedia
  const outs = [outU, outT, outKA, outKH];
  const ada  = outs.some(v => v !== null);
  const nh   = ada ? outs.reduce((s, v) => s + (v ?? 0), 0) : null;
  setCell(`nh_${sid}`, nh);

  // Sinkronkan ke kolom Nilai Harian di Tahap 2 (jika sudah render)
  const dispEl = document.getElementById(`nh2_disp_${sid}`);
  const valEl  = document.getElementById(`nh2_val_${sid}`);
  if (dispEl) dispEl.textContent = nh !== null ? nh.toFixed(2) : '—';
  if (valEl)  valEl.value = nh !== null ? nh : '';
  hitung2(sid);
}

// ── TAHAP 2 ─────────────────────────────────────────────────────────────────
function hitung2(sid) {
  const nhVal = getVal(`nh2_val_${sid}`);
  const outNH = nhVal !== null ? nhVal * 0.60 : null;
  setCell(`out_nh2_${sid}`, outNH);

  const asts    = getVal(`asts_${sid}`);
  const outASTS = asts !== null ? asts * 0.20 : null;
  setCell(`out_asts_${sid}`, outASTS);

  const asas    = getVal(`asas_${sid}`);
  const outASAS = asas !== null ? asas * 0.20 : null;
  setCell(`out_asas_${sid}`, outASAS);

  // Nilai Akhir Final
  const out2 = [outNH, outASTS, outASAS];
  const ada2 = out2.some(v => v !== null);
  const akhir = ada2 ? out2.reduce((s, v) => s + (v ?? 0), 0) : null;
  setCell(`akhir2_${sid}`, akhir);
}

// Hitung semua baris saat halaman dimuat
document.addEventListener('DOMContentLoaded', () => {
  <?php foreach ($siswaList as $s): ?>
  hitung1(<?= $s['id'] ?>);
  hitung2(<?= $s['id'] ?>);
  <?php endforeach; ?>
});
</script>
