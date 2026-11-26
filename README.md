# Monitoring Absensi & Tugas Karyawan UMKM

Aplikasi web PHP + MySQL untuk memantau absensi, jadwal kerja, koreksi kehadiran, dan progres tugas karyawan UMKM. Antarmuka responsif untuk layar 360 px sampai desktop.

## Struktur folder

```
absen/
├── index.php               # Front controller (semua halaman via ?page=...)
├── install.php             # Halaman pembuat tabel + data contoh (hapus setelah dipakai)
├── .env / .env.example     # Konfigurasi aplikasi & MySQL
├── config/
│   ├── bootstrap.php       # Session, timezone, helper, autoload controller
│   ├── database.php        # Koneksi PDO MySQL
│   └── env.php             # Pembaca file .env
├── src/
│   ├── Auth.php            # Login, hash password, throttle, otorisasi
│   ├── Db.php              # Helper query & transaksi PDO
│   ├── Errors.php          # Penerjemah error constraint MySQL
│   ├── Support.php         # Helper view, CSRF, flash, redirect, URL
│   ├── Validation.php      # Validator input
│   ├── Pdf.php             # Generator PDF tanpa library eksternal
│   └── controllers/        # Logika per halaman (owner & employee)
├── views/                  # Template PHP (layout, dashboard, absensi, tugas, laporan)
├── public/assets/
│   ├── css/app.css         # Stylesheet vanilla, responsif
│   ├── css/wireframe.css   # Override Lo-Fi Wireframe (monokrom, sketsa)
│   └── js/app.js           # Sidebar mobile, konfirmasi, sinkron progres, dll.
├── sql/
│   ├── schema.sql          # 7 tabel + constraint + index (sesuai MySQL Workbench)
│   └── seed.sql            # Akun & data contoh (hanya untuk database kosong)
└── uploads/evidence/       # Bukti pekerjaan yang diunggah karyawan
```

## Cara menjalankan

1. Pastikan PHP 8.1+ dengan ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, dan MySQL 8.0 berjalan.
2. Salin `.env.example` menjadi `.env`, lalu isi:

   ```ini
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=umkm_monitoring_absensi
   DB_USERNAME=root
   DB_PASSWORD=password_mysql_anda
   APP_BASE_URL=/absen/
   ```

   `APP_BASE_URL` disesuaikan dengan path aplikasi di web server. Jika document root IIS/XAMPP langsung menunjuk folder `absen`, gunakan `/absen/`; jika aplikasi diakses sebagai root, gunakan `/`.
3. **Apa itu `install.php`?** Singkatnya: halaman sekali pakai untuk menyiapkan database.
   Saat tombol ditekan, halaman ini:
   - membuat 7 tabel jika belum ada (tabel yang sudah ada **tidak** diubah/dihapus);
   - mengisi akun & data contoh **hanya jika tabel `users` masih kosong**.

   Artinya, jika database Anda sudah dibuat dari MySQL Workbench dan sudah berisi akun,
   **tidak perlu membuka `install.php`**. Cukup pastikan `.env` sudah benar lalu login
   memakai email + kata sandi akun Anda sendiri.
   Alternatif lain: import `sql/schema.sql` lalu `sql/seed.sql` lewat MySQL Workbench/phpMyAdmin
   (seed juga aman karena memakai `INSERT IGNORE`).
4. Hapus `install.php` setelah selesai (demi keamanan).
5. Buka `http://localhost/absen/index.php?page=login`.

### Akun demo (hanya ada jika `seed.sql` dijalankan pada database kosong)

| Role | Email | Kata sandi |
|------|-------|------------|
| Owner | `owner@umkm.test` | `password123` |
| Karyawan | `budi@umkm.test` | `password123` |
| Karyawan | `siti@umkm.test` | `password123` |
| Karyawan | `rizky@umkm.test` | `password123` |
| Karyawan | `nina@umkm.test` | `password123` |

Jika database Anda sudah punya akun sendiri (misalnya `owner@umkm.local`) tetapi kata sandinya
lupa, jalankan perintah ini di MySQL Workbench untuk mengubah kata sandi akun tersebut menjadi
`password123`:

```sql
UPDATE users
SET password_hash = '$2y$12$XipvCrqm4p4Xn.Z920aXReL9ZyQ50xZZ62idI1ICKkDEu6RcZoYbe'
WHERE email = 'owner@umkm.local';
```

Ganti `email` dengan akun Anda. Perintah ini hanya mengubah kata sandi, bukan data absensi/tugas.

### Kompatibilitas skema

Aplikasi sudah diselaraskan dengan skema MySQL Workbench asli:

