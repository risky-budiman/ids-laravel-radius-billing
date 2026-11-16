<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pengecualian hak akses satu role untuk satu rute (lihat App\Support\RouteAccess). */
class RolePermission extends Model
{
    protected $fillable = ['role', 'route', 'allowed', 'updated_by'];

    protected $casts = ['allowed' => 'boolean'];
}
