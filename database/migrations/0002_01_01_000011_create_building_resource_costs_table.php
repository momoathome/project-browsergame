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
        Schema::create('building_resource_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')
                  ->constrained('buildings')
                  ->onDelete('cascade');
            $table->foreignId('resource_id')
                  ->constrained('resources')
                  ->onDelete('cascade');
            $table->integer('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_resource_costs');
    }
};
