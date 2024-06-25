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
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('collage_trip_id')->constrained('collage_trips')->cascadeOnDelete()->cascadeOnUpdate();
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->enum('type', ['Go', 'Back']);
            $table->index('collage_trip_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
