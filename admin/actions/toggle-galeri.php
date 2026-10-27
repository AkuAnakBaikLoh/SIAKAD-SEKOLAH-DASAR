<?php
// admin/actions/toggle-galeri.php
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $db->prepare("UPDATE galeri SET dipublikasi = NOT dipublikasi WHERE id=?")->execute([$id]);
    setFlash('sukses', 'Status publikasi diperbarui!');
}
redirect(BASE_URL . '/admin/dashboard.php?tab=galeri');
