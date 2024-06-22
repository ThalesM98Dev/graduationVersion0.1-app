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
            $table->integer('available_seats')->nullable();
            $table->time('depature_hour')->nullable();
            $table->time('arrival_hour')->nullable();
            $table->enum('trip_type', ['External', 'Universities']);
            $table->string('starting_place')->nullable();
            $table->integer('price')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('destination_id')->nullable()->constrained('destinations')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('bus_id')->nullable()->constrained('buses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('driver_id')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('collage_trip_id')->nullable()->constrained('collage_trips')->cascadeOnUpdate()->cascadeOnDelete();
            $table->json('seats')->nullable();
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
