<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    'role',
    'status',
    'registered_at'
])]
#[Hidden([
    'password',
    'remember_token'
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;


    /**
     * Relationship:
     * A user can have many establishments.
     */
    public function establishments()
    {
        return $this->hasMany(Establishment::class);
    }


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'registered_at' => 'datetime',
            'status' => 'boolean',
            'password' => 'hashed',
        ];
    }


    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        $initials = Str::initials(
            $this->first_name . ' ' . $this->last_name,
            true
        );

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1)
                . Str::substr($initials, -1)
            : $initials;
    }
}