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
        Schema::create('shipment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_trip_id')->constrained('shipment_trips')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('weight');
            $table->integer('price')->nullable();
            $table->string('status')->default('pending');
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('nationality')->nullable();
            $table->integer('mobile_number')->nullable();
            $table->bigInteger('id_number')->nullable();
            $table->integer('age')->nullable();
            $table->string('image_of_ID')->nullable();
            $table->string('image_of_commercial_register')->nullable();
            $table->string('image_of_industrial_register')->nullable();
            $table->string('image_of_customs_declaration')->nullable();
            $table->string('image_of_pledge')->nullable();
            $table->timestamps();
            $table->index(['shipment_trip_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_requests');
    }
};
