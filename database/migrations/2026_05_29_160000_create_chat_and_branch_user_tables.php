<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'access_all_branches')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('access_all_branches')->default(true)->after('is_protected_admin');
            });
        }

        if (! Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'branch_id']);
            });
        }

        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('guest_key', 64)->nullable();
                $table->string('guest_name')->nullable();
                $table->string('guest_phone', 30)->nullable();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status', 20)->default('open');
                $table->timestamp('last_message_at')->nullable();
                $table->unsignedInteger('staff_unread_count')->default(0);
                $table->unsignedInteger('customer_unread_count')->default(0);
                $table->timestamps();

                $table->index(['tenant_id', 'branch_id', 'status', 'last_message_at'], 'chat_conv_tenant_branch_status_idx');
                $table->index(['customer_id', 'branch_id'], 'chat_conv_customer_branch_idx');
            });
        }

        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
                $table->string('sender_type', 20);
                $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('sender_customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->text('body');
                $table->timestamp('read_at_staff')->nullable();
                $table->timestamp('read_at_customer')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('branch_user');

        if (Schema::hasColumn('users', 'access_all_branches')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('access_all_branches');
            });
        }
    }
};
