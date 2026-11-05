<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::catalog.index')->name('home');
Route::livewire('establecimiento/{establishment:slug}', 'pages::catalog.show')->name('catalog.show');
Route::livewire('carrito', 'pages::cart.index')->name('cart.index');
Route::livewire('checkout', 'pages::checkout.index')->name('checkout');
Route::livewire('pedido/{token}', 'pages::tracking.show')
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
    Route::livewire('categories', 'pages::categories.index')->name('categories.index');
    Route::livewire('products', 'pages::products.index')->name('products.index');
});

Route::middleware(['auth', 'establishment:orders'])->group(function () {
    Route::livewire('orders', 'orders.index')->name('orders.index');
    Route::livewire('reports', 'pages::reports.index')->name('reports.index');
});

Route::middleware(['auth', 'establishment:settings'])->group(function () {
    Route::livewire('schedules', 'pages::schedules.index')->name('schedules.index');
    Route::livewire('faqs', 'pages::faqs.index')->name('faqs.index');
    Route::livewire('chatbot', 'pages::chatbot.settings')->name('chatbot.settings');
    Route::livewire('team', 'pages::team.index')->name('team.index');
});

Route::middleware(['auth', 'platform'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('establishments', 'pages::admin.establishments')->name('establishments');
    Route::livewire('users', 'pages::admin.users')->name('users');
    Route::livewire('plans', 'pages::admin.plans')->name('plans');
    Route::livewire('subscriptions', 'pages::admin.subscriptions')->name('subscriptions');
    Route::livewire('statistics', 'pages::admin.statistics')->name('statistics');
});

require __DIR__.'/settings.php';