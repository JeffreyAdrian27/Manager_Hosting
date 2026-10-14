<?php
/**
 * Create Client Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../../includes/auth.php';
require_auth();

$activePage = 'clients';
$pageTitle = 'Tambah Klien Baru';

$pdo = getDBConnection();
$errors = [];

$formData = [
    'nama' => '',
    'no_telp' => '',
    'email' => '',
    'nama_website' => '',
    'link_hosting' => '',
    'biaya' => '',
    'tanggal_mulai' => date('Y-m-d'),
    'tanggal_expired' => date('Y-m-d', strtotime('+1 year')),
    'hosting_dimana' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Validasi keamanan CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $formData['nama'] = trim($_POST['nama'] ?? '');
        $formData['no_telp'] = trim($_POST['no_telp'] ?? '');
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['nama_website'] = trim($_POST['nama_website'] ?? '');
        $formData['link_hosting'] = trim($_POST['link_hosting'] ?? '');
        // Clean biaya from dot/comma separators
        $rawBiaya = preg_replace('/[^0-9]/', '', $_POST['biaya'] ?? '0');
        $formData['biaya'] = $rawBiaya;
        $formData['tanggal_mulai'] = trim($_POST['tanggal_mulai'] ?? '');
        $formData['tanggal_expired'] = trim($_POST['tanggal_expired'] ?? '');
        $formData['hosting_dimana'] = trim($_POST['hosting_dimana'] ?? '');

        // Validation rules
        if (empty($formData['nama'])) {
            $errors[] = 'Nama klien wajib diisi.';
        }

        if (empty($formData['no_telp'])) {
            $errors[] = 'Nomor telepon wajib diisi.';
        } elseif (!preg_match('/^(08|62|\+62)[0-9]{8,13}$/', preg_replace('/[\s\-]/', '', $formData['no_telp']))) {
            $errors[] = 'Format nomor telepon tidak valid (contoh: 08123456789 atau 628123456789).';
        }

        if (empty($formData['email'])) {
            $errors[] = 'Email wajib diisi.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format alamat email tidak valid.';
        }

        if (empty($formData['nama_website'])) {
            $errors[] = 'Nama website wajib diisi.';
        }

        if (empty($formData['link_hosting'])) {
            $errors[] = 'Link hosting / URL wajib diisi.';
        } elseif (!filter_var($formData['link_hosting'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Format link hosting harus berupa URL yang valid (menggunakan http:// atau https://).';
        }

        if ($rawBiaya <= 0) {
            $errors[] = 'Biaya hosting harus lebih dari 0.';
        }

        if (empty($formData['tanggal_mulai'])) {
            $errors[] = 'Tanggal mulai hosting wajib diisi.';
        }

        if (empty($formData['tanggal_expired'])) {
            $errors[] = 'Tanggal expired wajib diisi.';
        } elseif (!empty($formData['tanggal_mulai']) && $formData['tanggal_expired'] < $formData['tanggal_mulai']) {
            $errors[] = 'Tanggal expired harus sama atau setelah tanggal mulai hosting.';
        }

        if (empty($formData['hosting_dimana'])) {
            $errors[] = 'Nama penyedia hosting wajib diisi.';
        }

        // Handle Image Upload if present
        $uploadedFilename = null;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handle_upload_nota($_FILES['gambar']);
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $uploadedFilename = $uploadResult['filename'];
            }
        }

        // Insert into Database if no errors
        if (empty($errors)) {
            $insertSql = "
                INSERT INTO clients (
                    nama, no_telp, email, nama_website, link_hosting,
                    biaya, gambar, tanggal_mulai, tanggal_expired, hosting_dimana
                ) VALUES (
                    :nama, :no_telp, :email, :nama_website, :link_hosting,
                    :biaya, :gambar, :tanggal_mulai, :tanggal_expired, :hosting_dimana
                )
            ";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':nama' => $formData['nama'],
                ':no_telp' => $formData['no_telp'],
                ':email' => $formData['email'],
                ':nama_website' => $formData['nama_website'],
                ':link_hosting' => $formData['link_hosting'],
                ':biaya' => $formData['biaya'],
                ':gambar' => $uploadedFilename,
                ':tanggal_mulai' => $formData['tanggal_mulai'],
                ':tanggal_expired' => $formData['tanggal_expired'],
                ':hosting_dimana' => $formData['hosting_dimana'],
            ]);

            $newClientId = $pdo->lastInsertId();
            flash_message('success', 'Data klien ' . e($formData['nama']) . ' berhasil ditambahkan!');
            header('Location: ' . base_url('pages/clients/detail.php?id=' . $newClientId));
            exit;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Klien Hosting Baru</h1>
        <p class="page-desc">Lengkapi formulir di bawah ini untuk mencatat data klien dan masa aktif hosting.</p>
    </div>
    <div class="page-actions">
        <a href="<?= base_url('pages/clients/index.php') ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span>Kembali ke Daftar</span>
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <div>
            <strong>Harap periksa kesalahan berikut:</strong>
            <ul style="margin: 0.5rem 0 0 1.25rem; font-size: 0.875rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Informasi Klien &amp; Hosting</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('pages/clients/create.php') ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <div class="form-grid">
                <!-- Data Klien -->
                <div class="form-group">
                    <label for="nama" class="form-label">
                        Nama Klien <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nama" 
                        name="nama" 
                        class="form-control" 
                        placeholder="Contoh: PT Sumber Makmur / Bpk. Budi"
                        value="<?= e($formData['nama']) ?>"
                        required
                    >
                    <span class="form-helper">Nama pemilik atau penanggung jawab layanan.</span>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        Alamat Email <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="klien@example.com"
                        value="<?= e($formData['email']) ?>"
                        required
                    >
                    <span class="form-helper">Email utama yang akan menerima pengingat perpanjangan H-14 dan H-3.</span>
                </div>

                <div class="form-group">
                    <label for="no_telp" class="form-label">
                        Nomor Telepon / WhatsApp <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="no_telp" 
                        name="no_telp" 
                        class="form-control" 
                        placeholder="08123456789"
                        value="<?= e($formData['no_telp']) ?>"
                        required
                    >
                    <span class="form-helper">Format 08xx atau 62xx untuk kontak langsung.</span>
                </div>

                <!-- Data Website & Hosting -->
                <div class="form-group">
                    <label for="nama_website" class="form-label">
                        Nama Website <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nama_website" 
                        name="nama_website" 
                        class="form-control" 
                        placeholder="sumbermakmur.co.id"
                        value="<?= e($formData['nama_website']) ?>"
                        required
                    >
                    <span class="form-helper">Nama domain / label website yang muncul di template email.</span>
                </div>

                <div class="form-group">
                    <label for="link_hosting" class="form-label">
                        Link Website / Panel Hosting <span class="required">*</span>
                    </label>
                    <input 
                        type="url" 
                        id="link_hosting" 
                        name="link_hosting" 
                        class="form-control" 
                        placeholder="https://sumbermakmur.co.id"
                        value="<?= e($formData['link_hosting']) ?>"
                        required
                    >
                    <span class="form-helper">Tautan akses website klien.</span>
                </div>

                <div class="form-group">
                    <label for="hosting_dimana" class="form-label">
                        Penyedia Hosting <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="hosting_dimana" 
                        name="hosting_dimana" 
                        class="form-control" 
                        placeholder="Niagahoster, Hostinger, DomaiNesia, dll."
                        value="<?= e($formData['hosting_dimana']) ?>"
                        required
                    >
                    <span class="form-helper">Nama server provider tempat website ini di-hosting.</span>
                </div>

                <!-- Biaya & Periode -->
                <div class="form-group">
                    <label for="biaya" class="form-label">
                        Biaya Perpanjangan (Rp) <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="biaya" 
                        name="biaya" 
                        class="form-control rupiah-input" 
                        placeholder="1.500.000"
                        value="<?= !empty($formData['biaya']) ? number_format((float)$formData['biaya'], 0, ',', '.') : '' ?>"
                        required
                    >
                    <span class="form-helper">Biaya perpanjangan yang akan ditagihkan ke klien.</span>
                </div>

                <div class="form-group">
                    <label for="tanggal_mulai" class="form-label">
                        Tanggal Mulai Hosting <span class="required">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="tanggal_mulai" 
                        name="tanggal_mulai" 
                        class="form-control" 
                        value="<?= e($formData['tanggal_mulai']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="tanggal_expired" class="form-label">
                        Tanggal Expired / Jatuh Tempo <span class="required">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="tanggal_expired" 
                        name="tanggal_expired" 
                        class="form-control" 
                        value="<?= e($formData['tanggal_expired']) ?>"
                        required
                    >
                    <span class="form-helper">Harus sama atau setelah tanggal mulai hosting.</span>
                </div>
            </div>

            <!-- Upload Nota -->
            <div class="form-group" style="margin-top: 1.5rem; border-top: 1px solid var(--border-subtle); padding-top: 1.5rem;">
                <label for="gambar" class="form-label">
                    Gambar Bukti Nota / Invoice Tagihan (Opsional)
                </label>
                <input 
                    type="file" 
                    id="gambar" 
                    name="gambar" 
                    class="form-control image-preview-input" 
                    accept="image/jpeg,image/png,image/webp"
                    data-preview-target="nota-preview"
                >
                <span class="form-helper">Format JPG, PNG, atau WEBP. Maksimal ukuran 2 MB. Gambar ini akan dilampirkan ke email tagihan klien.</span>

                <div style="margin-top: 1rem;">
                    <img 
                        id="nota-preview" 
                        src="" 
                        alt="Preview Bukti Nota" 
                        style="display:none; max-width: 280px; max-height: 200px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); object-fit: cover;"
                    >
                </div>
            </div>

            <!-- Submit Button Group -->
            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" id="btn-save-client">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.1rem;height:1.1rem;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>Simpan Klien</span>
                </button>
                <a href="<?= base_url('pages/clients/index.php') ?>" class="btn btn-secondary">
                    <span>Batal</span>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
