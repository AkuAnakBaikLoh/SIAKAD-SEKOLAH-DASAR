<?php // admin/tabs/pengaturan.php ?>
<div class="dash-header">
  <div class="dash-breadcrumb">Admin <span>/ Pengaturan Sekolah</span></div>
  <h2>⚙️ Pengaturan Sekolah</h2>
</div>
<div class="kartu" style="max-width:700px;">
  <form action="actions/simpan-pengaturan.php" method="POST" enctype="multipart/form-data">
    <h3 style="color:var(--hijau);margin-bottom:1.2rem;font-size:1rem;">Foto Background Beranda</h3>
    <?php if(!empty($set['foto_sekolah'])): ?>
    <div style="margin-bottom:1rem;">
      <img src="<?= fotoUrl($set['foto_sekolah']) ?>" style="max-width:100%;max-height:200px;border-radius:var(--radius);object-fit:cover;" alt="Foto Sekolah">
      <p style="font-size:12px;color:var(--abu);margin-top:4px;">Foto background beranda saat ini</p>
    </div>
    <?php endif; ?>
    <div class="form-grup">
      <div class="upload-area" onclick="document.getElementById('fotoSekolah').click()"
        ondragover="event.preventDefault();this.classList.add('dragover')"
        ondragleave="this.classList.remove('dragover')"
        ondrop="dropBg(event)">
        <div class="upload-ikon">🏫</div>
        <p>Klik atau drag foto gedung/kegiatan sekolah</p>
        <p style="font-size:12px;color:var(--abu);margin-top:4px;">JPG, PNG, WEBP — Rekomendasi: 1920x1080px</p>
      </div>
      <input type="file" id="fotoSekolah" name="foto_sekolah" accept="image/*" style="display:none;" onchange="previewBg(this)">
      <img id="previewBg" style="display:none;max-width:100%;max-height:200px;border-radius:var(--radius);margin-top:8px;object-fit:cover;" src="" alt="Preview">
    </div>

    <h3 style="color:var(--hijau);margin:1.5rem 0 1rem;font-size:1rem;">📋 Informasi Sekolah</h3>
    <div class="form-grid-2">
      <div class="form-grup form-full">
        <label>Nama Sekolah</label>
        <input type="text" name="nama_sekolah" class="form-kontrol" value="<?= e($set['nama_sekolah']??'') ?>">
      </div>
      <div class="form-grup"><label>NPSN</label><input type="text" name="npsn" class="form-kontrol" value="<?= e($set['npsn']??'') ?>"></div>
      <div class="form-grup"><label>Akreditasi</label>
        <select name="akreditasi" class="form-kontrol">
          <?php foreach(['A','B','C'] as $a): ?>
          <option value="<?= $a ?>" <?= ($set['akreditasi']??'A')===$a?'selected':'' ?>><?= $a ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-grup"><label>Kepala Sekolah</label><input type="text" name="kepala_sekolah" class="form-kontrol" value="<?= e($set['kepala_sekolah']??'') ?>"></div>
      <div class="form-grup"><label>Tahun Berdiri</label><input type="number" name="tahun_berdiri" class="form-kontrol" value="<?= e($set['tahun_berdiri']??1984) ?>"></div>
      <div class="form-grup"><label>Telepon</label><input type="text" name="telepon" class="form-kontrol" value="<?= e($set['telepon']??'') ?>"></div>
      <div class="form-grup"><label>Email</label><input type="email" name="email" class="form-kontrol" value="<?= e($set['email']??'') ?>"></div>
      <div class="form-grup form-full"><label>Alamat</label><textarea name="alamat" class="form-kontrol" rows="2"><?= e($set['alamat']??'') ?></textarea></div>
      <div class="form-grup form-full"><label>Visi</label><textarea name="visi" class="form-kontrol" rows="2"><?= e($set['visi']??'') ?></textarea></div>
      <div class="form-grup form-full">
        <label>Misi (satu baris per poin)</label>
        <textarea name="misi" class="form-kontrol" rows="5"><?= e($set['misi']??'') ?></textarea>
      </div>
    </div>
    <button type="submit" class="btn btn-hijau">💾 Simpan Pengaturan</button>
  </form>
</div>
<script>
function previewBg(input) {
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('previewBg');
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(input.files[0]);
}
function dropBg(event) {
  event.preventDefault();
  const file = event.dataTransfer.files[0];
  const input = document.getElementById('fotoSekolah');
  const dt = new DataTransfer();
  dt.items.add(file);
  input.files = dt.files;
  previewBg(input);
}
</script>
