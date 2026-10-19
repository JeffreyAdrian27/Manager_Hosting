<?php
/**
 * PHPMailer Wrapper & Email Sending Service
 * Sistem Manager Hosting
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Instantiate and configure PHPMailer with current settings
 */
function create_configured_mailer(): PHPMailer {
    $mail = new PHPMailer(true);

    $smtpHost = get_setting('smtp_host', 'smtp.gmail.com');
    $smtpPort = (int)get_setting('smtp_port', '587');
    $smtpUser = get_setting('smtp_user', '');
    $smtpPass = get_setting('smtp_pass', '');
    $smtpSecure = get_setting('smtp_secure', 'tls');
    $adminEmail = get_setting('admin_email', 'admin@example.com');
    $adminName = get_setting('atas_nama', 'Admin Hosting');

    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = !empty($smtpUser);
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->Port       = $smtpPort;
    $mail->CharSet    = 'UTF-8';

    if (!empty($smtpSecure) && in_array(strtolower($smtpSecure), ['tls', 'ssl'])) {
        $mail->SMTPSecure = strtolower($smtpSecure) === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
    }

    // Sender details
    $fromEmail = !empty($smtpUser) && filter_var($smtpUser, FILTER_VALIDATE_EMAIL) ? $smtpUser : $adminEmail;
    $mail->setFrom($fromEmail, $adminName);
    if (!empty($adminEmail) && $fromEmail !== $adminEmail) {
        $mail->addReplyTo($adminEmail, $adminName);
    }

    return $mail;
}

/**
 * Render email template with placeholders replaced
 */
function render_email_template(string $rawTemplate, array $client): string {
    $replacements = [
        '[Nama Klien]'          => $client['nama'] ?? '',
        '[Nama Website]'        => $client['nama_website'] ?? '',
        '[Tanggal Expired]'     => format_tanggal($client['tanggal_expired'] ?? ''),
        '[Nominal]'             => format_rupiah($client['biaya'] ?? 0, false),
        '[Nama Bank / E-Wallet]' => get_setting('nama_bank', 'BCA'),
        '[Nomor Rekening]'      => get_setting('no_rekening', '-'),
        '[Nama Kamu]'           => get_setting('atas_nama', 'Admin Hosting'),
    ];

    $rendered = str_replace(array_keys($replacements), array_values($replacements), $rawTemplate);
    return $rendered;
}

/**
 * Send reminder email to client and notify admin
 * 
 * @param array $client Client data array
 * @param string $jenis 'H-14', 'H-3', or 'manual'
 * @return array ['success' => bool, 'error' => string|null]
 */
function send_reminder_email(array $client, string $jenis): array {
    $pdo = getDBConnection();
    $adminEmail = get_setting('admin_email');
    $template = get_setting('template_email');

    $subject = "Pengingat Perpanjangan Hosting – " . ($client['nama_website'] ?? 'Website');
    $bodyContent = render_email_template($template, $client);
    // Convert newlines to HTML paragraphs/breaks for HTML email
    $bodyHtml = nl2br(e($bodyContent));

    $htmlLayout = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .email-header { background: #1e293b; color: #ffffff; padding: 20px 24px; }
        .email-header h2 { margin: 0; font-size: 18px; font-weight: 600; }
        .email-body { padding: 24px; font-size: 15px; line-height: 1.6; }
        .email-footer { background: #f1f5f9; padding: 16px 24px; font-size: 12px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; }
        .nota-box { margin-top: 20px; padding: 14px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; text-align: center; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2>Pemberitahuan Layanan Hosting</h2>
        </div>
        <div class="email-body">
            {$bodyHtml}
        </div>
        <div class="email-footer">
            Pesan ini dikirim otomatis oleh Sistem Manager Hosting untuk {$client['nama_website']}.
        </div>
    </div>
</body>
</html>
HTML;

    $status = 'gagal';
    $errorMessage = null;

    try {
        $mail = create_configured_mailer();
        $mail->addAddress($client['email'], $client['nama']);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlLayout;
        $mail->AltBody = strip_tags($bodyContent);

        // Attach nota image if exists
        if (!empty($client['gambar'])) {
            $notaPath = __DIR__ . '/../uploads/nota/' . $client['gambar'];
            if (file_exists($notaPath)) {
                $mail->addAttachment($notaPath, 'Bukti_Nota_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $client['nama_website']) . '.' . pathinfo($notaPath, PATHINFO_EXTENSION));
            }
        }

        $mail->send();
        $status = 'berhasil';
    } catch (Exception $e) {
        $status = 'gagal';
        $errorMessage = $mail->ErrorInfo ?: $e->getMessage();
    }

    // Log to email_logs table
    try {
        $sentAt = date('Y-m-d H:i:s');
        $logStmt = $pdo->prepare("
            INSERT INTO email_logs (client_id, jenis, tanggal_expired, status, pesan_error, sent_at)
            VALUES (:client_id, :jenis, :tanggal_expired, :status, :pesan_error, :sent_at)
        ");
        $logStmt->execute([
            ':client_id' => $client['id'],
            ':jenis' => $jenis,
            ':tanggal_expired' => $client['tanggal_expired'],
            ':status' => $status,
            ':pesan_error' => $errorMessage,
            ':sent_at' => $sentAt,
        ]);
    } catch (PDOException $pe) {
        error_log("Failed to insert email log: " . $pe->getMessage());
    }

    // If client email sent successfully, notify admin
    if ($status === 'berhasil' && !empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $adminMail = create_configured_mailer();
            $adminMail->addAddress($adminEmail, 'Admin');
            $adminMail->isHTML(true);
            $adminMail->Subject = "[Notifikasi Admin] Pengingat {$jenis} Terkirim: {$client['nama_website']}";
            
            $adminNoticeHtml = "
                <p>Halo Admin,</p>
                <p>Email pengingat perpanjangan hosting telah berhasil dikirim ke klien:</p>
                <ul>
                    <li><strong>Nama Klien:</strong> " . e($client['nama']) . "</li>
                    <li><strong>Email Klien:</strong> " . e($client['email']) . "</li>
                    <li><strong>Nama Website:</strong> " . e($client['nama_website']) . "</li>
                    <li><strong>Tanggal Expired:</strong> " . format_tanggal($client['tanggal_expired']) . "</li>
                    <li><strong>Biaya:</strong> " . format_rupiah($client['biaya']) . "</li>
                    <li><strong>Jenis Pengingat:</strong> " . e($jenis) . "</li>
                </ul>
                <p>Silakan pantau pembayaran dari klien yang bersangkutan.</p>
            ";
            $adminMail->Body = $adminNoticeHtml;
            $adminMail->AltBody = strip_tags($adminNoticeHtml);
            $adminMail->send();
        } catch (Exception $adminEx) {
            error_log("Failed to send admin notification email: " . $adminEx->getMessage());
        }
    }

    return [
        'success' => ($status === 'berhasil'),
        'error' => $errorMessage,
    ];
}
