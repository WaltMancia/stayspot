<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Idempotency key → evita crear dos PaymentIntents
            // para la misma reserva
            if (!Schema::hasColumn('reservations', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id')->nullable()->after('status');
                $table->index('stripe_payment_intent_id');
            }

            // Ya existe stripe_payment_status en la migración original,
            // por eso lo eliminamos aquí para evitar duplicados

            if (!Schema::hasColumn('reservations', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('stripe_payment_intent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_payment_intent_id',
                'paid_at',
            ]);
        });
    }
};
