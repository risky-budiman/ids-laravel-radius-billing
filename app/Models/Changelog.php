<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Changelog extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'title',
        'description',
        'type',
        'release_date'
    ];

    /**
     * Get the latest application version
     */
    public static function latestVersion()
    {
        return app_version();
    }
}
