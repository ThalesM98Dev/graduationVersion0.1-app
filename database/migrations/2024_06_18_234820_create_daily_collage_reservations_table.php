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
        Schema::create('daily_collage_reservations', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->enum('type', ['Go', 'Back', 'Round Trip'])->default('Round Trip');
            $table->bigInteger('cost')->default(0);
            $table->bigInteger('used_points')->default(0);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('day_id')->constrained('days')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_collage_reservations');
    }
};
