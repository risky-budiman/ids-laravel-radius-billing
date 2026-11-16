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
        'snmp_v3_user',
        'snmp_v3_level',
        'snmp_v3_auth_protocol',
        'snmp_v3_auth_password',
        'snmp_v3_priv_protocol',
        'snmp_v3_priv_password',
        'olt_type',
        'is_active',
        'description',
        'latitude',
        'longitude',
    ];

    /** Kredensial tidak ikut terserialisasi (JSON/array) */
    protected $hidden = ['snmp_read_community', 'snmp_write_community', 'snmp_v3_auth_password', 'snmp_v3_priv_password'];

    protected $casts = [
        // Disimpan terenkripsi dengan APP_KEY (dulu teks biasa)
        'snmp_read_community' => 'encrypted',
        'snmp_write_community' => 'encrypted',
        'snmp_v3_auth_password' => 'encrypted',
        'snmp_v3_priv_password' => 'encrypted',
        'is_active' => 'boolean',
        'snmp_port' => 'integer',
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
