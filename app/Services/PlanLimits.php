<?php

namespace App\Services;

use App\Models\Establishment;
use Illuminate\Validation\ValidationException;

/**
 * Aplica en el servidor los límites del plan vigente de cada establecimiento.
 */
class PlanLimits
{
    public function canAddProduct(Establishment $establishment): bool
    {
        $limit = $establishment->currentPlan()?->product_limit;

        return $limit === null || $establishment->productCount() < $limit;
    }

    public function canAddUser(Establishment $establishment): bool
    {
        $plan = $establishment->currentPlan();

        if (! $plan?->hasFeature('team')) {
            return false;
        }

        // El propietario cuenta como el primer usuario del establecimiento.
        return $plan->user_limit === null
            || $establishment->members()->count() + 1 < $plan->user_limit;
    }

    /**
     * @throws ValidationException
     */
    public function ensureCanAddProduct(Establishment $establishment, string $field = 'categoryId'): void
    {
        if (! $this->canAddProduct($establishment)) {
            throw ValidationException::withMessages([
                $field => 'Alcanzaste el límite de productos de tu plan.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function ensureCanAddUser(Establishment $establishment, string $field = 'email'): void
    {
        if (! $this->canAddUser($establishment)) {
            throw ValidationException::withMessages([
                $field => 'Tu plan no permite agregar más usuarios.',
            ]);
        }
    }
}
