<?php
/**
 * Root Entry Point
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . base_url('pages/dashboard.php'));
} else {
    header('Location: ' . base_url('pages/login.php'));
}
exit;
