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
        Schema::create('collage_trips', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('go_price')->nullable();
            $table->bigInteger('round_trip_price')->nullable();
           // $table->bigInteger('semester_go_price')->nullable();
            $table->bigInteger('semester_round_trip_price')->nullable();
            $table->bigInteger('go_points')->nullable();
            $table->bigInteger('round_trip_points')->nullable();
           // $table->bigInteger('semester_go_points')->nullable();
            $table->bigInteger('semester_round_trip_points')->nullable();
            $table->bigInteger('required_go_points')->nullable();
            $table->bigInteger('required_round_trip_points')->nullable();
            //$table->bigInteger('required_semester_go_points')->nullable();
            $table->bigInteger('required_semester_round_trip_points')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collage_trips');
    }
};
