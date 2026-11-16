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
        'web_username',
        'web_password',
        'web_port',
        'web_https',
        'olt_type',
        'is_active',
        'description',
        'latitude',
        'longitude',
    ];

    /** Kredensial tidak ikut terserialisasi (JSON/array) */
    protected $hidden = ['snmp_read_community', 'snmp_write_community', 'snmp_v3_auth_password', 'snmp_v3_priv_password', 'web_password'];

    protected $casts = [
        // Disimpan terenkripsi dengan APP_KEY (dulu teks biasa)
        'snmp_read_community' => 'encrypted',
        'snmp_write_community' => 'encrypted',
        'snmp_v3_auth_password' => 'encrypted',
        'snmp_v3_priv_password' => 'encrypted',
        'web_password' => 'encrypted',
        'web_https' => 'boolean',
        'web_port' => 'integer',
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

    /** Tipe OLT => label tampilan (satu sumber untuk daftar, detail & form). */
    public const TYPES = [
        'HSGQ_EPON' => 'HSGQ EPON',
        'ZTE_C320' => 'ZTE ZXA10 C320',
        'ZTE_C300' => 'ZTE ZXA10 C300',
        'HIOSO' => 'Hioso',
        'OTHER' => 'Lainnya',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->olt_type] ?? str_replace('_', ' ', (string) $this->olt_type);
    }

    /** Teknologi PON untuk badge: EPON (HSGQ/Hioso) atau GPON (ZTE). */
    public function ponTechnology(): string
    {
        return str_contains(strtoupper((string) $this->olt_type), 'ZTE') ? 'GPON' : 'EPON';
    }

    public function snmpVersionLabel(): string
    {
        return match ((int) $this->snmp_version) { 1 => 'v1', 3 => 'v3', default => 'v2c' };
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
