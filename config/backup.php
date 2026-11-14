<?php

/*
 * Backup database otomatis (app:backup-database). Dibaca lewat config() agar tetap
 * berlaku setelah `php artisan config:cache` (update.sh), saat env() di luar config/ kosong.
 */
return [
    // Koneksi database yang di-backup (default: koneksi utama aplikasi)
    'connection' => env('BACKUP_DB_CONNECTION'),

    // Folder penyimpanan lokal (default storage/app/backups/db)
    'directory' => env('BACKUP_DIRECTORY'),

    // Path mysqldump bila tidak ada di PATH (mis. C:/laragon/bin/mysql/.../mysqldump.exe)
    // Kosong = dicari otomatis (PATH, folder instalasi MySQL/MariaDB, lokasi umum)
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', ''),

    // Salinan ke disk lain di config/filesystems.php (mis. s3, sftp). Kosong = hanya lokal.
    'remote_disk' => env('BACKUP_REMOTE_DISK', ''),
    'remote_path' => env('BACKUP_REMOTE_PATH', 'radius-billing/db'),

    // Rotasi: N file harian terbaru + file pertama tiap bulan selama M bulan
    'keep_daily' => (int) env('BACKUP_KEEP_DAILY', 7),
    'keep_monthly' => (int) env('BACKUP_KEEP_MONTHLY', 6),
];
