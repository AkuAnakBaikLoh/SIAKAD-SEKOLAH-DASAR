<?php
// admin/actions/hapus-galeri.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $b = $db->prepare("SELECT foto FROM galeri WHERE id=?"); $b->execute([$id]); $b = $b->fetch();
    if ($b && $b['foto']) hapusFile($b['foto']);
    $db->prepare("DELETE FROM galeri WHERE id=?")->execute([$id]);
    setFlash('sukses', 'Galeri berhasil dihapus!');
}
redirect(BASE_URL . '/admin/dashboard.php?tab=galeri');
