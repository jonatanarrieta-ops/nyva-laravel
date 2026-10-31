
<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::catalog.index')->name('home');
Route::livewire('establecimientos/{establishment}', 'pages::catalog.show')->name('catalog.show');
Route::livewire('carrito', 'pages::cart.index')->name('cart.index');

Route::middleware('auth')->group(function () {
    Route::livewire('checkout', 'pages::checkout.index')->name('checkout');
    Route::livewire('mis-pedidos', 'pages::my-orders.index')->name('my-orders.index');
    Route::livewire('mis-pedidos/{order}', 'pages::my-orders.show')->name('my-orders.show');
    Route::livewire('mis-pedidos/{order}/confirmacion', 'pages::my-orders.confirmation')->name('my-orders.confirmation');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', function () {
        $user = Auth::user();

        if (! $user->isAdmin()) {
            return redirect()->route('home');
        }

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

        $orderStatusCounts = (clone $orders)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = now()->toDateString();

        $ordersToday = (clone $orders)
            ->whereDate('ordered_at', $today)
            ->count();

        $salesToday = (clone $orders)
            ->whereDate('ordered_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        $recentOrders = (clone $orders)
            ->with('establishment')
            ->orderByDesc('ordered_at')
            ->limit(5)
            ->get();

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
            'orderStatusCounts',
            'ordersToday',
            'salesToday',
            'recentOrders',
            'subscriptions'
        ));
    })->name('dashboard');

});

Route::middleware(['auth', 'admin'])->group(function () {

    Route::livewire(
        'establishments',
        'pages::establishments.index'
    )->name('establishments.index');

    Route::livewire(
        'categories',
        'pages::categories.index'
    )->name('categories.index');
    Route::livewire(
        'products',
        'pages::products.index'
    )->name('products.index');

    Route::livewire(
        'orders',
        'orders.index'
    )->name('orders.index');

    Route::livewire(
        'reports',
        'pages::reports.index'
    )->name('reports.index');

});

require __DIR__.'/settings.php';
