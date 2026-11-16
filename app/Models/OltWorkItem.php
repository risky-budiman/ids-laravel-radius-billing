<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pekerjaan manual di OLT (daftarkan/allow ONU, hapus ONU, ganti profil T-CONT).
 */
class OltWorkItem extends Model
{
    public const TYPES = [
        'provision' => 'Daftarkan / allow ONU',
        'delete' => 'Hapus ONU',
        'profile' => 'Ganti profil T-CONT',
    ];

    protected $fillable = ['olt_id', 'customer_id', 'type', 'onu_sn', 'onu_index', 'vlan', 'profile', 'note',
        'status', 'resolution', 'resolved_by', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    /** SN tanpa pemisah huruf besar (MAC "00:1a:…" = "001A…"). */
    public static function normalizeSn(?string $sn): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $sn));
    }

    /**
     * Catat pekerjaan; pekerjaan sama yang masih menunggu (OLT, jenis, SN) tidak digandakan — datanya diperbarui.
     * Pekerjaan berlawanan yang masih menunggu (daftarkan vs hapus untuk SN yang sama) dibatalkan otomatis.
     */
    public static function record(int $oltId, string $type, array $data): self
    {
        $sn = $data['onu_sn'] ?? null;
        if ($sn && in_array($type, ['provision', 'delete'], true)) {
            static::pending()->where('olt_id', $oltId)->where('type', $type === 'provision' ? 'delete' : 'provision')
                ->where('onu_sn', $sn)->update(['status' => 'dismissed', 'resolution' => 'auto', 'resolved_at' => now(),
                    'note' => 'Dibatalkan: ada pekerjaan berlawanan yang lebih baru.']);
        }

        return static::updateOrCreate(
            ['olt_id' => $oltId, 'type' => $type, 'onu_sn' => $sn, 'status' => 'pending'],
            $data
        );
    }
}
