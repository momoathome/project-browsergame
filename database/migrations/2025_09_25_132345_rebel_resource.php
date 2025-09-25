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
        Schema::create('rebel_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rebel_id')->constrained()->onDelete('cascade');
            $table->foreignId('resource_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('amount')->default(0); // Anzahl der Ressourcen
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rebel_resources');
    }
};
