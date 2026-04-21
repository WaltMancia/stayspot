<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('space_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('guest_id')
                ->constrained('users')
                ->onDelete('cascade');

            // date() solo guarda la fecha sin hora — perfecto para check-in/out
            $table->date('check_in');
            $table->date('check_out');

            $table->unsignedSmallInteger('guests_count')->default(1);

            // Guardamos el precio al momento de la reserva
            // Si el anfitrión cambia el precio, las reservas anteriores no se afectan
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->unsignedSmallInteger('nights');

            $table->enum('status', [
                'pending',    // esperando confirmación del host
                'confirmed',  // confirmada
                'cancelled',  // cancelada
                'completed',  // estadía finalizada
            ])->default('pending');

            // Para integración con Stripe en pasos siguientes
            $table->string('stripe_payment_id')->nullable();
            $table->string('stripe_payment_status')->nullable();

            $table->text('cancellation_reason')->nullable();

            // Índice para verificar disponibilidad eficientemente
            $table->index(['space_id', 'check_in', 'check_out']);
            $table->index(['guest_id', 'status']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
