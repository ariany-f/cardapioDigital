<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variation_groups', 'allow_quantity')) {
            Schema::table('product_variation_groups', function (Blueprint $table) {
                $table->boolean('allow_quantity')->default(false)->after('max_select');
            });
        }

        if (! Schema::hasColumn('product_variation_options', 'max_quantity')) {
            Schema::table('product_variation_options', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_quantity')->nullable()->after('additional_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variation_groups', 'allow_quantity')) {
            Schema::table('product_variation_groups', function (Blueprint $table) {
                $table->dropColumn('allow_quantity');
            });
        }

        if (Schema::hasColumn('product_variation_options', 'max_quantity')) {
            Schema::table('product_variation_options', function (Blueprint $table) {
                $table->dropColumn('max_quantity');
            });
        }
    }
};
