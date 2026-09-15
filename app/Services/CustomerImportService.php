<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadUserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Exception;

class CustomerImportService
{
    /**
     * Generate template spreadsheet with:
     * 1. Sheet 'Format Import' (Clean table ready to be filled, with dropdowns and required/optional badges)
     * 2. Sheet 'Contoh & Panduan' (Sample data & explanations placed safely outside the import table)
     * 3. Sheet 'Referensi' (Hidden sheet storing dynamic package names)
     */
    public function generateTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // ==========================================
        // SHEET 1: Format Import (Main Working Sheet)
        // ==========================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Format Import');

        // Row 1: Status Keterangan (Wajib / Opsional)
        $requirementBadges = [
            'A1' => 'WAJIB',
            'B1' => 'OPSIONAL', // Username dapat dikosongkan (otomatis di-generate)
            'C1' => 'OPSIONAL', // Password dapat dikosongkan (otomatis di-generate)
            'D1' => 'WAJIB',
            'E1' => 'WAJIB',
            'F1' => 'OPSIONAL',
            'G1' => 'OPSIONAL',
            'H1' => 'OPSIONAL',
            'I1' => 'OPSIONAL',
            'J1' => 'OPSIONAL',
            'K1' => 'OPSIONAL',
            'L1' => 'OPSIONAL',
            'M1' => 'OPSIONAL', // tgl_aktivasi
            'N1' => 'OPSIONAL', // id_pelanggan
            'O1' => 'OPSIONAL', // email
            'P1' => 'OPSIONAL', // nik_ktp
            'Q1' => 'OPSIONAL', // alamat
            'R1' => 'OPSIONAL', // serial_number_onu
            'S1' => 'OPSIONAL', // latitude
            'T1' => 'OPSIONAL', // longitude
        ];

