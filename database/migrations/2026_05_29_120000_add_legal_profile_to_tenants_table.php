<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'legal_name' => fn (Blueprint $table) => $table->string('legal_name')->nullable()->after('name'),
            'document_type' => fn (Blueprint $table) => $table->string('document_type', 10)->default('cnpj')->after('legal_name'),
            'document_number' => fn (Blueprint $table) => $table->string('document_number', 20)->nullable()->after('document_type'),
            'state_registration' => fn (Blueprint $table) => $table->string('state_registration', 30)->nullable()->after('document_number'),
            'municipal_registration' => fn (Blueprint $table) => $table->string('municipal_registration', 30)->nullable()->after('state_registration'),
            'email' => fn (Blueprint $table) => $table->string('email')->nullable()->after('phone'),
            'website' => fn (Blueprint $table) => $table->string('website')->nullable()->after('email'),
            'street' => fn (Blueprint $table) => $table->string('street')->nullable()->after('website'),
            'number' => fn (Blueprint $table) => $table->string('number', 20)->nullable()->after('street'),
            'complement' => fn (Blueprint $table) => $table->string('complement')->nullable()->after('number'),
            'neighborhood' => fn (Blueprint $table) => $table->string('neighborhood')->nullable()->after('complement'),
            'city' => fn (Blueprint $table) => $table->string('city')->nullable()->after('neighborhood'),
            'state' => fn (Blueprint $table) => $table->string('state', 2)->nullable()->after('city'),
            'postal_code' => fn (Blueprint $table) => $table->string('postal_code', 10)->nullable()->after('state'),
        ];

        foreach ($columns as $name => $callback) {
            if (! Schema::hasColumn('tenants', $name)) {
                Schema::table('tenants', $callback);
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'legal_name', 'document_type', 'document_number', 'state_registration', 'municipal_registration',
            'email', 'website', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'postal_code',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('tenants', $column)) {
                Schema::table('tenants', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
