<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'delivery_confirmation_code')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_confirmation_code', 6)->nullable()->after('cancel_reason');
            $table->timestamp('delivery_confirmed_at')->nullable()->after('delivery_confirmation_code');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'delivery_confirmation_code')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_confirmation_code', 'delivery_confirmed_at']);
        });
    }
};
