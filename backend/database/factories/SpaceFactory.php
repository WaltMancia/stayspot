<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpaceFactory extends Factory
{
    public function definition(): array
    {
        // Ciudades reales de Guatemala para datos más realistas
        $ciudades = [
            'Ciudad de Guatemala',
            'Antigua Guatemala',
            'Quetzaltenango',
            'Panajachel',
            'Flores',
            'Cobán',
            'Huehuetenango',
            'Monterrico',
        ];

        $nombres = [
            'Casa Colonial',
            'Apartamento Moderno',
            'Cabaña de Montaña',
            'Villa con Vista',
            'Estudio Acogedor',
            'Suite Ejecutiva',
            'Casa de Playa',
            'Bungalow Tropical',
            'Loft Urbano',
            'Finca Cafetalera',
        ];

        $amenidades = [
            'WiFi',
            'Piscina',
            'Estacionamiento',
            'Cocina equipada',
            'Aire acondicionado',
            'TV por cable',
            'Lavadora',
            'Balcón',
            'Jardín',
            'Chimenea',
            'Jacuzzi',
            'Gimnasio',
        ];

        // Imágenes de Unsplash para que se vea bonito
        $imagenes = [
            'https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1449158743715-0abbc851b579?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop',
        ];

        return [
            // Para el host_id usamos un ID existente o creamos uno
            // La factory maneja esto automáticamente
            'host_id'         => User::factory()->host(),
            'name'            => fake()->randomElement($nombres) . ' en ' .
                fake()->randomElement($ciudades),
            'description'     => fake()->paragraphs(2, true),
            'city'            => fake()->randomElement($ciudades),
            'address'         => fake()->streetAddress(),
            'country'         => 'Guatemala',
            'price_per_night' => fake()->randomFloat(2, 30, 500),
            'max_guests'      => fake()->numberBetween(1, 10),
            'bedrooms'        => fake()->numberBetween(1, 5),
            'bathrooms'       => fake()->numberBetween(1, 3),
            // randomElements devuelve un subconjunto aleatorio del array
            'amenities'       => fake()->randomElements($amenidades, rand(3, 7)),
            'latitude'        => fake()->latitude(13.5, 15.8),  // coords de Guatemala
            'longitude'       => fake()->longitude(-92.5, -88.2),
            'is_active'       => true,
        ];
    }

    // Estado para espacios destacados (precio alto + buenas características)
    public function premium(): static
    {
        return $this->state(fn(array $attributes) => [
            'price_per_night' => fake()->randomFloat(2, 200, 500),
            'bedrooms'        => fake()->numberBetween(3, 5),
            'bathrooms'       => fake()->numberBetween(2, 3),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
