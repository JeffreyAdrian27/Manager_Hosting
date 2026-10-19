<?php
/**
 * Header Component
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/auth.php';

$currentUser = current_user();
$activePage = $activePage ?? '';
$pageTitle = isset($pageTitle) ? $pageTitle . ' - Manager Hosting' : 'Sistem Manager Hosting';
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Sistem Manager Hosting untuk pemantauan masa aktif domain dan pengingat perpanjangan hosting otomatis.">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css?v=' . (file_exists(__DIR__ . '/../assets/css/app.css') ? filemtime(__DIR__ . '/../assets/css/app.css') : time())) ?>">
</head>
<body>
    <header class="site-header">
        <div class="app-container">
            <div class="header-inner">
                <a href="<?= base_url('pages/dashboard.php') ?>" class="brand-logo" id="nav-brand">
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                            <line x1="6" y1="6" x2="6.01" y2="6"></line>
                            <line x1="6" y1="18" x2="6.01" y2="18"></line>
                        </svg>
                    </div>
                    <span>HostingManager</span>
                </a>

                <?php if ($currentUser): ?>
                <nav>
                    <ul class="nav-links">
                        <li>
                            <a href="<?= base_url('pages/dashboard.php') ?>" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" id="nav-dashboard">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('pages/clients/index.php') ?>" class="nav-link <?= $activePage === 'clients' ? 'active' : '' ?>" id="nav-clients">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>Klien Hosting</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('pages/logs.php') ?>" class="nav-link <?= $activePage === 'logs' ? 'active' : '' ?>" id="nav-logs">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <span>Log Email</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('pages/settings.php') ?>" class="nav-link <?= $activePage === 'settings' ? 'active' : '' ?>" id="nav-settings">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                </svg>
                                <span>Pengaturan</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="user-menu">
                    <div class="user-badge">
                        <div class="user-avatar"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></div>
                        <span><?= e($currentUser['username']) ?></span>
                    </div>
                    <a href="<?= base_url('pages/logout.php') ?>" class="btn btn-secondary btn-sm" id="btn-logout" title="Keluar dari sistem" onclick="return confirmAction('Apakah Anda yakin ingin keluar?');">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:0.9rem;height:0.9rem">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Keluar</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="app-container">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert" id="flash-alert">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <?php if ($flash['type'] === 'success'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php elseif ($flash['type'] === 'danger'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        <?php else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?php endif; ?>
                        <span><?= e($flash['message']) ?></span>
                    </div>
                    <button type="button" class="alert-close" aria-label="Tutup notifikasi">&times;</button>
                </div>
            <?php endif; ?>
