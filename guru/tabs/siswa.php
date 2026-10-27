<?php
// guru/tabs/siswa.php
$kelasIds = array_column($kelasDiajar, 'id');
if(empty($kelasIds)){ echo '<p style="color:var(--abu)">Anda belum memiliki kelas.</p>'; return; }

$placeholders = implode(',', array_fill(0, count($kelasIds), '?'));
$siswaList = $db->prepare("
    SELECT s.*, k.nama_kelas FROM siswa s
    JOIN kelas k ON k.id = s.kelas_id
    WHERE s.kelas_id IN ($placeholders) AND s.aktif = 1
    ORDER BY k.nama_kelas, s.nama
");
$siswaList->execute($kelasIds);
$siswaList = $siswaList->fetchAll();
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Guru <span>/ Data Siswa</span></div>
  <h2>👥 Data Siswa</h2>
  <p>Siswa di kelas yang Anda ampu</p>
</div>
<div class="tabel-wrap">
  <div class="tabel-header">
    <h3>Daftar Siswa (<?= count($siswaList) ?>)</h3>
    <input class="input-cari" placeholder="🔍 Cari siswa..." oninput="filterTbl(this)">
  </div>
  <div class="tabel-scroll">
    <table id="tblSiswa">
      <thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Absensi Hadir</th><th>Rata-rata Nilai</th></tr></thead>
      <tbody>
        <?php foreach($siswaList as $i=>$s):
          $absS = $db->prepare("SELECT COUNT(*) FROM absensi WHERE siswa_id=? AND guru_id=? AND status='Hadir'");
          $absS->execute([$s['id'],$guruData['id']]); $hadir = $absS->fetchColumn();
          $totAbs = $db->prepare("SELECT COUNT(*) FROM absensi WHERE siswa_id=? AND guru_id=?");
          $totAbs->execute([$s['id'],$guruData['id']]); $total = $totAbs->fetchColumn();
          $nStmt = $db->prepare("SELECT nilai_harian,uts,uas FROM nilai WHERE siswa_id=? AND guru_id=?");
          $nStmt->execute([$s['id'],$guruData['id']]);
          $grades = $nStmt->fetchAll();
          $ratas = [];
          foreach ($grades as $gRow) {
              $r = hitungRata($gRow['nilai_harian']??null, $gRow['uts']??null, $gRow['uas']??null);
              if ($r !== null) $ratas[] = $r;
          }
          $rata = count($ratas) ? array_sum($ratas)/count($ratas) : null;
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= e($s['nis']) ?></td>
          <td><strong><?= e($s['nama']) ?></strong></td>
          <td><span class="badge badge-hijau"><?= e($s['nama_kelas']) ?></span></td>
          <td><?= $hadir ?>/<?= $total ?> pertemuan</td>
          <td>
            <?php if($rata !== null): ?>
              <strong class="<?= $rata>=75?'nilai-bagus':($rata>=60?'nilai-cukup':'nilai-kurang') ?>"><?= number_format($rata,1) ?></strong>
            <?php else: echo '<span style="color:var(--abu)">-</span>'; endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
function filterTbl(input) {
  const q = input.value.toLowerCase();
  document.querySelectorAll('#tblSiswa tbody tr').forEach(tr=>tr.style.display=tr.textContent.toLowerCase().includes(q)?'':'none');
}
</script>
