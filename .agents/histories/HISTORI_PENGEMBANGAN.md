# Histori & Rangkuman Proyek: Sistem Manager Hosting

> **Catatan Konteks:** Dokumen ini dibuat sebagai rangkuman lengkap dari seluruh percakapan dan tahapan pengerjaan proyek, sehingga sesi chat baru dapat langsung memahami arsitektur, histori keputusan teknis, konfigurasi kredensial, dan status deployment terakhir.

---

## 1. Ikhtisar Proyek & Dokumen Acuan

* **Nama Proyek:** Sistem Manager Hosting
* **Spesifikasi Acuan:** Berkas PRD di [.agents/PRD.md](file:///e:/Github/Manager_Hosting/.agents/PRD.md)
* **Tech Stack:**
  * **Backend:** PHP Native ($\ge$ 8.0, PDO dengan *prepared statements*, CSRF protection, session auth)
  * **Database:** MySQL $\ge$ 5.7 / MariaDB $\ge$ 10.3
  * **Email Library:** PHPMailer (v7.1.1 via Composer)
  * **Zona Waktu:** `Asia/Jakarta` (WIB)
* **Design Principles (Skills Terpasang):**
  * `impeccable`: Mode **Operate** untuk antarmuka dashboard admin & kepadatan data.
  * `taste-skill`: Mencegah tampilan generik (*anti AI-slop*), menghindari hitam pekat `#000000`, tata letak Slate Dark Theme modern.
  * `ui-ux-pro-max`: Kontras aksesibilitas tinggi ($\ge 4.5:1$), target interaksi $\ge 44\text{ px}$, validasi form jelas.
  * `simple-english`: Struktur bahasa lugas, rapi, dan mudah dipahami.

---

## 2. Struktur Direktori Proyek

```
e:\Github\Manager_Hosting/
├── assets/
│   ├── css/
│   │   └── app.css             # Desain Slate Dark Theme, status badge, form, responsive
│   └── js/
│       └── app.js              # Interaktivitas UI & format rupiah real-time
├── config/
│   └── database.php            # Koneksi PDO mandiri (kebal getenv nonaktif di shared hosting)
├── cron/
│   └── send_reminders.php      # Eksekutor pengingat otomatis H-14 & H-3 (CLI & Web trigger)
├── includes/
│   ├── auth.php                # Proteksi login, sesi 2 jam, helper current_user
│   ├── functions.php           # Helper XSS, CSRF, format tanggal Indonesia, format rupiah, upload nota
│   ├── header.php              # Header navigasi & alert banner
│   ├── footer.php              # Footer hak cipta ("© Jeffrey Adrian. Sistem Manager Hosting.")
│   └── mailer.php              # Wrapper PHPMailer, template email replacement, attachment nota
├── pages/
│   ├── clients/
│   │   ├── create.php          # Tambah data klien + upload nota
│   │   ├── delete.php          # Soft delete klien
│   │   ├── detail.php          # Rincian klien, bukti nota, riwayat email, kirim manual
│   │   ├── edit.php            # Ubah data klien & ganti nota
│   │   ├── index.php           # Tabel daftar klien, filter status, search, pagination
│   │   ├── renew.php           # Perpanjang hosting & reset siklus pengingat
│   │   └── send_email.php      # Handler aksi tombol "Kirim Email Sekarang"
│   ├── dashboard.php           # 4 kartu metrik, hosting terdekat expired, log terbaru
│   ├── login.php               # Halaman login dengan toggle ikon mata (show/hide password)
│   ├── logout.php              # Handler keluar & redirect ke root URL
│   ├── logs.php                # Tabel riwayat log email (filter jenis & status)
│   └── settings.php            # Pengaturan data rekening, konfigurasi SMTP, uji kirim
├── uploads/
│   └── nota/                   # Direktori berkas gambar bukti nota/invoice (.gitkeep)
├── .agents/
│   ├── PRD.md                  # Dokumen Product Requirements Document asli
│   └── histories/
│       └── HISTORI_PENGEMBANGAN.md # Dokumen histori ini
├── .env                        # Konfigurasi environment lokal
├── .env.example                # Template acuan environment
├── .env.infinityfree           # Template acuan khusus hosting InfinityFree
├── database.sql                # Skema database awal (lengkap dengan CREATE DATABASE)
├── database_infinityfree.sql   # Skema khusus InfinityFree (langsung CREATE TABLE)
├── jalankan_cron.bat           # Pintasan eksekusi cron di Windows
├── index.php                   # Entry point root: menyajikan login langsung tanpa redirect
└── README.md                   # Dokumentasi instalasi dan penggunaan
```

---

## 3. Skema & Data Database

### Tabel:
1. **`users`**: Akun admin (id, username, password bcrypt, created_at).
2. **`clients`**: Data klien (id, nama, no_telp, email, nama_website, link_hosting, biaya, gambar, tanggal_mulai, tanggal_expired, hosting_dimana, deleted_at, created_at, updated_at).
3. **`email_logs`**: Catatan pengiriman (id, client_id, jenis [H-14, H-3, manual], tanggal_expired, status [berhasil, gagal], pesan_error, sent_at).
4. **`settings`**: Pasangan key-value (admin_email, nama_bank, no_rekening, atas_nama, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, template_email).

### Kredensial Login Default:
* **Username:** `admin`
* **Password:** `admin123`
*(Hash bcrypt: `$2y$10$z8Br6up1WafdDC8WDueNNeYvWGouF7rosaB1Na4fyzqmtLU3DWI/.`)*

### Kredensial SMTP Email Pemilik yang Digunakan:
* **Email Admin / SMTP User:** `jeffreyadrian27@gmail.com`
* **App Password Google:** `gubn derv uues xlik` *(tanpa spasi: `gubndervuuesxlik`)*
* **SMTP Host:** `smtp.gmail.com`
* **SMTP Port:** `587`
* **Enkripsi:** `TLS`

*(Catatan: Tabel `clients` dan `email_logs` telah dikosongkan/reset atas permintaan pengguna agar bersih untuk data asli).*

---

## 4. Mekanisme Pengingat Otomatis (Cron Job)

* **Skrip:** [cron/send_reminders.php](file:///e:/Github/Manager_Hosting/cron/send_reminders.php)
* **Aturan Bisnis:**
  * **H-14:** Mengirim email saat sisa hari masa aktif antara 4 s/d 14 hari, dan belum pernah terkirim pengingat H-14 untuk periode expired yang bersangkutan.
  * **H-3:** Mengirim email saat sisa hari antara 0 s/d 3 hari, dan belum pernah terkirim pengingat H-3 untuk periode expired yang bersangkutan.
  * **Anti-Duplikasi:** Pengecekan ke tabel `email_logs` mencegah pengiriman ganda.
  * **Tembusan Admin:** Salinan notifikasi pengiriman dikirim ke email admin.
  * **Lampiran Nota:** Otomatis melampirkan gambar nota jika klien memilikinya.
* **Metode Eksekusi:**
  1. **Lokal (Windows):** Windows Task Scheduler (Daily 08:00 WIB) atau klik ganda [jalankan_cron.bat](file:///e:/Github/Manager_Hosting/jalankan_cron.bat).
  2. **cPanel / Linux:** Cron command `0 8 * * * php /path/to/cron/send_reminders.php`.
  3. **Web Cron / InfinityFree (cron-job.org):** Trigger URL harian ke `https://managerhosting.freedev.app/cron/send_reminders.php?cron_key=bebas`.

---

## 5. Histori Masalah & Keputusan Teknis Penting

### A. Perbaikan URL & Navigasi Root
* **Isu:** Pengguna ingin URL `https://managerhosting.freedev.app` langsung membuka halaman login tanpa berubah menjadi `.../pages/login.php`.
* **Solusi:** [index.php](file:///e:/Github/Manager_Hosting/index.php) diubah untuk langsung memanggil `require_once __DIR__ . '/pages/login.php'` jika belum login (bukan redirect header). Formulir login disesuaikan action-nya ke `REQUEST_URI` agar submit tetap di URL yang sama.

### B. Toggle Ikon Mata di Halaman Login
* **Isu:** Pengguna meminta tombol/ikon mata untuk melihat password saat login.
* **Solusi:** Ditambahkan container password dengan toggle tombol SVG mata terbuka / mata tercoret di [pages/login.php](file:///e:/Github/Manager_Hosting/pages/login.php) lengkap dengan javascript interaktif dan aksesibilitas `aria-label`.

### C. Ikon Tanda Seru pada Kartu "Kritis (≤3 Hari)"
* **Isu:** Pengguna meminta tanda seru (`!`) pada kartu metrik Kritis dipertegas.
* **Solusi:** SVG segitiga di [pages/dashboard.php](file:///e:/Github/Manager_Hosting/pages/dashboard.php) diperbarui dengan garis `stroke-width="2.8"` dan titik lingkaran bawah `circle r="1.3" fill="currentColor"` agar terlihat tegas dan kontras.

### D. Deployment ke InfinityFree (`managerhosting.freedev.app`)
* **Isu Error 404:**
  1. Di InfinityFree, direktif `Options -Indexes` pada `.htaccess` dilarang dan menyebabkan Apache melempar error yang dialihkan ke halaman custom 404 iFastNet.
  2. Pengguna sempat mengira ada folder terpisah `managerhosting.freedev.app`, namun terkonfirmasi bahwa folder dokumennya adalah `Home / htdocs`.
* **Isu Koneksi Database:**
  1. InfinityFree tidak mengizinkan query `CREATE DATABASE` (harus dibuat via vPanel). Berkas [database_infinityfree.sql](file:///e:/Github/Manager_Hosting/database_infinityfree.sql) disediakan khusus untuk diimpor langsung via phpMyAdmin.
  2. Server gratis InfinityFree sering menonaktifkan fungsi `getenv()` / `putenv()`. Berkas [config/database.php](file:///e:/Github/Manager_Hosting/config/database.php) diperbarui dengan *custom env parser* mandiri yang membaca berkas `.env` secara langsung dari filesystem.

### E. Data Akun InfinityFree yang Terhubung:
* **Domain:** `https://managerhosting.freedev.app`
* **MySQL Host:** `sql108.infinityfree.com`
* **MySQL User:** `if0_43111975`
* **MySQL DB Name:** `if0_43111975_manager_hosting`
* **MySQL Port:** `3306`

---

## 6. Status Akhir Proyek

* Aplikasi berfungsi penuh secara lokal (`php -S 127.0.0.1:8000`) maupun online (`managerhosting.freedev.app`).
* Pengujian pengiriman email via SMTP Google telah berhasil terverifikasi.
* Database telah disiapkan dan tabel transaksi siap digunakan untuk entri data riil.
