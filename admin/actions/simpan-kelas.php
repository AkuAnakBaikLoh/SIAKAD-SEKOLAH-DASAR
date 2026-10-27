<?php
// admin/actions/simpan-kelas.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();

$aksi       = $_POST['aksi'] ?? '';
$id         = (int)($_POST['id'] ?? 0);
$namaKelas  = trim($_POST['nama_kelas'] ?? '');
$tingkat    = $_POST['tingkat'] ?? '';
$waliKelas  = ($_POST['wali_kelas'] ?? '') ?: null;

// Olah daftar mapel kelas
$mapelRaw = $_POST['mapel_kelas'] ?? [];
if (is_array($mapelRaw)) {
    $mapelList = array_filter(array_map('trim', $mapelRaw));
    $mapelKelas = implode(', ', $mapelList);
} else {
    $mapelKelas = trim((string)$mapelRaw);
}

// Validasi tingkat
$validTingkat = ['I', 'II', 'III', 'IV', 'V', 'VI'];

if ($aksi === 'tambah') {
    // ---- TAMBAH KELAS BARU ----
    if (empty($namaKelas) || !in_array($tingkat, $validTingkat)) {
        setFlash('error', 'Nama kelas dan tingkat wajib diisi!');
        redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
    }

    // Cek duplikat nama kelas
    $cek = $db->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
    $cek->execute([$namaKelas]);
    if ($cek->fetch()) {
        setFlash('error', "Kelas \"$namaKelas\" sudah ada!");
        redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
    }

    $stmt = $db->prepare("INSERT INTO kelas (nama_kelas, tingkat, wali_kelas, mapel) VALUES (?, ?, ?, ?)");
    $stmt->execute([$namaKelas, $tingkat, $waliKelas, $mapelKelas ?: null]);
    setFlash('sukses', "Kelas \"$namaKelas\" berhasil dibuat!");

} elseif ($aksi === 'edit') {
    // ---- EDIT KELAS ----
    if (empty($namaKelas) || $id <= 0) {
        setFlash('error', 'Data tidak valid!');
        redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
    }

    // Cek duplikat nama (kecuali dirinya sendiri)
    $cek = $db->prepare("SELECT id FROM kelas WHERE nama_kelas = ? AND id != ?");
    $cek->execute([$namaKelas, $id]);
    if ($cek->fetch()) {
        setFlash('error', "Nama kelas \"$namaKelas\" sudah digunakan kelas lain!");
        redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
    }

    $stmt = $db->prepare("UPDATE kelas SET nama_kelas = ?, wali_kelas = ?, mapel = ? WHERE id = ?");
    $stmt->execute([$namaKelas, $waliKelas, $mapelKelas ?: null, $id]);
    setFlash('sukses', "Kelas \"$namaKelas\" berhasil diperbarui!");

} else {
    setFlash('error', 'Aksi tidak dikenal.');
}

redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
