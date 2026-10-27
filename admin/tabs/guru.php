<?php
// admin/tabs/guru.php
$guruList = $db->query("SELECT * FROM guru ORDER BY nama")->fetchAll();
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Admin <span>/ Data Guru</span></div>
  <h2>👩‍🏫 Kelola Data Guru</h2>
  <p>Manajemen akun dan data guru pengajar</p>
</div>

<div class="tabel-wrap">
  <div class="tabel-header">
    <h3>Daftar Guru (<?= count($guruList) ?>)</h3>
    <div class="tabel-aksi">
      <input class="input-cari" placeholder="🔍 Cari guru..." oninput="filterTabel(this,'tabelGuru')">
      <button class="btn btn-orange" onclick="bukaTambahGuru()">➕ Tambah Guru</button>
    </div>
  </div>
  <div class="tabel-scroll">
    <table id="tabelGuru">
      <thead>
        <tr>
          <th>Foto</th><th>Nama</th><th></th>
          <th>Username</th><th>Status</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($guruList as $g):
          $inisial = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $g['nama']), 0, 2))));
        ?>
        <tr>
          <td>
            <?php if(!empty($g['foto'])): ?>
              <img src="<?= fotoUrl($g['foto']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--orange-light);" alt="">
            <?php else: ?>
              <div style="width:40px;height:40px;border-radius:50%;background:<?= e($g['warna']??'#1a5c3a') ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;"><?= $inisial ?></div>
            <?php endif; ?>
          </td>
          <td><strong><?= e($g['nama']) ?></strong></td>

          <td><?= e($g['mapel']) ?></td>
          <td><code style="background:var(--abu-muda);padding:2px 6px;border-radius:4px;font-size:12px;"><?= e($g['username']) ?></code></td>
          <td><span class="badge <?= $g['aktif'] ? 'badge-hijau' : 'badge-merah' ?>"><?= $g['aktif'] ? '✓ Aktif' : '✗ Nonaktif' ?></span></td>
          <td style="white-space:nowrap;">
            <button class="btn btn-outline btn-sm" onclick='editGuru(<?= json_encode($g) ?>)'>✏️ Edit</button>
            <a href="actions/hapus-guru.php?id=<?= $g['id'] ?>" class="btn btn-merah btn-sm" onclick="return confirm('Yakin hapus guru ini?')">🗑️</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL TAMBAH GURU -->
<div class="modal-bg" id="modalTambahGuru">
  <div class="modal" style="max-width:600px;">
    <div class="modal-judul">
      <h3>➕ Tambah Guru</h3>
      <button class="modal-tutup" onclick="tutupModal('modalTambahGuru')">✕</button>
    </div>
    <form action="actions/simpan-guru.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-grid-2">
        <div class="form-grup form-full">
          <label>Foto Profil</label>
          <div class="upload-area" onclick="document.getElementById('fotoGuru').click()">
            <div class="upload-ikon">📷</div>
            <p>Klik untuk pilih foto <span>atau drag & drop</span></p>
            <p style="font-size:12px;margin-top:4px;">JPG, PNG, WEBP (maks. 5MB)</p>
          </div>
          <input type="file" id="fotoGuru" name="foto" accept="image/*" style="display:none;" onchange="previewFoto(this,'previewTambahGuru')">
          <img id="previewTambahGuru" class="preview-foto" src="" alt="Preview">
        </div>
        <div class="form-grup">
          <label>Nama Lengkap *</label>
          <input type="text" name="nama" class="form-kontrol" placeholder="Nama lengkap dengan gelar" required>
        </div>

        <div class="form-grup">
          <label>Username Login *</label>
          <input type="text" name="username" class="form-kontrol" placeholder="Username unik" required>
        </div>
        <div class="form-grup">
          <label>Password *</label>
          <input type="password" name="password" class="form-kontrol" placeholder="Minimal 6 karakter" required minlength="6">
        </div>
        <div class="form-grup form-full">
          <label>Status</label>
          <select name="aktif" class="form-kontrol">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem;">
        <button type="button" class="btn btn-abu" onclick="tutupModal('modalTambahGuru')">Batal</button>
        <button type="submit" class="btn btn-hijau">💾 Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT GURU -->
<div class="modal-bg" id="modalEditGuru">
  <div class="modal" style="max-width:600px;">
    <div class="modal-judul">
      <h3>✏️ Edit Data Guru</h3>
      <button class="modal-tutup" onclick="tutupModal('modalEditGuru')">✕</button>
    </div>
    <form action="actions/simpan-guru.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="edit">
      <input type="hidden" name="id" id="editGuruId">
      <div class="form-grid-2">
        <div class="form-grup form-full">
          <label>Foto Profil</label>
          <div id="editFotoPreviewWrap" style="margin-bottom:8px;"></div>
          <div class="upload-area" onclick="document.getElementById('editFotoGuru').click()">
            <div class="upload-ikon">📷</div>
            <p>Klik untuk ganti foto <span>atau drag & drop</span></p>
          </div>
          <input type="file" id="editFotoGuru" name="foto" accept="image/*" style="display:none;" onchange="previewFoto(this,'previewEditGuru')">
          <img id="previewEditGuru" class="preview-foto" src="" alt="Preview">
        </div>
        <div class="form-grup"><label>Nama Lengkap *</label><input type="text" name="nama" id="editGuruNama" class="form-kontrol" required></div>

        <div class="form-grup"><label>Warna Avatar</label><input type="color" name="warna" id="editGuruWarna" class="form-kontrol"></div>
        <div class="form-grup"><label>Username *</label><input type="text" name="username" id="editGuruUsername" class="form-kontrol" required></div>
        <div class="form-grup"><label>Password Baru</label><input type="password" name="password" class="form-kontrol" placeholder="Kosongkan jika tidak diganti"></div>
        <div class="form-grup form-full">
          <label>Status</label>
          <select name="aktif" id="editGuruAktif" class="form-kontrol">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem;">
        <button type="button" class="btn btn-abu" onclick="tutupModal('modalEditGuru')">Batal</button>
        <button type="submit" class="btn btn-hijau">💾 Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function filterTabel(input, tabelId) {
  const q = input.value.toLowerCase();
  document.querySelectorAll('#' + tabelId + ' tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function previewFoto(input, previewId) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById(previewId);
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(file);
}
function escHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function bukaTambahGuru() {
  bukaModal('modalTambahGuru');
}
function editGuru(g) {
  document.getElementById('editGuruId').value      = g.id;
  document.getElementById('editGuruNama').value    = g.nama;

  document.getElementById('editGuruWarna').value   = g.warna || '#1a5c3a';
  document.getElementById('editGuruUsername').value = g.username;
  document.getElementById('editGuruAktif').value   = g.aktif;
  const wrap = document.getElementById('editFotoPreviewWrap');
  if (g.foto) {
    wrap.innerHTML = `<img src="<?= BASE_URL ?>/${g.foto}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--orange);" alt="Foto saat ini">
    <p style="font-size:12px;color:var(--abu);margin-top:4px;">Foto saat ini</p>`;
  } else {
    wrap.innerHTML = '';
  }
  document.getElementById('previewEditGuru').style.display = 'none';
  bukaModal('modalEditGuru');
}
</script>
