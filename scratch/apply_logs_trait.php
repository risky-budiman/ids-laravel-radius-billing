<?php

$models = [
    'Sto.php',
    'Stb.php',
    'Supplier.php',
    'InventoryItem.php',
    'InventoryCategory.php'
];

foreach ($models as $filename) {
    $path = "app/Models/" . $filename;
    if (!file_exists($path)) continue;

    $content = file_get_contents($path);
    
    // Add import if not exists
    if (strpos($content, 'use App\Traits\LogsActivity;') === false) {
        $content = str_replace('namespace App\Models;', "namespace App\Models;\n\nuse App\Traits\LogsActivity;", $content);
    }
    
    // Add use trait inside class if not exists
    if (strpos($content, 'use LogsActivity;') === false) {
        // Find the class opening
        $content = preg_replace('/class\s+\w+\s+extends\s+Model\s*\{/', "$0\n    use LogsActivity;", $content);
    }
    
    file_put_contents($path, $content);
    echo "Updated $filename\n";
}
