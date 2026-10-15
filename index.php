<?php
/**
 * Root Entry Point
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/includes/auth.php';

// Jika sudah login, arahkan ke Dashboard
if (is_logged_in()) {
    header('Location: ' . base_url('pages/dashboard.php'));
    exit;
}

// Jika belum login, langsung tampilkan halaman Login di URL root utama
require_once __DIR__ . '/pages/login.php';
