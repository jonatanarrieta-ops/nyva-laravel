<?php

namespace App\Http\Controllers;

use App\Models\Establishment;
use App\Models\Plan;
use App\Models\Product;
use App\Services\Chatbot\EstablishmentContext;
use Illuminate\Contracts\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        return view('public.catalog.index', [
            'title' => 'Asistente virtual para negocios',
            'plans' => Plan::query()->where('status', 'active')->orderBy('monthly_price')->get(),
        ]);
    }

    public function show(Establishment $establishment): View
    {
        abort_unless($establishment->isPublic(), 404);

        $categories = $establishment->categories()
            ->where('status', 'active')
            ->whereHas('products', fn ($query) => $query->where('status', 'active'))
            ->with(['products' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
            ->orderBy('name')
            ->get();

        $pendingProduct = Product::query()
            ->whereKey(session('pending_product_id'))
            ->whereHas('category', fn ($query) => $query->where('establishment_id', $establishment->id))
            ->first();

        return view('public.catalog.show', [
            'title' => $establishment->name,
            'publicEstablishment' => $establishment,
            'establishment' => $establishment,
            'canOrder' => $establishment->canReceiveOrders(),
            'hours' => (new EstablishmentContext($establishment))->scheduleLines(),
            'categories' => $categories,
            'pendingProduct' => $pendingProduct,
        ]);
    }
}
