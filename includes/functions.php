<?php
/**
 * Helper Functions
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize output string against XSS
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate Base URL
 */
function base_url(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        $projectDir = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');

        if (!empty($docRoot) && !empty($projectDir) && str_starts_with($projectDir, $docRoot)) {
            $subPath = substr($projectDir, strlen($docRoot));
            $base = '/' . trim($subPath, '/');
            if ($base === '/') {
                $base = '';
            }
        } else {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $pos = strpos($script, '/pages/');
            if ($pos !== false) {
                $base = rtrim(substr($script, 0, $pos), '/');
            } else {
                $base = rtrim(dirname($script), '/\\');
                if ($base === '/' || $base === '\\') {
                    $base = '';
                }
            }
        }
    }

    $cleanPath = ltrim($path, '/');
    return ($base === '') ? '/' . $cleanPath : $base . '/' . $cleanPath;
}

/**
 * Generate CSRF Token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF Hidden Input Field
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format currency to Rupiah string
 */
function format_rupiah($amount, bool $with_prefix = true): string {
    $formatted = number_format((float)$amount, 0, ',', '.');
    return $with_prefix ? 'Rp ' . $formatted : $formatted;
}

/**
 * Format date to Indonesian format (e.g., 14 Oktober 2026)
 */
function format_tanggal(?string $dateString): string {
    if (!$dateString || $dateString === '0000-00-00') {
        return '-';
    }

    $timestamp = strtotime($dateString);
    if (!$timestamp) {
        return '-';
    }

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $d = date('j', $timestamp);
    $m = (int)date('n', $timestamp);
    $y = date('Y', $timestamp);

    return "{$d} {$months[$m]} {$y}";
}

/**
 * Calculate remaining days to expiration
 */
function hitung_sisa_hari(string $tanggal_expired): int {
    $today = new DateTime('today', new DateTimeZone('Asia/Jakarta'));
    $expDate = new DateTime($tanggal_expired, new DateTimeZone('Asia/Jakarta'));
    
    // Set both to midnight for clean integer comparison
    $today->setTime(0, 0, 0);
    $expDate->setTime(0, 0, 0);

    $diff = $today->diff($expDate);
    return $diff->invert ? -$diff->days : $diff->days;
}

/**
 * Determine status badge representation
 */
function get_status_info(int $sisa_hari): array {
    if ($sisa_hari < 0) {
        return [
            'key' => 'expired',
            'label' => 'Expired (' . abs($sisa_hari) . ' hari lalu)',
            'short_label' => 'Expired',
            'badge_class' => 'badge-expired',
            'color' => '#f43f5e',
        ];
    } elseif ($sisa_hari <= 3) {
        return [
            'key' => 'kritis',
            'label' => 'Kritis (' . $sisa_hari . ' hari lagi)',
            'short_label' => 'Kritis (≤3 hari)',
            'badge_class' => 'badge-critical',
            'color' => '#f97316',
        ];
    } elseif ($sisa_hari <= 14) {
        return [
            'key' => 'segera_expired',
            'label' => 'Segera Expired (' . $sisa_hari . ' hari lagi)',
            'short_label' => 'Segera Expired',
            'badge_class' => 'badge-warning',
            'color' => '#f59e0b',
        ];
    } else {
        return [
            'key' => 'aktif',
            'label' => 'Aktif (' . $sisa_hari . ' hari lagi)',
            'short_label' => 'Aktif',
            'badge_class' => 'badge-active',
            'color' => '#10b981',
        ];
    }
}

/**
 * Set flash message
 */
function flash_message(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * Retrieve and clear flash message
 */
function get_flash_message(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Retrieve system setting by key
 */
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;

    if ($settingsCache === null) {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT `key`, `value` FROM `settings`");
        $settingsCache = [];
        while ($row = $stmt->fetch()) {
            $settingsCache[$row['key']] = $row['value'];
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Update system setting
 */
function update_setting(string $key, string $value): bool {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO `settings` (`key`, `value`)
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE `value` = :value_update
    ");
    return $stmt->execute([
        ':key' => $key,
        ':value' => $value,
        ':value_update' => $value,
    ]);
}

/**
 * Validate and handle image file upload
 */
function handle_upload_nota(array $file): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal mengupload file (Error code: ' . $file['error'] . ').'];
    }

    // Limit size: 2MB (2 * 1024 * 1024 bytes)
    $maxBytes = 2 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'filename' => null, 'error' => 'Ukuran file gambar maksimal 2 MB.'];
    }

    // Verify MIME type safely using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!array_key_exists($mimeType, $allowedMimes)) {
        return ['success' => false, 'filename' => null, 'error' => 'Format file harus JPG, PNG, atau WEBP.'];
    }

    $ext = $allowedMimes[$mimeType];
    $newFilename = 'nota_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/nota/';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal menyimpan file ke direktori uploads.'];
    }

    return ['success' => true, 'filename' => $newFilename, 'error' => null];
}
