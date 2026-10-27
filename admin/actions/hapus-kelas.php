<?php
// admin/actions/hapus-kelas.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('error', 'ID kelas tidak valid.');
    redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
}

// Ambil info kelas
$kelas = $db->prepare("SELECT * FROM kelas WHERE id = ?");
$kelas->execute([$id]);
$kelas = $kelas->fetch();

if (!$kelas) {
    setFlash('error', 'Kelas tidak ditemukan.');
    redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
}

// Cek apakah masih ada siswa aktif di kelas ini
$jmlSiswa = $db->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = ? AND aktif = 1");
$jmlSiswa->execute([$id]);
$jmlSiswa = (int)$jmlSiswa->fetchColumn();

if ($jmlSiswa > 0) {
    setFlash('error', "Kelas \"{$kelas['nama_kelas']}\" tidak dapat dihapus karena masih memiliki {$jmlSiswa} siswa aktif.");
    redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
}

// Hapus relasi guru_kelas terlebih dahulu, lalu hapus kelas
$db->prepare("DELETE FROM guru_kelas WHERE kelas_id = ?")->execute([$id]);
$db->prepare("DELETE FROM kelas WHERE id = ?")->execute([$id]);

setFlash('sukses', "Kelas \"{$kelas['nama_kelas']}\" berhasil dihapus.");
redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
