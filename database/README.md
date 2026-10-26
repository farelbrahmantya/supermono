# Database LASMONO

Kompatibel MySQL 5.1 / phpMyAdmin 3.2.5 (struktur tabel tetap sama dengan versi awal).

- `upgrade_lasmono_db.sql`: jalankan **sekali** pada database `lasmono_db` yang sudah berjalan
  (Ekspor/backup dulu). Isinya: perbaikan hash password petugas, master blok yard A1-F5,
  index, foreign key, dan perapian data lama.
- `upgrade_02_kitir_timer.sql`: jalankan **sekali** setelah upgrade pertama. Menambah status
  `IN_SURVEY` (Yardman scan 2x), kolom data kitir di antrean, dan kolom `depo` di `yard_zones`
  (default A-C = Depo 1, D-F = Depo 2; sesuaikan dengan kondisi lapangan).
- `upgrade_03_principal_blok.sql`: pembagian blok per principal (pelayaran) beserta warnanya
  (Depo 1: A1-C3 ONE, A4-C5 Wan Hai; Depo 2: D1-F3 KMTC, D4-F5 CMA CGM).
- `upgrade_04_aktivitas_kitir.sql`: kolom `activity` di antrean (huruf awal kitir: Z Repo In,
  A Import, D Export, E Repo Out). Export & Repo Out tidak melalui survey.
- `lasmono_db.sql`: struktur lengkap + data awal untuk instalasi baru (misalnya laptop demo).

Login petugas setelah upgrade: `yardman1`, `admin_ct`, `gate_in`, `gate_out` dengan password
`password123`. Ganti password ini sebelum dipakai di luar demo.
