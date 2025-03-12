<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('action_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('status')->default('pending');
            $table->jsonb('details')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('user_id');
            $table->index('action_type');
            $table->index('status');
            $table->index('end_time');

            // Composite index for common queries
            $table->index(['user_id', 'status', 'action_type']);
        });

        // Add check constraints using raw SQL
        DB::statement("ALTER TABLE action_queue ADD CONSTRAINT check_action_type CHECK (action_type IN ('mining', 'building', 'produce', 'trade'))");
        DB::statement("ALTER TABLE action_queue ADD CONSTRAINT check_status CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled', 'failed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_queue');
    }
};
