<?php
/**
 * Login Page
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../includes/auth.php';

redirect_if_logged_in();

$error = null;
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Validasi keamanan CSRF gagal. Silakan coba kembali.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Harap isi username dan password.';
        } else {
            $result = attempt_login($username, $password);
            if ($result['success']) {
                flash_message('success', 'Selamat datang kembali, ' . e($username) . '!');
                $targetUrl = !empty($redirect) && str_starts_with($redirect, '/') ? $redirect : base_url('pages/dashboard.php');
                header('Location: ' . $targetUrl);
                exit;
            } else {
                $error = $result['error'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Sistem Manager Hosting</title>
    <meta name="description" content="Masuk ke portal pengelolaan klien hosting dan pengingat masa aktif domain.">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
            </div>
            <h1 class="auth-title">Manager Hosting</h1>
            <p class="auth-subtitle">Masuk untuk mengelola hosting dan klien</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert" id="login-error">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <span><?= e($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php 
        $flash = get_flash_message();
        if ($flash): 
        ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('pages/login.php' . (!empty($redirect) ? '?redirect=' . urlencode($redirect) : '')) ?>" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username" class="form-label">
                    Username <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    placeholder="Masukkan username" 
                    value="<?= e($_POST['username'] ?? '') ?>" 
                    required 
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="form-group" style="margin-bottom: 1.75rem;">
                <label for="password" class="form-label">
                    Password <span class="required">*</span>
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Masukkan password" 
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primary" id="btn-submit-login" style="width: 100%; height: 44px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                <span>Masuk ke Dashboard</span>
            </button>
        </form>

        <div style="margin-top: 1.75rem; text-align: center; border-top: 1px solid var(--border-subtle); padding-top: 1.25rem;">
            <p style="font-size: 0.8rem; color: var(--text-faint);">
                Login default: <code>admin</code> / <code>admin123</code>
            </p>
        </div>
    </div>
</body>
</html>
