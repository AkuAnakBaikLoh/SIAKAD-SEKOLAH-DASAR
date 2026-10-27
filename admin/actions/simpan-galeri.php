<?php
// admin/actions/simpan-galeri.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();

$judul       = trim($_POST['judul'] ?? '');
$kategori    = trim($_POST['kategori'] ?? 'Kegiatan Sekolah');
$ringkasan   = trim($_POST['ringkasan'] ?? '');
$emoji       = trim($_POST['emoji'] ?? '📸');
$dipublikasi = (int)($_POST['dipublikasi'] ?? 1);

if (!$judul || !$ringkasan) {
    setFlash('error', 'Judul dan ringkasan wajib diisi!');
    redirect(BASE_URL . '/admin/dashboard.php?tab=galeri');
}

$fotoPath = null;
if (!empty($_FILES['foto']['name'])) {
    $hasil = uploadGambar($_FILES['foto'], 'galeri', 'galeri');
    if (!$hasil['sukses']) {
        setFlash('error', 'Upload foto gagal: ' . $hasil['pesan']);
        redirect(BASE_URL . '/admin/dashboard.php?tab=galeri');
    }
    $fotoPath = $hasil['path'];
}

$stmt = $db->prepare("INSERT INTO galeri (judul, kategori, ringkasan, foto, emoji, dipublikasi, created_by) VALUES (?,?,?,?,?,?,?)");
$stmt->execute([$judul, $kategori, $ringkasan, $fotoPath, $emoji, $dipublikasi, $_SESSION['admin_id']]);
setFlash('sukses', 'Berita/galeri berhasil ditambahkan!');
redirect(BASE_URL . '/admin/dashboard.php?tab=galeri');
