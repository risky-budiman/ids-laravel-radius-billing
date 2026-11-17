<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'is_sales',
        'profile_photo',
        'commission_rate',
        'commission_type',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    // Role Constants
    const ROLE_ADMINISTRATOR = 'administrator';
    const ROLE_ADMIN = 'admin';
    const ROLE_TEKNISI = 'teknisi';
    const ROLE_KASIR = 'kasir';
    const ROLE_SALES = 'sales';
    const ROLE_MITRA = 'mitra';
    const ROLE_CUSTOMER = 'customer';
    /** Customer Service: tiket, data kontak, lihat tagihan & status; ganti paket hanya lewat pengajuan. */
    const ROLE_CS = 'cs';

    /** Role akun staf yang bisa dibuat/diubah (web & API) beserta labelnya. */
    const STAFF_ROLES = [
        self::ROLE_ADMINISTRATOR => 'Administrator',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_TEKNISI => 'Teknisi',
        self::ROLE_KASIR => 'Kasir',
        self::ROLE_SALES => 'Sales',
        self::ROLE_CS => 'Customer Service (CS)',
        self::ROLE_MITRA => 'Partner / Mitra',
    ];

    /** Apakah akun ini administrator aktif terakhir (tidak boleh diturunkan/dinonaktifkan/dihapus). */
    public function isLastActiveAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR && $this->is_active
            && ! static::where('role', self::ROLE_ADMINISTRATOR)->where('is_active', true)->whereKeyNot($this->getKey())->exists();
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            if (in_array(self::ROLE_SALES, $roles) && $this->hasSalesFlag()) {
                return true;
            }
            return in_array($this->role, $roles);
        }

        if ($roles === self::ROLE_SALES && $this->hasSalesFlag()) {
            return true;
        }

        return $this->role === $roles;
    }

    /**
     * Role Helper Methods
     */
    public function isAdministrator(): bool { return $this->role === self::ROLE_ADMINISTRATOR; }
    public function isAdmin(): bool { return in_array($this->role, [self::ROLE_ADMINISTRATOR, self::ROLE_ADMIN]); }
    public function isTeknisi(): bool { return $this->role === self::ROLE_TEKNISI; }
    public function isKasir(): bool { return $this->role === self::ROLE_KASIR; }
    public function isSales(): bool { return $this->role === self::ROLE_SALES || $this->hasSalesFlag(); }

    /** Penanda "juga sales" hanya berlaku untuk staf internal (bukan mitra/pelanggan). */
    public function hasSalesFlag(): bool
    {
        return (bool) $this->is_sales && in_array($this->role, [self::ROLE_ADMINISTRATOR, self::ROLE_ADMIN, self::ROLE_TEKNISI, self::ROLE_KASIR, self::ROLE_CS], true);
    }
    public function isMitra(): bool { return $this->role === self::ROLE_MITRA; }
    public function isCustomer(): bool { return $this->role === self::ROLE_CUSTOMER; }
    public function isCs(): bool { return $this->role === self::ROLE_CS; }

    /**
     * Relationship to active sessions
     */
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Get the URL to the user's profile photo or a default initial avatar.
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_sales' => 'boolean',
            'is_active' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** Secret & kode cadangan 2FA tidak pernah tertulis di Log Aktivitas (aktif/nonaktif dicatat terpisah). */
    public function activityRedactedFields(): array
    {
        return ['two_factor_secret', 'two_factor_recovery_codes'];
    }

    /** Pemakaian kode 2FA (langkah waktu / sisa kode cadangan) saat login bukan perubahan akun: tidak dicatat. */
    public function shouldLogActivity(): bool
    {
        $changed = array_keys($this->exists && $this->wasChanged() ? $this->getChanges() : $this->getDirty());

        return ! $this->exists || array_diff($changed, ['two_factor_last_step', 'two_factor_recovery_codes', 'remember_token', 'updated_at']) !== [] || $changed === [];
    }

    /** Verifikasi dua langkah aktif (secret sudah dikonfirmasi dengan kode dari aplikasi authenticator). */
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && filled($this->two_factor_secret);
    }
}
