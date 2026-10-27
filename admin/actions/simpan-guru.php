<?php
// admin/actions/simpan-guru.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();

$aksi     = $_POST['aksi'] ?? '';
$nama     = trim($_POST['nama'] ?? '');

$mapelRaw = $_POST['mapel'] ?? '';
if (is_array($mapelRaw)) {
    $mapelList = array_filter(array_map('trim', $mapelRaw));
    $mapel = implode(', ', $mapelList);
} else {
    $mapel = trim($mapelRaw);
}
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$warna    = trim($_POST['warna'] ?? '#1a5c3a');
$aktif    = (int)($_POST['aktif'] ?? 1);

if (!$nama || !$username) {
    setFlash('error', 'Nama dan username wajib diisi!');
    redirect(BASE_URL . '/admin/dashboard.php?tab=guru');
}

// Handle upload foto
$fotoPath = null;
if (!empty($_FILES['foto']['name'])) {
    $hasil = uploadGambar($_FILES['foto'], 'foto_guru', 'guru');
    if (!$hasil['sukses']) {
        setFlash('error', 'Upload foto gagal: ' . $hasil['pesan']);
        redirect(BASE_URL . '/admin/dashboard.php?tab=guru');
    }
    $fotoPath = $hasil['path'];
}

if ($aksi === 'tambah') {
    // Cek username unik
    $cek = $db->prepare("SELECT id FROM guru WHERE username = ?");
    $cek->execute([$username]);
    if ($cek->fetch()) {
        setFlash('error', 'Username sudah digunakan!');
        redirect(BASE_URL . '/admin/dashboard.php?tab=guru');
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO guru (nama, mapel, username, password, foto, warna, aktif) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$nama, $mapel, $username, $hash, $fotoPath, $warna, $aktif]);
    setFlash('sukses', "Guru '$nama' berhasil ditambahkan!");

} elseif ($aksi === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    // Cek username unik (kecuali milik sendiri)
    $cek = $db->prepare("SELECT id FROM guru WHERE username = ? AND id != ?");
    $cek->execute([$username, $id]);
    if ($cek->fetch()) {
        setFlash('error', 'Username sudah digunakan oleh guru lain!');
        redirect(BASE_URL . '/admin/dashboard.php?tab=guru');
    }
    // Ambil foto lama jika tidak upload baru
    if ($fotoPath === null) {
        $lama = $db->prepare("SELECT foto FROM guru WHERE id = ?");
        $lama->execute([$id]);
        $fotoPath = $lama->fetchColumn();
    } else {
        // Hapus foto lama
        $lama = $db->prepare("SELECT foto FROM guru WHERE id = ?");
        $lama->execute([$id]);
        $fotoLama = $lama->fetchColumn();
        if ($fotoLama) hapusFile($fotoLama);
    }
    if ($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE guru SET nama=?,mapel=?,username=?,password=?,foto=?,warna=?,aktif=? WHERE id=?");
        $stmt->execute([$nama, $mapel, $username, $hash, $fotoPath, $warna, $aktif, $id]);
    } else {
        $stmt = $db->prepare("UPDATE guru SET nama=?,mapel=?,username=?,foto=?,warna=?,aktif=? WHERE id=?");
        $stmt->execute([$nama, $mapel, $username, $fotoPath, $warna, $aktif, $id]);
    }
    setFlash('sukses', "Data guru '$nama' berhasil diperbarui!");
}

redirect(BASE_URL . '/admin/dashboard.php?tab=guru');
