<?php
/**
 * Edit Client Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../../includes/auth.php';
require_auth();

$activePage = 'clients';
$pageTitle = 'Edit Klien Hosting';

$pdo = getDBConnection();
$clientId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id AND deleted_at IS NULL LIMIT 1");
$stmt->execute([':id' => $clientId]);
$client = $stmt->fetch();

if (!$client) {
    flash_message('danger', 'Data klien tidak ditemukan atau telah dihapus.');
    header('Location: ' . base_url('pages/clients/index.php'));
    exit;
}

$errors = [];
$formData = $client;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Validasi keamanan CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $formData['nama'] = trim($_POST['nama'] ?? '');
        $formData['no_telp'] = trim($_POST['no_telp'] ?? '');
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['nama_website'] = trim($_POST['nama_website'] ?? '');
        $formData['link_hosting'] = trim($_POST['link_hosting'] ?? '');
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
            $errors[] = 'Format nomor telepon tidak valid.';
        }

        if (empty($formData['email'])) {
            $errors[] = 'Email wajib diisi.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }

        if (empty($formData['nama_website'])) {
            $errors[] = 'Nama website wajib diisi.';
        }

        if (empty($formData['link_hosting'])) {
            $errors[] = 'Link hosting wajib diisi.';
        } elseif (!filter_var($formData['link_hosting'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Format link hosting harus berupa URL yang valid.';
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
        $uploadedFilename = $client['gambar'];
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handle_upload_nota($_FILES['gambar']);
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $uploadedFilename = $uploadResult['filename'];
            }
        }

        // If user wants to delete image
        if (!empty($_POST['hapus_gambar'])) {
            $uploadedFilename = null;
        }

        // Update Database if no errors
        if (empty($errors)) {
            $updateSql = "
                UPDATE clients SET
                    nama = :nama,
                    no_telp = :no_telp,
                    email = :email,
                    nama_website = :nama_website,
                    link_hosting = :link_hosting,
                    biaya = :biaya,
                    gambar = :gambar,
                    tanggal_mulai = :tanggal_mulai,
                    tanggal_expired = :tanggal_expired,
                    hosting_dimana = :hosting_dimana
                WHERE id = :id AND deleted_at IS NULL
            ";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
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
                ':id' => $clientId,
            ]);

            flash_message('success', 'Perubahan data klien berhasil disimpan!');
            header('Location: ' . base_url('pages/clients/detail.php?id=' . $clientId));
            exit;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Data Klien: <?= e($client['nama']) ?></h1>
        <p class="page-desc">Perbarui informasi klien, link hosting, biaya, atau tanggal jatuh tempo.</p>
    </div>
    <div class="page-actions">
        <a href="<?= base_url('pages/clients/detail.php?id=' . $clientId) ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span>Kembali ke Detail</span>
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
        <h2 class="card-title">Formulir Perubahan Data</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('pages/clients/edit.php?id=' . $clientId) ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <div class="form-grid">
                <!-- Data Klien -->
                <div class="form-group">
                    <label for="nama" class="form-label">Nama Klien <span class="required">*</span></label>
                    <input type="text" id="nama" name="nama" class="form-control" value="<?= e($formData['nama']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= e($formData['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="no_telp" class="form-label">Nomor Telepon / WA <span class="required">*</span></label>
                    <input type="text" id="no_telp" name="no_telp" class="form-control" value="<?= e($formData['no_telp']) ?>" required>
                </div>

                <!-- Website & Hosting -->
                <div class="form-group">
                    <label for="nama_website" class="form-label">Nama Website <span class="required">*</span></label>
                    <input type="text" id="nama_website" name="nama_website" class="form-control" value="<?= e($formData['nama_website']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="link_hosting" class="form-label">Link Hosting <span class="required">*</span></label>
                    <input type="url" id="link_hosting" name="link_hosting" class="form-control" value="<?= e($formData['link_hosting']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="hosting_dimana" class="form-label">Penyedia Hosting <span class="required">*</span></label>
                    <input type="text" id="hosting_dimana" name="hosting_dimana" class="form-control" value="<?= e($formData['hosting_dimana']) ?>" required>
                </div>

                <!-- Biaya & Periode -->
                <div class="form-group">
                    <label for="biaya" class="form-label">Biaya Perpanjangan (Rp) <span class="required">*</span></label>
                    <input type="text" id="biaya" name="biaya" class="form-control rupiah-input" value="<?= number_format((float)$formData['biaya'], 0, ',', '.') ?>" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai Hosting <span class="required">*</span></label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="<?= e($formData['tanggal_mulai']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_expired" class="form-label">Tanggal Expired <span class="required">*</span></label>
                    <input type="date" id="tanggal_expired" name="tanggal_expired" class="form-control" value="<?= e($formData['tanggal_expired']) ?>" required>
                </div>
            </div>

            <!-- Upload Nota Section -->
            <div class="form-group" style="margin-top: 1.5rem; border-top: 1px solid var(--border-subtle); padding-top: 1.5rem;">
                <label for="gambar" class="form-label">Bukti Nota / Invoice Tagihan</label>
                
                <?php if (!empty($client['gambar'])): ?>
                    <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem;">
                        <img 
                            src="<?= base_url('uploads/nota/' . e($client['gambar'])) ?>" 
                            alt="Nota saat ini" 
                            style="width: 120px; height: 90px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);"
                        >
                        <div>
                            <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 500;">Gambar Nota Aktif</div>
                            <label style="font-size: 0.825rem; color: #fb7185; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; margin-top: 0.25rem;">
                                <input type="checkbox" name="hapus_gambar" value="1">
                                <span>Hapus gambar ini</span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <input 
                    type="file" 
                    id="gambar" 
                    name="gambar" 
                    class="form-control image-preview-input" 
                    accept="image/jpeg,image/png,image/webp"
                    data-preview-target="edit-preview"
                >
                <span class="form-helper">Upload gambar baru jika ingin mengganti nota lama. Format JPG/PNG/WEBP maks 2 MB.</span>

                <div style="margin-top: 1rem;">
                    <img id="edit-preview" src="" alt="Preview Baru" style="display:none; max-width: 260px; max-height: 180px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); object-fit: cover;">
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.1rem;height:1.1rem;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>Simpan Perubahan</span>
                </button>
                <a href="<?= base_url('pages/clients/detail.php?id=' . $clientId) ?>" class="btn btn-secondary">
                    <span>Batal</span>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
