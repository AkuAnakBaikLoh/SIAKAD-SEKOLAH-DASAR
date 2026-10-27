<?php require_once __DIR__.'/../../config/database.php'; cekAdmin(); $db=getDB();
$id=(int)($_GET['id']??0); if($id){$db->prepare("DELETE FROM pesan_kontak WHERE id=?")->execute([$id]);setFlash('sukses','Pesan dihapus!');}
redirect(BASE_URL.'/admin/dashboard.php?tab=pesan');
