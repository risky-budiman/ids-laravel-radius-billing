<?php
function tailFile($filepath, $lines = 100) {
    $f = @fopen($filepath, "rb");
    if ($f === false) return [];
    $cursor = -1;
    fseek($f, $cursor, SEEK_END);
    $char = fgetc($f);
    // Trim trailing newline chars of the file
    while ($char === "\n" || $char === "\r") {
        fseek($f, $cursor--, SEEK_END);
        $char = fgetc($f);
    }
    $lineCounter = 0;
    while ($lineCounter < $lines && fseek($f, $cursor--, SEEK_END) !== -1) {
        $char = fgetc($f);
        if ($char === "\n") {
            $lineCounter++;
        }
    }
    
    // Check if we hit the beginning of the file
    if ($lineCounter < $lines) {
        rewind($f); // Go to the very beginning
    } else {
        fseek($f, $cursor + 2, SEEK_END);
    }
    
    $output = [];
    while (!feof($f)) {
        $output[] = fgets($f);
    }
    fclose($f);
    return $output;
}
print_r(tailFile('storage/logs/laravel.log', 10));
