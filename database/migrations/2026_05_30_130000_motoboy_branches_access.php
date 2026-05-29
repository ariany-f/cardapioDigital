<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('motoboys', 'access_all_branches')) {
            Schema::table('motoboys', function (Blueprint $table) {
                $table->boolean('access_all_branches')->default(true)->after('uses_app');
            });
        }

        if (! Schema::hasTable('branch_motoboy')) {
            Schema::create('branch_motoboy', function (Blueprint $table) {
                $table->id();
                $table->foreignId('motoboy_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['motoboy_id', 'branch_id']);
            });
        }

        if (Schema::hasColumn('motoboys', 'branch_id')) {
            $rows = DB::table('motoboys')->whereNotNull('branch_id')->get(['id', 'branch_id']);

            foreach ($rows as $row) {
                DB::table('motoboys')->where('id', $row->id)->update(['access_all_branches' => false]);

                DB::table('branch_motoboy')->insertOrIgnore([
                    'motoboy_id' => $row->id,
                    'branch_id' => $row->branch_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_motoboy');

        if (Schema::hasColumn('motoboys', 'access_all_branches')) {
            Schema::table('motoboys', function (Blueprint $table) {
                $table->dropColumn('access_all_branches');
            });
        }
    }
};
