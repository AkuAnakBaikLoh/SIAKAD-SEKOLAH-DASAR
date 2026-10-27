<?php
// admin/tabs/siswa.php
$kelasList   = $db->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas")->fetchAll();
$kelasFilter = (int)($_GET['kelas'] ?? 0);
if ($kelasFilter) {
    $s = $db->prepare("SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON k.id=s.kelas_id WHERE s.kelas_id=? AND s.aktif=1 ORDER BY s.nama");
    $s->execute([$kelasFilter]); $siswaList = $s->fetchAll();
} else {
    $siswaList = $db->query("SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON k.id=s.kelas_id WHERE s.aktif=1 ORDER BY k.nama_kelas, s.nama")->fetchAll();
}
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Admin <span>/ Data Siswa</span></div>
  <h2>👥 Data Siswa</h2>
</div>
<div class="tabel-wrap">
  <div class="tabel-header">
    <h3>Daftar Siswa (<?= count($siswaList) ?>)</h3>
    <div class="tabel-aksi">
      <select class="select-filter" onchange="window.location='?tab=siswa&kelas='+this.value">
        <option value="">Semua Kelas</option>
        <?php foreach($kelasList as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $kelasFilter==$k['id']?'selected':'' ?>><?= e($k['nama_kelas']) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="input-cari" placeholder="🔍 Cari siswa..." oninput="filterTabel(this,'tblSiswa')">
      <button class="btn btn-orange" onclick="bukaModal('modalTambahSiswa')">➕ Tambah Siswa</button>
    </div>
  </div>
  <div class="tabel-scroll">
    <table id="tblSiswa">
      <thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>JK</th><th>Username</th><th>Status Akun</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($siswaList as $i=>$s): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= e($s['nis']) ?></td>
          <td><strong><?= e($s['nama']) ?></strong></td>
          <td><span class="badge badge-hijau"><?= e($s['nama_kelas']) ?></span></td>
          <td><?= $s['jenis_kelamin']==='L' ? '👦 L' : '👧 P' ?></td>
          <td>
            <?php if(!empty($s['username'])): ?>
              <span style="font-family:monospace;font-size:.82rem;background:var(--abu-muda);padding:2px 8px;border-radius:4px;"><?= e($s['username']) ?></span>
            <?php else: ?>
              <span style="color:var(--abu);font-size:.8rem;font-style:italic;">Belum diatur</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if(!empty($s['password'])): ?>
              <span class="badge badge-hijau">✅ Aktif</span>
            <?php else: ?>
              <span class="badge badge-merah">❌ Belum</span>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap;">
            <button class="btn btn-outline btn-sm" onclick='editSiswa(<?= json_encode($s) ?>)'>✏️ Edit</button>
            <a href="actions/hapus-siswa.php?id=<?= $s['id'] ?>" class="btn btn-merah btn-sm" onclick="return confirm('Yakin hapus siswa ini?')">🗑️</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL TAMBAH SISWA -->
<div class="modal-bg" id="modalTambahSiswa">
  <div class="modal">
    <div class="modal-judul"><h3>➕ Tambah Siswa</h3><button class="modal-tutup" onclick="tutupModal('modalTambahSiswa')">✕</button></div>
    <form action="actions/simpan-siswa.php" method="POST">
      <div class="form-grid-2">
        <div class="form-grup"><label>NIS *</label><input type="text" name="nis" class="form-kontrol" required placeholder="Nomor Induk Siswa"></div>
        <div class="form-grup form-full"><label>Nama Lengkap *</label><input type="text" name="nama" class="form-kontrol" required></div>
        <div class="form-grup">
          <label>Kelas *</label>
          <select name="kelas_id" class="form-kontrol" required>
            <option value="">— Pilih Kelas —</option>
            <?php foreach($kelasList as $k): ?><option value="<?= $k['id'] ?>"><?= e($k['nama_kelas']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-grup">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin" class="form-kontrol"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select>
        </div>
        <div class="form-grup">
          <label>Username Login <small style="color:var(--abu)">(opsional)</small></label>
          <input type="text" name="username" class="form-kontrol" placeholder="cth: adi.nugroho" autocomplete="off">
        </div>
        <div class="form-grup">
          <label>Password <small style="color:var(--abu)">(opsional)</small></label>
          <input type="password" name="password" class="form-kontrol" placeholder="Min. 6 karakter" autocomplete="new-password">
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem;">
        <button type="button" class="btn btn-abu" onclick="tutupModal('modalTambahSiswa')">Batal</button>
        <button type="submit" class="btn btn-hijau">💾 Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT SISWA (Data diri + Akun dalam 1 modal — seperti guru) -->
<div class="modal-bg" id="modalEditSiswa">
  <div class="modal" style="max-width:600px;">
    <div class="modal-judul">
      <h3>✏️ Edit Data Siswa</h3>
      <button class="modal-tutup" onclick="tutupModal('modalEditSiswa')">✕</button>
    </div>
    <form action="actions/edit-siswa.php" method="POST">
      <input type="hidden" name="siswa_id" id="editSiswaId">
      <div class="form-grid-2">
        <div class="form-grup"><label>NIS *</label><input type="text" name="nis" id="editSiswaNis" class="form-kontrol" required></div>
        <div class="form-grup form-full"><label>Nama Lengkap *</label><input type="text" name="nama" id="editSiswaNama" class="form-kontrol" required></div>
        <div class="form-grup">
          <label>Kelas *</label>
          <select name="kelas_id" id="editSiswaKelas" class="form-kontrol" required>
            <option value="">— Pilih Kelas —</option>
            <?php foreach($kelasList as $k): ?><option value="<?= $k['id'] ?>"><?= e($k['nama_kelas']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-grup">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin" id="editSiswaJK" class="form-kontrol">
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
        <div class="form-grup form-full">
          <label>Username Login</label>
          <input type="text" name="username" id="editSiswaUsername" class="form-kontrol" placeholder="Kosongkan jika belum perlu" autocomplete="off">
          <small style="color:var(--abu);">Username untuk login ke Portal Siswa</small>
        </div>
        <div class="form-grup">
          <label>Password Baru</label>
          <input type="password" name="password" class="form-kontrol" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password">
        </div>
        <div class="form-grup">
          <label>Konfirmasi Password</label>
          <input type="password" name="password_confirm" class="form-kontrol" placeholder="Ulangi password baru" autocomplete="new-password">
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem;">
        <button type="button" class="btn btn-abu" onclick="tutupModal('modalEditSiswa')">Batal</button>
        <button type="submit" class="btn btn-hijau">💾 Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function filterTabel(input, id) {
  const q = input.value.toLowerCase();
  document.querySelectorAll('#'+id+' tbody tr').forEach(tr => tr.style.display = tr.textContent.toLowerCase().includes(q)?'':'none');
}
function editSiswa(s) {
  document.getElementById('editSiswaId').value       = s.id;
  document.getElementById('editSiswaNis').value      = s.nis;
  document.getElementById('editSiswaNama').value     = s.nama;
  document.getElementById('editSiswaKelas').value    = s.kelas_id;
  document.getElementById('editSiswaJK').value       = s.jenis_kelamin;
  document.getElementById('editSiswaUsername').value = s.username || '';
  bukaModal('modalEditSiswa');
}
</script>
