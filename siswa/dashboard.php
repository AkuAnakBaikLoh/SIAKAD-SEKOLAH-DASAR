<?php
// siswa/dashboard.php — Portal Siswa
require_once __DIR__ . '/../config/database.php';
$siswaData = cekSiswa();
$db  = getDB();
$tab = $_GET['tab'] ?? 'beranda';

// ============================================================
// PROSES POST — harus sebelum HTML output agar header() bisa jalan
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ubah_password') {
    $siswaId      = $siswaData['id'];
    $passwordLama = $_POST['password_lama'] ?? '';
    $passwordBaru = $_POST['password_baru'] ?? '';
    $konfirmasi   = $_POST['konfirmasi']    ?? '';

    if (empty($passwordLama) || empty($passwordBaru) || empty($konfirmasi)) {
        setFlash('gagal', 'Semua kolom wajib diisi.');
    } elseif (strlen($passwordBaru) < 6) {
        setFlash('gagal', 'Password baru minimal 6 karakter.');
    } elseif ($passwordBaru !== $konfirmasi) {
        setFlash('gagal', 'Konfirmasi password tidak cocok.');
    } elseif ($passwordBaru === $passwordLama) {
        setFlash('gagal', 'Password baru tidak boleh sama dengan password lama.');
    } else {
        $stmtCek = $db->prepare("SELECT * FROM siswa WHERE id = ? AND aktif = 1 LIMIT 1");
        $stmtCek->execute([$siswaId]);
        $siswaTerkini = $stmtCek->fetch();

        if (!$siswaTerkini) {
            setFlash('gagal', 'Data siswa tidak ditemukan.');
        } elseif (!password_verify($passwordLama, $siswaTerkini['password'])) {
            setFlash('gagal', 'Password lama tidak sesuai.');
        } else {
            $hashBaru = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $stmtUp   = $db->prepare("UPDATE siswa SET password = ? WHERE id = ?");
            $stmtUp->execute([$hashBaru, $siswaId]);
            $_SESSION['siswa_data']['password'] = $hashBaru;
            setFlash('sukses', 'Password berhasil diubah! Gunakan password baru Anda saat login berikutnya.');
        }
    }
    redirect(BASE_URL . '/siswa/dashboard.php?tab=ubah-password');
}
// ============================================================

$flash = getFlash();

$set       = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();
$pageTitle = 'Portal Siswa — ' . $siswaData['nama'];

// Ambil data kelas siswa
$kelasData = $db->prepare("SELECT k.* FROM kelas k WHERE k.id = ?");
$kelasData->execute([$siswaData['kelas_id']]);
$kelas = $kelasData->fetch();

