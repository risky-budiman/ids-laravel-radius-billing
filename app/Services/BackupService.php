<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Exception;

class BackupService
{
    /**
     * Directory path for backups
     */
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Map of modules and their tables in logical order
     */
    public function getModules(): array
    {
        return [
            'billing' => [
                'name' => 'Billing & Keuangan',
                'description' => 'Tagihan pelanggan, pembayaran, pajak, template invoice, dan gateway',
                'icon' => 'banknotes',
                'tables' => [
                    'gateways',
                    'taxes',
                    'invoice_templates',
                    'invoices',
                    'payments',
                ]
            ],
            'customers' => [
                'name' => 'Pelanggan & Paket',
                'description' => 'Data profil pelanggan, paket internet, riwayat paket, dan booster kuota',
                'icon' => 'users',
                'tables' => [
                    'packages',
                    'package_histories',
                    'boosters',
                    'customers',
                    'customer_boosters',
                ]
            ],
            'radius' => [
                'name' => 'RADIUS & Jaringan',
                'description' => 'Data NAS/Router MikroTik, radcheck, radreply, group check, group reply, radacct',
                'icon' => 'signal',
                'tables' => [
                    'nas',
                    'radgroupcheck',
                    'radgroupreply',
                    'radcheck',
                    'radreply',
                    'radusergroup',
                    'radacct',
                    'radpostauth',
                ]
            ],
            'accounting' => [
                'name' => 'Akuntansi & Bank',
                'description' => 'Bagan akun (COA), jurnal umum, item jurnal, rekening bank, & transaksi bank',
                'icon' => 'calculator',
                'tables' => [
                    'chart_of_accounts',
                    'accounting_periods',
                    'journals',
                    'journal_items',
                    'bank_accounts',
                    'bank_transactions',
                ]
            ],
            'infrastructure' => [
                'name' => 'Infrastruktur FTTH',
                'description' => 'Data Wilayah, STO, STB, ODC, ODP, OLT Fiber, dan Server GenieACS',
                'icon' => 'server-stack',
                'tables' => [
                    'regions',
                    'stos',
                    'stbs',
                    'odcs',
                    'odps',
                    'acs_servers',
                    'olts',
                    'olt_pon_ports',
                    'olt_status_logs',
                    'customer_signal_caches',
                    'customer_signal_logs',
                ]
            ],
            'inventory' => [
                'name' => 'Inventaris & Aset',
                'description' => 'Kategori barang, item stok, mutasi stok, supplier, PO, & aset tetap',
                'icon' => 'archive-box',
                'tables' => [
                    'inventory_categories',
                    'suppliers',
                    'inventory_items',
                    'inventory_stocks',
                    'inventory_movements',
                    'purchase_orders',
                    'purchase_order_items',
                    'fixed_assets',
                ]
            ],
            'tickets' => [
                'name' => 'Tiket Gangguan',
                'description' => 'Tiket keluhan pelanggan dan riwayat percakapan teknisi',
                'icon' => 'chat-bubble-left-right',
                'tables' => [
                    'tickets',
                    'ticket_replies',
                ]
            ],
            'partners_sales' => [
                'name' => 'Mitra & Sales',
                'description' => 'Komisi mitra, penarikan dana mitra, komisi sales, & penarikan dana sales',
                'icon' => 'user-group',
                'tables' => [
                    'partner_commissions',
                    'partner_withdrawals',
                    'sales_commissions',
                    'sales_withdrawals',
                ]
            ],
            'whatsapp' => [
                'name' => 'WhatsApp Gateway',
                'description' => 'Template pesan broadcast & riwayat pengiriman pesan WhatsApp',
                'icon' => 'phone',
                'tables' => [
                    'whatsapp_templates',
                    'whatsapp_logs',
                ]
            ],
            'settings' => [
                'name' => 'Pengaturan & Akun Staff',
                'description' => 'Konfigurasi aplikasi, profil perusahaan, & data pengguna admin/staff',
                'icon' => 'cog-6-tooth',
                'tables' => [
                    'settings',
                    'users',
                ]
            ],
        ];
    }

    /**
     * Create a backup archive in encrypted .bak format
     */
    public function createBackup(array $selectedModuleKeys, ?string $notes = null, ?string $customPassword = null, ?string $creatorName = null): array
    {
        $allModules = $this->getModules();
        $tablesToBackup = [];
        $recordedModules = [];

        foreach ($selectedModuleKeys as $key) {
            if (isset($allModules[$key])) {
                $tablesToBackup = array_merge($tablesToBackup, $allModules[$key]['tables']);
                $recordedModules[$key] = $allModules[$key]['name'];
            }
        }

        $tablesToBackup = array_unique($tablesToBackup);

        if (empty($tablesToBackup)) {
            throw new Exception("Tidak ada tabel yang dipilih untuk dicadangkan.");
        }

        $availableTablesInDb = array_map(function ($row) {
            return array_values((array)$row)[0];
        }, DB::select('SHOW TABLES'));

        $dataPayload = [];
        $totalRecords = 0;
        $tableCounts = [];

        foreach ($tablesToBackup as $table) {
            if (!in_array($table, $availableTablesInDb)) {
                continue;
            }

            // Fetch records in chunks to prevent memory exhaustion
            $tableRecords = [];
            DB::table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use (&$tableRecords) {
                foreach ($rows as $row) {
                    $tableRecords[] = (array) $row;
                }
            });

            $count = count($tableRecords);
            $totalRecords += $count;
            $tableCounts[$table] = $count;
            $dataPayload[$table] = $tableRecords;
        }

