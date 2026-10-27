<?php
// guru/tabs/absensi.php
$kelasIds     = array_column($kelasDiajar,'id');
$kelasIdPilih = (int)($_GET['kelas_id'] ?? 0);
$tglPilih     = $_GET['tgl'] ?? date('Y-m-d');
$mapelPilih   = $_GET['mapel'] ?? '';

// Mapel yang tersedia untuk kelas yang dipilih
$mapelTersedia = $kelasIdPilih && isset($mapelPerKelas[$kelasIdPilih])
    ? $mapelPerKelas[$kelasIdPilih]
    : [];

if ($mapelPilih === '' && !empty($mapelTersedia)) {
    $mapelPilih = $mapelTersedia[0];
}

// Jika form dikirim (AJAX / POST biasa)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan_absensi'])){
    $kId   = (int)($_POST['kelas_id']??0);
    $tgl   = $_POST['tanggal']??date('Y-m-d');
    $mapel = trim($_POST['mapel_simpan']??'');
    $siswaAbs = $_POST['status'] ?? [];
    $ketAbs   = $_POST['keterangan'] ?? [];
    // Validasi
    $kelasOk = in_array($kId,$kelasIds);
    $mapelOk = $mapel !== '' && in_array($mapel, $mapelPerKelas[$kId] ?? []);
    $ok = $kelasOk && $mapelOk;
    if($ok){
        foreach($siswaAbs as $siswaId => $status){
            try {
                $stmt = $db->prepare("INSERT INTO absensi (siswa_id,kelas_id,guru_id,tanggal,status,keterangan)
                    VALUES (?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE status=VALUES(status), keterangan=VALUES(keterangan)");
                $stmt->execute([$siswaId,$kId,$guruData['id'],$tgl,$status,$ketAbs[$siswaId]??'']);
            } catch(Exception $e){ $ok=false; }
        }
    }
    setFlash($ok?'sukses':'error', $ok?'Absensi berhasil disimpan!':'Gagal menyimpan absensi.');
    header("Location: ?tab=absensi&kelas_id=$kId&mapel=".urlencode($mapel)."&tgl=$tgl"); exit;
}

// Ambil siswa jika kelas dipilih
$siswaList = [];
if($kelasIdPilih && in_array($kelasIdPilih,$kelasIds)){
    $sq = $db->prepare("SELECT s.* FROM siswa s WHERE s.kelas_id=? AND s.aktif=1 ORDER BY s.nama");
    $sq->execute([$kelasIdPilih]); $siswaList = $sq->fetchAll();
}
// Ambil absensi yang sudah ada (unik per siswa+kelas+guru+tanggal — TIDAK per mapel, karena absensi per hari)
$absensiAda = [];
if($kelasIdPilih && $siswaList && $mapelPilih){
    $sids = array_column($siswaList,'id');
    $ph   = implode(',',array_fill(0,count($sids),'?'));
    $qa   = $db->prepare("SELECT siswa_id,status,keterangan FROM absensi WHERE siswa_id IN ($ph) AND kelas_id=? AND guru_id=? AND tanggal=?");
    $qa->execute([...$sids,$kelasIdPilih,$guruData['id'],$tglPilih]);
    foreach($qa->fetchAll() as $a) $absensiAda[$a['siswa_id']] = $a;
}
$namaKelas = '';
if($kelasIdPilih){
    $idx = array_search($kelasIdPilih, array_column($kelasDiajar,'id'));
    if($idx !== false) $namaKelas = $kelasDiajar[$idx]['nama_kelas'];
}
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Guru <span>/ Input Absensi</span></div>
  <h2>✅ Input Absensi</h2>
  <p>Rekam kehadiran siswa untuk mata pelajaran yang Anda ajarkan.</p>
</div>

<!-- FILTER -->
<div class="kartu" style="margin-bottom:1.5rem;">
  <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
    <input type="hidden" name="tab" value="absensi">
    <div class="form-grup" style="flex:1;min-width:160px;margin:0;">
      <label>Kelas</label>
      <select name="kelas_id" class="form-kontrol" onchange="this.form.submit()">
        <option value="">— Pilih Kelas —</option>
        <?php foreach($kelasDiajar as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $kelasIdPilih==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if($kelasIdPilih && !empty($mapelTersedia)): ?>
    <div class="form-grup" style="flex:1;min-width:170px;margin:0;">
      <label>Mata Pelajaran</label>
      <select name="mapel" class="form-kontrol" onchange="this.form.submit()">
        <?php foreach($mapelTersedia as $m): ?>
        <option value="<?= e($m) ?>" <?= $mapelPilih===$m?'selected':'' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-grup" style="flex:1;min-width:160px;margin:0;">
      <label>Tanggal</label>
      <input type="date" name="tgl" class="form-kontrol" value="<?= e($tglPilih) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
    </div>
  </form>
</div>

<?php if($kelasIdPilih && empty($mapelTersedia)): ?>
<div style="text-align:center;padding:3rem;color:var(--abu);">
  <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
  <p>Anda belum ditugaskan mengajar mapel apapun di kelas ini.<br>Hubungi admin untuk pengaturan guru-mapel.</p>
</div>
<?php elseif($kelasIdPilih && $mapelPilih && $siswaList): ?>
<form method="POST">
  <input type="hidden" name="simpan_absensi" value="1">
  <input type="hidden" name="kelas_id" value="<?= $kelasIdPilih ?>">
  <input type="hidden" name="mapel_simpan" value="<?= e($mapelPilih) ?>">
  <input type="hidden" name="tanggal" value="<?= e($tglPilih) ?>">

  <div class="tabel-wrap">
    <div class="tabel-header">
      <h3>
        ✅ <?= e($mapelPilih) ?> — <?= e($namaKelas) ?>
        | <?= date('d F Y',strtotime($tglPilih)) ?>
      </h3>
      <div class="tabel-aksi">
        <button type="button" class="btn btn-abu btn-sm" onclick="isiSemua('Hadir')">✓ Semua Hadir</button>
      </div>
    </div>
    <div class="tabel-scroll">
      <table>
        <thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Status</th><th>Keterangan</th></tr></thead>
        <tbody>
          <?php foreach($siswaList as $i=>$s):
            $absAda = $absensiAda[$s['id']] ?? null;
            $stAda  = $absAda ? $absAda['status'] : 'Hadir';
            $ketAda = $absAda ? $absAda['keterangan'] : '';
          ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= e($s['nis']) ?></td>
            <td><strong><?= e($s['nama']) ?></strong></td>
            <td>
              <select name="status[<?= $s['id'] ?>]" class="hadir-select status-select" onchange="warnaBaris(this)">
                <?php foreach(['Hadir'=>'✓','Sakit'=>'🤒','Izin'=>'📋','Alpa'=>'✗'] as $opt=>$iko): ?>
                <option value="<?= $opt ?>" <?= $stAda===$opt?'selected':'' ?>><?= $iko ?> <?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <input type="text" name="keterangan[<?= $s['id'] ?>]" class="form-kontrol" style="padding:5px 10px;font-size:13px;"
                value="<?= e($ketAda) ?>" placeholder="Keterangan (opsional)">
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:1rem 1.5rem;border-top:1px solid var(--abu-muda);display:flex;justify-content:flex-end;">
      <button type="submit" class="btn btn-hijau">💾 Simpan Absensi</button>
    </div>
  </div>
</form>
<?php elseif($kelasIdPilih && $mapelPilih && !$siswaList): ?>
  <div style="text-align:center;padding:3rem;color:var(--abu);">Tidak ada siswa aktif di kelas ini.</div>
<?php else: ?>
  <div style="text-align:center;padding:3rem;color:var(--abu);">
    <div style="font-size:3rem;margin-bottom:1rem;">📋</div>
    <p>Silakan pilih kelas dan mata pelajaran untuk memulai absensi.</p>
  </div>
<?php endif; ?>

<script>
function isiSemua(status) {
  document.querySelectorAll('.status-select').forEach(sel => {
    sel.value = status; warnaBaris(sel);
  });
}
function warnaBaris(sel) {
  const tr = sel.closest('tr');
  const colors = {Hadir:'',Sakit:'#fff8e1',Izin:'#e8f0fb',Alpa:'#fdecea'};
  tr.style.background = colors[sel.value] || '';
}
// Warna awal
document.querySelectorAll('.status-select').forEach(sel => warnaBaris(sel));
</script>
