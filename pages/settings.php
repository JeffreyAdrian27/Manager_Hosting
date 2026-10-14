<?php
/**
 * System Settings Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
require_auth();

$activePage = 'settings';
$pageTitle = 'Pengaturan Sistem';

$pdo = getDBConnection();
$errors = [];
$successMessage = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Validasi keamanan CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $action = $_POST['action'] ?? 'save_settings';

        if ($action === 'save_settings') {
            $settingsToUpdate = [
                'admin_email'    => trim($_POST['admin_email'] ?? ''),
                'nama_bank'      => trim($_POST['nama_bank'] ?? ''),
                'no_rekening'    => trim($_POST['no_rekening'] ?? ''),
                'atas_nama'      => trim($_POST['atas_nama'] ?? ''),
                'smtp_host'      => trim($_POST['smtp_host'] ?? ''),
                'smtp_port'      => trim($_POST['smtp_port'] ?? '587'),
                'smtp_user'      => trim($_POST['smtp_user'] ?? ''),
                'smtp_secure'    => trim($_POST['smtp_secure'] ?? 'tls'),
                'template_email' => trim($_POST['template_email'] ?? ''),
            ];

            // Only update password if provided (not empty)
            if (!empty($_POST['smtp_pass'])) {
                $settingsToUpdate['smtp_pass'] = trim($_POST['smtp_pass']);
            }

            // Validations
            if (empty($settingsToUpdate['admin_email']) || !filter_var($settingsToUpdate['admin_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Alamat email admin harus berupa format email yang valid.';
            }

            if (empty($settingsToUpdate['template_email'])) {
                $errors[] = 'Template email tidak boleh kosong.';
            }

            if (empty($errors)) {
                foreach ($settingsToUpdate as $key => $val) {
                    update_setting($key, $val);
                }
                flash_message('success', 'Pengaturan sistem berhasil disimpan.');
                header('Location: ' . base_url('pages/settings.php'));
                exit;
            }
        } elseif ($action === 'test_smtp') {
            // Test SMTP Email sending to admin_email
            $testRecipient = get_setting('admin_email');
            if (empty($testRecipient) || !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email admin belum diisi dengan benar untuk menerima email pengujian.';
            } else {
                try {
                    $mail = create_configured_mailer();
                    $mail->addAddress($testRecipient, 'Admin');
                    $mail->isHTML(true);
                    $mail->Subject = 'Uji Coba Koneksi SMTP - Sistem Manager Hosting';
                    $mail->Body = '<h3>Koneksi SMTP Berhasil!</h3><p>Ini adalah email pengujian untuk memverifikasi bahwa konfigurasi SMTP server pada Sistem Manager Hosting telah berfungsi dengan baik.</p><p>Waktu pengiriman: ' . date('d F Y H:i:s') . ' WIB</p>';
                    $mail->AltBody = 'Uji coba koneksi SMTP berhasil pada ' . date('d F Y H:i:s') . ' WIB.';
                    $mail->send();
                    flash_message('success', 'Uji coba SMTP BERHASIL! Email tes telah dikirim ke ' . e($testRecipient) . '.');
                    header('Location: ' . base_url('pages/settings.php'));
                    exit;
                } catch (\Exception $e) {
                    $errors[] = 'Uji coba SMTP GAGAL: ' . $e->getMessage();
                }
            }
        }
    }
}

// Current Settings
$adminEmail = get_setting('admin_email', 'admin@example.com');
$namaBank = get_setting('nama_bank', 'BCA');
$noRekening = get_setting('no_rekening', '1234567890');
$atasNama = get_setting('atas_nama', 'Admin Hosting');
$smtpHost = get_setting('smtp_host', 'smtp.gmail.com');
$smtpPort = get_setting('smtp_port', '587');
$smtpUser = get_setting('smtp_user', '');
$smtpPass = get_setting('smtp_pass', '');
$smtpSecure = get_setting('smtp_secure', 'tls');
$templateEmail = get_setting('template_email', '');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Pengaturan Sistem</h1>
        <p class="page-desc">Konfigurasi SMTP, akun rekening pembayaran, dan template pesan pengingat tagihan.</p>
    </div>
    <div class="page-actions">
        <!-- Test SMTP Form -->
        <form method="POST" action="<?= base_url('pages/settings.php') ?>" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="test_smtp">
            <button type="submit" class="btn btn-secondary" id="btn-test-smtp">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                <span>Uji Coba Kirim Email (SMTP)</span>
            </button>
        </form>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <div>
            <strong>Gagal menyimpan pengaturan:</strong>
            <ul style="margin: 0.5rem 0 0 1.25rem; font-size: 0.875rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= base_url('pages/settings.php') ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_settings">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Card 1: Data Rekening & Admin -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Data Rekening &amp; Admin</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="admin_email" class="form-label">
                        Email Admin (Penerima Notifikasi) <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="admin_email" 
                        name="admin_email" 
                        class="form-control" 
                        value="<?= e($adminEmail) ?>"
                        required
                    >
                    <span class="form-helper">Salinan pengingat H-14 dan H-3 akan dikirimkan ke alamat email ini.</span>
                </div>

                <div class="form-group">
                    <label for="nama_bank" class="form-label">
                        Nama Bank / E-Wallet <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nama_bank" 
                        name="nama_bank" 
                        class="form-control" 
                        placeholder="BCA, Mandiri, BRI, GoPay, OVO, dll."
                        value="<?= e($namaBank) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="no_rekening" class="form-label">
                        Nomor Rekening / Akun <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="no_rekening" 
                        name="no_rekening" 
                        class="form-control" 
                        value="<?= e($noRekening) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="atas_nama" class="form-label">
                        Atas Nama Rekening <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="atas_nama" 
                        name="atas_nama" 
                        class="form-control" 
                        value="<?= e($atasNama) ?>"
                        required
                    >
                </div>
            </div>
        </div>

        <!-- Card 2: Konfigurasi SMTP Server -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Konfigurasi SMTP Email</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="smtp_host" class="form-label">
                        SMTP Host <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="smtp_host" 
                        name="smtp_host" 
                        class="form-control" 
                        placeholder="smtp.gmail.com / mail.domainanda.com"
                        value="<?= e($smtpHost) ?>"
                        required
                    >
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="smtp_port" class="form-label">
                            SMTP Port <span class="required">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="smtp_port" 
                            name="smtp_port" 
                            class="form-control" 
                            placeholder="587"
                            value="<?= e($smtpPort) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="smtp_secure" class="form-label">
                            Enkripsi
                        </label>
                        <select id="smtp_secure" name="smtp_secure" class="form-control">
                            <option value="tls" <?= strtolower($smtpSecure) === 'tls' ? 'selected' : '' ?>>TLS (Port 587)</option>
                            <option value="ssl" <?= strtolower($smtpSecure) === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                            <option value="none" <?= strtolower($smtpSecure) === 'none' ? 'selected' : '' ?>>None (Tidak Terenkripsi)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="smtp_user" class="form-label">
                        SMTP Username / Email Pengirim
                    </label>
                    <input 
                        type="text" 
                        id="smtp_user" 
                        name="smtp_user" 
                        class="form-control" 
                        placeholder="email@gmail.com"
                        value="<?= e($smtpUser) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="smtp_pass" class="form-label">
                        SMTP Password / App Password
                    </label>
                    <input 
                        type="password" 
                        id="smtp_pass" 
                        name="smtp_pass" 
                        class="form-control" 
                        placeholder="<?= !empty($smtpPass) ? '•••••••• (Biarkan kosong jika tidak ingin mengubah)' : 'Masukkan password SMTP' ?>"
                    >
                    <span class="form-helper">Untuk Gmail, gunakan <em>App Password</em> 16 digit dari Google Account Security.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Template Email -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Template Email Pengingat Klien</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">Kustomisasi teks pemberitahuan tagihan yang dikirim ke klien</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="template_email" class="form-label">
                    Isi Template Pesan <span class="required">*</span>
                </label>
                <textarea 
                    id="template_email" 
                    name="template_email" 
                    class="form-control" 
                    rows="8"
                    style="font-family: inherit; font-size: 0.925rem; line-height: 1.6;"
                    required
                ><?= e($templateEmail) ?></textarea>
            </div>

            <!-- Placeholder Chips / Reference Table -->
            <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); padding: 1.25rem; border-radius: var(--radius-md);">
                <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.75rem;">
                    Variabel Placeholder yang Tersedia:
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; font-size: 0.825rem;">
                    <code style="background: var(--bg-app); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); color: #60a5fa;">[Nama Klien]</code>
                    <code style="background: var(--bg-app); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); color: #60a5fa;">[Nama Website]</code>
                    <code style="background: var(--bg-app); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); color: #60a5fa;">[Tanggal Expired]</code>
                    <code style="background: var(--bg-app); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); color: #60a5fa;">[Nominal]</code>
                    <code style="background: var(--bg-app); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); color: #60a5fa;">[Nama Bank / E-Wallet]</code>
                    <code style="background: var(--bg-app); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); color: #60a5fa;">[Nomor Rekening]</code>
                    <code style="background: var(--bg-app); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); color: #60a5fa;">[Nama Kamu]</code>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div style="display: flex; gap: 0.75rem;">
        <button type="submit" class="btn btn-primary" id="btn-save-settings">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.1rem;height:1.1rem;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            <span>Simpan Seluruh Pengaturan</span>
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
