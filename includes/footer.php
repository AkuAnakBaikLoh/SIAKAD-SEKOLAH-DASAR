<?php
// includes/footer.php — Footer umum
require_once __DIR__ . '/../config/database.php';
$db = getDB();
$set = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch();
?>
<footer>
  <p>&copy; <?= date('Y') ?> <strong><?= e($set['nama_sekolah'] ?? 'SMP Nusantara Bangsa') ?></strong></p>
  <p style="margin-top:4px;"><?= e($set['alamat'] ?? '') ?> &nbsp;|&nbsp; Telp: <?= e($set['telepon'] ?? '') ?></p>
</footer>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
