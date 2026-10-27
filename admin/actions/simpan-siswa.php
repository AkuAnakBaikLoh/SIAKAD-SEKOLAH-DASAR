<?php
// admin/actions/simpan-siswa.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin(); $db = getDB();

$nis      = trim($_POST['nis']      ?? '');
$nama     = trim($_POST['nama']     ?? '');
$kelas_id = (int)($_POST['kelas_id'] ?? 0);
$jk       = $_POST['jenis_kelamin'] ?? 'L';
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$nis || !$nama || !$kelas_id) {
    setFlash('error', 'Data tidak lengkap!');
    redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
}

// Validasi username unik jika diisi
if ($username) {
    $cek = $db->prepare("SELECT id FROM siswa WHERE username = ?");
    $cek->execute([$username]);
    if ($cek->fetch()) {
        setFlash('error', "Username '$username' sudah digunakan oleh siswa lain!");
        redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
    }
}

$hashedPwd = (!empty($password) && strlen($password) >= 6) ? password_hash($password, PASSWORD_BCRYPT) : null;

try {
    $db->prepare("INSERT INTO siswa (nis, nama, kelas_id, jenis_kelamin, username, password) VALUES (?,?,?,?,?,?)")
       ->execute([$nis, $nama, $kelas_id, $jk, $username ?: null, $hashedPwd]);
    $msg = "Siswa '$nama' berhasil ditambahkan!";
    if ($username && $hashedPwd) $msg .= " Akun login sudah dibuat.";
    elseif ($username)            $msg .= " (Password belum diatur — min. 6 karakter)";
    setFlash('sukses', $msg);
} catch (Exception $e) {
    setFlash('error', 'NIS sudah digunakan oleh siswa lain!');
}
redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
