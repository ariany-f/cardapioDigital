<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        if (! Schema::hasColumn('customers', 'tenant_id')) {
            $this->ensureEmailUnique();

            return;
        }

        $this->mergeDuplicateCustomersByEmail();

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropUnique(['tenant_id', 'email']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        $this->ensureEmailUnique();
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || Schema::hasColumn('customers', 'tenant_id')) {
            return;
        }

        if ($this->indexExists('customers', 'customers_email_unique')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['tenant_id', 'email']);
        });
    }

    protected function mergeDuplicateCustomersByEmail(): void
    {
        $emails = DB::table('customers')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        foreach ($emails as $email) {
            $ids = DB::table('customers')->where('email', $email)->orderBy('id')->pluck('id');
            $keepId = $ids->first();

            foreach ($ids->slice(1) as $duplicateId) {
                DB::table('orders')->where('customer_id', $duplicateId)->update(['customer_id' => $keepId]);
                DB::table('customer_addresses')->where('customer_id', $duplicateId)->update(['customer_id' => $keepId]);

                if (Schema::hasTable('support_requests')) {
                    DB::table('support_requests')->where('customer_id', $duplicateId)->update(['customer_id' => $keepId]);
                }

                DB::table('customers')->where('id', $duplicateId)->delete();
            }
        }
    }

    protected function ensureEmailUnique(): void
    {
        if ($this->indexExists('customers', 'customers_email_unique')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::select(
            'SELECT COUNT(*) AS count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return ($result[0]->count ?? 0) > 0;
    }
};
