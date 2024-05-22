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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile_number');
            $table->integer('age');
            $table->string('address');
            $table->string('nationality');
            $table->string('image_of_ID');
            $table->string('image_of_passport')->nullable();
            $table->string('image_of_security_clearance')->nullable();
            $table->string('image_of_visa')->nullable();
            $table->unsignedBigInteger('user_id')->default();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
