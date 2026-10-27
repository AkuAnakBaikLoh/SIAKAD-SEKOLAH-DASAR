<?php
// admin/actions/hapus-guru.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $g = $db->prepare("SELECT foto FROM guru WHERE id=?"); $g->execute([$id]); $g = $g->fetch();
    if ($g && $g['foto']) hapusFile($g['foto']);
    $db->prepare("DELETE FROM guru WHERE id=?")->execute([$id]);
    setFlash('sukses', 'Guru berhasil dihapus!');
}
redirect(BASE_URL . '/admin/dashboard.php?tab=guru');
