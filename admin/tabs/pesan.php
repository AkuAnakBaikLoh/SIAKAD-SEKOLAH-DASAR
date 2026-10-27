<?php
// admin/tabs/pesan.php
$db->query("UPDATE pesan_kontak SET sudah_dibaca = 1");
$pesanList = $db->query("SELECT * FROM pesan_kontak ORDER BY created_at DESC")->fetchAll();
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Admin <span>/ Pesan Kontak</span></div>
  <h2>📩 Pesan Masuk</h2>
</div>
<div class="tabel-wrap">
  <div class="tabel-header"><h3>Pesan Kontak (<?= count($pesanList) ?>)</h3></div>
  <div class="tabel-scroll">
  <table>
    <thead><tr><th>Nama</th><th>Email</th><th>Subjek</th><th>Pesan</th><th>Tanggal</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php if(empty($pesanList)): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--abu);padding:2rem;">Belum ada pesan masuk.</td></tr>
      <?php endif; ?>
      <?php foreach($pesanList as $p): ?>
      <tr>
        <td><strong><?= e($p['nama']) ?></strong></td>
        <td style="font-size:13px;"><?= e($p['email']) ?></td>
        <td><?= e($p['subjek']) ?></td>
        <td style="max-width:200px;font-size:13px;color:var(--abu);"><?= e(mb_strimwidth($p['isi_pesan'],0,60,'...')) ?></td>
        <td style="font-size:12px;color:var(--abu);"><?= formatTanggal($p['created_at']) ?></td>
        <td>
          <button class="btn btn-outline btn-sm" onclick='lihatPesan(<?= json_encode($p) ?>)'>👁️ Lihat</button>
          <a href="actions/hapus-pesan.php?id=<?= $p['id'] ?>" class="btn btn-merah btn-sm" onclick="return confirm('Hapus pesan?')">🗑️</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="modal-bg" id="modalPesan">
  <div class="modal">
    <div class="modal-judul"><h3>📩 Detail Pesan</h3><button class="modal-tutup" onclick="tutupModal('modalPesan')">✕</button></div>
    <p><strong>Dari:</strong> <span id="detailNama"></span></p>
    <p style="margin-top:4px;"><strong>Email:</strong> <span id="detailEmail"></span></p>
    <p style="margin-top:4px;"><strong>Subjek:</strong> <span id="detailSubjek"></span></p>
    <hr style="margin:1rem 0;border:none;border-top:1px solid var(--abu-muda);">
    <p id="detailPesan" style="color:var(--abu-gelap);line-height:1.7;"></p>
  </div>
</div>
<script>
function lihatPesan(p) {
  document.getElementById('detailNama').textContent   = p.nama;
  document.getElementById('detailEmail').textContent  = p.email;
  document.getElementById('detailSubjek').textContent = p.subjek;
  document.getElementById('detailPesan').textContent  = p.isi_pesan;
  bukaModal('modalPesan');
}
</script>
