<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('action_queue_spacecraft_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('action_queue_id');
            $table->unsignedBigInteger('spacecraft_details_id');
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->foreign('action_queue_id')->references('id')->on('action_queue')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('action_queue_spacecraft_locks');
    }
};
