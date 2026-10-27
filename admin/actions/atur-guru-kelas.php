<?php
// admin/actions/atur-guru-kelas.php — Mendukung multi guru, multi mapel
require_once __DIR__ . '/../../config/database.php';
cekAdmin();
$db = getDB();

$kelasId      = (int)($_POST['kelas_id'] ?? 0);
$guruMapelRaw = $_POST['guru_mapel'] ?? [];

if (!$kelasId) {
    setFlash('error', 'ID kelas tidak valid.');
    redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
}

$stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
$stmtKelas->execute([$kelasId]);
$kelas = $stmtKelas->fetch();

if (!$kelas) {
    setFlash('error', 'Kelas tidak ditemukan.');
    redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
}

// ── Deduplikasi & normalisasi ─────────────────────────────────────────────
$guruMapelList = [];
$seen          = [];

foreach ($guruMapelRaw as $row) {
    $guruId = (int)($row['guru_id'] ?? 0);
    $mapel  = trim($row['mapel'] ?? '');

    if ($guruId <= 0 || $mapel === '') continue;

    $kunci = $guruId . '|' . mb_strtolower($mapel);
    if (isset($seen[$kunci])) continue;

    $seen[$kunci] = true;
    $guruMapelList[] = ['guru_id' => $guruId, 'mapel' => $mapel];
}

// ── Simpan ke database ────────────────────────────────────────────────────
$db->prepare("DELETE FROM guru_kelas WHERE kelas_id = ?")->execute([$kelasId]);

$jumlah = 0;
if (!empty($guruMapelList)) {
    $stmt = $db->prepare(
        "INSERT IGNORE INTO guru_kelas (guru_id, kelas_id, mapel) VALUES (?, ?, ?)"
    );
    foreach ($guruMapelList as $item) {
        $stmt->execute([$item['guru_id'], $kelasId, $item['mapel']]);
        $jumlah += $stmt->rowCount();
    }
}

$namaKelas = $kelas['nama_kelas'];
if ($jumlah > 0) {
    setFlash('sukses', "Berhasil mengatur {$jumlah} penugasan guru-mapel untuk kelas \"{$namaKelas}\".");
} else {
    setFlash('sukses', "Semua penugasan guru-mapel kelas \"{$namaKelas}\" telah dihapus.");
}

redirect(BASE_URL . '/admin/dashboard.php?tab=kelas');