$inisial = strtoupper(substr($siswaData['nama'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css?v=<?= filemtime(__DIR__ . "/../assets/css/main.css") ?>">
  <style>
    /* ===== SISWA PORTAL EXTRAS ===== */
    .siswa-hero {
      background: linear-gradient(135deg, #1a3a5c 0%, #1a5c3a 60%, #0d7a4e 100%);
      border-radius: var(--radius);
      padding: 2rem 2.5rem;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 2rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    .siswa-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .siswa-hero-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      font-weight: 800;
      flex-shrink: 0;
      border: 3px solid rgba(255,255,255,0.4);
      backdrop-filter: blur(6px);
    }
    .siswa-hero-info h2 { font-size: 1.5rem; margin-bottom: .25rem; }
    .siswa-hero-info p { opacity: .85; font-size: .9rem; }
    .siswa-hero-badges { display: flex; gap: .5rem; margin-top: .75rem; flex-wrap: wrap; }
    .siswa-badge {
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.3);
      padding: 4px 12px;
      border-radius: 999px;
      font-size: .78rem;
      backdrop-filter: blur(4px);
    }
    .nilai-card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .nilai-card {
      background: var(--putih);
      border: 1px solid var(--abu-muda);
      border-radius: var(--radius);
      padding: 1.25rem 1.5rem;
      transition: box-shadow .2s, transform .2s;
    }
    .nilai-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); transform: translateY(-2px); }
    .nilai-card-mapel {
      font-size: .8rem;
      font-weight: 700;
      color: var(--hijau);
      text-transform: uppercase;
      letter-spacing: .05em;
      margin-bottom: .5rem;
    }
    .nilai-card-guru { font-size: .78rem; color: var(--abu); margin-bottom: 1rem; }
    .nilai-bar-row {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin-bottom: .4rem;
    }
    .nilai-bar-label { width: 80px; font-size: .78rem; color: var(--abu-gelap); }
    .nilai-bar-wrap {
      flex: 1;
      height: 8px;
      background: var(--abu-muda);
      border-radius: 4px;
      overflow: hidden;
    }
    .nilai-bar-fill {
      height: 100%;
      border-radius: 4px;
      transition: width .6s ease;
    }
    .nilai-bar-val { width: 36px; font-size: .82rem; font-weight: 600; text-align: right; }
    .nilai-card-rata {
      margin-top: .75rem;
      padding-top: .75rem;
      border-top: 1px solid var(--abu-muda);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .rata-angka {
      font-size: 1.6rem;
      font-weight: 800;
      line-height: 1;
    }
    .absensi-summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .abs-stat {
      border-radius: var(--radius);
      padding: 1.25rem;
      text-align: center;
      transition: transform .2s;
    }
    .abs-stat:hover { transform: translateY(-2px); }
    .abs-stat .abs-angka { font-size: 2rem; font-weight: 800; }
    .abs-stat .abs-label { font-size: .8rem; margin-top: .25rem; opacity: .85; }
    .abs-hadir { background: linear-gradient(135deg, #1a5c3a, #0d7a4e); color: #fff; }
    .abs-sakit { background: linear-gradient(135deg, #d4a017, #f0b429); color: #fff; }
    .abs-izin  { background: linear-gradient(135deg, #1a5276, #2874a6); color: #fff; }
    .abs-alpa  { background: linear-gradient(135deg, #c0392b, #e74c3c); color: #fff; }
    .absensi-table-wrap .tabel-scroll { max-height: 500px; overflow-y: auto; }
    .filter-bar {
      background: var(--putih);
      border: 1px solid var(--abu-muda);
      border-radius: var(--radius);
      padding: 1.25rem 1.5rem;
      margin-bottom: 1.5rem;
      display: flex;
      gap: 1rem;
      align-items: flex-end;
      flex-wrap: wrap;
    }
    .filter-bar .form-grup { margin: 0; flex: 1; min-width: 150px; }
    .tab-nav {
      display: flex;
      gap: .5rem;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid var(--abu-muda);
      padding-bottom: 0;
    }
    .tab-nav a {
      padding: .6rem 1.2rem;
      font-size: .9rem;
      font-weight: 600;
      color: var(--abu);
      text-decoration: none;
      border-radius: var(--radius-sm) var(--radius-sm) 0 0;
      border: 2px solid transparent;
      border-bottom: none;
      margin-bottom: -2px;
      transition: all .2s;
    }
    .tab-nav a.aktif {
      color: var(--hijau);
      border-color: var(--abu-muda);
      border-bottom-color: var(--putih);
      background: var(--putih);
    }
    .tab-nav a:hover:not(.aktif) { color: var(--hijau); background: var(--hijau-light); }
    .welcome-section { 
      text-align: center; 
      padding: 3rem 2rem;
      background: var(--putih);
      border-radius: var(--radius);
      border: 1px solid var(--abu-muda);
      margin-bottom: 1.5rem;
    }
    .welcome-section .welcome-emoji { font-size: 3rem; margin-bottom: 1rem; }
    .welcome-section h3 { font-size: 1.3rem; margin-bottom: .5rem; }
    .quick-stats {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    @media (max-width: 600px) {
      .absensi-summary-grid { grid-template-columns: repeat(2,1fr); }
      .siswa-hero { flex-direction: column; text-align: center; gap: 1rem; }
      .siswa-hero-badges { justify-content: center; }
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">
    <div class="nav-logo">
      <img src="<?= BASE_URL ?>/assets/logo.jpeg" alt="Logo Sekolah" class="nav-logo-ikon" style="object-fit: cover; background: transparent;">
      <div class="nav-logo-teks">
        <strong><?= e($set['nama_sekolah'] ?? '') ?></strong>
        <span>Portal Siswa</span>
      </div>
    </div>
    <button type="button" class="nav-toggle" aria-label="Buka menu" onclick="document.getElementById('navMenu').classList.toggle('terbuka')">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-menu" id="navMenu">
      <a href="<?= BASE_URL ?>/index.php">🌐 Beranda</a>
      <a href="<?= BASE_URL ?>/logout.php" style="background:var(--merah);color:#fff;padding:7px 16px;border-radius:var(--radius-sm);">🚪 Keluar</a>
    </div>
  </div>
</nav>

<div class="notif-toast" id="notifToast"></div>

<div class="dash-layout">
  <!-- SIDEBAR -->
  <aside class="dash-sidebar">
    <div class="sidebar-user">
      <div class="sidebar-avatar-inisial" style="background: linear-gradient(135deg, #1a3a5c, #1a5c3a);">
        <?= $inisial ?>
      </div>
      <div class="sidebar-nama"><?= e(explode(' ', $siswaData['nama'])[0]) ?></div>
      <div class="sidebar-role">Siswa <?= e($kelas['nama_kelas'] ?? '') ?></div>
    </div>
    <ul class="sidebar-menu">
      <li class="menu-judul">PORTAL SISWA</li>
      <li><a href="?tab=beranda" class="<?= $tab==='beranda'?'aktif':'' ?>"><span class="menu-ikon">🏠</span> Beranda</a></li>
      <li class="menu-judul">AKADEMIK</li>
      <li><a href="?tab=nilai" class="<?= $tab==='nilai'?'aktif':'' ?>"><span class="menu-ikon">📊</span> Nilai Saya</a></li>
      <li><a href="?tab=absensi" class="<?= $tab==='absensi'?'aktif':'' ?>"><span class="menu-ikon">📅</span> Absensi Saya</a></li>
      <li class="menu-judul">AKUN</li>
      <li><a href="?tab=ubah-password" class="<?= $tab==='ubah-password'?'aktif':'' ?>"><span class="menu-ikon">🔐</span> Ubah Password</a></li>
    </ul>
  </aside>

  <!-- KONTEN -->
  <main class="dash-konten">
    <?php if ($flash): ?>
    <div class="flash-msg flash-<?= $flash['type'] ?>">
      <?= $flash['type']==='sukses'?'✅':'❌' ?> <?= e($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <?php
    switch($tab) {
        case 'nilai':          include __DIR__.'/tabs/nilai.php'; break;
        case 'absensi':        include __DIR__.'/tabs/absensi.php'; break;
        case 'ubah-password':  include __DIR__.'/tabs/ubah-password.php'; break;
        default:               include __DIR__.'/tabs/beranda.php'; break;
    }
    ?>
  </main>
</div>

<script>
function tampilNotif(pesan, tipe='sukses') {
  const el = document.getElementById('notifToast');
  el.innerHTML = (tipe==='sukses'?'✅':'❌') + ' ' + pesan;
  el.className = 'notif-toast notif-'+tipe+' muncul';
  setTimeout(()=>el.classList.remove('muncul'),4000);
}
</script>
</body>
</html>
