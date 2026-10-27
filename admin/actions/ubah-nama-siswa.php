<?php
// admin/actions/ubah-nama-siswa.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();

$id   = (int)($_POST['siswa_id'] ?? 0);
$nama = trim($_POST['nama']      ?? '');
$nis  = trim($_POST['nis']       ?? '');

if (!$id || !$nama || !$nis) {
    setFlash('error', 'Data tidak lengkap!');
    redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
}

// Cek NIS unik (kecuali milik siswa itu sendiri)
$cekNis = $db->prepare("SELECT id FROM siswa WHERE nis = ? AND id != ?");
$cekNis->execute([$nis, $id]);
if ($cekNis->fetch()) {
    setFlash('error', "NIS '$nis' sudah digunakan oleh siswa lain!");
    redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
}

$stmt = $db->prepare("UPDATE siswa SET nama = ?, nis = ? WHERE id = ?");
$stmt->execute([$nama, $nis, $id]);

setFlash('sukses', "Data siswa berhasil diperbarui menjadi '$nama'.");
redirect(BASE_URL . '/admin/dashboard.php?tab=siswa');
