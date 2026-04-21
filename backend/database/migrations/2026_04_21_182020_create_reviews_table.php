<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('space_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('guest_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Solo se puede reseñar una reserva completada
            $table->foreignId('reservation_id')
                ->constrained()
                ->onDelete('cascade');

            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();

            // Cada huésped solo puede dejar una reseña por reserva
            $table->unique(['reservation_id', 'guest_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
