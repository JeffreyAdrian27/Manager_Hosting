# Sistem Manager Hosting

Aplikasi web manajemen klien hosting dan domain berbasis PHP Native dan MySQL, dilengkapi dengan sistem otomasi pengingat tagihan perpanjangan (**H-14** dan **H-3**) via PHPMailer, lampiran bukti nota, dan pencatatan riwayat log pengiriman.

---

## Fitur Utama

1. **Dashboard Analitik & Pemantauan**:
   - Total klien aktif, segera expired ($\le$ 14 hari), kritis ($\le$ 3 hari), dan expired.
   - Tabel ringkasan hosting terdekat jatuh tempo.
   - Ringkasan riwayat pengiriman email pengingat terakhir.

2. **Manajemen Klien (CRUD)**:
   - Pencarian real-time berdasarkan nama, website, email, atau penyedia hosting.
   - Filter status dinamis: *Semua*, *Aktif*, *Segera Expired*, *Kritis*, *Expired*.
   - Upload & simpan gambar bukti nota tagihan/invoice (JPG, PNG, WEBP maks 2 MB).
   - Pengarsipan data dengan *soft delete*.

3. **Fitur Perpanjang (Renew)**:
   - Memperbarui tanggal mulai dan jatuh tempo baru saat klien telah melakukan pembayaran.
   - Mereset siklus pengingat otomatis untuk periode masa aktif berikutnya.

4. **Pengingat Tagihan Otomatis & Manual**:
   - Pengiriman otomatis via CLI Cron Job pada **H-14** dan **H-3**.
   - Mekanisme anti-duplikasi pengiriman pada periode expired yang sama.
   - Tombol manual *"Kirim Email Sekarang"* di halaman detail klien.
   - Salinan email konfirmasi terkirim ke alamat email admin.
   - Pencatatan log status berhasil/gagal secara rinci ke tabel database.

5. **Pengaturan Sistem**:
   - Konfigurasi akun bank / e-wallet penampung transfer tagihan.
   - Konfigurasi server SMTP (Host, Port, Username, Password, Enkripsi TLS/SSL).
   - Fitur tombol *Uji Coba Kirim Email (SMTP)* langsung dari antarmuka.
   - Editor kustomisasi template email pengingat dengan variabel placeholder dinamis.

---

## Struktur Folder

```
manager-hosting/
├── assets/
│   ├── css/
│   │   └── app.css             # Desain antarmuka modern slate dark theme
│   └── js/
│       └── app.js              # Interaktivitas UI & format rupiah
├── config/
│   └── database.php            # Koneksi database PDO & load environment
├── cron/
│   └── send_reminders.php      # Eksekutor cron pengingat H-14 & H-3
├── includes/
│   ├── auth.php                # Autentikasi sesi & proteksi login
│   ├── functions.php           # Helper fungsi, format tanggal & rupiah, CSRF
│   ├── header.php              # Navigasi & template header
│   ├── footer.php              # Template footer
│   └── mailer.php              # Wrapper PHPMailer & template renderer
├── pages/
│   ├── clients/
│   │   ├── create.php          # Tambah klien baru
│   │   ├── delete.php          # Soft delete klien
│   │   ├── detail.php          # Detail lengkap klien & kirim manual
│   │   ├── edit.php            # Edit data klien
│   │   ├── index.php           # Tabel daftar klien & filter
│   │   ├── renew.php           # Form perpanjangan masa aktif
│   │   └── send_email.php      # Handler kirim email manual
│   ├── dashboard.php           # Dashboard statistik utama
│   ├── login.php               # Halaman login admin
│   ├── logout.php              # Handler keluar sistem
│   ├── logs.php                # Riwayat log pengiriman email
│   └── settings.php            # Konfigurasi sistem & SMTP
├── uploads/
│   └── nota/                   # Direktori penyimpanan bukti nota klien
├── database.sql                # Skema database & data awal (seed)
├── index.php                   # Gerbang utama routing
└── README.md
```

---

## Panduan Instalasi & Menjalankan

### 1. Kebutuhan Sistem
- **PHP** $\ge$ 8.0 (disertai ekstensi `pdo_mysql`, `fileinfo`, `mbstring`)
- **MySQL** $\ge$ 5.7 atau **MariaDB** $\ge$ 10.3
- **Composer** (untuk dependensi PHPMailer)

### 2. Konfigurasi Database
1. Buat database atau impor berkas `database.sql`:
   ```bash
   mysql -u root -p < database.sql
   ```
2. Sesuaikan konfigurasi file `.env`:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=manager_hosting
   DB_USER=root
   DB_PASS=
   ```

### 3. Jalankan Server Lokal
Anda dapat menggunakan Laragon / Apache / Nginx, atau built-in PHP server:
```bash
php -S 127.0.0.1:8000
```
Buka browser dan akses: [http://127.0.0.1:8000](http://127.0.0.1:8000)

**Akun Login Default:**
- **Username:** `admin`
- **Password:** `admin123`

---

## Konfigurasi Cron Job (Pengingat Otomatis)

Jadwalkan eksekusi script cron setiap hari pukul 08:00 pagi menggunakan crontab di server:

```bash
0 8 * * * php /path/to/manager_hosting/cron/send_reminders.php >> /path/to/manager_hosting/cron.log 2>&1
```

Di lingkungan Windows Task Scheduler:
- **Program/script:** `php.exe`
- **Arguments:** `cron\send_reminders.php`
- **Start in:** `E:\Github\Manager_Hosting`
- **Trigger:** Harian pukul 08:00
