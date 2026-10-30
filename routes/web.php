<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('dashboard', function () {
    $user = Auth::user();

    $establishmentIds = $user->establishments()->pluck('id');

    $establishmentCount = $establishmentIds->count();

    $categoryCount = Category::whereIn(
        'establishment_id',
        $establishmentIds
    )->count();

    $productCount = Product::whereHas('category', function ($query) use ($establishmentIds) {
        $query->whereIn('establishment_id', $establishmentIds);
    })->count();

    $orders = Order::whereIn(
        'establishment_id',
        $establishmentIds
    );

    $orderCount = (clone $orders)->count();

    $pendingOrderCount = (clone $orders)
        ->where('status', 'pending')
        ->count();

    $subscriptions = Subscription::with('plan')
        ->whereIn('establishment_id', $establishmentIds)
        ->orderByDesc('start_date')
        ->get();

    return view('dashboard', compact(
        'establishmentCount',
        'categoryCount',
        'productCount',
        'orderCount',
        'pendingOrderCount',
        'subscriptions'
    ));
})->name('dashboard');

});

Route::middleware(['auth'])->group(function () {
    Route::livewire(
        'establishments',
        'pages::establishments.index'
    )->name('establishments.index');
});

require __DIR__.'/settings.php';