        $metadata = [
            'format' => 'IDS-BILLING-BAK',
            'version' => '2.0.0',
            'created_at' => now()->toIso8601String(),
            'created_by' => $creatorName ?: (auth()->user()->name ?? 'Administrator'),
            'notes' => $notes ?: 'Cadangan otomatis data sistem',
            'modules' => $recordedModules,
            'tables' => array_keys($dataPayload),
            'table_counts' => $tableCounts,
            'total_records' => $totalRecords,
            'is_custom_encrypted' => !empty($customPassword),
        ];

        $package = [
            'meta' => $metadata,
            'data' => $dataPayload,
        ];

        $rawJson = json_encode($package, JSON_UNESCAPED_UNICODE);
        if (!$rawJson) {
            throw new Exception("Gagal mengompres data ke format JSON.");
        }

        // Compress with gzdeflate
        $compressed = gzdeflate($rawJson, 9);
        if ($compressed === false) {
            throw new Exception("Gagal mengompresi payload data.");
        }

        // Encrypt payload
        if (!empty($customPassword)) {
            $encryptedData = $this->encryptWithPassword($compressed, $customPassword);
        } else {
            $encryptedData = Crypt::encrypt($compressed, false);
        }

        $filename = 'backup_' . date('Y-m-d_His') . '_' . substr(md5(uniqid()), 0, 6) . '.bak';
        $fullPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        File::put($fullPath, $encryptedData);

