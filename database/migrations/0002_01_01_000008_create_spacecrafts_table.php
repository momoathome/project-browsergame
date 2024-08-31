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
        Schema::create('spacecrafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('details_id')
                  ->constrained('spacecraft_details')
                  ->onDelete('cascade');
            $table->integer('combat')->nullable();
            $table->integer('count')->default(1);
            $table->integer('cargo')->default(1);
            $table->integer('build_time')->nullable();
            $table->integer('unit_limit')->nullable();
            $table->boolean('unlocked')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spacecrafts');
    }
};
