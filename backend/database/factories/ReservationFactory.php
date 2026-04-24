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
        $checkIn  = Carbon::now()->addDays(rand(5, 30));
        $nights   = rand(1, 7);
        $checkOut = $checkIn->copy()->addDays($nights);
        $price    = fake()->randomFloat(2, 50, 300);

        return [
            'space_id'        => Space::factory(),
            'guest_id'        => User::factory(),
            'check_in'        => $checkIn->format('Y-m-d'),
            'check_out'       => $checkOut->format('Y-m-d'),
            'guests_count'    => rand(1, 4),
            'price_per_night' => $price,
            'total_price'     => $price * $nights,
            'nights'          => $nights,
            'status'          => 'confirmed',
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }

    public function completed(): static
    {
        $checkIn = Carbon::now()->subDays(rand(10, 60));
        $nights  = rand(1, 7);

        return $this->state([
            'check_in'  => $checkIn->format('Y-m-d'),
            'check_out' => $checkIn->copy()->addDays($nights)->format('Y-m-d'),
            'nights'    => $nights,
            'status'    => 'completed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