        return [
            'filename' => $filename,
            'path' => $fullPath,
            'size' => File::size($fullPath),
            'metadata' => $metadata,
        ];
    }

    /**
     * Inspect backup file and return metadata without executing restore
     */
    public function inspectBackup(string $filePath, ?string $customPassword = null): array
    {
        if (!File::exists($filePath)) {
            throw new Exception("File backup tidak ditemukan.");
        }

        $rawContent = File::get($filePath);
        $compressed = null;

        // Try decrypting
        if (!empty($customPassword)) {
            $compressed = $this->decryptWithPassword($rawContent, $customPassword);
        } else {
            try {
                $compressed = Crypt::decrypt($rawContent, false);
            } catch (DecryptException $e) {
                // Check if it was password encrypted
                throw new Exception("File backup ini membutuhkan password enkripsi khusus atau dibuat dengan kunci sistem lain.");
            }
        }

        $json = @gzinflate($compressed);
        if ($json === false) {
            throw new Exception("Format data backup tidak valid atau file korup.");
        }

        $package = json_decode($json, true);
        if (!isset($package['meta']) || !isset($package['data'])) {
            throw new Exception("Struktur file backup (.bak) tidak sesuai format IDS-Billing.");
        }

        return [
            'metadata' => $package['meta'],
            'available_tables' => array_keys($package['data']),
            'table_counts' => $package['meta']['table_counts'] ?? [],
        ];
    }

    /**
     * Restore data strictly according to what's inside the backup file
     */
    public function restoreBackup(string $filePath, array $targetTables = [], ?string $customPassword = null, bool $wipeExisting = true): array
    {
        if (!File::exists($filePath)) {
            throw new Exception("File backup tidak ditemukan.");
        }

        $rawContent = File::get($filePath);
        $compressed = null;

        if (!empty($customPassword)) {
            $compressed = $this->decryptWithPassword($rawContent, $customPassword);
        } else {
            try {
                $compressed = Crypt::decrypt($rawContent, false);
            } catch (DecryptException $e) {
                throw new Exception("Kunci enkripsi tidak valid atau file membutuhkan password khusus.");
            }
        }

        $json = @gzinflate($compressed);
        if ($json === false) {
            throw new Exception("Gagal mengekstrak isi cadangan.");
        }

        $package = json_decode($json, true);
        if (!isset($package['data']) || !is_array($package['data'])) {
            throw new Exception("Payload data backup tidak valid.");
        }

        $allData = $package['data'];
        $tablesInFile = array_keys($allData);

        // If targetTables is empty, restore ALL tables present in the backup file
        $tablesToProcess = empty($targetTables) ? $tablesInFile : array_intersect($tablesInFile, $targetTables);

        if (empty($tablesToProcess)) {
            throw new Exception("Tidak ada tabel valid yang dapat dipulihkan dari file ini.");
        }

        $restoredStats = [];

        // Disable Foreign Key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            DB::beginTransaction();

            foreach ($tablesToProcess as $table) {
                $rows = $allData[$table] ?? [];

                if ($wipeExisting) {
                    // Use delete() instead of truncate().
                    // In MySQL, TRUNCATE is a DDL statement that triggers an implicit COMMIT, destroying active transactions!
                    DB::table($table)->delete();
                }

                $inserted = 0;
                if (!empty($rows)) {
                    // Chunk rows to prevent query payload limits
                    $chunked = array_chunk($rows, 200);
                    foreach ($chunked as $batch) {
                        DB::table($table)->insert($batch);
                        $inserted += count($batch);
                    }
                }

                $restoredStats[$table] = $inserted;
            }

            if (DB::transactionLevel() > 0) {
                DB::commit();
            }
        } catch (Exception $ex) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $ex;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        return [
            'restored_tables' => array_keys($restoredStats),
            'stats' => $restoredStats,
            'total_rows_restored' => array_sum($restoredStats),
            'metadata' => $package['meta'] ?? [],
        ];
    }

    /**
     * List all existing backup files on server storage
     */
    public function listLocalBackups(): array
    {
        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'bak') {
                continue;
            }

            $filename = $file->getFilename();
            $size = $file->getSize();
            $modified = date('Y-m-d H:i:s', $file->getMTime());

            // Try fast inspect metadata
            $meta = null;
            try {
                $inspect = $this->inspectBackup($file->getRealPath());
                $meta = $inspect['metadata'];
            } catch (Exception $e) {
                // It might be password protected or external key
                $meta = [
                    'format' => 'IDS-BILLING-BAK',
                    'notes' => 'Terkunci password / Kunci enkripsi berbeda',
                    'created_at' => $modified,
                    'created_by' => 'Unknown',
                    'modules' => [],
                    'tables' => [],
                    'total_records' => '-',
                    'is_custom_encrypted' => true,
                ];
            }

            $backups[] = [
                'filename' => $filename,
                'extension' => $file->getExtension(),
                'size' => $this->formatSize($size),
                'size_formatted' => $this->formatSize($size),
                'size_bytes' => $size,
                'created_at' => $meta['created_at'] ?? $modified,
                'created_by' => $meta['created_by'] ?? 'Administrator',
                'notes' => $meta['notes'] ?? null,
                'tables_count' => isset($meta['tables']) ? count($meta['tables']) : null,
                'total_records' => $meta['total_records'] ?? 0,
                'modified_at' => $modified,
                'metadata' => $meta,
            ];
        }

        // Sort by newest first
        usort($backups, function ($a, $b) {
            return strcmp($b['modified_at'], $a['modified_at']);
        });

        return $backups;
    }

    /**
     * Delete backup file
     */
    public function deleteBackup(string $filename): bool
    {
        $safeName = basename($filename);
        $path = $this->backupDir . DIRECTORY_SEPARATOR . $safeName;

        if (File::exists($path)) {
            return File::delete($path);
        }

        return false;
    }

    /**
     * Get backup file path safely
     */
    public function getBackupPath(string $filename): ?string
    {
        $safeName = basename($filename);
        $path = $this->backupDir . DIRECTORY_SEPARATOR . $safeName;

        return File::exists($path) ? $path : null;
    }

    /**
     * Format file size helper
     */
    public function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Custom password-based AES-256-CBC encryption
     */
    protected function encryptWithPassword(string $data, string $password): string
    {
        $salt = random_bytes(16);
        $key = hash_pbkdf2('sha256', $password, $salt, 10000, 32, true);
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $salt . $iv . $ciphertext, $key, true);

        // Header flag 'IDS-PWD' + salt + iv + hmac + ciphertext
        return 'IDS-PWD' . $salt . $iv . $hmac . $ciphertext;
    }

    /**
     * Custom password-based AES-256-CBC decryption
     */
    protected function decryptWithPassword(string $data, string $password): string
    {
        if (str_starts_with($data, 'IDS-PWD')) {
            $data = substr($data, 7);
            $salt = substr($data, 0, 16);
            $iv = substr($data, 16, 16);
            $hmac = substr($data, 32, 32);
            $ciphertext = substr($data, 64);

            $key = hash_pbkdf2('sha256', $password, $salt, 10000, 32, true);
            $expectedHmac = hash_hmac('sha256', $salt . $iv . $ciphertext, $key, true);

            if (!hash_equals($hmac, $expectedHmac)) {
                throw new Exception("Password salah atau data cadangan telah dimodifikasi.");
            }

            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($decrypted === false) {
                throw new Exception("Gagal mendekripsi file dengan password yang diberikan.");
            }

            return $decrypted;
        }

        // If not custom password format, attempt standard decrypt
        return Crypt::decrypt($data, false);
    }
}
