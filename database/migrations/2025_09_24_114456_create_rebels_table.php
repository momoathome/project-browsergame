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
        Schema::create('rebels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('faction');
            $table->integer('x');
            $table->integer('y');
            $table->integer('difficulty_level');
            $table->timestamp('last_interaction');
            $table->integer('defeated_count')->default(0);
            $table->integer('fleet_cap');
            $table->float('fleet_growth_rate');
            $table->float('loot_multiplier');
            $table->integer('adaptation_level')->default(0);
            $table->string('behavior');
            $table->float('base_chance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rebels');
    }
};
