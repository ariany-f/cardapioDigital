<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BranchController as AdminBranchController;
use App\Http\Controllers\Admin\BranchOrdersStatusController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ComboController;
use App\Http\Controllers\Admin\DeliveryManagementController;
use App\Http\Controllers\Admin\DeliveryZoneController;
use App\Http\Controllers\Admin\DiningTableController;
use App\Http\Controllers\Admin\MotoboyController;
use App\Http\Controllers\Admin\MotoboyReportController as AdminMotoboyReportController;
use App\Http\Controllers\Admin\WebhookTokenController;
use App\Http\Controllers\Entregador\MotoboyAuthController;
use App\Http\Controllers\Entregador\MotoboyDashboardController;
use App\Http\Controllers\Public\CustomerPasswordResetController;
use App\Http\Controllers\Public\MotoboyReportController as PublicMotoboyReportController;
use App\Http\Controllers\Admin\KdsController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderRatingController as AdminOrderRatingController;
use App\Http\Controllers\Admin\OrderSettingsController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SupportRequestController as AdminSupportRequestController;
use App\Http\Controllers\Admin\TenantSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Public\BranchController;
use App\Http\Controllers\Public\ChatController as PublicChatController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\DeliveryQuoteController;
use App\Http\Controllers\Public\GeocodeController;
use App\Http\Controllers\Public\CustomerAuthController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LegalController;
use App\Http\Controllers\Public\MenuController;
use App\Http\Controllers\Public\SupportRequestController;
use App\Http\Controllers\Public\GuestOrderLookupController;
use App\Http\Controllers\Public\TrackOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('{tenant}')
    ->middleware(['tenant', 'tenant.active'])
    ->name('tenant.')
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/termos-de-uso', [LegalController::class, 'terms'])->name('legal.terms');
        Route::get('/acompanhar-pedido', [GuestOrderLookupController::class, 'create'])
            ->name('track.lookup');
        Route::post('/acompanhar-pedido', [GuestOrderLookupController::class, 'store'])
            ->middleware('throttle:public-forms')
            ->name('track.lookup.store');
        Route::get('/pedido/{order_number}', [TrackOrderController::class, 'show'])->name('track');
        Route::post('/pedido/{order_number}/acesso', [GuestOrderLookupController::class, 'verify'])
            ->middleware('throttle:public-forms')
            ->name('track.verify');
        Route::post('/pedido/{order_number}/avaliar', [TrackOrderController::class, 'rate'])
            ->middleware('throttle:public-forms')
            ->name('track.rate');
        Route::get('/pedido/{order_number}/status', [TrackOrderController::class, 'status'])->name('track.status');
        Route::post('/pedido/{order_number}/denunciar-entregador', [PublicMotoboyReportController::class, 'store'])
            ->middleware('throttle:public-forms')
            ->name('track.report-motoboy');
        Route::post('/pedido/{order_number}/problema-pedido', [TrackOrderController::class, 'reportOrder'])
            ->middleware('throttle:public-forms')
            ->name('track.report-order');
        Route::post('/checkout', [CheckoutController::class, 'store'])
            ->middleware('throttle:checkout')
            ->name('checkout');

        Route::post('/geocode/forward', [GeocodeController::class, 'forward'])
            ->middleware('throttle:public-forms')
            ->name('geocode.forward');
        Route::post('/geocode/reverse', [GeocodeController::class, 'reverse'])
            ->middleware('throttle:public-forms')
            ->name('geocode.reverse');

        Route::prefix('chat')->name('chat.')->middleware('throttle:chat')->group(function () {
            Route::post('/{branch}/start', [PublicChatController::class, 'start'])->name('start');
            Route::get('/c/{uuid}/messages', [PublicChatController::class, 'messages'])->name('messages');
            Route::post('/c/{uuid}/messages', [PublicChatController::class, 'send'])->name('send');
        });

        Route::get('/ajuda', [SupportRequestController::class, 'create'])->name('support');
        Route::post('/ajuda', [SupportRequestController::class, 'store'])
            ->middleware('throttle:public-forms')
            ->name('support.store');

        Route::prefix('conta')->name('conta.')->group(function () {
            Route::middleware('guest:customer')->group(function () {
                Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
                Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
                Route::post('/login', [CustomerAuthController::class, 'login'])
                    ->middleware('throttle:auth')
                    ->name('login.store');
                Route::post('/register', [CustomerAuthController::class, 'register'])
                    ->middleware('throttle:auth')
                    ->name('register.store');
                Route::get('/forgot-password', [CustomerPasswordResetController::class, 'create'])->name('password.request');
                Route::post('/forgot-password', [CustomerPasswordResetController::class, 'store'])
                    ->middleware('throttle:auth')
                    ->name('password.email');
                Route::get('/reset-password/{token}', [CustomerPasswordResetController::class, 'edit'])->name('password.reset');
                Route::post('/reset-password', [CustomerPasswordResetController::class, 'update'])
                    ->middleware('throttle:auth')
                    ->name('password.store');
            });

            Route::middleware('auth:customer')->group(function () {
                Route::get('/', [CustomerAuthController::class, 'dashboard'])->name('dashboard');
                Route::get('/pedidos/{order}/repetir', [CustomerAuthController::class, 'repeatOrder'])->name('orders.repeat');
                Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
            });
        });

        Route::prefix('entregador')->name('entregador.')->middleware('motoboys.enabled')->group(function () {
            Route::middleware('guest:motoboy')->group(function () {
                Route::get('/login', [MotoboyAuthController::class, 'showLogin'])->name('login');
                Route::post('/login', [MotoboyAuthController::class, 'login'])
                    ->middleware('throttle:auth')
                    ->name('login.store');
            });

            Route::middleware(['auth:motoboy', 'motoboy.tenant'])->group(function () {
                Route::get('/', [MotoboyDashboardController::class, 'index'])->name('dashboard');
                Route::get('/poll', [MotoboyDashboardController::class, 'poll'])->name('poll');
                Route::post('/entregas/{delivery}/respond', [MotoboyDashboardController::class, 'respond'])->name('deliveries.respond');
                Route::patch('/entregas/{delivery}/status', [MotoboyDashboardController::class, 'updateStatus'])->name('deliveries.status');
                Route::post('/logout', [MotoboyAuthController::class, 'logout'])->name('logout');
            });
        });

        Route::prefix('admin')
            ->middleware(['auth', 'tenant.user'])
            ->name('admin.')
            ->group(function () {
                Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
                Route::get('/dashboard', [AdminDashboardController::class, 'index']);
                Route::middleware('permission:users.manage')->group(function () {
                    Route::get('/settings', [TenantSettingsController::class, 'edit'])->name('settings');
                    Route::put('/settings', [TenantSettingsController::class, 'update'])->name('settings.update');
                });

                Route::middleware('permission:orders.view')->group(function () {
                    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
                    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
                    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
                });

                Route::middleware('permission:orders.accept')->group(function () {
                    Route::get('/pedidos/configuracao', [OrderSettingsController::class, 'index'])
                        ->name('orders.settings');
                    Route::patch('/branches/{branch}/orders-status', [BranchOrdersStatusController::class, 'update'])
                        ->name('branches.orders-status');
                    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
                    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
                    Route::post('/orders/{order}/reject', [OrderController::class, 'reject'])->name('orders.reject');
                    Route::post('/orders/{order}/paid', [OrderController::class, 'markPaid'])->name('orders.paid');
                    Route::post('/orders/{order}/revert-payment', [OrderController::class, 'revertPayment'])->name('orders.revert-payment');
                    Route::patch('/orders/{order}/correct-status', [OrderController::class, 'correctStatus'])->name('orders.correct-status');
                });

                Route::middleware('permission:orders.cancel')->group(function () {
                    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
                });

                Route::middleware('permission:orders.print')->group(function () {
                    Route::get('/orders/{order}/print', [\App\Http\Controllers\Admin\PrintOrderController::class, 'show'])->name('orders.print');
                });

                Route::middleware('permission:branches.manage')->group(function () {
                    Route::resource('branches', AdminBranchController::class)->except(['show']);
                    Route::get('branches/{branch}/zones', [DeliveryZoneController::class, 'index'])->name('branches.zones');
                    Route::post('branches/{branch}/zones', [DeliveryZoneController::class, 'store'])->name('branches.zones.store');
                    Route::put('branches/{branch}/zones/{zone}', [DeliveryZoneController::class, 'update'])->name('branches.zones.update');
                    Route::delete('branches/{branch}/zones/{zone}', [DeliveryZoneController::class, 'destroy'])->name('branches.zones.destroy');
                    Route::get('tables', [DiningTableController::class, 'index'])->name('tables.index');
                    Route::post('tables', [DiningTableController::class, 'store'])->name('tables.store');
                    Route::delete('tables/{table}', [DiningTableController::class, 'destroy'])->name('tables.destroy');
                });

                Route::middleware('permission:deliveries.update')->group(function () {
                    Route::get('/webhooks', [WebhookTokenController::class, 'index'])->name('webhooks.index');
                    Route::post('/webhooks', [WebhookTokenController::class, 'store'])->name('webhooks.store');
                    Route::put('/webhooks/{webhookToken}', [WebhookTokenController::class, 'update'])->name('webhooks.update');
                    Route::post('/webhooks/{webhookToken}/rotate', [WebhookTokenController::class, 'rotate'])->name('webhooks.rotate');
                    Route::delete('/webhooks/{webhookToken}', [WebhookTokenController::class, 'destroy'])->name('webhooks.destroy');
                    Route::middleware('motoboys.enabled')->group(function () {
                        Route::get('/motoboy-reports', [AdminMotoboyReportController::class, 'index'])->name('motoboy-reports.index');
                        Route::patch('/motoboy-reports/{report}', [AdminMotoboyReportController::class, 'update'])->name('motoboy-reports.update');
                        Route::get('/motoboys', [MotoboyController::class, 'index'])->name('motoboys.index');
                        Route::post('/motoboys', [MotoboyController::class, 'store'])->name('motoboys.store');
                        Route::put('/motoboys/{motoboy}', [MotoboyController::class, 'update'])->name('motoboys.update');
                        Route::post('/motoboys/{motoboy}/login', [MotoboyController::class, 'updateLogin'])->name('motoboys.login.update');
                        Route::post('/motoboys/{motoboy}/reset-password', [MotoboyController::class, 'resetPassword'])->name('motoboys.reset-password');
                        Route::delete('/motoboys/{motoboy}', [MotoboyController::class, 'destroy'])->name('motoboys.destroy');
                    });
                    Route::patch('/orders/{order}/delivery', [DeliveryManagementController::class, 'update'])->name('orders.delivery');
                    Route::post('/orders/{order}/confirm-delivery', [OrderController::class, 'confirmDelivery'])
                        ->name('orders.confirm-delivery');
                });

                Route::middleware('permission:products.manage')->group(function () {
                    Route::resource('products', ProductController::class)->except(['show']);
                    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
                    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
                    Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
                    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
                    Route::get('/combos', [ComboController::class, 'index'])->name('combos.index');
                    Route::post('/combos', [ComboController::class, 'store'])->name('combos.store');
                    Route::put('/combos/{combo}', [ComboController::class, 'update'])->name('combos.update');
                    Route::delete('/combos/{combo}', [ComboController::class, 'destroy'])->name('combos.destroy');
                    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
                    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
                    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
                    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
                    Route::get('/languages', [LanguageController::class, 'index'])->name('languages.index');
                    Route::post('/languages', [LanguageController::class, 'store'])->name('languages.store');
                    Route::get('/languages/export', [LanguageController::class, 'exportTemplate'])->name('languages.export');
                    Route::post('/languages/import', [LanguageController::class, 'import'])->name('languages.import');
                });

                Route::middleware('permission:coupons.manage')->group(function () {
                    Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
                    Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
                    Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
                    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
                });

                Route::middleware(['permission:kds.access', 'plan.feature:kds', 'kds.enabled'])->group(function () {
                    Route::get('/kds', [KdsController::class, 'index'])->name('kds');
                });

                Route::middleware(['permission:orders.pos', 'plan.feature:pos', 'pos.enabled'])->group(function () {
                    Route::get('/pos', [PosController::class, 'index'])->name('pos');
                    Route::post('/pos/orders', [PosController::class, 'store'])->name('pos.store');
                });

                Route::middleware('permission:requests.view')->group(function () {
                    Route::get('/requests', [AdminSupportRequestController::class, 'index'])->name('requests.index');
                });

                Route::middleware('permission:requests.close')->group(function () {
                    Route::patch('/requests/{supportRequest}', [AdminSupportRequestController::class, 'update'])->name('requests.update');
                    Route::post('/requests/{supportRequest}/process-return', [AdminSupportRequestController::class, 'processReturn'])->name('requests.process-return');
                });

                Route::middleware(['permission:reports.view', 'plan.feature:reports'])->group(function () {
                    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
                    Route::get('/reports/orders.csv', [ReportController::class, 'orders'])->name('reports.orders');
                });

                Route::middleware('permission:ratings.manage')->group(function () {
                    Route::get('/ratings', [AdminOrderRatingController::class, 'index'])->name('ratings.index');
                    Route::patch('/ratings/{rating}', [AdminOrderRatingController::class, 'updateStatus'])->name('ratings.update-status');
                });

                Route::middleware('permission:chat.access')->group(function () {
                    Route::get('/chat', [AdminChatController::class, 'index'])->name('chat.index');
                    Route::get('/chat/updates', [AdminChatController::class, 'updates'])->name('chat.updates');
                    Route::get('/chat/unread', [AdminChatController::class, 'unread'])->name('chat.unread');
                    Route::get('/chat/c/{uuid}/messages', [AdminChatController::class, 'messages'])->name('chat.messages');
                    Route::post('/chat/c/{uuid}/messages', [AdminChatController::class, 'send'])->name('chat.send');
                    Route::post('/chat/c/{uuid}/close', [AdminChatController::class, 'close'])->name('chat.close');
                });

                Route::middleware('permission:users.manage')->group(function () {
                    Route::get('/users', [UserController::class, 'index'])->name('users.index');
                    Route::post('/users', [UserController::class, 'store'])->name('users.store');
                    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
                    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
                });
            });

        Route::post('/{branch}/delivery-quote', [DeliveryQuoteController::class, 'quote'])
            ->middleware('throttle:public-forms')
            ->where('branch', '(?!admin|conta|pedido|ajuda|chat)[a-z0-9\-]+')
            ->name('branch.delivery-quote');

        Route::get('/{branch}/mesa/{token}', [BranchController::class, 'showTable'])
            ->where('branch', '(?!admin|conta|pedido|ajuda|chat)[a-z0-9\-]+')
            ->where('token', '[a-zA-Z0-9]+')
            ->name('table');

        Route::get('/{branch}', [BranchController::class, 'show'])
            ->where('branch', '(?!admin|conta|pedido|ajuda|chat)[a-z0-9\-]+')
            ->name('branch');
        Route::get('/{branch}/cardapio', [MenuController::class, 'show'])
            ->where('branch', '(?!admin|conta|pedido|ajuda|chat)[a-z0-9\-]+')
            ->name('menu');
    });
