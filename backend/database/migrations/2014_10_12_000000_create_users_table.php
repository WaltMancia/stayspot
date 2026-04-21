<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Return new class es la sintaxis moderna de PHP 8
// Las migraciones son clases anónimas
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

            $table->string('name', 100);

            // unique() añade índice único automáticamente
            $table->string('email', 150)->unique();

            $table->timestamp('email_verified_at')->nullable();

            // En Laravel NUNCA guardamos la contraseña — guardamos el hash
            // El nombre 'password' es convención de Laravel
            $table->string('password');

            // enum() genera un ENUM en MySQL
            $table->enum('role', ['guest', 'host', 'admin'])
                ->default('guest');

            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);

            $table->rememberToken(); // para "recuérdame" — columna remember_token

            // timestamps() crea created_at y updated_at automáticamente
            $table->timestamps();
        });

        // Tabla para tokens de contraseña olvidada
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
    }
};
