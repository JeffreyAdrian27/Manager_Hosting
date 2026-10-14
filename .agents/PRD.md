# PRD — Sistem Manager Hosting

| | |
|---|---|
| **Versi** | 1.0 |
| **Tanggal** | 14 Oktober 2026 |
| **Tech Stack** | PHP Native, MySQL |
| **Status** | Draft |

---

## 1. Ringkasan

Sistem Manager Hosting adalah aplikasi web untuk mencatat dan memantau klien yang menggunakan layanan hosting/domain dari pemilik sistem. Sistem menyimpan data klien beserta masa aktif hostingnya, lalu **otomatis mengirim email pengingat perpanjangan** ke klien pada **H-14** dan **H-3** sebelum tanggal expired, lengkap dengan gambar bukti nota. Pemilik juga menerima salinan notifikasi agar tidak lupa menagih.

## 2. Latar Belakang & Masalah

- Data klien hosting tersebar (chat, catatan, spreadsheet) dan sulit dipantau.
- Tanggal expired sering terlewat sehingga website klien mati atau penagihan terlambat.
- Pengiriman pengingat perpanjangan dilakukan manual dan memakan waktu.

## 3. Tujuan

1. Semua data klien hosting tersimpan terpusat dan mudah dicari.
2. Pengingat perpanjangan terkirim otomatis tanpa intervensi manual.
3. Pemilik selalu mendapat notifikasi sehingga tidak ada klien yang terlewat.
4. Riwayat pengiriman email tercatat sebagai bukti.

**Di luar cakupan (v1):** pembayaran online, portal klien, integrasi WhatsApp, multi-admin dengan role berbeda.

## 4. Pengguna

| Peran | Deskripsi |
|---|---|
| **Admin (Pemilik)** | Satu-satunya pengguna sistem. Mengelola data klien, melihat status expired, mengirim email manual, dan menerima notifikasi. |
| **Klien** | Penerima email pengingat (tidak login ke sistem). |

## 5. Fitur

### 5.1 Autentikasi Admin
- Login dengan username & password (hash `password_hash()` / bcrypt).
- Logout dan session timeout.

### 5.2 Dashboard
- Total klien aktif.
- Jumlah hosting yang akan expired dalam 14 hari / 3 hari.
- Jumlah hosting yang sudah expired.
- Daftar singkat hosting terdekat expired.

### 5.3 Daftar Klien Hosting (Read)
- Tabel: nama, nama website/link, penyedia hosting, biaya, tanggal expired, sisa hari, status.
- Status berwarna: **Aktif** (hijau), **Segera Expired ≤14 hari** (kuning), **Kritis ≤3 hari** (oranye), **Expired** (merah).
- Pencarian (nama/website/email) dan filter status.
- Urut berdasarkan tanggal expired.
- Pagination.

### 5.2 Tambah / Edit / Hapus Klien (CRUD)
Field data:

| Field | Tipe | Wajib | Catatan |
|---|---|---|---|
| Nama klien | text | Ya | |
| Nomor telepon | text | Ya | Validasi angka, format 08xx / 62xx |
| Email | email | Ya | Validasi format email |
| Nama website | text | Ya | Dipakai di template email |
| Link hosting | url | Ya | Validasi URL |
| Biaya hosting | decimal | Ya | Dalam Rupiah |
| Gambar | file | Tidak | Gambar bukti nota yang dilampirkan di email (JPG/PNG, maks 2 MB) |
| Tanggal mulai hosting | date | Ya | |
| Tanggal expired | date | Ya | Harus ≥ tanggal mulai |
| Hosting dimana | text | Ya | Nama penyedia (mis. Niagahoster, Hostinger) |

- Hapus data memakai konfirmasi (soft delete disarankan).
- Fitur **Perpanjang**: memperbarui tanggal mulai/expired setelah klien membayar, dan mereset status pengingat.

### 5.3 Pengiriman Email Pengingat

