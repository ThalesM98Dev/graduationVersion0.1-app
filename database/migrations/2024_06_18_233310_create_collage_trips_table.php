<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collage_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->date('start_date')->default(Carbon::now()->format('Y-m-d'));
            $table->date('end_date')->default(Carbon::now()->addMonths(2)->format('Y-m-d'));
            $table->bigInteger('go_price')->nullable();
            $table->bigInteger('round_trip_price')->nullable();
            $table->bigInteger('semester_round_trip_price')->nullable();
            $table->bigInteger('go_points')->nullable();
            $table->bigInteger('round_trip_points')->nullable();
            $table->bigInteger('semester_round_trip_points')->nullable();
            $table->bigInteger('required_go_points')->nullable();
            $table->bigInteger('required_round_trip_points')->nullable();
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
