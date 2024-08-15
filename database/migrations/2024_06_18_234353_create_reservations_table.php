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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->foreign('trip_id')->references('id')->on('trips')->onUpdate('cascade')->onDelete('cascade');
            //$table->string('ticket_type');
            //$table->integer('ticket_number');
            $table->enum('status', ['pending', 'accept']);
            $table->bigInteger('total_price')->nullable();
            $table->integer('count_of_persons');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->index('trip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