**Otomatis (scheduler)**
- Dijalankan via cron job harian (mis. pukul 08:00).
- Kirim ke email klien pada **14 hari** dan **3 hari** sebelum tanggal expired.
- Setiap jenis pengingat (H-14, H-3) hanya terkirim **satu kali** per periode expired (dicegah dengan log).
- Gambar bukti nota dilampirkan/ditampilkan di email.

**Manual**
- Tombol "Kirim Email Sekarang" pada detail klien (untuk kebutuhan di luar jadwal).

**Template email**

> **Subjek:** Pengingat Perpanjangan Hosting – [Nama Website]
>
> Halo Bapak/Ibu [Nama Klien], semoga sehat selalu.
>
> Menjelang berakhirnya masa aktif website [Nama Website] pada tanggal [Tanggal Expired], kami ingin menginformasikan perihal perpanjangan layanan hosting dan domain agar website tetap aktif dan bisa diakses normal.
>
> Berikut rincian biaya perpanjangannya:
> Biaya Hosting & Pemeliharaan: Rp [Nominal]
>
> Silakan melakukan transfer ke rekening [Nama Bank / E-Wallet] [Nomor Rekening] a.n [Nama Kamu]. Jika sudah, mohon kirimkan bukti transfernya ya. Terima kasih banyak!
>
> *(lampiran: gambar bukti nota)*

**Variabel template**

| Placeholder | Sumber |
|---|---|
| [Nama Klien] | `clients.nama` |
| [Nama Website] | `clients.nama_website` |
| [Tanggal Expired] | `clients.tanggal_expired` (format: 14 Oktober 2026) |
| [Nominal] | `clients.biaya` (format: 1.500.000) |
| [Nama Bank / E-Wallet], [Nomor Rekening], [Nama Kamu] | Pengaturan sistem |

### 5.4 Notifikasi ke Admin
- Setiap kali email pengingat dikirim ke klien, admin menerima email notifikasi (ringkasan: nama klien, website, tanggal expired, jenis pengingat H-14/H-3).
- Setiap pagi (opsional) admin menerima **ringkasan harian** daftar hosting yang akan expired dalam 14 hari.
- Notifikasi gagal kirim juga dikirim ke admin.

### 5.5 Pengaturan
- Email admin (penerima notifikasi).
- Data rekening: nama bank/e-wallet, nomor rekening, atas nama.
- Konfigurasi SMTP.
- Edit isi template email.

### 5.6 Log Email
- Riwayat: waktu, klien, jenis (H-14, H-3, manual), status (berhasil/gagal), pesan error.

## 6. Alur Utama

1. Admin login → tambah data klien hosting + upload gambar nota.
2. Sistem menyimpan data dan menghitung sisa hari ke tanggal expired.
3. Cron harian menjalankan `cron/send_reminders.php`:
   - Cari klien dengan `tanggal_expired - hari ini = 14` atau `= 3`.
   - Cek `email_logs` agar tidak terkirim ganda.
   - Kirim email ke klien (+ lampiran nota) → catat log.
   - Kirim notifikasi ke admin.
4. Klien membayar → admin menekan **Perpanjang** → tanggal expired diperbarui.

## 7. Struktur Database (MySQL)

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  no_telp VARCHAR(20) NOT NULL,
  email VARCHAR(100) NOT NULL,
  nama_website VARCHAR(150) NOT NULL,
  link_hosting VARCHAR(255) NOT NULL,
  biaya DECIMAL(12,2) NOT NULL,
  gambar VARCHAR(255) NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_expired DATE NOT NULL,
  hosting_dimana VARCHAR(100) NOT NULL,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_expired (tanggal_expired)
);

CREATE TABLE email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  jenis ENUM('H-14','H-3','manual') NOT NULL,
  tanggal_expired DATE NOT NULL,          -- periode expired saat email dikirim
  status ENUM('berhasil','gagal') NOT NULL,
  pesan_error TEXT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_unique_reminder (client_id, jenis, tanggal_expired)
);

