<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rebel_spacecrafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rebel_id')
                  ->constrained('rebels')
                  ->onDelete('cascade');
            $table->foreignId('details_id')
                  ->constrained('spacecraft_details')
                  ->onDelete('cascade');
            $table->integer('attack')->default(0);
            $table->integer('defense')->default(0);
            $table->integer('cargo')->default(0);
            $table->integer('speed')->default(0);
            $table->integer('operation_speed')->default(0);
            $table->integer('count')->default(0);
            $table->integer('locked_count')->default(0);
            $table->integer('build_time')->nullable();
            $table->integer('crew_limit')->default(0);
            $table->integer('research_cost')->default(0);
            $table->boolean('unlocked')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rebel_spacecrafts');
    }
};
