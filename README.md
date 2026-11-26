# Aplikasi Pencatatan Kehadiran dan Kas Rutin Komunitas Futsal Mingguan Berbasis AI

Aplikasi berbasis Laravel 12, Blade, Tailwind CSS, MySQL, dan Laravel Breeze untuk mengelola anggota, jadwal, kehadiran, kas, serta laporan analitik lokal.

## Persyaratan

- PHP 8.2 atau lebih baru dengan ekstensi PDO MySQL, Fileinfo, Mbstring, OpenSSL, Tokenizer, XML, Ctype, JSON, BCMath, dan GD.
- Composer 2
- Node.js dan npm
- MySQL 8+

## Menjalankan aplikasi

1. Buat database MySQL kosong bernama `futsal_community`.
2. Pasang dependensi PHP dan siapkan konfigurasi:

   ```sh
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Isi kredensial MySQL di `.env` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
4. Jalankan migrasi dan data contoh, lalu siapkan aset:

   ```sh
   php artisan migrate --seed
   npm install
   npm run build
   php artisan storage:link
   php artisan serve
   ```

5. Buka `http://127.0.0.1:8000`.

Untuk pengembangan frontend, jalankan `npm run dev` di terminal terpisah.

## Akun awal

- Email: `admin@futsal.com`
- Password: `password`

Ubah password awal sebelum aplikasi digunakan bersama. Registrasi publik membuat akun Bendahara secara default; peran Admin hanya ditetapkan pada akun seed Admin agar pendaftar tidak dapat menaikkan hak aksesnya sendiri.

## Fitur

- Dashboard dengan ringkasan anggota, kehadiran, kas, jadwal, grafik 4 minggu, tunggakan, dan kehadiran terbaru.
- CRUD anggota termasuk pencarian dan foto opsional; CRUD jadwal dan transaksi kas.
- Absensi massal dengan status Hadir, Izin, Alpa, dan Terlambat, rekap kehadiran anggota, serta penghapusan catatan.
- Laporan AI lokal yang merangkum data, menghitung rata-rata kehadiran, rekomendasi absen beruntun, dan proyeksi saldo tanpa layanan AI eksternal.
- Ekspor laporan PDF dan CSV yang dapat dibuka di Excel.

Semua halaman pengelolaan dan laporan memerlukan login. PDF dibuat dengan DomPDF. File foto anggota disimpan pada disk publik Laravel.
