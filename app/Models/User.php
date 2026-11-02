<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property bool $status
 * @property Carbon $registered_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'first_name',
    'last_name',
    'email',
    'password',
    'registered_at',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    public const PLATFORM_ROLES = ['admin', 'platform_admin'];

    public const PERMISSIONS = [
        'catalog' => 'Catálogo',
        'orders' => 'Pedidos',
        'settings' => 'Configuración',
    ];

    public const ROLES = [
        'platform_admin' => 'Administrador de plataforma',
        'establishment_admin' => 'Propietario',
        'establishment_staff' => 'Usuario autorizado',
        'customer' => 'Cliente',
    ];

    public $timestamps = false;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isPlatformAdmin(): bool
    {
        return in_array($this->role, self::PLATFORM_ROLES, true);
    }

    public function isEstablishmentUser(): bool
    {
        return in_array($this->role, ['establishment_admin', 'establishment_staff'], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Establecimientos propios y aquellos donde tiene el permiso indicado.
     *
     * @return Builder<Establishment>
     */
    public function accessibleEstablishments(?string $permission = null): Builder
    {
        return Establishment::query()->where(function (Builder $query) use ($permission) {
            $query->where('user_id', $this->id)
                ->orWhereHas('members', function (Builder $members) use ($permission) {
                    $members->where('users.id', $this->id)
                        ->when($permission, fn (Builder $scoped) => $scoped->whereJsonContains('establishment_user.permissions', $permission));
                });
        });
    }

    /**
     * @return BelongsToMany<Establishment, $this>
     */
    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(Establishment::class)->withPivot('permissions');
    }

    /**
     * @return HasMany<Establishment, User>
     */
    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class);
    }

    /**
     * @return HasMany<Order, User>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        $initials = Str::initials(
            $this->first_name.' '.$this->last_name,
            true
        );

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1)
                .Str::substr($initials, -1)
            : $initials;
    }
}
