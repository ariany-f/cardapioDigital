<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches') || Schema::hasColumn('branches', 'instagram')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->string('instagram', 100)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'instagram')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('instagram');
            });
        }
    }
};
