<?php
/**
 * Client Detail Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../../includes/auth.php';
require_auth();

$activePage = 'clients';
$pageTitle = 'Detail Klien Hosting';

$pdo = getDBConnection();
$clientId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT 
        c.*, 
        DATEDIFF(c.tanggal_expired, CURDATE()) AS sisa_hari
    FROM clients c 
    WHERE c.id = :id AND c.deleted_at IS NULL 
    LIMIT 1
");
$stmt->execute([':id' => $clientId]);
$client = $stmt->fetch();

if (!$client) {
    flash_message('danger', 'Data klien tidak ditemukan atau telah dihapus.');
    header('Location: ' . base_url('pages/clients/index.php'));
    exit;
}

$sisaHari = (int)$client['sisa_hari'];
$status = get_status_info($sisaHari);

// Initials for avatar
$words = explode(' ', trim($client['nama']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'CL';
}

// WhatsApp clean phone number
$waNumber = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $client['no_telp']));

// Fetch Email Logs for this client
$logsStmt = $pdo->prepare("
    SELECT * 
    FROM email_logs 
    WHERE client_id = :id 
    ORDER BY sent_at DESC
");
$logsStmt->execute([':id' => $clientId]);
$clientLogs = $logsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Scoped Self-Contained Styles for Detail Page -->
<style>
.client-hero-card {
  background: linear-gradient(135deg, rgba(22, 32, 50, 0.9), rgba(17, 24, 39, 0.98));
  border: 1px solid var(--border-subtle, #1f293d);
  border-radius: 14px;
  padding: 1.5rem 1.75rem;
  margin-bottom: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.25rem;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.25);
}

.client-hero-left {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  min-width: 260px;
}

.client-avatar {
  width: 3.5rem;
  height: 3.5rem;
  min-width: 3.5rem;
  min-height: 3.5rem;
  border-radius: 10px;
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  color: #fff;
  font-size: 1.35rem;
  font-weight: 700;
  display: grid;
  place-items: center;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
  flex-shrink: 0;
}

.client-hero-info h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-main, #f1f5f9);
  letter-spacing: -0.02em;
  margin: 0 0 0.35rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.client-hero-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.85rem;
  color: var(--text-muted, #94a3b8);
  flex-wrap: wrap;
}

.detail-layout-grid {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
  align-items: start;
}

@media (max-width: 960px) {
  .detail-layout-grid {
    grid-template-columns: 1fr;
  }
}

.info-section {
  margin-bottom: 1.5rem;
}

.info-section:last-child {
  margin-bottom: 0;
}

.info-section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-faint, #64748b);
  margin-bottom: 0.875rem;
  padding-bottom: 0.4rem;
  border-bottom: 1px solid var(--border-subtle, #1f293d);
}

.info-section-title svg {
  width: 16px !important;
  height: 16px !important;
  max-width: 16px !important;
  max-height: 16px !important;
  color: #60a5fa;
  flex-shrink: 0;
}

.info-grid-2col {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem 1.25rem;
}

@media (max-width: 580px) {
  .info-grid-2col {
    grid-template-columns: 1fr;
  }
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-faint, #64748b);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.info-val {
  font-size: 0.95rem;
  color: var(--text-main, #f1f5f9);
  font-weight: 500;
  word-break: break-word;
}

.info-val a {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.info-val-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: var(--bg-surface-elevated, #162032);
  border: 1px solid var(--border-subtle, #1f293d);
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-size: 0.85rem;
  color: var(--text-main, #f1f5f9);
}

.highlight-box {
  background: var(--bg-surface-elevated, #162032);
  border: 1px solid var(--border-subtle, #1f293d);
  border-radius: 10px;
  padding: 1.25rem;
  margin-top: 1.25rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  align-items: center;
}

@media (max-width: 580px) {
  .highlight-box {
    grid-template-columns: 1fr;
  }
}

.highlight-price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #60a5fa;
  font-family: var(--font-mono, monospace);
  line-height: 1.2;
}

.invoice-preview-wrapper {
  background: var(--bg-app, #0b0f17);
  border: 1px dashed var(--border-subtle, #1f293d);
  border-radius: 10px;
  padding: 1rem;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.invoice-thumbnail {
  max-width: 100%;
  max-height: 250px;
  object-fit: contain;
  border-radius: 6px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.35);
  cursor: zoom-in;
  transition: transform 150ms ease, box-shadow 150ms ease;
}

.invoice-thumbnail:hover {
  transform: scale(1.02);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
}

.invoice-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}

/* Lightbox Modal */
.lightbox-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(11, 15, 23, 0.92);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 9999;
  display: none;
  place-items: center;
  padding: 1.5rem;
  opacity: 0;
  transition: opacity 200ms ease;
}

