<?php
/**
 * Manual Email Trigger Handler
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_message('danger', 'Metode request tidak diizinkan.');
    header('Location: ' . base_url('pages/clients/index.php'));
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    flash_message('danger', 'Validasi keamanan CSRF gagal.');
    header('Location: ' . base_url('pages/clients/index.php'));
    exit;
}

$clientId = (int)($_POST['client_id'] ?? 0);
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id AND deleted_at IS NULL LIMIT 1");
$stmt->execute([':id' => $clientId]);
$client = $stmt->fetch();

if (!$client) {
    flash_message('danger', 'Data klien tidak ditemukan atau telah dihapus.');
    header('Location: ' . base_url('pages/clients/index.php'));
    exit;
}

// Check SMTP configured
$smtpHost = get_setting('smtp_host');
$smtpUser = get_setting('smtp_user');

$result = send_reminder_email($client, 'manual');

if ($result['success']) {
    flash_message('success', 'Email pengingat perpanjangan berhasil dikirim ke ' . e($client['email']) . '!');
} else {
    flash_message('danger', 'Gagal mengirim email ke ' . e($client['email']) . '. Pesan error: ' . e($result['error']) . '. Pastikan pengaturan SMTP sudah benar di menu Pengaturan.');
}

header('Location: ' . base_url('pages/clients/detail.php?id=' . $clientId));
exit;
