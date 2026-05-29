<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'cpf' => fn (Blueprint $table) => $table->string('cpf', 14)->nullable()->after('phone'),
            'email' => fn (Blueprint $table) => $table->string('email')->nullable()->after('cpf'),
            'document_rg' => fn (Blueprint $table) => $table->string('document_rg', 20)->nullable()->after('email'),
            'birth_date' => fn (Blueprint $table) => $table->date('birth_date')->nullable()->after('document_rg'),
            'street' => fn (Blueprint $table) => $table->string('street')->nullable()->after('birth_date'),
            'number' => fn (Blueprint $table) => $table->string('number', 20)->nullable()->after('street'),
            'complement' => fn (Blueprint $table) => $table->string('complement')->nullable()->after('number'),
            'neighborhood' => fn (Blueprint $table) => $table->string('neighborhood')->nullable()->after('complement'),
            'city' => fn (Blueprint $table) => $table->string('city')->nullable()->after('neighborhood'),
            'state' => fn (Blueprint $table) => $table->string('state', 2)->nullable()->after('city'),
            'postal_code' => fn (Blueprint $table) => $table->string('postal_code', 12)->nullable()->after('state'),
            'emergency_contact_name' => fn (Blueprint $table) => $table->string('emergency_contact_name')->nullable()->after('postal_code'),
            'emergency_contact_phone' => fn (Blueprint $table) => $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name'),
            'vehicle_type' => fn (Blueprint $table) => $table->string('vehicle_type', 20)->default('motorcycle')->after('vehicle'),
            'license_plate' => fn (Blueprint $table) => $table->string('license_plate', 15)->nullable()->after('vehicle_type'),
            'cnh_number' => fn (Blueprint $table) => $table->string('cnh_number', 20)->nullable()->after('license_plate'),
            'cnh_category' => fn (Blueprint $table) => $table->string('cnh_category', 10)->nullable()->after('cnh_number'),
            'cnh_expires_at' => fn (Blueprint $table) => $table->date('cnh_expires_at')->nullable()->after('cnh_category'),
            'pix_key_type' => fn (Blueprint $table) => $table->string('pix_key_type', 20)->nullable()->after('cnh_expires_at'),
            'pix_key' => fn (Blueprint $table) => $table->string('pix_key')->nullable()->after('pix_key_type'),
            'branch_id' => fn (Blueprint $table) => $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete(),
            'employment_type' => fn (Blueprint $table) => $table->string('employment_type', 20)->default('freelancer')->after('pix_key'),
            'employee_code' => fn (Blueprint $table) => $table->string('employee_code', 30)->nullable()->after('employment_type'),
            'hired_at' => fn (Blueprint $table) => $table->date('hired_at')->nullable()->after('employee_code'),
            'commission_percent' => fn (Blueprint $table) => $table->decimal('commission_percent', 5, 2)->nullable()->after('hired_at'),
            'operational_status' => fn (Blueprint $table) => $table->string('operational_status', 20)->default('available')->after('commission_percent'),
            'max_active_deliveries' => fn (Blueprint $table) => $table->unsignedSmallInteger('max_active_deliveries')->default(2)->after('operational_status'),
            'notes' => fn (Blueprint $table) => $table->text('notes')->nullable()->after('max_active_deliveries'),
        ];

        foreach ($columns as $name => $callback) {
            if (! Schema::hasColumn('motoboys', $name)) {
                Schema::table('motoboys', $callback);
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'notes', 'max_active_deliveries', 'operational_status', 'commission_percent', 'hired_at',
            'employee_code', 'employment_type', 'pix_key', 'pix_key_type', 'cnh_expires_at', 'cnh_category',
            'cnh_number', 'license_plate', 'vehicle_type', 'emergency_contact_phone', 'emergency_contact_name',
            'postal_code', 'state', 'city', 'neighborhood', 'complement', 'number', 'street', 'birth_date',
            'document_rg', 'email', 'cpf',
        ];

        if (Schema::hasColumn('motoboys', 'branch_id')) {
            Schema::table('motoboys', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
            });
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn('motoboys', $column)) {
                Schema::table('motoboys', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
