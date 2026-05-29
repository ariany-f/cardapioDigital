<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'guest_access_code')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('guest_access_code', 6)->nullable()->after('guest_email');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'guest_access_code')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('guest_access_code');
        });
    }
};
