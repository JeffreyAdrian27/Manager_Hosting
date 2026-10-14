<?php
/**
 * Cron Job: Automated Renewal Reminders Scheduler (H-14 & H-3)
 * Sistem Manager Hosting
 * 
 * Usage:
 * CLI: php cron/send_reminders.php
 * Cron schedule: 0 8 * * * php /path/to/hosting-manager/cron/send_reminders.php
 */

// Ensure script runs with CLI or authorized access
$isWeb = (php_sapi_name() !== 'cli');
if ($isWeb) {
    if (empty($_GET['cron_key'])) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=utf-8');
        echo "Akses ditolak. Silakan sertakan parameter cron_key, contoh: ?cron_key=secret\n";
        exit(1);
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = getDBConnection();
$today = date('Y-m-d');
$logPrefix = "[" . date('Y-m-d H:i:s') . " [CRON]]";

echo "{$logPrefix} Memulai eksekusi pemeriksaan pengingat perpanjangan hosting...\n";

// Query clients needing H-14:
// sisa_hari between 4 and 14 days, not yet sent H-14 for this tanggal_expired
$h14Query = "
    SELECT c.*, DATEDIFF(c.tanggal_expired, CURDATE()) AS sisa_hari
    FROM clients c
    WHERE c.deleted_at IS NULL
      AND DATEDIFF(c.tanggal_expired, CURDATE()) BETWEEN 4 AND 14
      AND NOT EXISTS (
          SELECT 1 FROM email_logs el 
          WHERE el.client_id = c.id 
            AND el.jenis = 'H-14' 
            AND el.tanggal_expired = c.tanggal_expired 
            AND el.status = 'berhasil'
      )
    ORDER BY c.tanggal_expired ASC
";

$h14Clients = $pdo->query($h14Query)->fetchAll();
$countH14 = count($h14Clients);
echo "{$logPrefix} Ditemukan {$countH14} klien yang memenuhi syarat pengingat H-14.\n";

$sentH14 = 0;
$failH14 = 0;

foreach ($h14Clients as $client) {
    echo "{$logPrefix} Mengirim pengingat H-14 ke {$client['nama']} ({$client['email']}) untuk {$client['nama_website']} (Sisa {$client['sisa_hari']} hari)... ";
    $res = send_reminder_email($client, 'H-14');
    if ($res['success']) {
        $sentH14++;
        echo "BERHASIL\n";
    } else {
        $failH14++;
        echo "GAGAL: {$res['error']}\n";
    }
}

// Query clients needing H-3:
// sisa_hari between 0 and 3 days, not yet sent H-3 for this tanggal_expired
$h3Query = "
    SELECT c.*, DATEDIFF(c.tanggal_expired, CURDATE()) AS sisa_hari
    FROM clients c
    WHERE c.deleted_at IS NULL
      AND DATEDIFF(c.tanggal_expired, CURDATE()) BETWEEN 0 AND 3
      AND NOT EXISTS (
          SELECT 1 FROM email_logs el 
          WHERE el.client_id = c.id 
            AND el.jenis = 'H-3' 
            AND el.tanggal_expired = c.tanggal_expired 
            AND el.status = 'berhasil'
      )
    ORDER BY c.tanggal_expired ASC
";

$h3Clients = $pdo->query($h3Query)->fetchAll();
$countH3 = count($h3Clients);
echo "{$logPrefix} Ditemukan {$countH3} klien yang memenuhi syarat pengingat H-3.\n";

$sentH3 = 0;
$failH3 = 0;

foreach ($h3Clients as $client) {
    echo "{$logPrefix} Mengirim pengingat H-3 ke {$client['nama']} ({$client['email']}) untuk {$client['nama_website']} (Sisa {$client['sisa_hari']} hari)... ";
    $res = send_reminder_email($client, 'H-3');
    if ($res['success']) {
        $sentH3++;
        echo "BERHASIL\n";
    } else {
        $failH3++;
        echo "GAGAL: {$res['error']}\n";
    }
}

$summary = "{$logPrefix} Selesai. Hasil: H-14 (Berhasil: {$sentH14}, Gagal: {$failH14}) | H-3 (Berhasil: {$sentH3}, Gagal: {$failH3}).\n";
echo $summary;

exit(0);
