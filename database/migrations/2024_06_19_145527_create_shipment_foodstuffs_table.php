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
        Schema::create('shipment_foodstuffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_request_id')->constrained('shipment_requests')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('foodstuff_id')->constrained('foodstuffs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_foodstuffs');
    }
};
