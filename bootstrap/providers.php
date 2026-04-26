<?php

$providers = [
    App\Providers\AppServiceProvider::class,
];

if (env('QUEUE_CONNECTION') === 'redis') {
    $providers[] = Laravel\Horizon\HorizonServiceProvider::class;
    $providers[] = App\Providers\HorizonServiceProvider::class;
}

return $providers;
