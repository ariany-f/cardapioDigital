<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_leads', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('city')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('internal_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_leads');
    }
};
