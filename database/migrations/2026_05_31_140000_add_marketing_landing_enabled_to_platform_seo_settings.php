<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_seo_settings')
            || Schema::hasColumn('platform_seo_settings', 'marketing_landing_enabled')) {
            return;
        }

        Schema::table('platform_seo_settings', function (Blueprint $table) {
            $table->boolean('marketing_landing_enabled')->default(true)->after('robots_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('platform_seo_settings', 'marketing_landing_enabled')) {
            return;
        }

        Schema::table('platform_seo_settings', function (Blueprint $table) {
            $table->dropColumn('marketing_landing_enabled');
        });
    }
};
