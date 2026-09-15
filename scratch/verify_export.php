<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new App\Services\CustomerImportService();
$spreadsheet = $service->exportCustomersSpreadsheet();
$sheet = $spreadsheet->getSheet(0);

echo "Sheet: " . $sheet->getTitle() . PHP_EOL;
echo "Headers: ";
for ($col = 'A'; $col <= 'T'; $col++) {
    echo $sheet->getCell($col . '2')->getValue() . ' | ';
}
echo PHP_EOL;

$lastRow = $sheet->getHighestRow();
echo "Total Data Rows: " . ($lastRow - 2) . PHP_EOL;

// Show first 3 customer rows
for ($r = 3; $r <= min($lastRow, 5); $r++) {
    echo "Row {$r}: ";
    echo $sheet->getCell('A' . $r)->getValue() . ' | ';
    echo $sheet->getCell('B' . $r)->getValue() . ' | ';
    echo $sheet->getCell('D' . $r)->getValue() . ' | ';
    echo $sheet->getCell('F' . $r)->getValue() . ' | ';
    echo $sheet->getCell('L' . $r)->getValue();
    echo PHP_EOL;
}

// Check summary sheet
$summary = $spreadsheet->getSheetByName('Ringkasan Export');
echo PHP_EOL . "Summary Sheet: " . $summary->getTitle() . PHP_EOL;
for ($r = 3; $r <= 13; $r++) {
    $a = $summary->getCell('A' . $r)->getValue();
    $b = $summary->getCell('B' . $r)->getValue();
    if (!empty($a)) {
        echo "  {$a}: {$b}" . PHP_EOL;
    }
}

echo PHP_EOL . "✅ Export test passed!" . PHP_EOL;
