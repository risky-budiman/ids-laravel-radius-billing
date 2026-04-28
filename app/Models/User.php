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

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Role Constants
    const ROLE_ADMINISTRATOR = 'administrator';
    const ROLE_ADMIN = 'admin';
    const ROLE_TEKNISI = 'teknisi';
    const ROLE_KASIR = 'kasir';
    const ROLE_SALES = 'sales';
    const ROLE_CUSTOMER = 'customer';

    /**
     * Check if user has specific role
     */
    public function hasRole(string|array $role): bool
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        return $this->role === $role;
    }

    /**
     * Role Helper Methods
     */
    public function isAdministrator(): bool { return $this->role === self::ROLE_ADMINISTRATOR; }
    public function isAdmin(): bool { return in_array($this->role, [self::ROLE_ADMINISTRATOR, self::ROLE_ADMIN]); }
    public function isTeknisi(): bool { return $this->role === self::ROLE_TEKNISI; }
    public function isKasir(): bool { return $this->role === self::ROLE_KASIR; }
    public function isSales(): bool { return $this->role === self::ROLE_SALES; }
    public function isCustomer(): bool { return $this->role === self::ROLE_CUSTOMER; }

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
        ];
    }
}
