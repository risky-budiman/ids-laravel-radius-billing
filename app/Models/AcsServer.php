<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcsServer extends Model
{
    protected $fillable = [
        'name',
        'url',
        'nbi_username',
        'nbi_password',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'nbi_password' => 'encrypted',
    ];

    /** Password NBI tidak pernah ikut terserialisasi (JSON/array). */
    protected $hidden = ['nbi_password'];

    /** Server ACS yang dipakai bila tidak dipilih: yang aktif lebih dulu. */
    public static function preferred(): ?self
    {
        return static::orderByDesc('is_active')->orderBy('id')->first();
    }

    /** Tanpa password NBI di Log Aktivitas. */
    public function activityRedactedFields(): array
    {
        return ['nbi_password'];
    }
}
