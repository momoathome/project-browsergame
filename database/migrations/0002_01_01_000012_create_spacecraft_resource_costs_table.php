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
        Schema::create('spacecraft_resource_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spacecraft_id')
                  ->constrained('spacecrafts')
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
        Schema::dropIfExists('spacecraft_resource_costs');
    }
};
