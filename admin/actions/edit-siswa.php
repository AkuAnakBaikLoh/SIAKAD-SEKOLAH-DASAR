<?php
// admin/actions/edit-siswa.php — Edit lengkap data siswa (data + akun) oleh admin
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();

$id        = (int)($_POST['siswa_id']        ?? 0);
$nis       = trim($_POST['nis']              ?? '');
$nama      = trim($_POST['nama']             ?? '');
$kelasId   = (int)($_POST['kelas_id']        ?? 0);
$jk        = $_POST['jenis_kelamin']         ?? 'L';
$username  = trim($_POST['username']         ?? '');
$password  = trim($_POST['password']         ?? '');
$pwKonfirm = trim($_POST['password_confirm'] ?? '');

// Validasi wajib
if (!$id || !$nis || !$nama || !$kelasId) {
    setFlash('error', 'Data tidak lengkap! NIS, Nama, dan Kelas wajib diisi.');
    redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
}

// Cek NIS unik (kecuali milik siswa itu sendiri)
$cekNis = $db->prepare("SELECT id FROM siswa WHERE nis = ? AND id != ?");
$cekNis->execute([$nis, $id]);
if ($cekNis->fetch()) {
    setFlash('error', "NIS '$nis' sudah digunakan oleh siswa lain!");
    redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
}

// Cek username unik jika diisi
if ($username) {
    $cekUser = $db->prepare("SELECT id FROM siswa WHERE username = ? AND id != ?");
    $cekUser->execute([$username, $id]);
    if ($cekUser->fetch()) {
        setFlash('error', "Username '$username' sudah digunakan oleh siswa lain!");
        redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
    }
}

// Validasi password jika diisi
if (!empty($password)) {
    if (strlen($password) < 6) {
        setFlash('error', 'Password baru minimal 6 karakter!');
        redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
    }
    if ($password !== $pwKonfirm) {
        setFlash('error', 'Konfirmasi password tidak cocok!');
        redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
    }
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $db->prepare("UPDATE siswa SET nis=?, nama=?, kelas_id=?, jenis_kelamin=?, username=?, password=? WHERE id=?")
       ->execute([$nis, $nama, $kelasId, $jk, $username ?: null, $hashed, $id]);
    setFlash('sukses', "Data siswa '$nama' berhasil diperbarui beserta password akun.");
} else {
    // Update tanpa mengubah password
    $db->prepare("UPDATE siswa SET nis=?, nama=?, kelas_id=?, jenis_kelamin=?, username=? WHERE id=?")
       ->execute([$nis, $nama, $kelasId, $jk, $username ?: null, $id]);
    setFlash('sukses', "Data siswa '$nama' berhasil diperbarui. Password tidak diubah.");
}

redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
