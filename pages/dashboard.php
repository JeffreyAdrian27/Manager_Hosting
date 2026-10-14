<?php
/**
 * Dashboard Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../includes/auth.php';
require_auth();

$activePage = 'dashboard';
$pageTitle = 'Dashboard Ringkasan';

$pdo = getDBConnection();

// Fetch summary metrics
$statsQuery = "
    SELECT 
        COUNT(*) AS total_clients,
        SUM(CASE WHEN tanggal_expired >= CURDATE() THEN 1 ELSE 0 END) AS total_active,
        SUM(CASE WHEN DATEDIFF(tanggal_expired, CURDATE()) BETWEEN 4 AND 14 THEN 1 ELSE 0 END) AS warning_14d,
        SUM(CASE WHEN DATEDIFF(tanggal_expired, CURDATE()) BETWEEN 0 AND 3 THEN 1 ELSE 0 END) AS critical_3d,
        SUM(CASE WHEN tanggal_expired < CURDATE() THEN 1 ELSE 0 END) AS total_expired
    FROM clients
    WHERE deleted_at IS NULL
";
$stats = $pdo->query($statsQuery)->fetch() ?: [
    'total_clients' => 0,
    'total_active' => 0,
    'warning_14d' => 0,
    'critical_3d' => 0,
    'total_expired' => 0,
];

// Fetch closest upcoming expired or recently expired clients (top 8)
$upcomingStmt = $pdo->prepare("
    SELECT 
        id, nama, email, no_telp, nama_website, link_hosting, 
        biaya, tanggal_expired, hosting_dimana,
        DATEDIFF(tanggal_expired, CURDATE()) AS sisa_hari
    FROM clients
    WHERE deleted_at IS NULL
    ORDER BY tanggal_expired ASC
    LIMIT 8
");
$upcomingStmt->execute();
$upcomingClients = $upcomingStmt->fetchAll();

// Recent Email Logs (last 5)
$recentLogsStmt = $pdo->query("
    SELECT el.*, c.nama, c.nama_website
    FROM email_logs el
    LEFT JOIN clients c ON el.client_id = c.id
    ORDER BY el.sent_at DESC
    LIMIT 5
");
$recentLogs = $recentLogsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Pemantauan</h1>
        <p class="page-desc">Ringkasan status masa aktif hosting dan pengingat perpanjangan klien.</p>
    </div>
    <div class="page-actions">
        <a href="<?= base_url('pages/clients/create.php') ?>" class="btn btn-primary" id="btn-add-client-dash">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Tambah Klien Baru</span>
        </a>
    </div>
</div>

<!-- Stat Metric Cards -->
<section class="stats-grid" aria-label="Metrik Utama">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Total Klien Aktif</span>
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format((int)$stats['total_active']) ?></div>
        <div class="stat-desc">Dari total <?= number_format((int)$stats['total_clients']) ?> klien terdaftar</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Segera Expired (≤14 Hari)</span>
            <div class="stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
        </div>
        <div class="stat-value" style="color: #fbbf24;"><?= number_format((int)$stats['warning_14d']) ?></div>
        <div class="stat-desc">Dijadwalkan menerima pengingat H-14</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Kritis (≤3 Hari)</span>
            <div class="stat-icon orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
        </div>
        <div class="stat-value" style="color: #fb923c;"><?= number_format((int)$stats['critical_3d']) ?></div>
        <div class="stat-desc">Perlu konfirmasi perpanjangan segera</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Sudah Expired</span>
            <div class="stat-icon rose">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            </div>
        </div>
        <div class="stat-value" style="color: #fb7185;"><?= number_format((int)$stats['total_expired']) ?></div>
        <div class="stat-desc">Layanan hosting telah melewati batas</div>
    </div>
</section>

<!-- Upcoming Expirations Table -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div>
            <h2 class="card-title">Hosting Terdekat Expired</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">Urutan klien berdasarkan tanggal jatuh tempo terdekat</p>
        </div>
        <a href="<?= base_url('pages/clients/index.php') ?>" class="btn btn-secondary btn-sm" id="link-all-clients">
            <span>Lihat Semua Klien</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.9rem;height:0.9rem;"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </div>

    <?php if (empty($upcomingClients)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="8" rx="2"></rect><rect x="2" y="14" width="20" height="8" rx="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
            </div>
            <h3 class="empty-state-title">Belum Ada Data Klien</h3>
            <p class="empty-state-desc">Tambahkan data klien hosting pertama Anda untuk mulai memantau masa aktif domain dan otomatisasi pengingat.</p>
            <a href="<?= base_url('pages/clients/create.php') ?>" class="btn btn-primary btn-sm">Tambah Klien Sekarang</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" aria-label="Tabel Hosting Terdekat Expired">
                <thead>
                    <tr>
                        <th>Klien / Website</th>
                        <th>Penyedia Hosting</th>
                        <th>Biaya</th>
                        <th>Tanggal Expired</th>
                        <th>Sisa Hari</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingClients as $client): 
                        $sisaHari = (int)$client['sisa_hari'];
                        $status = get_status_info($sisaHari);
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600;"><?= e($client['nama']) ?></div>
                            <a href="<?= e($client['link_hosting']) ?>" target="_blank" rel="noopener noreferrer" style="font-size: 0.825rem; color: var(--text-muted); display:inline-flex; align-items:center; gap:0.25rem;">
                                <span><?= e($client['nama_website']) ?></span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            </a>
                        </td>
                        <td>
                            <span style="font-size: 0.875rem; color: var(--text-muted);"><?= e($client['hosting_dimana']) ?></span>
                        </td>
                        <td style="font-family: var(--font-mono); font-size: 0.875rem;">
                            <?= format_rupiah($client['biaya']) ?>
                        </td>
                        <td>
                            <span style="font-size: 0.875rem;"><?= format_tanggal($client['tanggal_expired']) ?></span>
                        </td>
                        <td>
                            <span style="font-weight: 700; font-family: var(--font-mono); font-size: 0.95rem; color: <?= $status['color'] ?>;">
                                <?= $sisaHari < 0 ? abs($sisaHari) . ' hari lalu' : $sisaHari . ' hari' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $status['badge_class'] ?>">
                                <span class="badge-dot"></span>
                                <span><?= $status['short_label'] ?></span>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.35rem;">
                                <a href="<?= base_url('pages/clients/detail.php?id=' . $client['id']) ?>" class="btn btn-secondary btn-sm" title="Lihat detail">
                                    Detail
                                </a>
                                <a href="<?= base_url('pages/clients/renew.php?id=' . $client['id']) ?>" class="btn btn-success btn-sm" title="Perpanjang masa aktif">
                                    Perpanjang
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Email Logs Section -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Riwayat Email Terakhir</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">5 pengiriman email pengingat terbaru ke klien</p>
        </div>
        <a href="<?= base_url('pages/logs.php') ?>" class="btn btn-secondary btn-sm">
            <span>Lihat Log Lengkap</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:0.9rem;height:0.9rem;"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </div>

    <?php if (empty($recentLogs)): ?>
        <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
            Belum ada riwayat email pengingat yang dikirim.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" aria-label="Tabel Riwayat Email Terakhir">
                <thead>
                    <tr>
                        <th>Waktu Kirim</th>
                        <th>Klien / Website</th>
                        <th>Jenis Pengingat</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
                            <?= date('d M Y, H:i', strtotime($log['sent_at'])) ?> WIB
                        </td>
                        <td>
                            <div style="font-weight: 600;"><?= e($log['nama'] ?? 'Klien Dihapus') ?></div>
                            <span style="font-size: 0.8rem; color: var(--text-faint);"><?= e($log['nama_website'] ?? '-') ?></span>
                        </td>
                        <td>
                            <span class="badge" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: var(--text-main);">
                                <?= e($log['jenis']) ?>
                            </span>
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
                            <?= e($log['pesan_error'] ?: 'Terkirim normal') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
