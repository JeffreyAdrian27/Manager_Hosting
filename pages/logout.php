<?php
/**
 * Logout Handler
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../includes/auth.php';

logout_user();
session_start();
flash_message('info', 'Anda telah berhasil keluar dari sistem.');
header('Location: ' . base_url(''));
exit;
