<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'photo',
        'password',
        'role',
        'otp_code',
        'otp_expires_at',
    ];

    /**
     * Helper: apakah user adalah superadmin?
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Helper: apakah OTP yang diberikan valid dan belum expired?
     */
    public function isOtpValid(string $code): bool
    {
        return $this->otp_code === $code
            && $this->otp_expires_at
            && now()->lessThanOrEqualTo($this->otp_expires_at);
    }

    /**
     * Helper: dapatkan URL foto profil (atau default inisial).
     */
    public function photoUrl(): ?string
    {
        if (!$this->photo) return null;
        // disimpan di storage/app/public/avatars/, dilayani nginx alias /storage
        return '/storage/' . ltrim($this->photo, '/');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'otp_expires_at'    => 'datetime',
        ];
    }
}
