<?php

use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{catalog:slug}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{catalog}', [CartController::class, 'store'])->name('cart.store');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/approve-preview', [OrderController::class, 'approvePreview'])->name('orders.approve-preview');
    Route::post('/orders/{order}/revision', [OrderController::class, 'requestRevision'])->name('orders.revision');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::post('/orders/{order}/reviews/{catalog:id}', [ReviewController::class, 'store'])->name('orders.reviews.store');
    Route::post('/orders/{order}/payments/{payment}/pay', [PaymentController::class, 'pay'])->name('orders.payments.pay');
});

Route::post('/payments/midtrans/webhook', PaymentWebhookController::class)->name('payments.midtrans.webhook');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('categories', AdminCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('catalogs', AdminCatalogController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    Route::post('orders/{order}/progress-steps', [AdminOrderController::class, 'storeProgressStep'])->name('orders.progress-steps.store');
    Route::patch('orders/{order}/progress-steps/{step}', [AdminOrderController::class, 'updateProgressStep'])->name('orders.progress-steps.update');
    Route::delete('orders/{order}/progress-steps/{step}', [AdminOrderController::class, 'destroyProgressStep'])->name('orders.progress-steps.destroy');
});
