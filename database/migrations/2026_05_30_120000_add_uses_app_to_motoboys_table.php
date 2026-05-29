<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('motoboys', 'uses_app')) {
            Schema::table('motoboys', function (Blueprint $table) {
                $table->boolean('uses_app')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('motoboys', 'uses_app')) {
            Schema::table('motoboys', function (Blueprint $table) {
                $table->dropColumn('uses_app');
            });
        }
    }
};
