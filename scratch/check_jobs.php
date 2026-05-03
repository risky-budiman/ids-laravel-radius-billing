<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- JOBS ---\n";
$jobs = DB::table('jobs')->get();
foreach ($jobs as $job) {
    echo "ID: {$job->id}, Queue: {$job->queue}, Attempts: {$job->attempts}\n";
}

echo "\n--- FAILED JOBS ---\n";
$failed = DB::table('failed_jobs')->latest()->limit(5)->get();
foreach ($failed as $f) {
    echo "ID: {$f->id}, Failed At: {$f->failed_at}, Error: " . substr($f->exception, 0, 100) . "...\n";
}
