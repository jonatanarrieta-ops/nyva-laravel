<?php

namespace App\Concerns;

use App\Models\Establishment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Mantiene el establecimiento activo en las páginas del panel, validado siempre en el servidor.
 */
trait WithEstablishmentContext
{
    public ?int $currentEstablishmentId = null;

    abstract protected function establishmentPermission(): string;

    public function mountWithEstablishmentContext(): void
    {
        $query = Auth::user()->accessibleEstablishments($this->establishmentPermission());
        $remembered = session('current_establishment_id');

        $this->currentEstablishmentId = (clone $query)->whereKey($remembered)->value('id')
            ?? $query->orderBy('name')->value('id');

        abort_if($this->currentEstablishmentId === null, 403);
    }

    public function updatedCurrentEstablishmentId(): void
    {
        session(['current_establishment_id' => $this->currentEstablishment()->id]);
    }

    protected function currentEstablishment(): Establishment
    {
        if ($this->currentEstablishmentId === null) {
            $this->mountWithEstablishmentContext();
        }

        return Auth::user()
            ->accessibleEstablishments($this->establishmentPermission())
            ->findOrFail($this->currentEstablishmentId);
    }

    /**
     * @return Collection<int, Establishment>
     */
    protected function establishmentOptions(): Collection
    {
        return Auth::user()
            ->accessibleEstablishments($this->establishmentPermission())
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
