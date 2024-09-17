<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsteroidsTable extends Migration
{
    public function up()
    {
        Schema::create('asteroids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('size');
            $table->float('base');
            $table->float('multiplier');
            $table->integer('value');
            $table->integer('x');
            $table->integer('y');
            $table->float('pixel_size');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('asteroids');
    }
}

