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

<div class="page-header">
    <div>
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.25rem;">
            <h1 class="page-title"><?= e($client['nama']) ?></h1>
            <span class="badge <?= $status['badge_class'] ?>">
                <span class="badge-dot"></span>
                <span><?= $status['label'] ?></span>
            </span>
        </div>
        <p class="page-desc">ID Klien: #<?= $client['id'] ?> &bull; Terdaftar pada <?= format_tanggal($client['created_at']) ?></p>
    </div>
    <div class="page-actions">
        <!-- Manual Email Trigger Button -->
        <form method="POST" action="<?= base_url('pages/clients/send_email.php') ?>" style="display:inline;" onsubmit="return confirmAction('Kirim email pengingat tagihan ke <?= e(addslashes($client['email'])) ?> sekarang?');">
            <?= csrf_field() ?>
            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
            <button type="submit" class="btn btn-primary" id="btn-send-email-now">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                <span>Kirim Email Sekarang</span>
            </button>
        </form>

        <a href="<?= base_url('pages/clients/renew.php?id=' . $client['id']) ?>" class="btn btn-success" id="btn-renew-client">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            <span>Perpanjang</span>
        </a>

        <a href="<?= base_url('pages/clients/edit.php?id=' . $client['id']) ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            <span>Edit</span>
        </a>

        <form method="POST" action="<?= base_url('pages/clients/delete.php') ?>" style="display:inline;" onsubmit="return confirmAction('Yakin ingin menghapus klien ini?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $client['id'] ?>">
            <button type="submit" class="btn btn-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                <span>Hapus</span>
            </button>
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Left Column: Details -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Rincian Informasi Layanan</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-faint); font-weight: 600;">Nama Klien</span>
                    <div style="font-size: 1.05rem; font-weight: 600; color: var(--text-main); margin-top: 0.25rem;">
                        <?= e($client['nama']) ?>
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-faint); font-weight: 600;">Alamat Email</span>
                    <div style="font-size: 1.05rem; color: var(--text-main); margin-top: 0.25rem;">
                        <a href="mailto:<?= e($client['email']) ?>"><?= e($client['email']) ?></a>
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-faint); font-weight: 600;">Nomor Kontak / WhatsApp</span>
                    <div style="font-size: 1.05rem; color: var(--text-main); margin-top: 0.25rem;">
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $client['no_telp'])) ?>" target="_blank" rel="noopener noreferrer">
                            <?= e($client['no_telp']) ?>
                        </a>
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-faint); font-weight: 600;">Nama Website / Domain</span>
                    <div style="font-size: 1.05rem; font-weight: 600; color: var(--text-main); margin-top: 0.25rem;">
                        <?= e($client['nama_website']) ?>
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-faint); font-weight: 600;">Link Website / Panel</span>
                    <div style="font-size: 1.05rem; margin-top: 0.25rem;">
                        <a href="<?= e($client['link_hosting']) ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:0.35rem;">
                            <span><?= e($client['link_hosting']) ?></span>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-faint); font-weight: 600;">Penyedia Hosting</span>
                    <div style="font-size: 1.05rem; color: var(--text-main); margin-top: 0.25rem;">
                        <?= e($client['hosting_dimana']) ?>
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-faint); font-weight: 600;">Biaya Perpanjangan</span>
                    <div style="font-size: 1.35rem; font-weight: 700; color: #60a5fa; font-family: var(--font-mono); margin-top: 0.25rem;">
                        <?= format_rupiah($client['biaya']) ?>
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-faint); font-weight: 600;">Masa Aktif</span>
                    <div style="font-size: 0.95rem; color: var(--text-main); margin-top: 0.25rem;">
                        <div>Mulai: <strong><?= format_tanggal($client['tanggal_mulai']) ?></strong></div>
                        <div style="margin-top: 0.15rem;">Expired: <strong><?= format_tanggal($client['tanggal_expired']) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Bukti Nota -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Lampiran Nota / Invoice</h2>
        </div>
        <div class="card-body" style="text-align: center;">
            <?php if (!empty($client['gambar']) && file_exists(__DIR__ . '/../../uploads/nota/' . $client['gambar'])): ?>
                <a href="<?= base_url('uploads/nota/' . e($client['gambar'])) ?>" target="_blank" rel="noopener noreferrer">
                    <img 
                        src="<?= base_url('uploads/nota/' . e($client['gambar'])) ?>" 
                        alt="Bukti Nota <?= e($client['nama']) ?>" 
                        style="max-width: 100%; max-height: 260px; object-fit: contain; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); box-shadow: 0 4px 15px rgba(0,0,0,0.3);"
                    >
                </a>
                <div style="margin-top: 0.75rem; font-size: 0.825rem; color: var(--text-muted);">
                    Klik gambar untuk melihat ukuran penuh.
                </div>
            <?php else: ?>
                <div style="padding: 2.5rem 1rem; color: var(--text-faint);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 0.75rem;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <p style="font-size: 0.875rem;">Belum ada gambar nota yang dilampirkan.</p>
                    <a href="<?= base_url('pages/clients/edit.php?id=' . $client['id']) ?>" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;">
                        Upload Nota
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Email Logs for this Client -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Riwayat Email Pengingat Klien Ini</h2>
    </div>

    <?php if (empty($clientLogs)): ?>
        <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
            Belum ada catatan pengiriman email untuk klien ini.
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
