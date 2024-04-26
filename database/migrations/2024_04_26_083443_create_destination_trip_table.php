<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('destination_trip', function (Blueprint $table) {
            $table->id();
            $table->dateTime('arrival_dateTime');
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_station');
    }
};