.lightbox-overlay.active {
  display: grid;
  opacity: 1;
}

.lightbox-content {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.lightbox-img {
  max-width: 100%;
  max-height: 82vh;
  object-fit: contain;
  border-radius: 10px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
  border: 1px solid var(--border-subtle, #1f293d);
}

.lightbox-close-btn {
  position: absolute;
  top: -2.75rem;
  right: 0;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #fff;
  border-radius: 50%;
  width: 2.25rem;
  height: 2.25rem;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 1.25rem;
  transition: background 150ms ease;
}

.lightbox-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}
</style>

<!-- Breadcrumb Navigation -->
<div style="margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
    <a href="<?= base_url('pages/dashboard.php') ?>" style="color: inherit;">Dashboard</a>
    <span>/</span>
    <a href="<?= base_url('pages/clients/index.php') ?>" style="color: inherit;">Klien Hosting</a>
    <span>/</span>
    <span style="color: var(--text-main); font-weight: 600;"><?= e($client['nama']) ?></span>
</div>

<!-- Hero Header Card -->
<div class="client-hero-card">
    <div class="client-hero-left">
        <div class="client-avatar">
            <?= e($initials) ?>
        </div>
        <div class="client-hero-info">
            <h1>
                <span><?= e($client['nama']) ?></span>
                <span class="badge <?= $status['badge_class'] ?>">
                    <span class="badge-dot"></span>
                    <span><?= $status['label'] ?></span>
                </span>
            </h1>
            <div class="client-hero-meta">
                <span>ID: <strong>#<?= $client['id'] ?></strong></span>
                <span>&bull;</span>
                <span>Domain: <strong><?= e($client['nama_website']) ?></strong></span>
                <span>&bull;</span>
                <span>Terdaftar: <?= format_tanggal($client['created_at']) ?></span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="page-actions" style="margin: 0; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
        <form method="POST" action="<?= base_url('pages/clients/send_email.php') ?>" style="display:inline; margin:0;" onsubmit="return confirmAction('Kirim email pengingat tagihan ke <?= e(addslashes($client['email'])) ?> sekarang?');">
            <?= csrf_field() ?>
            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
            <button type="submit" class="btn btn-primary" id="btn-send-email-now" title="Kirim email pengingat manual sekarang">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                <span>Kirim Email</span>
            </button>
        </form>

        <a href="<?= base_url('pages/clients/renew.php?id=' . $client['id']) ?>" class="btn btn-success" id="btn-renew-client" title="Perpanjang masa aktif hosting">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            <span>Perpanjang</span>
        </a>

        <a href="<?= base_url('pages/clients/edit.php?id=' . $client['id']) ?>" class="btn btn-secondary" title="Edit data klien">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            <span>Edit</span>
        </a>

        <form method="POST" action="<?= base_url('pages/clients/delete.php') ?>" style="display:inline; margin:0;" onsubmit="return confirmAction('Yakin ingin mengarsipkan klien ini?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $client['id'] ?>">
            <button type="submit" class="btn btn-danger btn-icon" title="Hapus / Arsipkan klien">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </button>
        </form>
    </div>
</div>

<!-- Main 2-Column Balanced Grid -->
<div class="detail-layout-grid">
    <!-- Left Card: Informasi Layanan & Hosting -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Rincian Informasi Layanan</h2>
        </div>
        <div class="card-body">
            <!-- Section 1: Kontak Klien -->
            <div class="info-section">
                <div class="info-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;max-width:16px;max-height:16px;flex-shrink:0;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>Informasi Klien &amp; Kontak</span>
                </div>
                <div class="info-grid-2col">
                    <div class="info-item">
                        <span class="info-label">Nama Klien</span>
                        <span class="info-val" style="font-weight: 600; font-size: 1.05rem;"><?= e($client['nama']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Nomor WhatsApp / Kontak</span>
                        <div class="info-val">
                            <a href="https://wa.me/<?= e($waNumber) ?>" target="_blank" rel="noopener noreferrer" class="info-val-badge" style="color: #34d399;" title="Chat via WhatsApp">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;flex-shrink:0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                <span><?= e($client['no_telp']) ?></span>
                            </a>
                        </div>
                    </div>

                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">Alamat Email</span>
                        <div class="info-val">
                            <a href="mailto:<?= e($client['email']) ?>" style="display:inline-flex; align-items:center; gap:0.4rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;flex-shrink:0;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                <span><?= e($client['email']) ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Website & Server Hosting -->
            <div class="info-section">
                <div class="info-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;max-width:16px;max-height:16px;flex-shrink:0;"><rect x="2" y="2" width="20" height="8" rx="2"></rect><rect x="2" y="14" width="20" height="8" rx="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                    <span>Layanan Website &amp; Server</span>
                </div>
                <div class="info-grid-2col">
                    <div class="info-item">
                        <span class="info-label">Nama Website / Domain</span>
                        <span class="info-val" style="font-weight: 600; color: #60a5fa;"><?= e($client['nama_website']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Penyedia Hosting</span>
                        <div class="info-val">
                            <span class="info-val-badge">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;flex-shrink:0;"><rect x="2" y="2" width="20" height="8" rx="2"></rect><rect x="2" y="14" width="20" height="8" rx="2"></rect></svg>
                                <span><?= e($client['hosting_dimana']) ?></span>
                            </span>
                        </div>
                    </div>

                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">Link Akses Website / Panel</span>
                        <div class="info-val" style="max-width: 100%;">
                            <a href="<?= e($client['link_hosting']) ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:0.4rem; max-width: 100%; word-break: break-all;">
                                <span><?= e($client['link_hosting']) ?></span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;flex-shrink:0;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Tagihan & Masa Aktif (Highlighted Box) -->
            <div class="highlight-box">
                <div>
                    <span class="info-label" style="color: var(--text-muted);">Biaya Perpanjangan</span>
                    <div class="highlight-price"><?= format_rupiah($client['biaya']) ?></div>
                    <span style="font-size: 0.8rem; color: var(--text-faint);">Tarif per periode tagihan</span>
                </div>

                <div style="border-left: 1px solid var(--border-subtle); padding-left: 1.25rem;">
                    <span class="info-label" style="color: var(--text-muted);">Periode Masa Aktif</span>
                    <div style="font-size: 0.875rem; color: var(--text-main); line-height: 1.5;">
                        <div>Mulai: <strong><?= format_tanggal($client['tanggal_mulai']) ?></strong></div>
                        <div style="margin-top: 0.2rem;">Expired: <strong style="color: <?= $status['color'] ?>;"><?= format_tanggal($client['tanggal_expired']) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Card: Lampiran Bukti Nota -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Lampiran Nota / Invoice</h2>
            <?php if (!empty($client['gambar'])): ?>
                <span class="badge badge-active" style="font-size: 0.725rem;">Tersedia</span>
            <?php else: ?>
                <span class="badge" style="background: var(--bg-surface-elevated); color: var(--text-faint); font-size: 0.725rem;">Tidak Ada</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php 
            $imageExists = false;
            $imageWebPath = '';
            if (!empty($client['gambar'])) {
                $imageWebPath = base_url('uploads/nota/' . e($client['gambar']));
                $imageExists = true;
            }
            ?>

            <?php if ($imageExists): ?>
                <div class="invoice-preview-wrapper">
                    <img 
                        src="<?= $imageWebPath ?>" 
                        alt="Bukti Nota <?= e($client['nama']) ?>" 
                        class="invoice-thumbnail" 
                        id="invoice-img-thumb"
                        onclick="openLightbox('<?= $imageWebPath ?>')"
                        title="Klik untuk memperbesar gambar nota"
                    >
                </div>

                <div style="text-align: center; margin-top: 0.75rem;">
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Klik gambar di atas untuk melihat resolusi penuh
                    </span>
                </div>

                <div class="invoice-actions">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openLightbox('<?= $imageWebPath ?>')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;flex-shrink:0;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                        <span>Lihat Penuh</span>
                    </button>
                    <a href="<?= $imageWebPath ?>" download="Nota_<?= preg_replace('/[^a-zA-Z0-9_\-]/', '_', $client['nama_website']) ?>" class="btn btn-secondary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;flex-shrink:0;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Unduh</span>
                    </a>
                    <a href="<?= base_url('pages/clients/edit.php?id=' . $client['id']) ?>" class="btn btn-secondary btn-sm" title="Ganti berkas nota">
                        <span>Ganti Nota</span>
                    </a>
                </div>
            <?php else: ?>
                <div style="padding: 3rem 1.5rem; text-align: center; color: var(--text-faint);">
                    <div style="width: 3.5rem; height: 3.5rem; border-radius: 50%; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); display: grid; place-items: center; margin: 0 auto 1rem; color: var(--text-faint);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" style="width:24px;height:24px;flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-main); margin-bottom: 0.25rem;">Belum Ada Nota / Invoice</div>
                    <p style="font-size: 0.825rem; color: var(--text-muted); max-width: 240px; margin: 0 auto 1.25rem;">
                        Bukti nota tagihan akan dilampirkan otomatis saat pengiriman email ke klien.
                    </p>
                    <a href="<?= base_url('pages/clients/edit.php?id=' . $client['id']) ?>" class="btn btn-primary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;flex-shrink:0;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Upload Nota Sekarang</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bottom Card: Riwayat Email Pengingat Klien Ini -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Riwayat Email Pengingat Klien Ini</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">Daftar seluruh email pengingat (H-14, H-3, maupun manual) yang pernah dikirimkan ke klien ini.</p>
        </div>
    </div>

    <?php if (empty($clientLogs)): ?>
        <div style="padding: 2.5rem 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 0.75rem; color: var(--text-faint); display: block; width:32px; height:32px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            Belum ada catatan pengiriman email untuk klien ini. Gunakan tombol <strong>"Kirim Email"</strong> di atas jika ingin mengirimkan sekarang.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" aria-label="Riwayat Email Klien">
                <thead>
                    <tr>
                        <th>Waktu Dikirim</th>
                        <th>Jenis Pengingat</th>
                        <th>Periode Expired</th>
                        <th>Status</th>
                        <th>Keterangan / Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientLogs as $log): ?>
                    <tr>
                        <td style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
                            <?= date('d M Y, H:i', strtotime($log['sent_at'])) ?> WIB
                        </td>
                        <td>
                            <span class="badge" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: var(--text-main);">
                                <?= e($log['jenis']) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.85rem;">
                            <?= format_tanggal($log['tanggal_expired']) ?>
                        </td>
                        <td>
                            <?php if ($log['status'] === 'berhasil'): ?>
                                <span class="badge badge-active">
                                    <span class="badge-dot"></span>
                                    <span>Berhasil</span>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-expired">
                                    <span class="badge-dot"></span>
                                    <span>Gagal</span>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-muted);">
                            <?= e($log['pesan_error'] ?: 'Email berhasil terkirim.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal Overlay -->
<div class="lightbox-overlay" id="invoiceLightbox" onclick="closeLightbox(event)">
    <div class="lightbox-content">
        <button type="button" class="lightbox-close-btn" onclick="closeLightboxDirect()" aria-label="Tutup preview">&times;</button>
        <img src="" alt="Nota Full Preview" class="lightbox-img" id="lightboxTargetImg">
    </div>
</div>

<script>
function openLightbox(src) {
    const modal = document.getElementById('invoiceLightbox');
    const target = document.getElementById('lightboxTargetImg');
    if (modal && target) {
        target.src = src;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeLightboxDirect() {
    const modal = document.getElementById('invoiceLightbox');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeLightbox(e) {
    if (e.target.id === 'invoiceLightbox') {
        closeLightboxDirect();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightboxDirect();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
