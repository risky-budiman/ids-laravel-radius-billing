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
     * Get the latest application version from changelogs
     */
    public static function latestVersion()
    {
        $latest = self::latest('id')->first();
        return $latest ? $latest->version : 'v1.0.0';
    }
}
