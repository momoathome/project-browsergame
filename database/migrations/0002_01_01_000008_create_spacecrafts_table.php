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
            $table->integer('combat')->default(1);
            $table->integer('cargo')->default(1);
            $table->integer('speed')->default(1);
            $table->integer('count')->default(1);
            $table->integer('build_time')->nullable();
            $table->integer('crew_limit')->default(1);
            $table->integer('research_cost')->default(1);
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
