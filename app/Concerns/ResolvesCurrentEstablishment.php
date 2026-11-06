<?php

namespace App\Concerns;

use App\Models\Establishment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait ResolvesCurrentEstablishment
{
    protected function currentEstablishment(Request $request, string $permission): Establishment
    {
        $query = $request->user()->accessibleEstablishments($permission);

        if ($request->filled('establishment')) {
            $establishment = (clone $query)->findOrFail($request->input('establishment'));
        } else {
            $establishment = (clone $query)->whereKey(session('current_establishment_id'))->first()
                ?? $query->orderBy('name')->firstOrFail();
        }

        session(['current_establishment_id' => $establishment->id]);

        return $establishment;
    }

    /**
     * @return Collection<int, Establishment>
     */
    protected function establishmentOptions(Request $request, string $permission): Collection
    {
        return $request->user()->accessibleEstablishments($permission)->orderBy('name')->get(['id', 'name']);
    }
}
