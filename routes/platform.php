<?php

use App\Http\Controllers\Platform\MarketingLeadController;
use App\Http\Controllers\Platform\PlanController;
use App\Http\Controllers\Platform\PlatformGoogleMapsSettingsController;
use App\Http\Controllers\Platform\PlatformMailSettingsController;
use App\Http\Controllers\Platform\PlatformSeoSettingsController;
use App\Http\Controllers\Platform\TenantSeoController;
use App\Http\Controllers\Platform\PlatformCustomerController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\OrderRatingController as PlatformOrderRatingController;
use App\Http\Controllers\Platform\PlatformOrderController;
use App\Http\Controllers\Platform\TenantBranchController;
use App\Http\Controllers\Platform\TenantCategoryController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Platform\TenantPaymentController;
use App\Http\Controllers\Platform\TenantProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->name('platform.')->group(function () {
    Route::get('/', function () {
        return auth()->check() && auth()->user()->is_platform_user
            ? redirect()->route('platform.dashboard')
            : redirect()->route('login');
    })->name('home');

    Route::middleware(['auth', 'platform'])->group(function () {
        Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('dashboard');

        Route::resource('tenants', TenantController::class);

        Route::resource('tenants.branches', TenantBranchController::class)
            ->scoped()
            ->except(['show']);

        Route::middleware('platform.tenant')
            ->prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function () {
                Route::resource('products', TenantProductController::class)->except(['show']);
                Route::resource('categories', TenantCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
            });

        Route::get('orders', [PlatformOrderController::class, 'index'])->name('orders.index');
        Route::get('ratings', [PlatformOrderRatingController::class, 'index'])->name('ratings.index');
        Route::patch('ratings/{rating}', [PlatformOrderRatingController::class, 'updateStatus'])->name('ratings.update-status');
        Route::get('customers', [PlatformCustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [PlatformCustomerController::class, 'show'])->name('customers.show');
        Route::get('marketing-leads', [MarketingLeadController::class, 'index'])->name('marketing-leads.index');
        Route::get('marketing-leads/{lead}', [MarketingLeadController::class, 'show'])->name('marketing-leads.show');
        Route::put('marketing-leads/{lead}', [MarketingLeadController::class, 'update'])->name('marketing-leads.update');

        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
        Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');

        Route::get('settings/email', [PlatformMailSettingsController::class, 'edit'])->name('settings.email');
        Route::put('settings/email', [PlatformMailSettingsController::class, 'update'])->name('settings.email.update');
        Route::post('settings/email/test', [PlatformMailSettingsController::class, 'sendTest'])->name('settings.email.test');

        Route::get('settings/seo', [PlatformSeoSettingsController::class, 'edit'])->name('settings.seo');
        Route::put('settings/seo', [PlatformSeoSettingsController::class, 'update'])->name('settings.seo.update');

        Route::get('settings/maps', [PlatformGoogleMapsSettingsController::class, 'edit'])->name('settings.maps');
        Route::put('settings/maps', [PlatformGoogleMapsSettingsController::class, 'update'])->name('settings.maps.update');

        Route::get('tenants/{tenant}/seo', [TenantSeoController::class, 'edit'])->name('tenants.seo.edit');
        Route::put('tenants/{tenant}/seo', [TenantSeoController::class, 'update'])->name('tenants.seo.update');

        Route::get('payments', [TenantPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [TenantPaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [TenantPaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}/edit', [TenantPaymentController::class, 'edit'])->name('payments.edit');
        Route::put('payments/{payment}', [TenantPaymentController::class, 'update'])->name('payments.update');
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])
            ->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])
            ->name('tenants.activate');
    });
});