        foreach ($requirementBadges as $cell => $badge) {
            $sheet->setCellValue($cell, "[ {$badge} ]");
            $isWajib = ($badge === 'WAJIB');
            $sheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 9,
                    'color' => ['rgb' => $isWajib ? '991B1B' : '1E3A8A'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $isWajib ? 'FEE2E2' : 'DBEAFE'], // Rose-100 or Blue-100
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Row 2: Headers definition (Programmatic column keys)
        $headers = [
            'A2' => 'nama_pelanggan',
            'B2' => 'username',
            'C2' => 'password',
            'D2' => 'nama_paket',
            'E2' => 'no_telepon',
            'F2' => 'tipe_tagihan',
            'G2' => 'metode_tagihan',
            'H2' => 'tgl_jatuh_tempo',
            'I2' => 'kenakan_ppn',
            'J2' => 'tipe_diskon',
            'K2' => 'nilai_diskon',
            'L2' => 'status',
            'M2' => 'tgl_aktivasi',
            'N2' => 'id_pelanggan',
            'O2' => 'email',
            'P2' => 'nik_ktp',
            'Q2' => 'alamat',
            'R2' => 'serial_number_onu',
            'S2' => 'latitude',
            'T2' => 'longitude',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Style header row (Row 2)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Emerald-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '047857'],
                ],
            ],
        ];
        $sheet->getStyle('A2:T2')->applyFromArray($headerStyle);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // Freeze panes so header row 1 and 2 stay visible while scrolling
        $sheet->freezePane('A3');

        // Fetch active packages
        $packages = Package::where('is_active', true)->pluck('name')->toArray();
        if (empty($packages)) {
            $packages = ['Broadband 5Mbps'];
        }

        // Hidden Reference Sheet for packages list
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Referensi');
        foreach ($packages as $idx => $pkgName) {
            $refSheet->setCellValue('A' . ($idx + 1), $pkgName);
        }
        $refSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
        $pkgCount = count($packages);

        // Apply Data Validation (Dropdowns) for data rows (Row 3 to 1000)
        for ($i = 3; $i <= 1000; $i++) {
            // 1. Dropdown Nama Paket (From hidden sheet)
            $valPackage = $sheet->getCell("D{$i}")->getDataValidation();
            $valPackage->setType(DataValidation::TYPE_LIST);
            $valPackage->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $valPackage->setAllowBlank(false);
            $valPackage->setShowDropDown(true);
            $valPackage->setFormula1("Referensi!\$A\$1:\$A\${$pkgCount}");

            // 2. Dropdown Tipe Tagihan (prepaid / postpaid)
            $valBilling = $sheet->getCell("F{$i}")->getDataValidation();
            $valBilling->setType(DataValidation::TYPE_LIST);
            $valBilling->setShowDropDown(true);
            $valBilling->setFormula1('"prepaid,postpaid"');

            // 3. Dropdown Metode Tagihan (cycle, fixed, renewal)
            $valMethod = $sheet->getCell("G{$i}")->getDataValidation();
            $valMethod->setType(DataValidation::TYPE_LIST);
            $valMethod->setShowDropDown(true);
            $valMethod->setFormula1('"cycle,fixed,renewal"');

            // 4. Dropdown Tanggal Jatuh Tempo (1-28)
            $dueDays = implode(',', range(1, 28));
            $valDue = $sheet->getCell("H{$i}")->getDataValidation();
            $valDue->setType(DataValidation::TYPE_LIST);
            $valDue->setShowDropDown(true);
            $valDue->setFormula1('"' . $dueDays . '"');

            // 5. Dropdown PPN (YA / TIDAK)
            $valTax = $sheet->getCell("I{$i}")->getDataValidation();
            $valTax->setType(DataValidation::TYPE_LIST);
            $valTax->setShowDropDown(true);
            $valTax->setFormula1('"YA,TIDAK"');

            // 6. Dropdown Tipe Diskon (nominal, persen, tanpa_diskon)
            $valDiscount = $sheet->getCell("J{$i}")->getDataValidation();
            $valDiscount->setType(DataValidation::TYPE_LIST);
            $valDiscount->setShowDropDown(true);
            $valDiscount->setFormula1('"nominal,persen,tanpa_diskon"');

            // 7. Dropdown Status
            $valStatus = $sheet->getCell("L{$i}")->getDataValidation();
            $valStatus->setType(DataValidation::TYPE_LIST);
            $valStatus->setShowDropDown(true);
            $valStatus->setFormula1('"active,waiting_activation,suspended"');

            // 8. Format Kalender / Tanggal untuk Tanggal Aktivasi (Kolom M)
            $sheet->getStyle("M{$i}")->getNumberFormat()->setFormatCode('YYYY-MM-DD');
            $valDate = $sheet->getCell("M{$i}")->getDataValidation();
            $valDate->setType(DataValidation::TYPE_DATE);
            $valDate->setOperator(DataValidation::OPERATOR_BETWEEN);
            $valDate->setFormula1('DATE(2000,1,1)');
            $valDate->setFormula2('DATE(2099,12,31)');
            $valDate->setAllowBlank(true);
            $valDate->setShowInputMessage(true);
            $valDate->setShowErrorMessage(true);
            $valDate->setErrorTitle('Format Tanggal Salah');
            $valDate->setError('Masukkan tanggal yang valid atau pilih dari kalender (contoh: 2026-01-15).');
            $valDate->setPromptTitle('Tanggal Aktivasi');
            $valDate->setPrompt('Pilih tanggal dari kalender atau ketik YYYY-MM-DD');
        }

        // Define generous column widths tailored to header titles and typical data
        $columnWidths = [
            'A' => 26, // nama_pelanggan
            'B' => 30, // username
            'C' => 18, // password
            'D' => 24, // nama_paket
            'E' => 20, // no_telepon
            'F' => 18, // tipe_tagihan
            'G' => 20, // metode_tagihan
            'H' => 18, // tgl_jatuh_tempo
            'I' => 16, // kenakan_ppn
            'J' => 18, // tipe_diskon
            'K' => 18, // nilai_diskon
            'L' => 18, // status
            'M' => 20, // tgl_aktivasi
            'N' => 22, // id_pelanggan
            'O' => 28, // email
            'P' => 24, // nik_ktp
            'Q' => 38, // alamat
            'R' => 24, // serial_number_onu
            'S' => 18, // latitude
            'T' => 18, // longitude
        ];

        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Sheet 1 is kept unrestricted (no sheet protection password lock)
        // so users can freely adjust/expand column widths manually, insert/delete rows, and edit data.

        // =============================================================
        // SHEET 2: Contoh & Panduan (Separated safely from import table)
        // =============================================================
        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Contoh & Panduan');

        $guideSheet->setCellValue('A1', 'CONTOH PENGISIAN DATA PELANGGAN (JANGAN DIIMPOR LANGSUNG, HANYA CONTOH ACUAN)');
        $guideSheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('065F46');

        $sampleHeaders = array_values($headers);
        $colIdx = 'A';
        foreach ($sampleHeaders as $sh) {
            $guideSheet->setCellValue($colIdx . '3', $sh);
            $colIdx++;
        }
        $guideSheet->getStyle('A3:T3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Get default region, sto, stb codes for valid ID Pelanggan generation pattern (12 digits: Region[3] + STO[3] + STB[3] + Random[3])
        $sampleRegion = \App\Models\Region::first()?->code ?? '001';
        $sampleSto = \App\Models\Sto::first()?->code ?? '001';
        $sampleStb = \App\Models\Stb::first()?->code ?? '001';
        $companySuffix = \App\Models\Setting::where('key', 'company_domain')->first()?->value ?? 'nextlink';

        $sampleCode1 = $sampleRegion . $sampleSto . $sampleStb . '101';
        $sampleCode2 = $sampleRegion . $sampleSto . $sampleStb . '102';

        $sampleRows = [
            [
                'Budi Santoso',
                $sampleCode1 . '@' . $companySuffix,
                'budi12345',
                $packages[0] ?? 'Broadband 5Mbps',
                '081234567890',
                'prepaid',
                'fixed',
                20,
                'YA',
                'nominal',
                20000,
                'active',
                '2026-01-15',
                $sampleCode1,
                'budi@gmail.com',
                '3201012345670001',
                'Jl. Merpati No. 12, RT 01/RW 02',
                'ZTEGC1234567',
                -6.200000,
                106.816666,
            ],
            [
                'Siti Aminah',
                $sampleCode2 . '@' . $companySuffix,
                'siti12345',
                $packages[1] ?? ($packages[0] ?? 'Broadband 5Mbps'),
                '081987654321',
                'postpaid',
                'cycle',
                20,
                'TIDAK',
                'persen',
                10,
                'active',
                '2026-02-01',
                $sampleCode2,
                'siti@yahoo.com',
                '3201017654320002',
                'Jl. Kenanga No. 5, Blok C',
                'HWTCA9876543',
                -6.210000,
                106.820000,
            ],
        ];

        $sRow = 4;
        foreach ($sampleRows as $row) {
            $c = 'A';
            foreach ($row as $val) {
                $guideSheet->setCellValueExplicit($c . $sRow, $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $c++;
            }
            $sRow++;
        }

        // Instructions table on Sheet 2
        $guideSheet->setCellValue('A7', 'PENJELASAN KOLOM & ATURAN PENGISIAN');
        $guideSheet->getStyle('A7')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E293B');

        $guideItems = [
            ['nama_pelanggan', 'WAJIB', 'Nama lengkap pengguna/pelanggan.'],
            ['username', 'OPSIONAL', 'Username PPPoE/Hotspot. Jika dikosongkan, dibuat otomatis sesuai ID Pelanggan + domain (cth: 001001001101@nextlink).'],
            ['password', 'OPSIONAL', 'Password akun. Jika dikosongkan, dibuatkan password acak 8 karakter yang aman.'],
            ['nama_paket', 'WAJIB', 'Pilih dari dropdown paket aktif yang sesuai di sistem.'],
            ['no_telepon', 'WAJIB', 'Nomor WhatsApp / HP pelanggan.'],
            ['tipe_tagihan', 'OPSIONAL', 'Pilih: prepaid (prabayar) atau postpaid (pascabayar). Default: prepaid.'],
            ['metode_tagihan', 'OPSIONAL', 'Pilih: cycle (invoice tgl 1, jatuh tempo tgl 20), fixed (anniversary), renewal. Default: fixed.'],
            ['tgl_jatuh_tempo', 'OPSIONAL', 'Tanggal jatuh tempo tagihan per bulan (angka 1 s/d 28). Default: 20.'],
            ['kenakan_ppn', 'OPSIONAL', 'Pilih YA jika tagihan dikenakan PPN, atau TIDAK jika bebas PPN.'],
            ['tipe_diskon', 'OPSIONAL', 'Pilih: nominal (potongan Rp), persen (potongan %), atau tanpa_diskon.'],
            ['nilai_diskon', 'OPSIONAL', 'Nilai potongan (contoh: 20000 untuk Rp 20.000, atau 10 untuk 10%).'],
            ['status', 'OPSIONAL', 'Pilih: active (langsung aktif), waiting_activation, suspended. Default: active.'],
            ['tgl_aktivasi', 'OPSIONAL', 'Tanggal aktif berlangganan (Format: YYYY-MM-DD, contoh: 2026-01-15). Jika kosong, menggunakan tanggal hari ini.'],
            ['id_pelanggan', 'OPSIONAL', 'ID Pelanggan 12 digit (Format sistem: [Region:3][STO:3][STB:3][Unik:3], cth: 001001001101). Jika kosong, otomatis di-generate sistem.'],
            ['email', 'OPSIONAL', 'Alamat email pelanggan.'],
            ['nik_ktp', 'OPSIONAL', 'Nomor KTP / Identitas pelanggan.'],
            ['alamat', 'OPSIONAL', 'Alamat lokasi pemasangan.'],
            ['serial_number_onu', 'OPSIONAL', 'Nomor Serial Number modem / ONT.'],
            ['latitude', 'OPSIONAL', 'Titik koordinat GPS lintang.'],
            ['longitude', 'OPSIONAL', 'Titik koordinat GPS bujur.'],
        ];

        $guideSheet->setCellValue('A8', 'Nama Kolom');
        $guideSheet->setCellValue('B8', 'Status');
        $guideSheet->setCellValue('C8', 'Keterangan Pengisian');
        $guideSheet->getStyle('A8:C8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
        ]);

        $gIdx = 9;
        foreach ($guideItems as $g) {
            $guideSheet->setCellValue('A' . $gIdx, $g[0]);
            $guideSheet->setCellValue('B' . $gIdx, $g[1]);
            $guideSheet->setCellValue('C' . $gIdx, $g[2]);

            $isWajib = ($g[1] === 'WAJIB');
            $guideSheet->getStyle('B' . $gIdx)->getFont()->setBold(true)->getColor()->setRGB($isWajib ? 'DC2626' : '2563EB');
            $gIdx++;
        }

        foreach (range('A', 'T') as $col) {
            $guideSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Protect Sheet 2 (Contoh & Panduan) completely so samples are strictly READ-ONLY and cannot be altered
        $guideSheet->getProtection()->setPassword('radius_sample_locked');
        $guideSheet->getProtection()->setSheet(true);
        $guideSheet->getProtection()->setSelectLockedCells(true);
        $guideSheet->getProtection()->setSelectUnlockedCells(true);

        // Set default active sheet to the first sheet (Format Import)
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Import customers from uploaded spreadsheet (.xlsx, .xls, or .csv)
     */
    public function importFile(string $filePath, array $options = []): array
    {
        $duplicateAction = $options['duplicate_action'] ?? 'skip'; // 'skip' or 'update'

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        // Always target the main sheet (by name 'Format Import' or active sheet index 0)
        $sheet = $spreadsheet->getSheetByName('Format Import') ?: $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            throw new Exception("File Excel kosong atau tidak memuat data yang dapat diimpor.");
        }

        // Detect header row (Row 1 or Row 2)
        $headerRow = null;
        $startIndex = 1;

        foreach ($rows as $rowIndex => $row) {
            $rowValues = array_map('trim', array_map('strtolower', array_filter($row)));
            if (in_array('nama_pelanggan', $rowValues) && in_array('username', $rowValues)) {
                $headerRow = $rowValues;
                $startIndex = $rowIndex;
                break;
            }
        }

        if (!$headerRow) {
            throw new Exception("Baris judul kolom (header 'nama_pelanggan', 'username') tidak ditemukan pada sheet.");
        }

        $colMap = [];
        foreach ($rows[$startIndex] as $colLetter => $headerName) {
            $normalized = strtolower(trim((string)$headerName));
            if (!empty($normalized)) {
                $colMap[$normalized] = $colLetter;
            }
        }

        $requiredHeaders = ['nama_pelanggan', 'nama_paket', 'no_telepon'];
        foreach ($requiredHeaders as $req) {
            if (!isset($colMap[$req])) {
                throw new Exception("Kolom wajib '{$req}' tidak ditemukan pada template Excel.");
            }
        }

        // Cache packages by name (case-insensitive)
        $packages = Package::all()->keyBy(function ($item) {
            return strtolower(trim($item->name));
        });

        // Company domain suffix for auto-generating username
        $companySuffix = \App\Models\Setting::where('key', 'company_domain')->first()?->value ?? 'net.id';

        $totalProcessed = 0;
        $successCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Loop data starting after header row
        $currentRowIndex = 0;
        foreach ($rows as $rowIndex => $row) {
            $currentRowIndex++;
            if ($rowIndex <= $startIndex) {
                continue; // Skip badge row and header row
            }

            $name = trim((string)($row[$colMap['nama_pelanggan']] ?? ''));
            $username = trim((string)($row[$colMap['username'] ?? ''] ?? ''));
            $password = trim((string)($row[$colMap['password'] ?? ''] ?? ''));
            $packageName = trim((string)($row[$colMap['nama_paket']] ?? ''));
            $phone = trim((string)($row[$colMap['no_telepon']] ?? ''));

            // Ignore completely empty rows
            if (empty($name) && empty($username) && empty($packageName) && empty($phone)) {
                continue;
            }

            $totalProcessed++;

            // Validate mandatory fields (nama_pelanggan and nama_paket are strictly required)
            if (empty($name) || empty($packageName)) {
                $errors[] = [
                    'row' => $rowIndex,
                    'username' => $username ?: ($name ?: '-'),
                    'reason' => 'Nama pelanggan atau nama paket tidak boleh kosong.'
                ];
                $skippedCount++;
                continue;
            }

            // Find package
            $matchedPackage = $packages->get(strtolower($packageName));
            if (!$matchedPackage) {
                $errors[] = [
                    'row' => $rowIndex,
                    'username' => $username,
                    'reason' => "Paket internet '{$packageName}' tidak ditemukan pada sistem billing."
                ];
                $skippedCount++;
                continue;
            }

            // Parse billing_type (prepaid / postpaid)
            $billingType = strtolower(trim((string)($row[$colMap['tipe_tagihan'] ?? ''] ?? 'prepaid')));
            if (!in_array($billingType, ['prepaid', 'postpaid'])) {
                $billingType = 'prepaid';
            }

            // Parse billing_method (cycle, fixed, renewal)
            $billingMethod = strtolower(trim((string)($row[$colMap['metode_tagihan'] ?? ''] ?? '')));
            if (!in_array($billingMethod, ['cycle', 'fixed', 'renewal'])) {
                $billingMethod = ($billingType === 'postpaid') ? 'cycle' : 'fixed';
            }

            // Parse billing_due_day (1 - 28)
            $billingDueDay = (int)($row[$colMap['tgl_jatuh_tempo'] ?? ''] ?? 20);
            if ($billingDueDay < 1 || $billingDueDay > 28) {
                $billingDueDay = 20;
            }

            // Parse PPN
            $useTaxStr = strtoupper(trim((string)($row[$colMap['kenakan_ppn'] ?? ''] ?? '')));
            $useTax = in_array($useTaxStr, ['YA', 'YES', '1', 'TRUE']);

            // Parse discount
            $discountTypeRaw = strtolower(trim((string)($row[$colMap['tipe_diskon'] ?? ''] ?? '')));
            $discountType = null;
            if (in_array($discountTypeRaw, ['nominal', 'fixed', 'rp'])) {
                $discountType = 'fixed';
            } elseif (in_array($discountTypeRaw, ['persen', 'percentage', '%'])) {
                $discountType = 'percentage';
            }

            $discountValue = (float)($row[$colMap['nilai_diskon'] ?? ''] ?? 0);
            if ($discountValue <= 0) {
                $discountType = null;
                $discountValue = 0;
            }

            // Parse status
            $status = strtolower(trim((string)($row[$colMap['status'] ?? ''] ?? 'active')));
            if (!in_array($status, ['active', 'waiting_activation', 'suspended'])) {
                $status = 'active';
            }
            $isActive = ($status === 'active');

            // Parse customer code according to system billing rules (12 digits: Region[3] + STO[3] + STB[3] + Random[3])
            $customerCode = trim((string)($row[$colMap['id_pelanggan'] ?? ''] ?? ''));
            $regCode = '001';
            $stCode = '001';
            $tbCode = '001';

            if (empty($customerCode)) {
                // If username was provided and starts with numbers before '@' (e.g. 001001001123@nextlink)
                if (!empty($username) && preg_match('/^(\d{9,12})/i', $username, $matches)) {
                    $customerCode = $matches[1];
                } else {
                    // Generate according to system billing convention: Region(3) + STO(3) + STB(3) + 3 Random Digits
                    $firstReg = \App\Models\Region::first()?->code ?? '001';
                    $firstSto = \App\Models\Sto::first()?->code ?? '001';
                    $firstStb = \App\Models\Stb::first()?->code ?? '001';
                    $customerCode = $firstReg . $firstSto . $firstStb . rand(100, 999);
                }
            }

            // Extract region_code, sto_code, stb_code if customer_code is 12 digits
            if (strlen($customerCode) >= 9 && ctype_digit(substr($customerCode, 0, 9))) {
                $regCode = substr($customerCode, 0, 3);
                $stCode = substr($customerCode, 3, 3);
                $tbCode = substr($customerCode, 6, 3);
            }

            // Auto-generate username if blank (like the manual create form)
            if (empty($username)) {
                $username = $customerCode . '@' . $companySuffix;
            }

            // Auto-generate password if blank (random 8 alphanumeric chars)
            if (empty($password)) {
                $password = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            }

            // Parse activated_at (Tanggal Aktivasi)
            $activatedAtRaw = trim((string)($row[$colMap['tgl_aktivasi'] ?? ''] ?? ''));
            $activatedAt = null;
            if (!empty($activatedAtRaw)) {
                try {
                    // Check if numeric Excel timestamp or standard date string
                    if (is_numeric($activatedAtRaw)) {
                        $activatedAt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$activatedAtRaw);
                    } else {
                        $activatedAt = \Carbon\Carbon::parse($activatedAtRaw);
                    }
                } catch (\Exception $e) {
                    $activatedAt = now();
                }
            } elseif ($isActive) {
                $activatedAt = now();
            }

            $email = trim((string)($row[$colMap['email'] ?? ''] ?? '')) ?: null;
            $ktp = trim((string)($row[$colMap['nik_ktp'] ?? ''] ?? '')) ?: null;
            $address = trim((string)($row[$colMap['alamat'] ?? ''] ?? '')) ?: null;
            $onuSn = trim((string)($row[$colMap['serial_number_onu'] ?? ''] ?? '')) ?: null;
            $lat = is_numeric($row[$colMap['latitude'] ?? '']) ? (float)$row[$colMap['latitude']] : null;
            $lng = is_numeric($row[$colMap['longitude'] ?? '']) ? (float)$row[$colMap['longitude']] : null;

            // Check existing customer
            $existing = Customer::where('username', $username)->first();

            if ($existing) {
                if ($duplicateAction === 'skip') {
                    $skippedCount++;
                    $errors[] = [
                        'row' => $rowIndex,
                        'username' => $username,
                        'reason' => 'Username sudah ada (dilewati sesuai pengaturan).'
                    ];
                    continue;
                } else {
                    // UPDATE existing customer
                    DB::transaction(function () use ($existing, $name, $password, $matchedPackage, $phone, $billingType, $billingMethod, $billingDueDay, $useTax, $discountType, $discountValue, $status, $isActive, $customerCode, $regCode, $stCode, $tbCode, $activatedAt, $email, $ktp, $address, $onuSn, $lat, $lng, $username) {
                        $existing->update([
                            'name' => $name,
                            'password' => $password,
                            'package_id' => $matchedPackage->id,
                            'phone' => $phone,
                            'billing_type' => $billingType,
                            'billing_method' => $billingMethod,
                            'billing_due_day' => $billingDueDay,
                            'use_tax' => $useTax,
                            'discount_type' => $discountType,
                            'discount_value' => $discountValue,
                            'status' => $status,
                            'is_active' => $isActive,
                            'activated_at' => $activatedAt ?? $existing->activated_at,
                            'customer_code' => $customerCode,
                            'region_code' => $regCode,
                            'sto_code' => $stCode,
                            'stb_code' => $tbCode,
                            'email' => $email,
                            'ktp' => $ktp,
                            'address' => $address,
                            'onu_sn' => $onuSn,
                            'latitude' => $lat,
                            'longitude' => $lng,
                        ]);

                        if ($existing->is_active) {
                            $existing->syncBillingDates();
                        }

                        // Sync RADIUS
                        RadCheck::updateOrCreate(
                            ['username' => $username, 'attribute' => 'Cleartext-Password'],
                            ['op' => ':=', 'value' => $password]
                        );

                        RadUserGroup::updateOrCreate(
                            ['username' => $username],
                            ['groupname' => $matchedPackage->name, 'priority' => 1]
                        );
                    });

                    $updatedCount++;
                    continue;
                }
            }

            // CREATE new customer
            try {
                DB::transaction(function () use ($name, $username, $password, $matchedPackage, $phone, $billingType, $billingMethod, $billingDueDay, $useTax, $discountType, $discountValue, $status, $isActive, $customerCode, $regCode, $stCode, $tbCode, $activatedAt, $email, $ktp, $address, $onuSn, $lat, $lng) {
                    $customer = Customer::create([
                        'customer_code' => $customerCode,
                        'name' => $name,
                        'username' => $username,
                        'password' => $password,
                        'package_id' => $matchedPackage->id,
                        'phone' => $phone,
                        'billing_type' => $billingType,
                        'billing_method' => $billingMethod,
                        'billing_day' => 1,
                        'billing_due_day' => $billingDueDay,
                        'billing_next_date' => now()->startOfMonth(),
                        'billing_due_date' => now()->startOfMonth()->setDay(min($billingDueDay, 28)),
                        'use_tax' => $useTax,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                        'status' => $status,
                        'is_active' => $isActive,
                        'activated_at' => $activatedAt,
                        'customer_type' => Customer::TYPE_PERSONAL,
                        'email' => $email,
                        'ktp' => $ktp,
                        'address' => $address,
                        'onu_sn' => $onuSn,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'region_code' => $regCode,
                        'sto_code' => $stCode,
                        'stb_code' => $tbCode,
                    ]);

                    if ($customer->is_active) {
                        $customer->syncBillingDates();
                    }

                    // Sync RADIUS Auth
                    RadCheck::create([
                        'username' => $username,
                        'attribute' => 'Cleartext-Password',
                        'op' => ':=',
                        'value' => $password,
                    ]);

                    // Sync RADIUS UserGroup
                    RadUserGroup::create([
                        'username' => $username,
                        'groupname' => $matchedPackage->name,
                        'priority' => 1,
                    ]);
                });

                $successCount++;
            } catch (Exception $e) {
                Log::error("Failed to import customer row {$rowIndex}: " . $e->getMessage());
                $errors[] = [
                    'row' => $rowIndex,
                    'username' => $username,
                    'reason' => 'Kesalahan database: ' . $e->getMessage()
                ];
                $skippedCount++;
            }
        }

        return [
            'total_rows' => $totalProcessed,
            'success' => $successCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Export all customers to a spreadsheet compatible with the import template format.
     * This allows easy data migration to another billing system.
     *
     * @param array $filters Optional filters: status, package_id, billing_type, search
     */
    public function exportCustomersSpreadsheet(array $filters = []): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pelanggan');

        // Company domain suffix for username display
        $companySuffix = \App\Models\Setting::where('key', 'company_domain')->first()?->value ?? 'net.id';

        // Row 1: Status Keterangan (same as import template)
        $requirementBadges = [
            'A1' => 'WAJIB',
            'B1' => 'OPSIONAL',
            'C1' => 'OPSIONAL',
            'D1' => 'WAJIB',
            'E1' => 'WAJIB',
            'F1' => 'OPSIONAL',
            'G1' => 'OPSIONAL',
            'H1' => 'OPSIONAL',
            'I1' => 'OPSIONAL',
            'J1' => 'OPSIONAL',
            'K1' => 'OPSIONAL',
            'L1' => 'OPSIONAL',
            'M1' => 'OPSIONAL',
            'N1' => 'OPSIONAL',
            'O1' => 'OPSIONAL',
            'P1' => 'OPSIONAL',
            'Q1' => 'OPSIONAL',
            'R1' => 'OPSIONAL',
            'S1' => 'OPSIONAL',
            'T1' => 'OPSIONAL',
        ];

        foreach ($requirementBadges as $cell => $badge) {
            $sheet->setCellValue($cell, "[ {$badge} ]");
            $isWajib = ($badge === 'WAJIB');
            $sheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 9,
                    'color' => ['rgb' => $isWajib ? '991B1B' : '1E3A8A'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $isWajib ? 'FEE2E2' : 'DBEAFE'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Row 2: Headers (same structure as import template)
        $headers = [
            'A2' => 'nama_pelanggan',
            'B2' => 'username',
            'C2' => 'password',
            'D2' => 'nama_paket',
            'E2' => 'no_telepon',
            'F2' => 'tipe_tagihan',
            'G2' => 'metode_tagihan',
            'H2' => 'tgl_jatuh_tempo',
            'I2' => 'kenakan_ppn',
            'J2' => 'tipe_diskon',
            'K2' => 'nilai_diskon',
            'L2' => 'status',
            'M2' => 'tgl_aktivasi',
            'N2' => 'id_pelanggan',
            'O2' => 'email',
            'P2' => 'nik_ktp',
            'Q2' => 'alamat',
            'R2' => 'serial_number_onu',
            'S2' => 'latitude',
            'T2' => 'longitude',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Style header row
        $sheet->getStyle('A2:T2')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Blue-600 (different from import green to distinguish)
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1D4ED8'],
                ],
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->freezePane('A3');

        // Build query with optional filters
        $query = Customer::with('package');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }
        if (!empty($filters['billing_type'])) {
            $query->where('billing_type', $filters['billing_type']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->get();

        // Populate rows
        $rowIdx = 3;
        foreach ($customers as $customer) {
            // Map discount_type back to template format
            $discountType = 'tanpa_diskon';
            if ($customer->discount_type === 'fixed') {
                $discountType = 'nominal';
            } elseif ($customer->discount_type === 'percentage') {
                $discountType = 'persen';
            }

            $sheet->setCellValueExplicit('A' . $rowIdx, $customer->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $rowIdx, $customer->username, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $rowIdx, $customer->password, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rowIdx, $customer->package?->name ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $rowIdx, $customer->phone ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('F' . $rowIdx, $customer->billing_type ?? 'prepaid');
            $sheet->setCellValue('G' . $rowIdx, $customer->billing_method ?? 'fixed');
            $sheet->setCellValue('H' . $rowIdx, $customer->billing_due_day ?? 20);
            $sheet->setCellValue('I' . $rowIdx, $customer->use_tax ? 'YA' : 'TIDAK');
            $sheet->setCellValue('J' . $rowIdx, $discountType);
            $sheet->setCellValue('K' . $rowIdx, $customer->discount_value ?? 0);
            $sheet->setCellValue('L' . $rowIdx, $customer->status ?? 'active');
            $sheet->setCellValue('M' . $rowIdx, $customer->activated_at ? $customer->activated_at->format('Y-m-d') : '');
            $sheet->setCellValueExplicit('N' . $rowIdx, $customer->customer_code ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('O' . $rowIdx, $customer->email ?? '');
            $sheet->setCellValueExplicit('P' . $rowIdx, $customer->ktp ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('Q' . $rowIdx, $customer->address ?? '');
            $sheet->setCellValueExplicit('R' . $rowIdx, $customer->onu_sn ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('S' . $rowIdx, $customer->latitude);
            $sheet->setCellValue('T' . $rowIdx, $customer->longitude);

            // Alternate row colors for readability
            if ($rowIdx % 2 === 0) {
                $sheet->getStyle("A{$rowIdx}:T{$rowIdx}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);
            }

            $rowIdx++;
        }

        // Apply borders to data area
        if ($rowIdx > 3) {
            $lastRow = $rowIdx - 1;
            $sheet->getStyle("A3:T{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);
        }

        // Column widths (same as import template)
        $columnWidths = [
            'A' => 26, 'B' => 30, 'C' => 18, 'D' => 24, 'E' => 20,
            'F' => 18, 'G' => 20, 'H' => 18, 'I' => 16, 'J' => 18,
            'K' => 18, 'L' => 18, 'M' => 20, 'N' => 22, 'O' => 28,
            'P' => 24, 'Q' => 38, 'R' => 24, 'S' => 18, 'T' => 18,
        ];
        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // ====================================
        // SHEET 2: Ringkasan Export
        // ====================================
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Ringkasan Export');

        $summarySheet->setCellValue('A1', 'RINGKASAN EXPORT DATA PELANGGAN');
        $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E3A8A');

        $summaryItems = [
            ['Tanggal Export', now()->format('d/m/Y H:i:s')],
            ['Total Pelanggan', $customers->count()],
            ['Pelanggan Aktif', $customers->where('status', 'active')->count()],
            ['Pelanggan Suspended', $customers->where('status', 'suspended')->count()],
            ['Pelanggan Waiting', $customers->where('status', 'waiting_activation')->count()],
            ['', ''],
            ['CARA MENGGUNAKAN FILE INI:', ''],
            ['1.', 'File ini bisa langsung diimpor ke sistem billing lain yang mendukung format yang sama.'],
            ['2.', 'Buka menu Import Pelanggan di sistem tujuan, lalu unggah file ini.'],
            ['3.', 'Pastikan paket internet di sistem tujuan sudah dibuat terlebih dahulu dengan nama yang sama.'],
            ['4.', 'Kolom username dan password sudah terisi, sehingga akun PPPoE/RADIUS akan otomatis tersinkron.'],
        ];

        $sIdx = 3;
        foreach ($summaryItems as $item) {
            $summarySheet->setCellValue('A' . $sIdx, $item[0]);
            $summarySheet->setCellValue('B' . $sIdx, $item[1]);

            if (in_array($item[0], ['Tanggal Export', 'Total Pelanggan', 'Pelanggan Aktif', 'Pelanggan Suspended', 'Pelanggan Waiting'])) {
                $summarySheet->getStyle('A' . $sIdx)->getFont()->setBold(true);
            }
            if ($item[0] === 'CARA MENGGUNAKAN FILE INI:') {
                $summarySheet->getStyle('A' . $sIdx)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('059669');
            }

            $sIdx++;
        }

        $summarySheet->getColumnDimension('A')->setWidth(30);
        $summarySheet->getColumnDimension('B')->setWidth(80);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}

