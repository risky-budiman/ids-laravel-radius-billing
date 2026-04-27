<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Olt extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'snmp_port',
        'snmp_read_community',
        'snmp_write_community',
        'telnet_port',
        'username',
        'password',
        'olt_type',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'snmp_port' => 'integer',
        'telnet_port' => 'integer',
    ];

    /**
     * Get the PON ports for the OLT.
     */
    public function ponPorts(): HasMany
    {
        return $this->hasMany(OltPonPort::class);
    }
}
