<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variation_groups', 'type')) {
            Schema::table('product_variation_groups', function (Blueprint $table) {
                $table->string('type', 20)->default('choice')->after('name');
            });
        }

        if (! Schema::hasColumn('branches', 'order_disposables')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->json('order_disposables')->nullable()->after('packaging_fee_default');
            });
        }

        if (! Schema::hasColumn('orders', 'disposables_snapshot')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->json('disposables_snapshot')->nullable()->after('delivery_address');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'disposables_snapshot')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('disposables_snapshot');
            });
        }

        if (Schema::hasColumn('branches', 'order_disposables')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('order_disposables');
            });
        }

        if (Schema::hasColumn('product_variation_groups', 'type')) {
            Schema::table('product_variation_groups', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
