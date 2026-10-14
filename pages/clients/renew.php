<?php
/**
 * Renew Client Hosting Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../../includes/auth.php';
require_auth();

$activePage = 'clients';
$pageTitle = 'Perpanjang Hosting Klien';

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

// Suggested new dates: Start = old expiration date, End = old expiration date + 1 year
$defaultStartDate = $client['tanggal_expired'];
$defaultEndDate = date('Y-m-d', strtotime($defaultStartDate . ' +1 year'));

$formData = [
    'tanggal_mulai' => $defaultStartDate,
    'tanggal_expired' => $defaultEndDate,
    'biaya' => $client['biaya'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Validasi keamanan CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $formData['tanggal_mulai'] = trim($_POST['tanggal_mulai'] ?? '');
        $formData['tanggal_expired'] = trim($_POST['tanggal_expired'] ?? '');
        $rawBiaya = preg_replace('/[^0-9]/', '', $_POST['biaya'] ?? '0');
        $formData['biaya'] = $rawBiaya;

        if (empty($formData['tanggal_mulai'])) {
            $errors[] = 'Tanggal mulai periode baru wajib diisi.';
        }

        if (empty($formData['tanggal_expired'])) {
            $errors[] = 'Tanggal expired baru wajib diisi.';
        } elseif (!empty($formData['tanggal_mulai']) && $formData['tanggal_expired'] <= $formData['tanggal_mulai']) {
            $errors[] = 'Tanggal expired baru harus lebih lambat dari tanggal mulai.';
        }

        if ($rawBiaya <= 0) {
            $errors[] = 'Biaya perpanjangan harus lebih dari 0.';
        }

        // Handle Optional New Nota Image
        $uploadedFilename = $client['gambar'];
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handle_upload_nota($_FILES['gambar']);
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $uploadedFilename = $uploadResult['filename'];
            }
        }

        if (empty($errors)) {
            $updateSql = "
                UPDATE clients SET
                    tanggal_mulai = :tanggal_mulai,
                    tanggal_expired = :tanggal_expired,
                    biaya = :biaya,
                    gambar = :gambar
                WHERE id = :id AND deleted_at IS NULL
            ";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':tanggal_mulai' => $formData['tanggal_mulai'],
                ':tanggal_expired' => $formData['tanggal_expired'],
                ':biaya' => $formData['biaya'],
                ':gambar' => $uploadedFilename,
                ':id' => $clientId,
            ]);

            flash_message('success', 'Hosting klien ' . e($client['nama']) . ' berhasil diperpanjang hingga ' . format_tanggal($formData['tanggal_expired']) . '. Siklus pengingat otomatis telah direset untuk periode baru!');
            header('Location: ' . base_url('pages/clients/detail.php?id=' . $clientId));
            exit;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Perpanjang Hosting: <?= e($client['nama']) ?></h1>
        <p class="page-desc">Catat konfirmasi pembayaran dan perbarui masa aktif hosting klien untuk periode berikutnya.</p>
    </div>
    <div class="page-actions">
        <a href="<?= base_url('pages/clients/detail.php?id=' . $clientId) ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span>Kembali</span>
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

<div class="card" style="max-width: 760px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Konfirmasi Perpanjangan Periode Baru</h2>
    </div>
    <div class="card-body">
        <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.35rem;">Periode Saat Ini:</div>
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                <div>Mulai: <strong><?= format_tanggal($client['tanggal_mulai']) ?></strong></div>
                <div>Expired: <strong style="color: #fb7185;"><?= format_tanggal($client['tanggal_expired']) ?></strong></div>
                <div>Website: <strong><?= e($client['nama_website']) ?></strong></div>
            </div>
        </div>

        <form method="POST" action="<?= base_url('pages/clients/renew.php?id=' . $clientId) ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai Periode Baru <span class="required">*</span></label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="<?= e($formData['tanggal_mulai']) ?>" required>
                    <span class="form-helper">Biasanya sama dengan tanggal expired periode sebelumnya.</span>
                </div>

                <div class="form-group">
                    <label for="tanggal_expired" class="form-label">Tanggal Expired Baru <span class="required">*</span></label>
                    <input type="date" id="tanggal_expired" name="tanggal_expired" class="form-control" value="<?= e($formData['tanggal_expired']) ?>" required>
                    <span class="form-helper">Batas akhir periode baru setelah perpanjangan.</span>
                </div>
            </div>

            <div class="form-group">
                <label for="biaya" class="form-label">Nominal Pembayaran / Biaya Perpanjangan (Rp) <span class="required">*</span></label>
                <input type="text" id="biaya" name="biaya" class="form-control rupiah-input" value="<?= number_format((float)$formData['biaya'], 0, ',', '.') ?>" required>
                <span class="form-helper">Biaya yang telah disetujui / dibayar klien untuk perpanjangan ini.</span>
            </div>

            <div class="form-group" style="margin-top: 1.5rem; border-top: 1px solid var(--border-subtle); padding-top: 1.5rem;">
                <label for="gambar" class="form-label">Unggah Bukti Nota / Kwitansi Baru (Opsional)</label>
                <input type="file" id="gambar" name="gambar" class="form-control image-preview-input" accept="image/jpeg,image/png,image/webp" data-preview-target="renew-preview">
                <span class="form-helper">Jika diunggah, gambar nota ini akan menjadi bukti nota aktif untuk periode ini.</span>

                <div style="margin-top: 1rem;">
                    <img id="renew-preview" src="" alt="Preview Nota Baru" style="display:none; max-width: 260px; max-height: 180px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); object-fit: cover;">
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-success" id="btn-submit-renew" style="min-width: 180px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.1rem;height:1.1rem;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Simpan Perpanjangan</span>
                </button>
                <a href="<?= base_url('pages/clients/detail.php?id=' . $clientId) ?>" class="btn btn-secondary">
                    <span>Batal</span>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
