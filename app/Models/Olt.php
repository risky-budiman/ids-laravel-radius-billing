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
        'snmp_version',
        'snmp_read_community',
        'snmp_write_community',
        'telnet_port',
        'username',
        'password',
        'enable_password',
        'olt_type',
        'is_active',
        'description',
        'latitude',
        'longitude',
    ];

    /** Kredensial tidak ikut terserialisasi (JSON/array) */
    protected $hidden = ['password', 'enable_password', 'snmp_read_community', 'snmp_write_community'];

    protected $casts = [
        // Disimpan terenkripsi dengan APP_KEY (dulu teks biasa)
        'password' => 'encrypted',
        'enable_password' => 'encrypted',
        'snmp_read_community' => 'encrypted',
        'snmp_write_community' => 'encrypted',
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

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
