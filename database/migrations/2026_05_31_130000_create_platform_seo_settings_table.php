<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('App Cardápio');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('twitter_card', 30)->default('summary_large_image');
            $table->string('google_site_verification')->nullable();
            $table->string('google_analytics_id')->nullable();
            $table->boolean('json_ld_enabled')->default(true);
            $table->string('organization_name')->nullable();
            $table->string('organization_logo_path')->nullable();
            $table->string('tenant_title_template')->default('{name} — Cardápio e pedidos online');
            $table->text('tenant_meta_description_fallback')->nullable();
            $table->string('tenant_og_image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_seo_settings');
    }
};
