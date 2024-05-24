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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->integer('trip_number');
            $table->date('date');
            $table->time('depature_hour');
            $table->time('arrival_hour')->nullable();
            $table->string('trip_type');
            $table->string('starting_place');
            $table->integer('price')->nullable();
            $table->bigInteger('semester_price')->nullable();
            $table->bigInteger('daily_points')->nullable();
            $table->bigInteger('semester_points')->nullable();
            $table->unsignedBigInteger('destination_id');
            $table->foreign('destination_id')->references('id')->on('destinations')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('bus_id');
            $table->foreign('bus_id')->references('id')->on('buses')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('available_seats')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
