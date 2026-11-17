<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadUserGroup;
use App\Services\PackageChangeService;
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

        // Kolom berformat TEKS: dulu angka 0 di depan No. HP / ID pelanggan hilang dan NIK 16 digit
        // dibulatkan Excel (presisi angka Excel hanya 15 digit).
        foreach (['B', 'C', 'E', 'N', 'P', 'R'] as $textCol) {
            $sheet->getStyle("{$textCol}3:{$textCol}1000")->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
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
            ['no_telepon', 'WAJIB', 'Nomor WhatsApp / HP pelanggan (wajib untuk pelanggan baru). Kolom berformat teks agar angka 0 di depan tidak hilang.'],
            ['tipe_tagihan', 'OPSIONAL', 'Pilih: prepaid (prabayar) atau postpaid (pascabayar). Kosong = prepaid. Nilai lain ditolak.'],
            ['metode_tagihan', 'OPSIONAL', 'Pilih: cycle (invoice tgl 1, jatuh tempo tgl 20), fixed (anniversary), renewal. Kosong = cycle (postpaid) / fixed (prepaid). Nilai lain ditolak.'],
            ['tgl_jatuh_tempo', 'OPSIONAL', 'Tanggal jatuh tempo tagihan per bulan (angka 1 s/d 28). Kosong = 20. Di luar 1-28 ditolak.'],
            ['kenakan_ppn', 'OPSIONAL', 'Pilih YA jika tagihan dikenakan PPN, atau TIDAK jika bebas PPN. Kosong = TIDAK.'],
            ['tipe_diskon', 'OPSIONAL', 'Pilih: nominal (potongan Rp), persen (potongan %), atau tanpa_diskon.'],
            ['nilai_diskon', 'OPSIONAL', 'Nilai potongan: 20000 atau 20.000 untuk Rp 20.000; 10 untuk 10%. Persen maks. 100; nominal tidak boleh melebihi harga paket.'],
            ['status', 'OPSIONAL', 'Pilih: active (langsung aktif), waiting_activation, suspended. Kosong = active. Hanya berlaku untuk pelanggan baru.'],
            ['tgl_aktivasi', 'OPSIONAL', 'Tanggal aktif berlangganan (YYYY-MM-DD atau DD/MM/YYYY, contoh: 2026-01-15). Kosong = hari ini (status active). Tanggal yang tidak dikenali ditolak.'],
            ['id_pelanggan', 'OPSIONAL', 'ID Pelanggan 12 digit (Format sistem: [Region:3][STO:3][STB:3][Unik:3], cth: 001001001101). Kosong = dibuat otomatis tanpa bentrok. ID yang sudah dipakai pelanggan lain ditolak.'],
            ['email', 'OPSIONAL', 'Alamat email pelanggan (format email yang valid).'],
            ['nik_ktp', 'OPSIONAL', 'Nomor KTP 16 digit. Kolom WAJIB berformat teks: bila berformat angka, Excel membulatkan digit terakhir dan NIK diabaikan.'],
            ['alamat', 'OPSIONAL', 'Alamat lokasi pemasangan.'],
            ['serial_number_onu', 'OPSIONAL', 'Nomor Serial Number modem / ONT.'],
            ['latitude', 'OPSIONAL', 'Titik koordinat GPS lintang (-90 s/d 90). Titik atau koma desimal diterima (-6.2088 / -6,2088).'],
            ['longitude', 'OPSIONAL', 'Titik koordinat GPS bujur (-180 s/d 180). Titik atau koma desimal diterima.'],
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
     * Import pelanggan dari spreadsheet (.xlsx, .xls, .csv).
     *
     * Setiap baris diurai & divalidasi lengkap dulu; baris yang bermasalah dilewati dengan alasan yang jelas
     * (dulu nilai yang tidak dikenal diam-diam diganti nilai bawaan). Dengan opsi `dry_run` tidak ada yang
     * disimpan: hasilnya pratinjau aksi per baris (dibuat / diperbarui / dilewati).
     */
    /** Jumlah baris data (tanpa judul) untuk memutuskan impor langsung atau di latar belakang. */
    public function countDataRows(string $filePath): int
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $info = method_exists($reader, 'listWorksheetInfo') ? $reader->listWorksheetInfo($filePath) : [];
        if ($info) {
            $sheet = collect($info)->firstWhere('worksheetName', 'Format Import') ?? $info[0];

            return max(0, (int) ($sheet['totalRows'] ?? 0) - 1);
        }

        return max(0, $reader->load($filePath)->getActiveSheet()->getHighestDataRow() - 1);
    }

    public function importFile(string $filePath, array $options = []): array
    {
        $duplicateAction = $options['duplicate_action'] ?? 'skip'; // 'skip' or 'update'
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $sheet = $spreadsheet->getSheetByName('Format Import') ?: $spreadsheet->getSheet(0);
        // Nilai mentah (formatData=false): dulu nilai dibaca sebagai teks hasil format Excel
        // (mis. NIK menjadi "1.20511E+15", angka 20000 menjadi "20,000").
        $rows = $sheet->toArray(null, true, false, true);

        if (count($rows) < 2) {
            throw new Exception('File Excel kosong atau tidak memuat data yang dapat diimpor.');
        }

        $headerRow = null;
        $startIndex = 1;
        foreach ($rows as $rowIndex => $row) {
            $rowValues = array_map(fn ($v) => strtolower(trim($this->cellText($v))), array_filter($row, fn ($v) => $v !== null && $v !== ''));
            if (in_array('nama_pelanggan', $rowValues) && in_array('username', $rowValues)) {
                $headerRow = $rowValues;
                $startIndex = $rowIndex;
                break;
            }
        }
        if (! $headerRow) {
            throw new Exception("Baris judul kolom (header 'nama_pelanggan', 'username') tidak ditemukan pada sheet.");
        }

        $colMap = [];
        foreach ($rows[$startIndex] as $colLetter => $headerName) {
            $normalized = strtolower(trim($this->cellText($headerName)));
            if ($normalized !== '') {
                $colMap[$normalized] = $colLetter;
            }
        }
        foreach (['nama_pelanggan', 'nama_paket', 'no_telepon'] as $req) {
            if (! isset($colMap[$req])) {
                throw new Exception("Kolom wajib '{$req}' tidak ditemukan pada template Excel.");
            }
        }

        $packages = Package::all()->keyBy(fn ($item) => strtolower(trim($item->name)));
        $companySuffix = \App\Models\Setting::where('key', 'company_domain')->first()?->value ?? 'net.id';
        $defaultPrefix = (\App\Models\Region::first()?->code ?? '001') . (\App\Models\Sto::first()?->code ?? '001') . (\App\Models\Stb::first()?->code ?? '001');

        $totalProcessed = 0;
        $successCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $notes = [];     // informasi per baris yang tidak menggagalkan baris
        $preview = [];   // dry-run: rencana aksi per baris
        $reservedCodes = [];
        $seenUsernames = [];
        $seenKtp = [];

        // Impor latar belakang: laporkan kemajuan (baris ke-n dari total baris data) tiap 25 baris
        $progress = $options['progress'] ?? null;
        $dataRows = max(0, count($rows) - $startIndex);
        $seen = 0;

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex <= $startIndex) {
                continue;
            }
            $seen++;
            if ($progress && ($seen % 25 === 0 || $seen === $dataRows)) {
                $progress($seen, $dataRows);
            }

            $rawCell = fn (string $key) => isset($colMap[$key]) ? ($row[$colMap[$key]] ?? null) : null;
            $cell = fn (string $key) => trim($this->cellText($rawCell($key)));

            $name = $cell('nama_pelanggan');
            $username = $cell('username');
            $password = $cell('password');
            $packageName = $cell('nama_paket');
            $phone = $cell('no_telepon');

            if ($name === '' && $username === '' && $packageName === '' && $phone === '') {
                continue;
            }
            $totalProcessed++;

            // Username dari file (bukan yang dibuat otomatis) agar baris yang ditolak tidak tampak punya akun
            $fileUsername = $username;
            $fail = function (string $reason) use (&$errors, &$skippedCount, $rowIndex, $fileUsername, $name) {
                $errors[] = ['row' => $rowIndex, 'username' => $fileUsername ?: ($name ?: '-'), 'reason' => $reason];
                $skippedCount++;
            };

            if ($name === '' || $packageName === '') {
                $fail('Nama pelanggan atau nama paket tidak boleh kosong.');
                continue;
            }
            $matchedPackage = $packages->get(strtolower($packageName));
            if (! $matchedPackage) {
                $fail("Paket internet '{$packageName}' tidak ditemukan pada sistem billing.");
                continue;
            }

            $existing = $username !== '' ? Customer::where('username', $username)->first() : null;
            if ($existing && $duplicateAction === 'skip') {
                $fail('Username sudah ada (dilewati sesuai pengaturan).');
                continue;
            }
            $isNew = ! $existing;

            $problems = [];
            $rowNotes = [];

            if ($username !== '') {
                if (isset($seenUsernames[strtolower($username)])) {
                    $problems[] = "Username muncul lebih dari sekali di file (baris {$seenUsernames[strtolower($username)]}).";
                }
                $seenUsernames[strtolower($username)] = $rowIndex;
            }

            // No. HP: wajib untuk pelanggan baru; angka 0 di depan yang hilang karena format angka Excel dikembalikan
            if ($phone !== '') {
                $phone = preg_replace('/[^\d+]/', '', $phone);
                if (preg_match('/^8\d{8,12}$/', $phone)) {
                    $phone = '0' . $phone;
                    $rowNotes[] = 'No. HP tanpa angka 0 di depan; ditambahkan otomatis.';
                }
            } elseif ($isNew) {
                $problems[] = 'no_telepon wajib diisi untuk pelanggan baru.';
            }

            // Tipe & metode tagihan, jatuh tempo, PPN: kosong = bawaan; nilai tidak dikenal = ditolak
            $billingType = strtolower($cell('tipe_tagihan')) ?: 'prepaid';
            if (! in_array($billingType, ['prepaid', 'postpaid'], true)) {
                $problems[] = "tipe_tagihan '{$cell('tipe_tagihan')}' tidak dikenal (isi prepaid atau postpaid).";
            }
            $billingMethod = strtolower($cell('metode_tagihan')) ?: ($billingType === 'postpaid' ? 'cycle' : 'fixed');
            if (! in_array($billingMethod, ['cycle', 'fixed', 'renewal'], true)) {
                $problems[] = "metode_tagihan '{$cell('metode_tagihan')}' tidak dikenal (isi cycle, fixed atau renewal).";
            }
            $billingDueDay = 20;
            if ($cell('tgl_jatuh_tempo') !== '') {
                $d = $this->parseNumber($rawCell('tgl_jatuh_tempo'), 'money');
                if ($d === null || floor($d) != $d || $d < 1 || $d > 28) {
                    $problems[] = "tgl_jatuh_tempo '{$cell('tgl_jatuh_tempo')}' harus angka 1–28.";
                } else {
                    $billingDueDay = (int) $d;
                }
            }
            $useTax = false;
            $taxRaw = strtoupper($cell('kenakan_ppn'));
            if ($taxRaw !== '') {
                if (in_array($taxRaw, ['YA', 'YES', 'Y', '1', 'TRUE'], true)) {
                    $useTax = true;
                } elseif (! in_array($taxRaw, ['TIDAK', 'NO', 'N', '0', 'FALSE'], true)) {
                    $problems[] = "kenakan_ppn '{$cell('kenakan_ppn')}' tidak dikenal (isi YA atau TIDAK).";
                }
            }

            // Diskon: "20.000" = 20000 (dulu terbaca 20); persen maksimal 100
            $discountTypeRaw = strtolower($cell('tipe_diskon'));
            $discountType = match (true) {
                in_array($discountTypeRaw, ['', 'tanpa_diskon', 'tanpa', '-'], true) => null,
                in_array($discountTypeRaw, ['nominal', 'fixed', 'rp'], true) => 'fixed',
                in_array($discountTypeRaw, ['persen', 'percentage', '%'], true) => 'percentage',
                default => false,
            };
            if ($discountType === false) {
                $problems[] = "tipe_diskon '{$cell('tipe_diskon')}' tidak dikenal (isi nominal, persen atau tanpa_diskon).";
                $discountType = null;
            }
            $discountValue = 0.0;
            if ($cell('nilai_diskon') !== '') {
                $v = $this->parseNumber($rawCell('nilai_diskon'), 'money');
                if ($v === null || $v < 0) {
                    $problems[] = "nilai_diskon '{$cell('nilai_diskon')}' bukan angka yang valid.";
                } else {
                    $discountValue = $v;
                }
            }
            if ($discountValue > 0 && $discountType === null && $discountTypeRaw === '') {
                $problems[] = 'nilai_diskon diisi tapi tipe_diskon kosong (isi nominal atau persen).';
            }
            if ($discountType === 'percentage' && $discountValue > 100) {
                $problems[] = "Diskon persen tidak boleh lebih dari 100 (terisi {$cell('nilai_diskon')}).";
            }
            if ($discountType === 'fixed' && $discountValue > (float) $matchedPackage->price) {
                $problems[] = 'Diskon nominal melebihi harga paket.';
            }
            if ($discountValue <= 0) {
                $discountType = null;
                $discountValue = 0.0;
            }

            // Status: hanya berlaku untuk pelanggan baru (pelanggan lama tidak diubah lewat impor)
            $statusRaw = strtolower($cell('status'));
            $status = $statusRaw ?: 'active';
            if (! in_array($status, ['active', 'waiting_activation', 'suspended'], true)) {
                if ($isNew) {
                    $problems[] = "status '{$cell('status')}' tidak dikenal (isi active, waiting_activation atau suspended).";
                }
                $status = 'active';
            }
            $isActive = ($status === 'active');

            // ID pelanggan: angka 0 di depan dikembalikan; harus belum dipakai; dibuat otomatis tanpa bentrok
            $customerCode = $cell('id_pelanggan');
            if ($customerCode !== '' && (is_int($rawCell('id_pelanggan')) || is_float($rawCell('id_pelanggan'))) && ctype_digit($customerCode) && strlen($customerCode) < 12) {
                $customerCode = str_pad($customerCode, 12, '0', STR_PAD_LEFT);
                $rowNotes[] = "ID pelanggan berformat angka; angka 0 di depan dikembalikan ({$customerCode}).";
            }
            $codeFromFile = $customerCode !== '';
            if (! $codeFromFile && $username !== '' && preg_match('/^(\d{9,12})/', $username, $m) && ! $existing) {
                $customerCode = $m[1];
            }
            if ($customerCode !== '') {
                if (! preg_match('/^[A-Za-z0-9\-]{1,20}$/', $customerCode)) {
                    $problems[] = "ID pelanggan '{$customerCode}' tidak valid (huruf/angka, maks. 20).";
                } elseif (in_array($customerCode, $reservedCodes, true)
                    || Customer::where('customer_code', $customerCode)->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
                    if ($isNew && ! $codeFromFile) {
                        $customerCode = '';   // dari username tapi sudah dipakai: buat baru
                    } elseif ($isNew || $codeFromFile) {
                        $problems[] = "ID pelanggan {$customerCode} sudah dipakai pelanggan lain.";
                    }
                }
            }
            if ($customerCode === '' && $isNew) {
                $customerCode = Customer::nextFreeCode($defaultPrefix, $reservedCodes) ?? '';
                if ($customerCode === '') {
                    $problems[] = "Nomor ID pelanggan untuk prefix {$defaultPrefix} sudah habis; isi id_pelanggan manual.";
                }
            }
            $regCode = '001';
            $stCode = '001';
            $tbCode = '001';
            if (strlen($customerCode) >= 9 && ctype_digit(substr($customerCode, 0, 9))) {
                $regCode = substr($customerCode, 0, 3);
                $stCode = substr($customerCode, 3, 3);
                $tbCode = substr($customerCode, 6, 3);
            }

            if ($username === '') {
                $username = $customerCode . '@' . $companySuffix;
            }
            if ($password === '') {
                $password = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            }

            // Tanggal aktivasi: tanggal yang tidak dikenali ditolak (dulu diganti hari ini)
            $activatedAt = null;
            if ($cell('tgl_aktivasi') !== '') {
                $activatedAt = $this->parseDate($rawCell('tgl_aktivasi'));
                if (! $activatedAt) {
                    $problems[] = "tgl_aktivasi '{$cell('tgl_aktivasi')}' tidak dikenali (pakai format YYYY-MM-DD).";
                }
            } elseif ($isActive) {
                $activatedAt = now();
            }

            $email = $cell('email') ?: null;
            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $problems[] = "Email '{$email}' tidak valid.";
            }

            // NIK: sel berformat angka sudah dibulatkan Excel (maks. 15 digit) → tidak disimpan
            $ktp = $cell('nik_ktp') ?: null;
            if ($ktp !== null && (is_int($rawCell('nik_ktp')) || is_float($rawCell('nik_ktp'))) && strlen($ktp) >= 15) {
                $rowNotes[] = 'NIK diabaikan: kolom berformat angka sehingga digit terakhir dibulatkan Excel. Ubah kolom nik_ktp ke format teks.';
                $ktp = null;
            }
            if ($ktp !== null) {
                if (isset($seenKtp[$ktp])) {
                    $problems[] = "NIK {$ktp} muncul lebih dari sekali di file (baris {$seenKtp[$ktp]}).";
                } elseif (Customer::where('ktp', $ktp)->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
                    $problems[] = "NIK {$ktp} sudah dipakai pelanggan lain.";
                }
                $seenKtp[$ktp] = $rowIndex;
            }

            $address = $cell('alamat') ?: null;
            $onuSn = $cell('serial_number_onu') ?: null;

            // Koordinat: koma desimal ("-6,2088") diterima; di luar rentang ditolak (dulu diam-diam diabaikan)
            $lat = $lng = null;
            foreach (['latitude' => 90, 'longitude' => 180] as $key => $limit) {
                if ($cell($key) === '') {
                    continue;
                }
                $v = $this->parseNumber($rawCell($key), 'coord');
                if ($v === null || abs($v) > $limit) {
                    $problems[] = "{$key} '{$cell($key)}' tidak valid.";
                } elseif ($key === 'latitude') {
                    $lat = $v;
                } else {
                    $lng = $v;
                }
            }

            if ($problems) {
                $fail(implode(' ', $problems));
                continue;
            }
            if ($customerCode !== '') {
                $reservedCodes[] = $customerCode;
            }
            foreach ($rowNotes as $n) {
                $notes[] = ['row' => $rowIndex, 'username' => $username, 'reason' => $n];
            }
            if (! $isNew && $statusRaw !== '' && $status !== $existing->status) {
                $notes[] = ['row' => $rowIndex, 'username' => $username,
                    'reason' => "Status di file ('{$statusRaw}') diabaikan; status saat ini '{$existing->status}' tetap. Ubah lewat menu pelanggan."];
            }

            if ($dryRun) {
                $preview[] = [
                    'row' => $rowIndex, 'username' => $username, 'name' => $name,
                    'action' => $isNew ? 'dibuat' : 'diperbarui',
                    'detail' => $isNew
                        ? "ID {$customerCode}, paket {$matchedPackage->name}, status {$status}"
                        : ((int) $existing->package_id !== (int) $matchedPackage->id ? "paket → {$matchedPackage->name}" : 'kolom yang diisi diperbarui'),
                ];
                $isNew ? $successCount++ : $updatedCount++;
                continue;
            }

            if ($existing) {
                // UPDATE — hanya kolom yang diisi. Dulu kolom kosong menimpa data lama dan password kosong
                // diganti password acak sehingga pelanggan terputus.
                try {
                    $changes = ['name' => $name];
                    if ($phone !== '') $changes['phone'] = $phone;
                    if ($cell('tipe_tagihan') !== '') $changes['billing_type'] = $billingType;
                    if ($cell('metode_tagihan') !== '') $changes['billing_method'] = $billingMethod;
                    if ($cell('tgl_jatuh_tempo') !== '') $changes['billing_due_day'] = $billingDueDay;
                    if ($cell('kenakan_ppn') !== '') $changes['use_tax'] = $useTax;
                    if ($cell('tipe_diskon') !== '' || $cell('nilai_diskon') !== '') {
                        $changes['discount_type'] = $discountType;
                        $changes['discount_value'] = $discountValue;
                    }
                    if ($cell('tgl_aktivasi') !== '' && $activatedAt) $changes['activated_at'] = $activatedAt;
                    if ($codeFromFile) {
                        $changes += ['customer_code' => $customerCode, 'region_code' => $regCode, 'sto_code' => $stCode, 'stb_code' => $tbCode];
                    }
                    foreach (['email' => $email, 'ktp' => $ktp, 'address' => $address, 'onu_sn' => $onuSn, 'latitude' => $lat, 'longitude' => $lng] as $field => $value) {
                        if ($value !== null) $changes[$field] = $value;
                    }
                    $newPassword = $cell('password') !== '' ? $password : null;
                    if ($newPassword !== null) $changes['password'] = $newPassword;

                    DB::transaction(function () use ($existing, $changes, $newPassword, $matchedPackage, $username) {
                        $existing->update($changes);
                        if ($existing->is_active && ! $existing->billing_next_date) {
                            $existing->syncBillingDates();
                        }
                        if ($newPassword !== null) {
                            RadCheck::updateOrCreate(['username' => $username, 'attribute' => 'Cleartext-Password'], ['op' => ':=', 'value' => $newPassword]);
                        }
                        // Ganti paket lewat alur resmi: grup RADIUS (tetap ISOLIR bila terisolir), putus sesi, profil ONU
                        if ((int) $existing->package_id !== (int) $matchedPackage->id) {
                            app(PackageChangeService::class)->apply($existing->fresh(), $matchedPackage);
                        }
                    });
                    $updatedCount++;
                } catch (Exception $e) {
                    $fail($e->getMessage());
                }
                continue;
            }

            // CREATE
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
                        // suspended: dibuat aktif dulu, diisolir setelah akun RADIUS dibuat (lihat bawah)
                        'status' => $status === 'suspended' ? 'active' : $status,
                        'is_active' => $isActive || $status === 'suspended',
                        'activated_at' => $activatedAt ?? ($status === 'suspended' ? now() : null),
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

                    // updateOrCreate: sisa baris RADIUS lama untuk username yang sama tidak menjadi ganda
                    RadCheck::updateOrCreate(['username' => $username, 'attribute' => 'Cleartext-Password'], ['op' => ':=', 'value' => $password]);
                    RadUserGroup::updateOrCreate(['username' => $username], ['groupname' => $matchedPackage->name, 'priority' => 1]);

                    // Pelanggan terisolir di sistem lama: tetap terisolir (observer memindahkan ke grup ISOLIR).
                    if ($status === 'suspended') {
                        $customer->update(['status' => Customer::STATUS_SUSPENDED, 'is_active' => false, 'suspended_at' => now()]);
                    }
                });
                $successCount++;
            } catch (Exception $e) {
                Log::error("Failed to import customer row {$rowIndex}: " . $e->getMessage());
                $fail('Kesalahan database: ' . $e->getMessage());
            }
        }

        return [
            'dry_run' => $dryRun,
            'total_rows' => $totalProcessed,
            'success' => $successCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
            'notes' => $notes,
            'preview' => $preview,
        ];
    }

    /** Teks sel dari nilai mentah: angka bulat besar tidak menjadi notasi ilmiah (mis. NIK, No. HP). */
    private function cellText(mixed $v): string
    {
        return match (true) {
            $v === null => '',
            is_bool($v) => $v ? '1' : '0',
            is_int($v) => (string) $v,
            is_float($v) && floor($v) == $v && abs($v) < 1e18 => sprintf('%.0f', $v),
            default => (string) $v,
        };
    }

    /**
     * Angka dari sel. Mode 'money': "20.000" = 20000, "20.000,50" = 20000.5, "1,5" = 1.5.
     * Mode 'coord': koma desimal ("-6,2088") diterima.
     */
    private function parseNumber(mixed $v, string $mode): ?float
    {
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        $s = str_replace([' ', 'Rp', 'rp', 'RP', '%'], '', trim((string) $v));
        if ($s === '') {
            return null;
        }
        if ($mode === 'money') {
            if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
                $s = str_replace(['.', ','], ['', '.'], $s);
            } elseif (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
                $s = str_replace(',', '', $s);
            } elseif (preg_match('/^-?\d+,\d+$/', $s)) {
                $s = str_replace(',', '.', $s);
            }
        } elseif (preg_match('/^-?\d+,\d+$/', $s)) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    /** Tanggal dari sel: nomor seri Excel, YYYY-MM-DD, DD/MM/YYYY atau DD-MM-YYYY. */
    private function parseDate(mixed $v): ?\Carbon\Carbon
    {
        try {
            if (is_int($v) || is_float($v)) {
                return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v));
            }
            $s = trim((string) $v);
            foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s'] as $format) {
                $d = \Carbon\Carbon::createFromFormat('!' . $format, $s);
                if ($d && $d->format($format) === $s && $d->year >= 2000 && $d->year <= 2099) {
                    return $d;
                }
            }
        } catch (\Throwable) {
            // jatuh ke null
        }

        return null;
    }

    /**
     * Export all customers to a spreadsheet compatible with the import template format.
     * This allows easy data migration to another billing system.
     *
     * @param array $filters Optional filters: status, package_id, billing_type, search
     */
    /** Kolom ekspor: A–T sama dengan template impor (bisa diimpor ulang), sisanya kolom informasi (diabaikan saat impor). */
    public const EXPORT_IMPORT_COLUMNS = ['nama_pelanggan', 'username', 'password', 'nama_paket', 'no_telepon', 'tipe_tagihan', 'metode_tagihan',
        'tgl_jatuh_tempo', 'kenakan_ppn', 'tipe_diskon', 'nilai_diskon', 'status', 'tgl_aktivasi', 'id_pelanggan', 'email', 'nik_ktp',
        'alamat', 'serial_number_onu', 'latitude', 'longitude'];

    public const EXPORT_INFO_COLUMNS = ['info_tipe_pelanggan', 'info_biaya_instalasi', 'info_wilayah_sto_stb', 'info_odp', 'info_olt',
        'info_posisi_onu', 'info_tunggakan', 'info_invoice_belum_lunas', 'info_sales', 'info_mitra', 'info_tgl_daftar', 'info_tgl_berhenti'];

    /** Query ekspor (filter sama dengan daftar pelanggan) + tunggakan per pelanggan dalam satu query. */
    public function exportQuery(array $filters = [])
    {
        $unpaid = fn ($agg) => \App\Models\Invoice::query()->selectRaw($agg)->whereColumn('invoices.customer_id', 'customers.id')
            ->where('status', 'unpaid')->where('invoice_number', 'not like', 'INV-BOOST-%');

        return Customer::query()
            ->select('customers.*')
            ->selectSub($unpaid('COALESCE(SUM(amount), 0)'), 'export_arrears')
            ->selectSub($unpaid('COUNT(*)'), 'export_unpaid_count')
            ->with(['package:id,name', 'olt:id,name', 'odp:id,name', 'sales:id,name', 'partner:id,name'])
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['package_id']), fn ($q) => $q->where('package_id', $filters['package_id']))
            ->when(! empty($filters['billing_type']), fn ($q) => $q->where('billing_type', $filters['billing_type']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $like = '%' . addcslashes((string) $filters['search'], '%_\\') . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('username', 'like', $like)
                    ->orWhere('customer_code', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->orderBy('name')->orderBy('id');
    }

    /** Satu baris ekspor (urutan = EXPORT_IMPORT_COLUMNS + EXPORT_INFO_COLUMNS). */
    public function exportRow(Customer $c, bool $withPasswords): array
    {
        $discount = ['fixed' => 'nominal', 'percentage' => 'persen'][$c->discount_type] ?? 'tanpa_diskon';

        return [
            $c->name, $c->username,
            // Kosong bila password tidak diminta — impor ulang tidak mengubah password (kolom kosong = tetap)
            $withPasswords ? (string) $c->password : '',
            $c->package?->name ?? '', $c->phone ?? '', $c->billing_type ?? 'prepaid', $c->billing_method ?? 'fixed',
            $c->billing_due_day ?? 20, $c->use_tax ? 'YA' : 'TIDAK', $discount, (float) ($c->discount_value ?? 0),
            $c->status ?? 'active', $c->activated_at?->format('Y-m-d') ?? '', $c->customer_code ?? '', $c->email ?? '',
            $c->ktp ?? '', $c->address ?? '', $c->onu_sn ?? '', $c->latitude, $c->longitude,
            // informasi
            $c->customer_type ?? '', (float) ($c->installation_fee ?? 0),
            trim(implode('/', array_filter([$c->region_code, $c->sto_code, $c->stb_code])), '/'),
            trim(($c->odp?->name ?? '') . ($c->odp_port ? " port {$c->odp_port}" : '')),
            $c->olt?->name ?? '', $c->onu_index ?? '',
            (float) ($c->export_arrears ?? 0), (int) ($c->export_unpaid_count ?? 0),
            $c->sales?->name ?? '', $c->partner?->name ?? '',
            $c->created_at?->format('Y-m-d') ?? '', $c->terminated_at?->format('Y-m-d') ?? '',
        ];
    }

    /** Jumlah pelanggan per status untuk filter yang sama (satu query, semua status). */
    public function exportStatusCounts(array $filters = []): array
    {
        return $this->exportQuery($filters)->reorder()->setEagerLoads([])
            ->select('status')->selectRaw('COUNT(*) AS n')->groupBy('status')->pluck('n', 'status')->map(fn ($n) => (int) $n)->all();
    }

    /**
     * Ekspor Excel. Data dibaca bertahap (lazy, 500 baris) dan tanpa gaya per baris agar ribuan pelanggan
     * tidak menghabiskan memori. Untuk data sangat besar gunakan ekspor CSV (streaming).
     */
    public function exportCustomersSpreadsheet(array $filters = [], bool $withPasswords = false): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pelanggan');

        $headers = array_merge(self::EXPORT_IMPORT_COLUMNS, self::EXPORT_INFO_COLUMNS);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $importLast = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(self::EXPORT_IMPORT_COLUMNS));
        $required = ['nama_pelanggan', 'nama_paket', 'no_telepon'];

        // Baris 1: keterangan kolom; baris 2: judul (sama dengan template impor)
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", str_starts_with($h, 'info_') ? '[ INFO ]' : (in_array($h, $required, true) ? '[ WAJIB ]' : '[ OPSIONAL ]'));
            $sheet->setCellValue("{$col}2", $h);
        }
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray(['font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '1E3A8A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle("A2:{$importLast}2")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $infoFirst = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(self::EXPORT_IMPORT_COLUMNS) + 1);
        $sheet->getStyle("{$infoFirst}1:{$lastCol}2")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => '334155']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']]]);
        $sheet->freezePane('B3');

        // Kolom teks (No. HP, ID, NIK, SN, posisi ONU) agar tidak rusak saat diedit di Excel lalu diimpor ulang
        $textCols = ['username', 'password', 'no_telepon', 'id_pelanggan', 'nik_ktp', 'serial_number_onu', 'info_posisi_onu', 'info_wilayah_sto_stb'];
        $textIdx = array_map(fn ($h) => array_search($h, $headers, true), $textCols);
        $text = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;

        $row = 3;
        foreach ($this->exportQuery($filters)->lazy(500) as $customer) {
            foreach ($this->exportRow($customer, $withPasswords) as $i => $value) {
                $cell = [$i + 1, $row];
                if (in_array($i, $textIdx, true)) {
                    $sheet->setCellValueExplicit($cell, (string) $value, $text);
                } elseif ($value !== null && $value !== '') {
                    $sheet->setCellValue($cell, $value);
                }
            }
            $row++;
        }
        $lastRow = max(3, $row - 1);
        foreach ($textIdx as $i) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getStyle("{$col}3:{$col}" . max($row + 200, 1000))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }
        foreach (['info_biaya_instalasi', 'info_tunggakan', 'nilai_diskon'] as $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(array_search($h, $headers, true) + 1);
            $sheet->getStyle("{$col}3:{$col}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        }
        foreach ($headers as $i => $h) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1))
                ->setWidth(['nama_pelanggan' => 26, 'username' => 28, 'alamat' => 38, 'email' => 26, 'info_odp' => 22, 'info_olt' => 20][$h] ?? 18);
        }
        if ($row > 3) {
            $sheet->setAutoFilter("A2:{$lastCol}{$lastRow}");
        }

        // Ringkasan: SEMUA status (dulu hanya aktif/terisolir/menunggu aktivasi)
        $summary = $spreadsheet->createSheet();
        $summary->setTitle('Ringkasan Export');
        $summary->setCellValue('A1', 'RINGKASAN EXPORT DATA PELANGGAN');
        $summary->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E3A8A');
        $counts = $this->exportStatusCounts($filters);
        $labels = ['new' => 'Baru', 'waiting_activation' => 'Menunggu aktivasi', 'active' => 'Aktif', 'suspended' => 'Terisolir',
            'waiting_dismantle' => 'Menunggu dismantle', 'dismantled' => 'Dicabut', 'canceled' => 'Batal'];
        $items = [['Tanggal export', now()->format('d/m/Y H:i')], ['Total pelanggan', array_sum($counts)]];
        foreach ($labels as $k => $label) {
            $items[] = ["  {$label}", $counts[$k] ?? 0];
        }
        foreach (array_diff_key($counts, $labels) as $k => $n) {
            $items[] = ["  {$k}", $n];
        }
        $items = array_merge($items, [['', ''], ['CARA MEMAKAI FILE INI', ''],
            ['1.', 'Kolom A–T sama dengan template impor: file bisa diimpor ulang (mode Perbarui) atau ke sistem lain dengan format sama.'],
            ['2.', 'Kolom berawalan info_ (abu-abu) hanya informasi — tunggakan, OLT, ODP, sales, dll. — dan diabaikan saat impor.'],
            ['3.', 'Paket di sistem tujuan harus sudah dibuat dengan nama yang sama.'],
            ['4.', $withPasswords ? 'Kolom password TERISI: file ini RAHASIA, simpan & hapus dengan aman.'
                : 'Kolom password sengaja kosong; impor ulang (mode Perbarui) tidak mengubah password.']]);
        foreach ($items as $i => [$a, $b]) {
            $summary->setCellValue('A' . ($i + 3), $a);
            $summary->setCellValue('B' . ($i + 3), $b);
        }
        $summary->getStyle('A3:A4')->getFont()->setBold(true);
        $summary->getColumnDimension('A')->setWidth(30);
        $summary->getColumnDimension('B')->setWidth(90);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /** Ekspor CSV streaming (memori tetap kecil berapa pun jumlah pelanggan). */
    public function streamCustomersCsv(array $filters = [], bool $withPasswords = false): void
    {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // BOM agar Excel membaca UTF-8
        fputcsv($out, array_merge(self::EXPORT_IMPORT_COLUMNS, self::EXPORT_INFO_COLUMNS), ';');
        foreach ($this->exportQuery($filters)->lazy(500) as $customer) {
            fputcsv($out, array_map(fn ($v) => is_float($v) ? (string) $v : $v, $this->exportRow($customer, $withPasswords)), ';');
        }
        fclose($out);
    }
}

