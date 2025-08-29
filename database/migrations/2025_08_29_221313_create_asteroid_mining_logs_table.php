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
        Schema::create('asteroid_mining_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->json('asteroid_info');
            $table->json('resources_extracted');
            $table->json('spacecrafts_used');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asteroid_mining_logs');
    }
};
