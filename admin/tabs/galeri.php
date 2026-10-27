<?php
// admin/tabs/galeri.php
$galeriList = $db->query("SELECT * FROM galeri ORDER BY created_at DESC")->fetchAll();
$kategoriList = ['Akademik','Olahraga','Seni & Budaya','Ekstrakurikuler','Prestasi','Kegiatan Sekolah'];
?>
<div class="dash-header">
  <div class="dash-breadcrumb">Admin <span>/ Galeri Berita</span></div>
  <h2>🖼️ Kelola Galeri & Berita</h2>
  <p>Konten yang tampil di halaman publik beranda</p>
</div>

<!-- Grid Galeri -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.2rem;margin-bottom:1.5rem;" id="gridGaleri">
  <?php foreach($galeriList as $b): ?>
  <div class="berita-kartu" style="position:relative;">
    <?php if(!empty($b['foto'])): ?>
      <img src="<?= fotoUrl($b['foto']) ?>" alt="<?= e($b['judul']) ?>" style="width:100%;height:160px;object-fit:cover;" loading="lazy">
    <?php else: ?>
      <div class="berita-foto"><?= $b['emoji'] ?></div>
    <?php endif; ?>
    <div style="position:absolute;top:8px;right:8px;display:flex;gap:4px;">
      <span class="badge <?= $b['dipublikasi'] ? 'badge-hijau' : 'badge-abu' ?>"><?= $b['dipublikasi'] ? '✓ Publik' : '○ Draft' ?></span>
    </div>
    <div class="berita-isi">
      <span class="berita-tag"><?= e($b['kategori']) ?></span>
      <h4><?= e($b['judul']) ?></h4>
      <p><?= e(mb_strimwidth($b['ringkasan']??'',0,70,'...')) ?></p>
      <div style="display:flex;gap:6px;margin-top:10px;">
        <a href="actions/toggle-galeri.php?id=<?= $b['id'] ?>" class="btn btn-outline btn-sm"><?= $b['dipublikasi'] ? '○ Sembunyikan' : '✓ Publikasikan' ?></a>
        <a href="actions/hapus-galeri.php?id=<?= $b['id'] ?>" class="btn btn-merah btn-sm" onclick="return confirm('Yakin hapus?')">🗑️</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tombol Tambah -->
<div style="text-align:center;margin-bottom:2rem;">
  <button class="btn btn-orange btn-lg" onclick="bukaModal('modalTambahBerita')">➕ Tambah Berita / Galeri</button>
</div>

<!-- MODAL TAMBAH BERITA -->
<div class="modal-bg" id="modalTambahBerita">
  <div class="modal" style="max-width:580px;">
    <div class="modal-judul">
      <h3>➕ Tambah Berita / Galeri</h3>
      <button class="modal-tutup" onclick="tutupModal('modalTambahBerita')">✕</button>
    </div>
    <form action="actions/simpan-galeri.php" method="POST" enctype="multipart/form-data">
      <div class="form-grup">
        <label>Foto Kegiatan</label>
        <div class="upload-area" id="dropZoneGaleri"
          onclick="document.getElementById('fotoGaleri').click()"
          ondragover="event.preventDefault();this.classList.add('dragover')"
          ondragleave="this.classList.remove('dragover')"
          ondrop="dropFoto(event,'fotoGaleri','previewGaleri')">
          <div class="upload-ikon">🖼️</div>
          <p>Klik atau <span>drag & drop</span> foto ke sini</p>
          <p style="font-size:12px;margin-top:4px;">JPG, PNG, WEBP (maks. 5MB)</p>
        </div>
        <input type="file" id="fotoGaleri" name="foto" accept="image/*" style="display:none;" onchange="previewFoto(this,'previewGaleri')">
        <img id="previewGaleri" class="preview-foto" src="" alt="Preview" style="max-width:100%;max-height:220px;border-radius:var(--radius);margin-top:8px;">
      </div>
      <div class="form-grid-2">
        <div class="form-grup form-full">
          <label>Judul Berita *</label>
          <input type="text" name="judul" class="form-kontrol" placeholder="Judul berita atau kegiatan" required>
        </div>
        <div class="form-grup">
          <label>Kategori</label>
          <select name="kategori" class="form-kontrol">
            <?php foreach($kategoriList as $k): ?>
            <option value="<?= e($k) ?>"><?= e($k) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grup">
          <label>Emoji (jika tanpa foto)</label>
          <input type="text" name="emoji" class="form-kontrol" value="📸" placeholder="Emoji">
        </div>
        <div class="form-grup form-full">
          <label>Ringkasan *</label>
          <textarea name="ringkasan" class="form-kontrol" rows="3" placeholder="Deskripsi singkat kegiatan..." required></textarea>
        </div>
        <div class="form-grup form-full">
          <label>Status Publikasi</label>
          <select name="dipublikasi" class="form-kontrol">
            <option value="1">✓ Publikasikan (tampil di beranda)</option>
            <option value="0">○ Simpan sebagai Draft</option>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem;">
        <button type="button" class="btn btn-abu" onclick="tutupModal('modalTambahBerita')">Batal</button>
        <button type="submit" class="btn btn-orange">🚀 Publikasikan</button>
      </div>
    </form>
  </div>
</div>

<script>
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
function dropFoto(event, inputId, previewId) {
  event.preventDefault();
  document.getElementById('dropZoneGaleri').classList.remove('dragover');
  const file = event.dataTransfer.files[0];
  if (!file) return;
  const input = document.getElementById(inputId);
  const dt = new DataTransfer();
  dt.items.add(file);
  input.files = dt.files;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById(previewId);
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>
