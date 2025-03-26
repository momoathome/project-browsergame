<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('action_queue_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('status');
            $table->jsonb('details')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('action_type');
            $table->index('status');
            
            // Zusätzlicher Index für Zeitraumabfragen 
            $table->index(['start_time', 'end_time']);
        });

        // Check-Constraints wie bei action_queue
        DB::statement("ALTER TABLE action_queue_archives ADD CONSTRAINT check_archive_action_type CHECK (action_type IN ('mining', 'building', 'produce', 'trade', 'combat', 'research'))");
        DB::statement("ALTER TABLE action_queue_archives ADD CONSTRAINT check_archive_status CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled', 'failed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_queue_archives');
    }
};
