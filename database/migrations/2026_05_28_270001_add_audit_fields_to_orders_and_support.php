<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'approved_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('cancel_reason');
                $table->foreignId('approved_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable()->after('approved_by_user_id');
                $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable()->after('cancelled_by_user_id');
                $table->foreignId('rejected_by_user_id')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('support_requests') && ! Schema::hasColumn('support_requests', 'last_responded_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->timestamp('last_responded_at')->nullable()->after('closed_by');
                $table->foreignId('last_responded_by_user_id')->nullable()->after('last_responded_at')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'approved_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('approved_by_user_id');
                $table->dropConstrainedForeignId('cancelled_by_user_id');
                $table->dropConstrainedForeignId('rejected_by_user_id');
                $table->dropColumn(['approved_at', 'cancelled_at', 'rejected_at']);
            });
        }

        if (Schema::hasColumn('support_requests', 'last_responded_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('last_responded_by_user_id');
                $table->dropColumn('last_responded_at');
            });
        }
    }
};
