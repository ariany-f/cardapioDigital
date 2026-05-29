<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_storage_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('key')->nullable();
            $table->text('secret')->nullable();
            $table->string('region', 32)->default('us-east-1');
            $table->string('bucket')->nullable();
            $table->string('url')->nullable();
            $table->string('endpoint')->nullable();
            $table->boolean('use_path_style_endpoint')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_storage_settings');
    }
};
