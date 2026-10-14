@echo off
title Eksekusi Pengingat Hosting Otomatis
cd /d "%~dp0"
echo ====================================================
echo Menjalankan Pengecekan Pengingat Hosting (H-14 & H-3)
echo ====================================================
php cron\send_reminders.php
echo.
echo ====================================================
echo Selesai. Jendela ini akan tertutup otomatis...
timeout /t 5 >nul
