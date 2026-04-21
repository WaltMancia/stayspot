<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();

            // foreignId crea una FK correctamente tipada (BIGINT UNSIGNED)
            // constrained() deduce la tabla referenciada del nombre del campo
            // onDelete('cascade') → si se borra el host, se borran sus espacios
            $table->foreignId('host_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('city', 100);
            $table->string('address', 255)->nullable();
            $table->string('country', 100)->default('Guatemala');

            // decimal(precision, scale) para dinero — NUNCA float
            $table->decimal('price_per_night', 10, 2);

            $table->unsignedSmallInteger('max_guests')->default(1);
            $table->unsignedSmallInteger('bedrooms')->default(1);
            $table->unsignedSmallInteger('bathrooms')->default(1);

            // JSON para amenidades — evita una tabla extra para esta relación simple
            // MySQL tiene soporte nativo para JSON desde 5.7
            $table->json('amenities')->nullable();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->boolean('is_active')->default(true);

            // Índices para las búsquedas más frecuentes
            // Sin índices → full table scan en cada búsqueda
            $table->index('city');
            $table->index('price_per_night');
            $table->index('is_active');
            $table->index(['city', 'is_active']); // índice compuesto

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
