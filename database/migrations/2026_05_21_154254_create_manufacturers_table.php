<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name');
            $table->string('linkingType')->nullable();
            $table->string('image')->nullable()->default('https://cdn.iconscout.com/icon/free/png-256/free-car-icon-svg-download-png-7930436.png?f=webp&w=128');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturers');
    }
};
