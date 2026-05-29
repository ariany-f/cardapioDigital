<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')
            ->where('slug', 'basico')
            ->update(['price_monthly' => 29.90]);
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')
            ->where('slug', 'basico')
            ->update(['price_monthly' => 99.90]);
    }
};
