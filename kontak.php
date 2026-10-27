<?php
// kontak.php
require_once __DIR__ . '/config/database.php';
if(session_status()===PHP_SESSION_NONE) session_start();
$db = getDB();
$sukses = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nama    = trim($_POST['nama']??'');
    $email   = trim($_POST['email']??'');
    $subjek  = trim($_POST['subjek']??'');
    $pesan   = trim($_POST['pesan']??'');
    if($nama && $pesan){
        $db->prepare("INSERT INTO pesan_kontak (nama,email,subjek,isi_pesan) VALUES (?,?,?,?)")->execute([$nama,$email,$subjek,$pesan]);
        $sukses = 'Pesan Anda berhasil dikirim! Kami akan segera menghubungi Anda.';
    }
}
$halamanAktif = 'kontak';
$pageTitle    = 'Kontak';
include __DIR__ . '/includes/header.php';
?>
<section class="seksi">
  <div class="container">
    <div class="seksi-judul">
      <div class="label-atas">📞 Hubungi Kami</div>
      <h2>Kontak Sekolah</h2>
      <p>Kami siap membantu pertanyaan dan kebutuhan Anda</p>
      <div class="garis"></div>
    </div>
    <?php if($sukses): ?>
    <div class="flash-msg flash-sukses" style="max-width:700px;margin:0 auto 1.5rem;">✅ <?= e($sukses) ?></div>
    <?php endif; ?>
    <div class="kontak-grid">
      <div>
        <div class="kontak-info-list">
          <div class="kontak-item"><div class="kontak-ikon">📍</div><div><h4>Alamat</h4><p><?= e($set['alamat']??'') ?></p></div></div>
          <div class="kontak-item"><div class="kontak-ikon">📞</div><div><h4>Telepon</h4><p><?= e($set['telepon']??'') ?></p></div></div>
          <div class="kontak-item"><div class="kontak-ikon">📧</div><div><h4>Email</h4><p><?= e($set['email']??'') ?></p></div></div>
          <div class="kontak-item"><div class="kontak-ikon">🕐</div><div><h4>Jam Operasional</h4><p>Senin – Jumat: 07.00 – 15.00 WIB</p></div></div>
        </div>
      </div>
      <div class="kartu">
        <h3 class="kartu-judul"><span class="kartu-ikon">💬</span> Kirim Pesan</h3>
        <form method="POST">
          <div class="form-grup"><label>Nama Lengkap *</label><input type="text" name="nama" class="form-kontrol" required placeholder="Nama Anda"></div>
          <div class="form-grup"><label>Email</label><input type="email" name="email" class="form-kontrol" placeholder="Email Anda"></div>
          <div class="form-grup"><label>Subjek</label><input type="text" name="subjek" class="form-kontrol" placeholder="Subjek pesan"></div>
          <div class="form-grup"><label>Pesan *</label><textarea name="pesan" class="form-kontrol" rows="4" required placeholder="Tulis pesan Anda..."></textarea></div>
          <button type="submit" class="btn btn-orange" style="width:100%;justify-content:center;">📨 Kirim Pesan</button>
        </form>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
