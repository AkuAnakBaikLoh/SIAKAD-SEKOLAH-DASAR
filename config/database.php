<?php
// ============================================================
// config/database.php — Konfigurasi Koneksi Database
// SESUAIKAN dengan setting server Anda
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Ganti dengan username MySQL Anda
define('DB_PASS', '');             // Ganti dengan password MySQL Anda
define('DB_NAME', 'db_sekolah');
define('DB_PORT', 3306);

define('BASE_URL', 'http://localhost/sekolah');  // Ganti dengan URL Anda
define('BASE_PATH', __DIR__ . '/..');             // Root path proyek

// Upload settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('UPLOAD_ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Session
define('SESSION_TIMEOUT', 3600 * 8); // 8 jam

// ============================================================
// KONEKSI DATABASE (PDO)
// ============================================================
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Sanitasi output HTML
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format tanggal Indonesia
 */
function formatTanggal(string $date, bool $withTime = false): string
{
    $bulan = [
        '',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $ts = strtotime($date);
    $d = date('j', $ts);
    $m = $bulan[(int)date('n', $ts)];
    $y = date('Y', $ts);
    $result = "$d $m $y";
    if ($withTime) $result .= ' ' . date('H:i', $ts);
    return $result;
}

/**
 * Hitung rata-rata nilai
 */
function hitungRata(?float $harian, ?float $uts, ?float $uas): ?float
{
    $vals = array_filter([$harian, $uts, $uas], fn($v) => $v !== null);
    if (empty($vals)) return null;
    return round(array_sum($vals) / count($vals), 2);
}

/**
 * Tentukan predikat dari nilai
 * 88-100: Sangat Baik, 82-87: Baik, 75-81: Cukup Baik, 0-74: Perlu Bimbingan
 */
function predikat(?float $nilai): string
{
    if ($nilai === null) return '-';
    if ($nilai >= 88) return 'Sangat Baik';
    if ($nilai >= 82) return 'Baik';
    if ($nilai >= 75) return 'Cukup Baik';
    return 'Perlu Bimbingan';
}

/**
 * Warna predikat
 */
function warnaPredikat(?float $nilai): string
{
    if ($nilai === null) return '#8a8880';
    if ($nilai >= 88) return '#1a5c3a';
    if ($nilai >= 82) return '#2563eb';
    if ($nilai >= 75) return '#d97706';
    return '#c0392b';
}

/**
 * Upload file gambar
 */
function uploadGambar(array $file, string $folder, string $prefix = ''): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['sukses' => false, 'pesan' => 'Error upload file: ' . $file['error']];
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['sukses' => false, 'pesan' => 'Ukuran file terlalu besar (maks 5MB)'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_EXT)) {
        return ['sukses' => false, 'pesan' => 'Tipe file tidak diizinkan (jpg, png, webp, gif)'];
    }
    // Validasi MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, UPLOAD_ALLOWED_TYPES)) {
        return ['sukses' => false, 'pesan' => 'Tipe MIME file tidak valid'];
    }

    $uploadDir = BASE_PATH . '/uploads/' . $folder . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $namaFile = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $namaFile;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['sukses' => false, 'pesan' => 'Gagal menyimpan file'];
    }
    return ['sukses' => true, 'nama_file' => $namaFile, 'path' => 'uploads/' . $folder . '/' . $namaFile];
}

/**
 * Hapus file upload lama
 */
function hapusFile(string $path): void
{
    $fullPath = BASE_PATH . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) unlink($fullPath);
}

/**
 * URL foto
 */
function fotoUrl(?string $path, string $default = ''): string
{
    if (empty($path)) return $default;
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Redirect
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Ambil & hapus flash message
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/**
 * Cek login admin
 */
function cekAdmin(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        redirect(BASE_URL . '/login.php?role=admin');
    }
}

/**
 * Cek login guru
 */
function cekGuru(): array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['guru_id'])) {
        redirect(BASE_URL . '/login.php?role=guru');
    }
    return $_SESSION['guru_data'] ?? [];
}

/**
 * Cek login siswa
 */
function cekSiswa(): array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['siswa_id'])) {
        redirect(BASE_URL . '/login.php?role=siswa');
    }
    return $_SESSION['siswa_data'] ?? [];
}
