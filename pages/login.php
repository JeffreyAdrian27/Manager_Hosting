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

        <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?>" novalidate>
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
                <div style="position: relative;">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Masukkan password" 
                        required
                        autocomplete="current-password"
                        style="padding-right: 2.75rem;"
                    >
                    <button 
                        type="button" 
                        id="toggle-password" 
                        style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0.25rem;" 
                        aria-label="Lihat password"
                        title="Tampilkan / Sembunyikan password"
                    >
                        <svg id="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
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

    <script>
        document.getElementById('toggle-password')?.addEventListener('click', function () {
            const pwdInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');
            const isPassword = pwdInput.type === 'password';

            pwdInput.type = isPassword ? 'text' : 'password';
            eyeIcon.style.display = isPassword ? 'none' : 'block';
            eyeOffIcon.style.display = isPassword ? 'block' : 'none';
            this.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Lihat password');
        });
    </script>
</body>
</html>
