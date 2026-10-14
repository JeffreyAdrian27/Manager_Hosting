<?php
/**
 * Clients List (Index) Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../../includes/auth.php';
require_auth();

$activePage = 'clients';
$pageTitle = 'Daftar Klien Hosting';

$pdo = getDBConnection();

// Filters & Query Parameters
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$sortOrder = strtoupper(trim($_GET['sort'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Base Where Clause
$where = ["deleted_at IS NULL"];
$params = [];

if ($search !== '') {
    $where[] = "(nama LIKE :q1 OR nama_website LIKE :q2 OR email LIKE :q3 OR hosting_dimana LIKE :q4)";
    $searchWildcard = "%{$search}%";
    $params[':q1'] = $searchWildcard;
    $params[':q2'] = $searchWildcard;
    $params[':q3'] = $searchWildcard;
    $params[':q4'] = $searchWildcard;
}

if ($statusFilter === 'aktif') {
    $where[] = "DATEDIFF(tanggal_expired, CURDATE()) > 14";
} elseif ($statusFilter === 'segera_expired') {
    $where[] = "DATEDIFF(tanggal_expired, CURDATE()) BETWEEN 4 AND 14";
} elseif ($statusFilter === 'kritis') {
    $where[] = "DATEDIFF(tanggal_expired, CURDATE()) BETWEEN 0 AND 3";
} elseif ($statusFilter === 'expired') {
    $where[] = "tanggal_expired < CURDATE()";
}

$whereSql = implode(' AND ', $where);

// Count Total for Pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

// Fetch Records
$dataSql = "
    SELECT 
        id, nama, email, no_telp, nama_website, link_hosting, 
        biaya, gambar, tanggal_mulai, tanggal_expired, hosting_dimana,
        DATEDIFF(tanggal_expired, CURDATE()) AS sisa_hari
    FROM clients
    WHERE {$whereSql}
    ORDER BY tanggal_expired {$sortOrder}
    LIMIT :offset, :limit
";
$stmt = $pdo->prepare($dataSql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$clients = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Daftar Klien Hosting</h1>
        <p class="page-desc">Kelola dan pantau seluruh data masa aktif hosting klien Anda.</p>
    </div>
    <div class="page-actions">
        <a href="<?= base_url('pages/clients/create.php') ?>" class="btn btn-primary" id="btn-create-client">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Tambah Klien</span>
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form method="GET" action="<?= base_url('pages/clients/index.php') ?>" class="filter-bar" style="margin-bottom: 0;">
            <div class="search-box">
                <span class="search-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input 
                    type="text" 
                    name="q" 
                    class="form-control" 
                    placeholder="Cari nama klien, domain, website, atau email..." 
                    value="<?= e($search) ?>"
                >
            </div>

            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <select name="status" class="form-control" style="width: auto; min-width: 170px;">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : '' ?>>Aktif (&gt;14 hari)</option>
                    <option value="segera_expired" <?= $statusFilter === 'segera_expired' ? 'selected' : '' ?>>Segera Expired (≤14 hari)</option>
                    <option value="kritis" <?= $statusFilter === 'kritis' ? 'selected' : '' ?>>Kritis (≤3 hari)</option>
                    <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>

                <select name="sort" class="form-control" style="width: auto; min-width: 170px;">
                    <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Expired Terdekat</option>
                    <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Expired Terjauh</option>
                </select>

                <button type="submit" class="btn btn-secondary">
                    <span>Filter</span>
                </button>

                <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                    <a href="<?= base_url('pages/clients/index.php') ?>" class="btn btn-secondary" title="Reset filter">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Main Table Card -->
<div class="card">
    <?php if (empty($clients)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <h3 class="empty-state-title">Tidak Ada Klien Ditemukan</h3>
            <p class="empty-state-desc">
                <?= $search !== '' || $statusFilter !== 'all' ? 'Tidak ada hasil yang sesuai dengan kriteria pencarian Anda.' : 'Belum ada data klien yang terdaftar.' ?>
            </p>
            <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                <a href="<?= base_url('pages/clients/index.php') ?>" class="btn btn-secondary btn-sm">Bersihkan Filter</a>
            <?php else: ?>
                <a href="<?= base_url('pages/clients/create.php') ?>" class="btn btn-primary btn-sm">Tambah Klien Baru</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" aria-label="Daftar Klien Hosting">
                <thead>
                    <tr>
                        <th>Klien</th>
                        <th>Website & Link</th>
                        <th>Penyedia</th>
                        <th>Biaya Perpanjang</th>
                        <th>Tanggal Expired</th>
                        <th>Sisa Waktu</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): 
                        $sisaHari = (int)$client['sisa_hari'];
                        $status = get_status_info($sisaHari);
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-main);">
                                <a href="<?= base_url('pages/clients/detail.php?id=' . $client['id']) ?>" style="color:inherit;">
                                    <?= e($client['nama']) ?>
                                </a>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); display:flex; gap:0.5rem; align-items:center;">
                                <span><?= e($client['email']) ?></span>
                                <span>&bull;</span>
                                <span><?= e($client['no_telp']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500; font-size: 0.875rem;"><?= e($client['nama_website']) ?></div>
                            <a href="<?= e($client['link_hosting']) ?>" target="_blank" rel="noopener noreferrer" style="font-size: 0.8rem; display:inline-flex; align-items:center; gap:0.25rem;">
                                <span>Kunjungi Link</span>
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            </a>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; color: var(--text-muted);"><?= e($client['hosting_dimana']) ?></span>
                        </td>
                        <td style="font-family: var(--font-mono); font-size: 0.875rem;">
                            <?= format_rupiah($client['biaya']) ?>
                        </td>
                        <td>
                            <div style="font-size: 0.875rem;"><?= format_tanggal($client['tanggal_expired']) ?></div>
                            <div style="font-size: 0.775rem; color: var(--text-faint);">Mulai: <?= format_tanggal($client['tanggal_mulai']) ?></div>
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
                            <div style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                <a href="<?= base_url('pages/clients/detail.php?id=' . $client['id']) ?>" class="btn btn-secondary btn-sm" title="Lihat detail klien">
                                    Detail
                                </a>
                                <a href="<?= base_url('pages/clients/renew.php?id=' . $client['id']) ?>" class="btn btn-success btn-sm" title="Perpanjang hosting">
                                    Perpanjang
                                </a>
                                <a href="<?= base_url('pages/clients/edit.php?id=' . $client['id']) ?>" class="btn btn-secondary btn-icon" title="Edit data">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </a>
                                <form method="POST" action="<?= base_url('pages/clients/delete.php') ?>" style="display:inline;" onsubmit="return confirmAction('Apakah Anda yakin ingin menghapus klien <?= e(addslashes($client['nama'])) ?>? Data akan diarsipkan.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $client['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-icon" title="Hapus data klien">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted);">
                Menampilkan <?= count($clients) ?> dari total <?= number_format($totalRecords) ?> klien (Halaman <?= $page ?> dari <?= $totalPages ?>)
            </div>
            <ul class="pagination">
                <?php 
                $queryParams = $_GET;
                ?>
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <?php $queryParams['p'] = $page - 1; ?>
                    <a class="page-link" href="<?= base_url('pages/clients/index.php?' . http_build_query($queryParams)) ?>">&laquo;</a>
                </li>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <?php $queryParams['p'] = $i; ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= base_url('pages/clients/index.php?' . http_build_query($queryParams)) ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <?php $queryParams['p'] = $page + 1; ?>
                    <a class="page-link" href="<?= base_url('pages/clients/index.php?' . http_build_query($queryParams)) ?>">&raquo;</a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