CREATE TABLE settings (
  `key` VARCHAR(50) PRIMARY KEY,
  `value` TEXT NOT NULL
);
-- key: admin_email, nama_bank, no_rekening, atas_nama,
--      smtp_host, smtp_port, smtp_user, smtp_pass, template_email
```

## 8. Arsitektur & Struktur Folder

```
hosting-manager/
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   └── mailer.php          # wrapper PHPMailer
├── pages/
│   ├── login.php
│   ├── dashboard.php
│   ├── clients/            # index, create, edit, detail, renew
│   ├── logs.php
│   └── settings.php
├── cron/
│   └── send_reminders.php
├── uploads/nota/
├── assets/
└── index.php
```

- **Library email:** PHPMailer via SMTP (Gmail/SMTP hosting) — fungsi `mail()` bawaan tidak disarankan karena sering masuk spam.
- **Akses database:** PDO dengan prepared statements.
- **Cron:** `0 8 * * * php /path/hosting-manager/cron/send_reminders.php`

## 9. Aturan Bisnis

1. Pengingat H-14 dikirim saat `DATEDIFF(tanggal_expired, CURDATE()) = 14`; H-3 saat `= 3`.
2. Pengingat tidak dikirim ganda untuk kombinasi `client_id + jenis + tanggal_expired`.
3. Jika cron terlewat satu hari, sistem tetap mengirim pengingat yang belum pernah terkirim selama sisa hari ≥ 0 (catch-up).
4. Setelah **Perpanjang**, tanggal expired baru dianggap periode baru sehingga H-14 dan H-3 aktif kembali.
5. Klien yang sudah expired tidak dikirimi pengingat otomatis, tetapi tampil merah di dashboard.
6. Klien yang di-soft delete tidak diikutkan dalam pengingat.

## 10. Kebutuhan Non-Fungsional

- **Keamanan:** hash password, prepared statements (anti SQL injection), escape output (anti XSS), token CSRF pada form, validasi tipe & ukuran file upload (rename file acak, simpan di luar eksekusi script), kredensial SMTP tidak di-hardcode di repo.
- **Performa:** halaman daftar dimuat < 2 detik untuk hingga ±1.000 klien.
- **Keandalan:** kegagalan kirim email dicatat dan diberitahukan ke admin; tidak menghentikan proses klien lain.
- **Kompatibilitas:** PHP ≥ 8.0, MySQL ≥ 5.7 / MariaDB ≥ 10.3, responsif di desktop & mobile.
- **Zona waktu:** Asia/Jakarta (WIB).

## 11. Kriteria Penerimaan

- [ ] Admin dapat login dan melihat daftar klien beserta status expired.
- [ ] Admin dapat menambah, mengubah, menghapus klien dengan semua field yang diminta, termasuk upload gambar.
- [ ] Email H-14 dan H-3 terkirim otomatis dengan template dan variabel terisi benar, beserta gambar nota.
- [ ] Email tidak terkirim ganda untuk periode yang sama.
- [ ] Admin menerima notifikasi email setiap pengingat dikirim.
- [ ] Log email menampilkan status berhasil/gagal.
- [ ] Fitur Perpanjang mereset siklus pengingat.

## 12. Rencana Pengerjaan

| Tahap | Cakupan |
|---|---|
| 1 | Setup proyek, database, login |
| 2 | CRUD klien + upload gambar + dashboard |
| 3 | Integrasi PHPMailer, template email, kirim manual |
| 4 | Cron pengingat H-14 / H-3, notifikasi admin, log |
| 5 | Pengaturan, fitur Perpanjang, pengujian & deploy |

## 13. Pertanyaan Terbuka

1. Gambar bukti nota: apakah satu gambar per klien, atau nota baru tiap periode perpanjangan?
2. Gambar dikirim sebagai lampiran atau ditampilkan langsung di badan email?
3. Apakah perlu pengingat tambahan di luar H-14 dan H-3 (mis. H-1 atau saat sudah expired)?
4. Apakah perlu kirim lewat WhatsApp di versi berikutnya?
5. Apakah hosting akan dijalankan di shared hosting yang mendukung cron job?
