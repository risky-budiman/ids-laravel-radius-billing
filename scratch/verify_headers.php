<?php
// Script to verify header consistency between Format Import and Contoh & Panduan sheets

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CustomerImportService;

$service = new CustomerImportService();
$spreadsheet = $service->generateTemplateSpreadsheet();

// Sheet 1: Format Import
$sheet1 = $spreadsheet->getSheet(0);
echo "=== Sheet 1: " . $sheet1->getTitle() . " ===\n";
echo "Row 1 (Badges):\n";
for ($col = 'A'; $col <= 'T'; $col++) {
    $val = $sheet1->getCell($col . '1')->getValue();
    echo "  {$col}1: {$val}\n";
}
echo "\nRow 2 (Headers):\n";
$sheet1Headers = [];
for ($col = 'A'; $col <= 'T'; $col++) {
    $val = $sheet1->getCell($col . '2')->getValue();
    $sheet1Headers[$col] = $val;
    echo "  {$col}2: {$val}\n";
}

// Sheet 2: Contoh & Panduan
$sheet2 = $spreadsheet->getSheetByName('Contoh & Panduan');
echo "\n=== Sheet 2: " . $sheet2->getTitle() . " ===\n";
echo "Row 3 (Headers):\n";
$sheet2Headers = [];
for ($col = 'A'; $col <= 'T'; $col++) {
    $val = $sheet2->getCell($col . '3')->getValue();
    $sheet2Headers[$col] = $val;
    echo "  {$col}3: {$val}\n";
}

// Compare headers
echo "\n=== HEADER COMPARISON ===\n";
$mismatches = 0;
foreach ($sheet1Headers as $col => $header1) {
    $header2 = $sheet2Headers[$col] ?? '(MISSING)';
    if ($header1 !== $header2) {
        echo "  ❌ MISMATCH at column {$col}: Sheet1='{$header1}' vs Sheet2='{$header2}'\n";
        $mismatches++;
    }
}
if ($mismatches === 0) {
    echo "  ✅ All headers match between both sheets!\n";
}

// Verify guide items match headers
echo "\n=== GUIDE ITEMS ===\n";
$guideRow = 9;
$guideNames = [];
while (true) {
    $name = $sheet2->getCell('A' . $guideRow)->getValue();
    if (empty($name)) break;
    $status = $sheet2->getCell('B' . $guideRow)->getValue();
    $desc = $sheet2->getCell('C' . $guideRow)->getValue();
    $guideNames[] = $name;
    echo "  Row {$guideRow}: {$name} [{$status}]\n";
    $guideRow++;
}

echo "\n=== GUIDE vs HEADERS CHECK ===\n";
$headerValues = array_values($sheet1Headers);
foreach ($headerValues as $idx => $h) {
    if (!in_array($h, $guideNames)) {
        echo "  ⚠️ Header '{$h}' not found in guide items\n";
    }
}
foreach ($guideNames as $g) {
    if (!in_array($g, $headerValues)) {
        echo "  ⚠️ Guide item '{$g}' not found in headers\n";
    }
}
if (count(array_diff($headerValues, $guideNames)) === 0 && count(array_diff($guideNames, $headerValues)) === 0) {
    echo "  ✅ All guide items match all headers!\n";
}

// Check sample data column count
echo "\n=== SAMPLE DATA CHECK ===\n";
for ($row = 4; $row <= 5; $row++) {
    $nonEmpty = 0;
    for ($col = 'A'; $col <= 'T'; $col++) {
        $val = $sheet2->getCell($col . $row)->getValue();
        if (!empty($val)) $nonEmpty++;
    }
    echo "  Row {$row}: {$nonEmpty} columns filled (expected: 20)\n";
}

// Check data validations on Format Import
echo "\n=== DATA VALIDATION CHECK (Row 3) ===\n";
$validationCols = ['D', 'F', 'G', 'H', 'I', 'J', 'L', 'M'];
foreach ($validationCols as $vc) {
    $cell = $sheet1->getCell("{$vc}3");
    $dv = $cell->getDataValidation();
    $type = $dv->getType();
    $formula = $dv->getFormula1();
    echo "  {$vc}3: type={$type}, formula={$formula}\n";
}

echo "\n✅ Verification complete!\n";
