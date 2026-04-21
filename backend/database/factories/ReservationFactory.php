<?php

namespace Database\Factories;

use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    public function definition(): array
    {
        // Carbon es la librería de fechas de Laravel
        // Equivale a date-fns en JavaScript
        $checkIn  = Carbon::now()->addDays(rand(1, 30));
        $nights   = rand(1, 7);
        $checkOut = $checkIn->copy()->addDays($nights);

        $pricePerNight = fake()->randomFloat(2, 50, 300);

        return [
            'space_id'       => Space::factory(),
            'guest_id'       => User::factory(),
            'check_in'       => $checkIn->format('Y-m-d'),
            'check_out'      => $checkOut->format('Y-m-d'),
            'guests_count'   => rand(1, 4),
            'price_per_night' => $pricePerNight,
            'total_price'    => $pricePerNight * $nights,
            'nights'         => $nights,
            'status'         => fake()->randomElement([
                'pending',
                'confirmed',
                'completed'
            ]),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function completed(): static
    {
        // Reservas completadas son del pasado
        $checkIn = Carbon::now()->subDays(rand(10, 60));
        $nights  = rand(1, 7);

        return $this->state(fn(array $attributes) => [
            'check_in'  => $checkIn->format('Y-m-d'),
            'check_out' => $checkIn->copy()->addDays($nights)->format('Y-m-d'),
            'nights'    => $nights,
            'status'    => 'completed',
        ]);
    }
}
