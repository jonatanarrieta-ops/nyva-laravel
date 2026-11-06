<?php

use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::controller(CatalogController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('establecimiento/{establishment:slug}', 'show')->name('catalog.show');
});

Route::prefix('carrito')->name('cart.')->controller(CartController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::delete('/', 'clear')->name('clear');
    Route::post('productos/{product}', 'store')->name('items.store');
    Route::patch('productos/{product}', 'update')->whereNumber('product')->name('items.update');
    Route::delete('productos/{product}', 'destroy')->whereNumber('product')->name('items.destroy');
});

Route::controller(CheckoutController::class)->group(function () {
    Route::get('checkout', 'create')->name('checkout');
    Route::post('checkout', 'store')->name('checkout.store');
});

Route::get('pedido/{token}', [TrackingController::class, 'show'])
    ->middleware(['throttle:60,1', 'tracking'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->name('orders.track');

Route::middleware('auth')->group(function () {
    Route::livewire('mis-pedidos', 'pages::my-orders.index')->name('my-orders.index');
    Route::livewire('mis-pedidos/{order}', 'pages::my-orders.show')->name('my-orders.show');
    Route::livewire('mis-pedidos/{order}/confirmacion', 'pages::my-orders.confirmation')->name('my-orders.confirmation');
});

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'establishment'])->group(function () {
    Route::livewire('establishments', 'pages::establishments.index')->name('establishments.index');
});

Route::middleware(['auth', 'establishment:catalog'])->group(function () {
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
});

Route::middleware(['auth', 'establishment:orders'])->group(function () {
    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::livewire('reports', 'pages::reports.index')->name('reports.index');
});

Route::middleware(['auth', 'establishment:settings'])->group(function () {
    Route::prefix('schedules')->name('schedules.')->controller(ScheduleController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/', 'update')->name('update');
    });
    Route::resource('faqs', FaqController::class)->except('show');
    Route::livewire('chatbot', 'pages::chatbot.settings')->name('chatbot.settings');
    Route::livewire('team', 'pages::team.index')->name('team.index');
});

Route::middleware(['auth', 'platform'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('establishments', 'pages::admin.establishments')->name('establishments');
    Route::livewire('users', 'pages::admin.users')->name('users');
    Route::resource('plans', PlanController::class)->except('show');
    Route::resource('subscriptions', SubscriptionController::class)->except(['show', 'destroy']);
    Route::livewire('statistics', 'pages::admin.statistics')->name('statistics');
});

require __DIR__.'/settings.php';
