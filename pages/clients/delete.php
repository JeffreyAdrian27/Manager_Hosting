<?php
/**
 * Delete (Soft Delete) Client Handler
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../../includes/auth.php';
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

$clientId = (int)($_POST['id'] ?? 0);
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT nama FROM clients WHERE id = :id AND deleted_at IS NULL LIMIT 1");
$stmt->execute([':id' => $clientId]);
$client = $stmt->fetch();

if ($client) {
    $deleteStmt = $pdo->prepare("UPDATE clients SET deleted_at = NOW() WHERE id = :id");
    $deleteStmt->execute([':id' => $clientId]);
    flash_message('success', 'Data klien ' . e($client['nama']) . ' berhasil diarsipkan (soft delete).');
} else {
    flash_message('warning', 'Data klien tidak ditemukan atau sudah dihapus sebelumnya.');
}

header('Location: ' . base_url('pages/clients/index.php'));
exit;
