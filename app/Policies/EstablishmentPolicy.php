<?php

namespace App\Policies;

use App\Models\Establishment;
use App\Models\User;

/**
 * Las categorías, productos, pedidos, horarios y preguntas dependen del establecimiento,
 * así que esta política concentra la autorización de todos ellos.
 */
class EstablishmentPolicy
{
    public function view(User $user, Establishment $establishment): bool
    {
        return $user->isPlatformAdmin() || $this->allows($user, $establishment);
    }

    public function update(User $user, Establishment $establishment): bool
    {
        return $this->allows($user, $establishment, 'settings');
    }

    public function manageCatalog(User $user, Establishment $establishment): bool
    {
        return $this->allows($user, $establishment, 'catalog');
    }

    public function manageOrders(User $user, Establishment $establishment): bool
    {
        return $this->allows($user, $establishment, 'orders');
    }

    public function manageTeam(User $user, Establishment $establishment): bool
    {
        return $establishment->user_id === $user->id;
    }

    private function allows(User $user, Establishment $establishment, ?string $permission = null): bool
    {
        return $user->accessibleEstablishments($permission)->whereKey($establishment->id)->exists();
    }
}
