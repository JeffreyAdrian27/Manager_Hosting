<?php
/**
 * Email Logs History Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../includes/auth.php';
require_auth();

$activePage = 'logs';
$pageTitle = 'Riwayat Log Email';

$pdo = getDBConnection();

$filterJenis = trim($_GET['jenis'] ?? 'all');
$filterStatus = trim($_GET['status'] ?? 'all');
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = ["1=1"];
$params = [];

if ($filterJenis !== 'all' && in_array($filterJenis, ['H-14', 'H-3', 'manual'])) {
    $where[] = "el.jenis = :jenis";
    $params[':jenis'] = $filterJenis;
}

if ($filterStatus !== 'all' && in_array($filterStatus, ['berhasil', 'gagal'])) {
    $where[] = "el.status = :status";
    $params[':status'] = $filterStatus;
}

if ($search !== '') {
    $where[] = "(c.nama LIKE :q1 OR c.nama_website LIKE :q2 OR c.email LIKE :q3 OR el.pesan_error LIKE :q4)";
    $qWild = "%{$search}%";
    $params[':q1'] = $qWild;
    $params[':q2'] = $qWild;
    $params[':q3'] = $qWild;
    $params[':q4'] = $qWild;
}

$whereSql = implode(' AND ', $where);

// Count total records
$countSql = "
    SELECT COUNT(*) 
    FROM email_logs el
    LEFT JOIN clients c ON el.client_id = c.id
    WHERE {$whereSql}
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

// Fetch logs
$dataSql = "
    SELECT 
        el.*, 
        c.nama, 
        c.email, 
        c.nama_website
    FROM email_logs el
    LEFT JOIN clients c ON el.client_id = c.id
    WHERE {$whereSql}
    ORDER BY el.sent_at DESC
    LIMIT :offset, :limit
";
$stmt = $pdo->prepare($dataSql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Riwayat Log Email Pengingat</h1>
        <p class="page-desc">Catatan lengkap pengiriman email pengingat (H-14, H-3, dan manual) beserta status pengiriman.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form method="GET" action="<?= base_url('pages/logs.php') ?>" class="filter-bar" style="margin-bottom: 0;">
            <div class="search-box">
                <span class="search-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input 
                    type="text" 
                    name="q" 
                    class="form-control" 
                    placeholder="Cari nama klien, email, domain, atau pesan error..." 
                    value="<?= e($search) ?>"
                >
            </div>

            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <select name="jenis" class="form-control" style="width: auto; min-width: 150px;">
                    <option value="all" <?= $filterJenis === 'all' ? 'selected' : '' ?>>Semua Jenis</option>
                    <option value="H-14" <?= $filterJenis === 'H-14' ? 'selected' : '' ?>>Pengingat H-14</option>
                    <option value="H-3" <?= $filterJenis === 'H-3' ? 'selected' : '' ?>>Pengingat H-3</option>
                    <option value="manual" <?= $filterJenis === 'manual' ? 'selected' : '' ?>>Kirim Manual</option>
                </select>

                <select name="status" class="form-control" style="width: auto; min-width: 150px;">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="berhasil" <?= $filterStatus === 'berhasil' ? 'selected' : '' ?>>Berhasil</option>
                    <option value="gagal" <?= $filterStatus === 'gagal' ? 'selected' : '' ?>>Gagal</option>
                </select>

                <button type="submit" class="btn btn-secondary">
                    <span>Filter</span>
                </button>

                <?php if ($search !== '' || $filterJenis !== 'all' || $filterStatus !== 'all'): ?>
                    <a href="<?= base_url('pages/logs.php') ?>" class="btn btn-secondary">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card">
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </div>
            <h3 class="empty-state-title">Tidak Ada Catatan Log Email</h3>
            <p class="empty-state-desc">Belum ada riwayat email pengingat yang sesuai dengan kriteria penyaringan Anda.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" aria-label="Tabel Riwayat Log Email">
                <thead>
                    <tr>
                        <th>Waktu Kirim</th>
                        <th>Klien / Penerima</th>
                        <th>Website</th>
                        <th>Jenis Pengingat</th>
                        <th>Periode Expired</th>
                        <th>Status</th>
                        <th>Keterangan / Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
                            <?= date('d M Y, H:i:s', strtotime($log['sent_at'])) ?> WIB
                        </td>
                        <td>
                            <?php if (!empty($log['nama'])): ?>
                                <a href="<?= base_url('pages/clients/detail.php?id=' . $log['client_id']) ?>" style="font-weight: 600;">
                                    <?= e($log['nama']) ?>
                                </a>
                                <div style="font-size: 0.8rem; color: var(--text-faint);"><?= e($log['email']) ?></div>
                            <?php else: ?>
                                <span style="color: var(--text-faint); font-style: italic;">[Klien Dihapus #<?= $log['client_id'] ?>]</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-size: 0.875rem;"><?= e($log['nama_website'] ?? '-') ?></span>
                        </td>
                        <td>
                            <span class="badge" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: var(--text-main);">
                                <?= e($log['jenis']) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem;"><?= format_tanggal($log['tanggal_expired']) ?></span>
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
                        <td style="font-size: 0.85rem; color: var(--text-muted); max-width: 320px;">
                            <?php if ($log['status'] === 'berhasil'): ?>
                                <span style="color: #34d399;">Terkirim normal</span>
                            <?php else: ?>
                                <span style="color: #fb7185; word-break: break-word;"><?= e($log['pesan_error'] ?: 'Gagal tanpa pesan error.') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="card-footer" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted);">
                Menampilkan <?= count($logs) ?> dari total <?= number_format($totalRecords) ?> riwayat log
            </div>
            <ul class="pagination">
                <?php $queryParams = $_GET; ?>
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <?php $queryParams['p'] = $page - 1; ?>
                    <a class="page-link" href="<?= base_url('pages/logs.php?' . http_build_query($queryParams)) ?>">&laquo;</a>
                </li>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <?php $queryParams['p'] = $i; ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= base_url('pages/logs.php?' . http_build_query($queryParams)) ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <?php $queryParams['p'] = $page + 1; ?>
                    <a class="page-link" href="<?= base_url('pages/logs.php?' . http_build_query($queryParams)) ?>">&raquo;</a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
