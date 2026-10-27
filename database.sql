-- ============================================================
-- Database: db_sekolah
-- Sistem Informasi Sekolah — SDIT MODERN BANI LUQMAN
-- Dibuat ulang: 2026-05-19 (disesuaikan dengan schema aktual)
-- Versi server: MariaDB 10.4+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- Buat database (opsional, sesuaikan nama jika perlu)
-- ============================================================
-- CREATE DATABASE IF NOT EXISTS `db_sekolah` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE `db_sekolah`;

-- ============================================================
-- Matikan foreign key check sementara agar import lancar
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tabel: admin
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `username`   varchar(50)  NOT NULL,
  `password`   varchar(255) NOT NULL,
  `nama`       varchar(200) DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data admin default (password: password)
INSERT INTO `admin` (`id`, `username`, `password`, `nama`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- ------------------------------------------------------------
-- Tabel: pengaturan
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan`;
CREATE TABLE `pengaturan` (
  `id`             int(11)      NOT NULL AUTO_INCREMENT,
  `nama_sekolah`   varchar(200) DEFAULT NULL,
  `npsn`           varchar(20)  DEFAULT NULL,
  `alamat`         text         DEFAULT NULL,
  `telepon`        varchar(30)  DEFAULT NULL,
  `email`          varchar(100) DEFAULT NULL,
  `kepala_sekolah` varchar(200) DEFAULT NULL,
  `akreditasi`     varchar(10)  DEFAULT NULL,
  `foto_sekolah`   varchar(255) DEFAULT NULL,
  `visi`           text         DEFAULT NULL,
  `misi`           text         DEFAULT NULL,
  `tahun_berdiri`  year(4)      DEFAULT 2023,
  `updated_at`     timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data pengaturan sekolah
INSERT INTO `pengaturan` (`id`, `nama_sekolah`, `npsn`, `alamat`, `telepon`, `email`, `kepala_sekolah`, `akreditasi`, `foto_sekolah`, `visi`, `misi`, `tahun_berdiri`) VALUES
(1,
 'SDIT MODERN BANI LUQMAN',
 '20251913',
 'Jl. H. Hasan No. 08, RT 007/RW 002 Kel. Bahagia Kec. Babelan, Kabupaten Bekasi, Jawa Barat 15310',
 '0822-6113-0305',
 'sditbaniluqman@gmail.com',
 'Drs. Rahyon Sugianto.MM ',
 'A',
 NULL,
 'Mengedepankan Pembiasaan Adab Dan Berprestasi Dalam Akademis',
 'Menyelenggarakan pembelajaran aktif dan inovatif\nMenumbuhkan karakter Pancasila dalam setiap kegiatan\nMengembangkan potensi siswa melalui ekstrakurikuler\nMembangun kemitraan dengan orang tua dan masyarakat\nMewujudkan lingkungan sekolah yang bersih dan hijau',
 2023
);

-- ------------------------------------------------------------
-- Tabel: guru
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `guru`;
CREATE TABLE `guru` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `nama`       varchar(200) NOT NULL,
  `mapel`      varchar(100) NOT NULL,
  `username`   varchar(50)  NOT NULL,
  `password`   varchar(255) NOT NULL,
  `foto`       varchar(255) DEFAULT NULL,
  `warna`      varchar(20)  DEFAULT '#1a5c3a',
  `aktif`      tinyint(1)   DEFAULT 1,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Tabel: kelas
-- Kolom tingkat menggunakan VARCHAR (bukan ENUM) agar fleksibel.
-- Format nilai: 'I','II','III','IV','V','VI' untuk SD kelas 1-6
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `kelas`;
CREATE TABLE `kelas` (
  `id`         int(11)     NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(20) NOT NULL,
  `tingkat`    varchar(10) NOT NULL DEFAULT '',
  `wali_kelas` int(11)     DEFAULT NULL,
  `mapel`      text        DEFAULT NULL,
  `created_at` timestamp   NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `wali_kelas` (`wali_kelas`),
  CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`wali_kelas`) REFERENCES `guru` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Tabel: guru_kelas (relasi banyak-ke-banyak guru ↔ kelas)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `guru_kelas`;
CREATE TABLE `guru_kelas` (
  `id`       int(11) NOT NULL AUTO_INCREMENT,
  `guru_id`  int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `mapel`    varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_guru_kelas_mapel` (`guru_id`, `kelas_id`, `mapel`),
  KEY `kelas_id` (`kelas_id`),
  CONSTRAINT `guru_kelas_ibfk_1` FOREIGN KEY (`guru_id`)  REFERENCES `guru`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `guru_kelas_ibfk_2` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Tabel: siswa
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
  `id`            int(11)         NOT NULL AUTO_INCREMENT,
  `nis`           varchar(20)     NOT NULL,
  `nama`          varchar(200)    NOT NULL,
  `kelas_id`      int(11)         NOT NULL,
  `jenis_kelamin` enum('L','P')   DEFAULT 'L',
  `tanggal_lahir` date            DEFAULT NULL,
  `alamat`        text            DEFAULT NULL,
  `aktif`         tinyint(1)      DEFAULT 1,
  `created_at`    timestamp       NOT NULL DEFAULT current_timestamp(),
  `username`      varchar(50)     DEFAULT NULL,
  `password`      varchar(255)    DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nis`                   (`nis`),
  UNIQUE KEY `unique_siswa_username` (`username`),
  KEY `kelas_id` (`kelas_id`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Tabel: absensi
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `absensi`;
CREATE TABLE `absensi` (
  `id`          int(11)                              NOT NULL AUTO_INCREMENT,
  `siswa_id`    int(11)                              NOT NULL,
  `kelas_id`    int(11)                              NOT NULL,
  `guru_id`     int(11)                              NOT NULL,
  `tanggal`     date                                 NOT NULL,
  `status`      enum('Hadir','Sakit','Izin','Alpa')  DEFAULT 'Hadir',
  `keterangan`  varchar(255)                         DEFAULT NULL,
  `created_at`  timestamp                            NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_absensi` (`siswa_id`, `kelas_id`, `tanggal`, `guru_id`),
  KEY `kelas_id` (`kelas_id`),
  KEY `guru_id`  (`guru_id`),
  CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `absensi_ibfk_3` FOREIGN KEY (`guru_id`)  REFERENCES `guru`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Tabel: nilai
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `nilai`;
CREATE TABLE `nilai` (
  `id`                int(11)         NOT NULL AUTO_INCREMENT,
  `siswa_id`          int(11)         NOT NULL,
  `kelas_id`          int(11)         NOT NULL,
  `guru_id`           int(11)         NOT NULL,
  `mapel`             varchar(100)    NOT NULL,
  `semester`          enum('1','2')   DEFAULT '1',
  `tahun_ajaran`      varchar(10)     DEFAULT '2024/2025',
  `nilai_harian`      decimal(5,2)    DEFAULT NULL,
  `uts`               decimal(5,2)    DEFAULT NULL,
  `uas`               decimal(5,2)    DEFAULT NULL,
  `u1`                decimal(5,2)    DEFAULT NULL,
  `u2`                decimal(5,2)    DEFAULT NULL,
  `u3`                decimal(5,2)    DEFAULT NULL,
  `t1`                decimal(5,2)    DEFAULT NULL,
  `t2`                decimal(5,2)    DEFAULT NULL,
  `t3`                decimal(5,2)    DEFAULT NULL,
  `t4`                decimal(5,2)    DEFAULT NULL,
  `keaktifan`         decimal(5,2)    DEFAULT NULL,
  `kehadiran`         decimal(5,2)    DEFAULT NULL,
  `nilai_akhir`       decimal(5,2)    DEFAULT NULL,
  `asts`              decimal(5,2)    DEFAULT NULL,
  `asas`              decimal(5,2)    DEFAULT NULL,
  `nilai_akhir_final` decimal(5,2)    DEFAULT NULL,
  `created_at`        timestamp       NOT NULL DEFAULT current_timestamp(),
  `updated_at`        timestamp       NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nilai` (`siswa_id`, `kelas_id`, `guru_id`, `mapel`, `semester`, `tahun_ajaran`),
  KEY `kelas_id` (`kelas_id`),
  KEY `guru_id`  (`guru_id`),
  CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nilai_ibfk_2` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nilai_ibfk_3` FOREIGN KEY (`guru_id`)  REFERENCES `guru`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Tabel: galeri (berita/kegiatan sekolah)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `galeri`;
CREATE TABLE `galeri` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `judul`       varchar(300) NOT NULL,
  `kategori`    varchar(100) DEFAULT 'Kegiatan Sekolah',
  `ringkasan`   text         DEFAULT NULL,
  `foto`        varchar(255) DEFAULT NULL,
  `emoji`       varchar(20)  DEFAULT '📸',
  `dipublikasi` tinyint(1)   DEFAULT 1,
  `created_by`  int(11)      DEFAULT NULL,
  `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `galeri_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Tabel: pesan_kontak
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pesan_kontak`;
CREATE TABLE `pesan_kontak` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `nama`         varchar(200) NOT NULL,
  `email`        varchar(200) DEFAULT NULL,
  `subjek`       varchar(300) DEFAULT NULL,
  `isi_pesan`    text         NOT NULL,
  `sudah_dibaca` tinyint(1)   DEFAULT 0,
  `created_at`   timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Aktifkan kembali foreign key check
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;
