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
        Schema::create('shipment_trips', function (Blueprint $table) {
            $table->id();
            $table->integer('trip_number');
            $table->foreignId('destination_id')->nullable()->constrained('destinations')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('date');
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('status')->default('pending');
            $table->integer('available_weight');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_trips');
    }
};
