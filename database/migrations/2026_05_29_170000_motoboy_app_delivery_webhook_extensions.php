<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motoboys', function (Blueprint $table) {
            if (! Schema::hasColumn('motoboys', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('motoboys', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
        });

        Schema::table('deliveries', function (Blueprint $table) {
            if (! Schema::hasColumn('deliveries', 'motoboy_assignment_status')) {
                $table->string('motoboy_assignment_status', 30)->nullable()->after('motoboy_id');
            }
            if (! Schema::hasColumn('deliveries', 'motoboy_assigned_at')) {
                $table->timestamp('motoboy_assigned_at')->nullable()->after('motoboy_assignment_status');
            }
            if (! Schema::hasColumn('deliveries', 'motoboy_responded_at')) {
                $table->timestamp('motoboy_responded_at')->nullable()->after('motoboy_assigned_at');
            }
            if (! Schema::hasColumn('deliveries', 'motoboy_reject_reason')) {
                $table->string('motoboy_reject_reason')->nullable()->after('motoboy_responded_at');
            }
        });

        if (! Schema::hasTable('motoboy_reports')) {
            Schema::create('motoboy_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('motoboy_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->text('message');
                $table->string('status', 20)->default('open');
                $table->text('admin_response')->nullable();
                $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('handled_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }

        if (! Schema::hasTable('customer_password_reset_tokens')) {
            Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('motoboy_reports');

        Schema::table('deliveries', function (Blueprint $table) {
            foreach (['motoboy_reject_reason', 'motoboy_responded_at', 'motoboy_assigned_at', 'motoboy_assignment_status'] as $col) {
                if (Schema::hasColumn('deliveries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('motoboys', function (Blueprint $table) {
            foreach (['password', 'email'] as $col) {
                if (Schema::hasColumn('motoboys', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
