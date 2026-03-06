<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // ─── Helpers de rol ───────────────────────────────────────────────────────

    /**
     * Comprueba si el usuario tiene uno o varios roles.
     *
     * Uso: $user->hasRole('admin')
     *       $user->hasRole(['admin', 'cajero'])
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return in_array($this->role?->name, $roles, true);
    }

    public function isAdmin(): bool    { return $this->hasRole('admin'); }
    public function isCajero(): bool   { return $this->hasRole('cajero'); }
    public function isCocina(): bool   { return $this->hasRole('cocina'); }
    public function isMozo(): bool     { return $this->hasRole('mozo'); }

    // ─── Utilidades ───────────────────────────────────────────────────────────

    /** Devuelve las iniciales del nombre (máx. 2 letras). */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