| Fungsi | Kolom yang dipakai |
|--------|--------------------|
| Status akun | `users.status` = `active` / `inactive` |
| Jadwal | `employee_id`, `start_time`, `end_time` |
| Absensi | `check_in_at`, `check_out_at`, `status` = `on_time` / `late` / `incomplete` |
| Koreksi | `requested_date`, `requested_check_in`, `requested_check_out` (DATETIME) |
| Tugas | `status` = `assigned` / `in_progress` / `submitted` / `revision` / `completed` |
| Log progres | `updated_by`, `status_snapshot`, `notes` |
| Bukti | `file_size_bytes`, `uploaded_at` |

Kode keterlambatan dihitung otomatis dari `start_time` dan `check_in_at`, jadi kolom
`late_minutes` tidak diperlukan.

## Fitur utama

- **Autentikasi & otorisasi**: password di-hash `password_hash`, session regenerated saat login, CSRF di semua POST, akun nonaktif ditolak, proteksi role dan kepemilikan data di server.
- **Owner**: CRUD karyawan, kelola jadwal kerja, pantau absensi, setujui/tolak koreksi, bagi tugas, review bukti & progres, dashboard metrik, laporan PDF/CSV.
- **Karyawan**: lihat jadwal sendiri, check-in/check-out, ajukan koreksi, lihat riwayat sendiri, update progres 0–100, unggah bukti, ajukan tugas selesai.
- **Integritas data**: `uq_attendance_employee_schedule` mencegah dobel check-in, CHECK constraint untuk jam dan progres, transaksi `BEGIN/COMMIT/ROLLBACK` saat menyetujui koreksi dan menyimpan log progres.
- **Ekspor**: PDF (generator bawaan) dan CSV berkode UTF-8 BOM agar rapi dibuka di Excel.

## Mode Lo-Fi Wireframe

Dua cara melihat wireframe tanpa mengubah logika atau data aplikasi:

1. **Preview statis (paling mudah di-screenshot):** buka `http://localhost/absen/index.php?page=wireframe`.
   Halaman ini berisi seluruh alur inti (login owner/karyawan, dashboard owner, absensi & riwayat,
   koreksi, tugas & bukti, laporan) dalam satu dokumen monokrom dengan kode `FR-01`–`FR-15`.
   Dapat dibuka tanpa login dan selalu memakai gaya wireframe.
2. **Mode pada aplikasi asli:** klik tombol **Lo-Fi: ON/OFF** di header (atau tambahkan
   `?wireframe=1` pada URL). Pilihan tersimpan di session, jadi tetap aktif setelah form disubmit.
   Halaman asli tetap berfungsi normal; hanya tampilannya yang berubah menjadi sketsa hitam-putih
   dengan garis tegas, placeholder grafik/foto/avatar, dan badge kode fitur.

Cara mematikan: klik lagi tombol **Lo-Fi: OFF** (atau `?wireframe=0`), lalu refresh.

## Ringkasan pengujian yang disarankan

1. Login sebagai `budi@umkm.test` → Dashboard karyawan → check-in pada jadwal hari ini → ulangi check-in untuk memastikan pesan duplikat ramah.
2. Ajukan koreksi absensi dengan alasan → login owner → setujui → cek `attendances` berubah sesuai jam yang diajukan.
3. Karyawan ubah progres ke 101 harus ditolak; ubah ke 100 → unggah bukti JPG/PDF → ajukan selesai.
4. Owner buka detail tugas → setujui atau kembalikan untuk revisi; cek `task_progress_logs` bertambah setiap update progres.
5. Coba akses `index.php?page=employees` sebagai karyawan → harus mendapat halaman 403.
6. Buka laporan absensi dan tugas, ekspor PDF serta CSV, lalu periksa isinya.
7. Uji tampilan pada lebar 360 px: sidebar muncul lewat tombol ☰, tabel bisa digeser horizontal.

## Mengatasi masalah login

Jika halaman login kembali ke login lagi setelah kata sandi benar, kemungkinan session tidak
tersimpan. Di IIS lokal, `session.use_strict_mode = 1` bisa menyebabkan session id berganti
setiap request. Ini sudah dimatikan di `config/bootstrap.php`; login juga memakai
`session_regenerate_id()` saat berhasil.

Jika masih bermasalah:
1. Pastikan membuka alamat yang sama persis dengan `APP_BASE_URL` di `.env`
   (contoh: `http://localhost/absen/index.php?page=login`, bukan `http://127.0.0.1/...`).
2. Hapus cookie lama browser untuk `localhost`, lalu coba lagi.
3. Cek email yang dipakai harus sama dengan email di tabel `users` (misalnya `owner@umkm.local`).

## Catatan keamanan

- File `.env` berisi kredensial database; jangan dipublikasikan.
- Batasi akses folder `uploads/` dari eksekusi PHP di web server (file diunggah sudah diverifikasi MIME dan diganti namanya).
- Hapus `install.php` setelah instalasi selesai.
