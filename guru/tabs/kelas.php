<?php
// guru/tabs/kelas.php
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Guru <span>/ Kelas Saya</span></div>
  <h2>🏫 Kelas yang Saya Ampu</h2>
  <p>Mata Pelajaran: <strong><?= e($guruData['mapel']) ?></strong></p>
</div>

<?php if (empty($kelasDiajar)): ?>
<div style="text-align:center;padding:3rem 1.5rem;background:var(--kartu);border-radius:var(--radius);border:2px dashed var(--border);">
  <div style="font-size:4rem;margin-bottom:1rem;">🏫</div>
  <h3 style="color:var(--abu);margin-bottom:0.5rem;">Belum Ada Kelas</h3>
  <p style="color:var(--abu);font-size:14px;max-width:400px;margin:0 auto;">
    Anda belum ditugaskan ke kelas manapun. Silakan hubungi Admin untuk menugaskan Anda ke kelas yang sesuai melalui menu <strong>Manajemen Kelas → Atur Guru Pengajar</strong>.
  </p>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.2rem;">
  <?php foreach($kelasDiajar as $k): ?>
  <div class="kartu" style="text-align:center;transition:transform .2s,box-shadow .2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 10px 28px rgba(26,92,58,.13)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div style="font-size:3rem;margin-bottom:0.8rem;">🏫</div>
    <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;color:var(--hijau);"><?= e($k['nama_kelas']) ?></h3>
    <p style="color:var(--abu);font-size:14px;margin-bottom:0.3rem;"><?= $k['jml_siswa'] ?> Siswa Aktif</p>
    <p style="font-size:13px;color:var(--orange);font-weight:600;margin-bottom:1rem;"><?= e($guruData['mapel']) ?></p>
    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
      <a href="?tab=absensi&kelas_id=<?= $k['id'] ?>" class="btn btn-outline btn-sm">✅ Absensi</a>
      <a href="?tab=nilai&kelas_id=<?= $k['id'] ?>" class="btn btn-orange btn-sm">📝 Nilai</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
