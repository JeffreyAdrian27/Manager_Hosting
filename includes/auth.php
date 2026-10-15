<?php
/**
 * Authentication & Session Management
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/functions.php';

// Safe session startup
if (session_status() === PHP_SESSION_NONE) {
    // 2 hours session cookie lifetime
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Check if current user is logged in and session hasn't expired (2 hours)
 */
function is_logged_in(): bool {
    if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
        return false;
    }

    $timeout = 7200; // 2 hours
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        logout_user();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Require authentication, redirect to login if not authenticated
 */
function require_auth(): void {
    if (!is_logged_in()) {
        flash_message('warning', 'Sesi Anda telah berakhir. Silakan login kembali.');
        $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . base_url('?redirect=' . urlencode($currentUri)));
        exit;
    }
}

/**
 * Redirect to dashboard if already logged in
 */
function redirect_if_logged_in(): void {
    if (is_logged_in()) {
        header('Location: ' . base_url('pages/dashboard.php'));
        exit;
    }
}

/**
 * Attempt user login
 */
function attempt_login(string $username, string $password): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Username atau password tidak sesuai.'];
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['last_activity'] = time();

    return ['success' => true, 'error' => null];
}

/**
 * Perform logout and clean session
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }
}

/**
 * Get logged in user details
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
    ];
}
