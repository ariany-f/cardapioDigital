<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches') || Schema::hasColumn('branches', 'orders_status_override')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->string('orders_status_override', 20)->nullable()->after('allow_scheduled_orders');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'orders_status_override')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('orders_status_override');
            });
        }
    }
};
