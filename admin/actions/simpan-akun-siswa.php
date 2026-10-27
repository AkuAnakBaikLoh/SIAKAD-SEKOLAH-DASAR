<?php
// admin/actions/simpan-akun-siswa.php — Set/reset username & password akun siswa
require_once __DIR__ . '/../../config/database.php';
cekAdmin(); $db = getDB();

$siswaId         = (int)($_POST['siswa_id']       ?? 0);
$username        = trim($_POST['username']         ?? '');
$password        = trim($_POST['password']         ?? '');
$passwordConfirm = trim($_POST['password_confirm'] ?? '');

if (!$siswaId || !$username) {
    setFlash('error', 'Data tidak lengkap!');
    redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
}

// Pastikan siswa ada
$siswa = $db->prepare("SELECT id, nama FROM siswa WHERE id = ? AND aktif = 1");
$siswa->execute([$siswaId]);
$siswa = $siswa->fetch();
if (!$siswa) {
    setFlash('error', 'Siswa tidak ditemukan!');
    redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
}

// Cek username sudah dipakai siswa LAIN
$cekUser = $db->prepare("SELECT id FROM siswa WHERE username = ? AND id != ?");
$cekUser->execute([$username, $siswaId]);
if ($cekUser->fetch()) {
    setFlash('error', "Username '$username' sudah digunakan oleh siswa lain!");
    redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
}

// Update username (dan password jika diisi)
if (!empty($password)) {
    if (strlen($password) < 6) {
        setFlash('error', 'Password minimal 6 karakter!');
        redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
    }
    if ($password !== $passwordConfirm) {
        setFlash('error', 'Konfirmasi password tidak cocok!');
        redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
    }
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $db->prepare("UPDATE siswa SET username = ?, password = ? WHERE id = ?")
       ->execute([$username, $hashed, $siswaId]);
    setFlash('sukses', "Akun siswa '{$siswa['nama']}' berhasil diperbarui! Username dan password telah diubah.");
} else {
    // Hanya update username
    $db->prepare("UPDATE siswa SET username = ? WHERE id = ?")
       ->execute([$username, $siswaId]);
    setFlash('sukses', "Username siswa '{$siswa['nama']}' berhasil diperbarui. Password tidak diubah.");
}

redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
