<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    // define() describe cómo generar un usuario falso
    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            // fake() usa Faker — genera datos falsos realistas
            'email'    => fake()->unique()->safeEmail(),
            'role'     => 'guest',
            'phone'    => fake()->phoneNumber(),
            'password' => Hash::make('password'), // siempre 'password' en desarrollo
            'is_active' => true,
        ];
    }

    // Estados — variaciones de la factory
    // UserFactory::new()->host()->create()
    public function host(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'host',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
