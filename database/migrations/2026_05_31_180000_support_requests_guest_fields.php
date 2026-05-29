<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('support_requests', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('customer_id');
            }
            if (! Schema::hasColumn('support_requests', 'guest_phone')) {
                $table->string('guest_phone', 30)->nullable()->after('guest_name');
            }
            if (! Schema::hasColumn('support_requests', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('guest_phone');
            }
        });

        if (Schema::hasColumn('support_requests', 'customer_id')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->unsignedBigInteger('customer_id')->nullable()->change();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('support_requests', 'guest_name')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn(['guest_name', 'guest_phone', 'guest_email']);
                $table->unsignedBigInteger('customer_id')->nullable(false)->change();
                $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            });
        }
    }
};
