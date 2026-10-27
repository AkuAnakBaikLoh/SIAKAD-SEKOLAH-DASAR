<?php
// admin/actions/simpan-pengaturan.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();

$fields = ['nama_sekolah','npsn','akreditasi','kepala_sekolah','tahun_berdiri','telepon','email','alamat','visi','misi'];
$data   = [];
foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

// Upload foto sekolah (background beranda)
if (!empty($_FILES['foto_sekolah']['name'])) {
    $hasil = uploadGambar($_FILES['foto_sekolah'], 'sekolah', 'bg');
    if (!$hasil['sukses']) {
        setFlash('error', 'Upload foto gagal: ' . $hasil['pesan']);
        redirect(BASE_URL . '/admin/dashboard.php?tab=pengaturan');
    }
    // Hapus foto lama
    $lama = $db->query("SELECT foto_sekolah FROM pengaturan LIMIT 1")->fetchColumn();
    if ($lama) hapusFile($lama);
    $data['foto_sekolah'] = $hasil['path'];
} else {
    // Pertahankan foto lama
    $lama = $db->query("SELECT foto_sekolah FROM pengaturan LIMIT 1")->fetchColumn();
    $data['foto_sekolah'] = $lama;
}

// Cek apakah sudah ada row
$ada = $db->query("SELECT COUNT(*) FROM pengaturan")->fetchColumn();
if ($ada) {
    $set  = [];
    $vals = [];
    foreach ($data as $k => $v) { $set[] = "$k = ?"; $vals[] = $v; }
    $stmt = $db->prepare("UPDATE pengaturan SET " . implode(', ', $set));
    $stmt->execute($vals);
} else {
    $cols = implode(', ', array_keys($data));
    $plh  = implode(', ', array_fill(0, count($data), '?'));
    $stmt = $db->prepare("INSERT INTO pengaturan ($cols) VALUES ($plh)");
    $stmt->execute(array_values($data));
}

setFlash('sukses', 'Pengaturan sekolah berhasil disimpan!');
redirect(BASE_URL . '/admin/dashboard.php?tab=pengaturan');
