<?php

use App\Http\Controllers\Marketing\LandingController;
use App\Http\Controllers\Public\LegalController;
use App\Http\Controllers\Public\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('marketing.landing');
Route::post('/contato', [LandingController::class, 'contact'])
    ->middleware('throttle:contact')
    ->name('marketing.contact');
Route::get('/termos-de-uso', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

// Slugs reservados (login, platform, etc.) não são tenants
Route::pattern('tenant', '(?!(?:login|register|platform|password|forgot-password|reset-password|verify-email|confirm-password|logout|dashboard|profile|up|api|sanctum|storage|conta|chat|entregador|contato)$)[a-z0-9][a-z0-9\-]*');

require __DIR__.'/conta.php';
require __DIR__.'/platform.php';
require __DIR__.'/auth.php';
require __DIR__.'/tenant.php';
