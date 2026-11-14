<?php

namespace App\Models\Radius;

use Illuminate\Database\Eloquent\Model;

/**
 * Atribut balasan RADIUS per user (mis. Mikrotik-Rate-Limit untuk FUP).
 *
 * Sebelumnya model ini kosong: Laravel menebak tabel "rad_replies" (tidak ada),
 * menulis timestamps, dan menolak mass assignment — sehingga FUP selalu gagal.
 */
class RadReply extends Model
{
    protected $table = 'radreply';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'attribute',
        'op',
        'value',
    ];
}
