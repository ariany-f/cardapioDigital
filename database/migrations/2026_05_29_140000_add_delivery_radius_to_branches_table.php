<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('branches', 'delivery_radius_km')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->decimal('delivery_radius_km', 6, 2)->nullable()->after('delivery_available');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'delivery_radius_km')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('delivery_radius_km');
            });
        }
    }
};
