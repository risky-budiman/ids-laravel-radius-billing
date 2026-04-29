<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

$setting = Setting::where('key', 'enable_partner_module')->first();
if (!$setting) {
    Setting::create([
        'key' => 'enable_partner_module',
        'value' => '1',
        'group' => 'module'
    ]);
    echo "Setting created and enabled.\n";
} else {
    $setting->update(['value' => '1']);
    echo "Setting updated to enabled.\n";
}

// Also ensure default commission settings exist
$defaults = [
    'default_commission_rate' => '10.00',
    'default_commission_type' => 'percentage'
];

foreach ($defaults as $key => $value) {
    Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'module']);
}

echo "All partner settings initialized.\n";
