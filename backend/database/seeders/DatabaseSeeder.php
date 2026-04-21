<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Review;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Usuarios fijos para desarrollo ──────────────────
        // Usamos firstOrCreate para idempotencia
        // Si ya existen → no los duplica
        $admin = User::firstOrCreate(
            ['email' => 'admin@stayspot.com'],
            [
                'name'     => 'Admin StaySpot',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'phone'    => '+502 1234-5678',
            ]
        );

        $host1 = User::firstOrCreate(
            ['email' => 'carlos@stayspot.com'],
            [
                'name'     => 'Carlos Anfitrión',
                'password' => Hash::make('password'),
                'role'     => 'host',
                'phone'    => '+502 8765-4321',
            ]
        );

        $host2 = User::firstOrCreate(
            ['email' => 'maria@stayspot.com'],
            [
                'name'     => 'María García',
                'password' => Hash::make('password'),
                'role'     => 'host',
            ]
        );

        $guest = User::firstOrCreate(
            ['email' => 'demo@stayspot.com'],
            [
                'name'     => 'Usuario Demo',
                'password' => Hash::make('password'),
                'role'     => 'guest',
            ]
        );

        // ── Hosts aleatorios ─────────────────────────────────
        // factory()->count(5)->create() crea 5 hosts
        // Solo si no tenemos ya suficientes
        if (User::where('role', 'host')->count() < 7) {
            User::factory()->count(5)->host()->create();
        }

        // ── Espacios de los hosts fijos ──────────────────────
        if (Space::count() === 0) {
            // Espacios de Carlos (host1)
            Space::create([
                'host_id'         => $host1->id,
                'name'            => 'Casa Colonial en Antigua',
                'description'     => 'Hermosa casa colonial en el corazón de Antigua Guatemala. ' .
                    'Patios internos, fuentes y decoración tradicional.',
                'city'            => 'Antigua Guatemala',
                'address'         => '5a Calle Oriente 12',
                'price_per_night' => 150.00,
                'max_guests'      => 6,
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'amenities'       => ['WiFi', 'Piscina', 'Cocina equipada', 'Jardín'],
                'latitude'        => 14.5586,
                'longitude'       => -90.7295,
                'is_active'       => true,
            ]);

            Space::create([
                'host_id'         => $host1->id,
                'name'            => 'Cabaña con Vista al Lago Atitlán',
                'description'     => 'Cabaña rústica con impresionante vista al Lago Atitlán. ' .
                    'Perfecta para desconectarse.',
                'city'            => 'Panajachel',
                'address'         => 'Calle Santander km 2',
                'price_per_night' => 200.00,
                'max_guests'      => 4,
                'bedrooms'        => 2,
                'bathrooms'       => 1,
                'amenities'       => ['WiFi', 'Balcón', 'Chimenea', 'Kayaks'],
                'latitude'        => 14.7387,
                'longitude'       => -91.1571,
                'is_active'       => true,
            ]);

            // Espacios de María (host2)
            Space::create([
                'host_id'         => $host2->id,
                'name'            => 'Apartamento Moderno Zona 10',
                'description'     => 'Apartamento de lujo en la mejor zona de Guatemala City. ' .
                    'Ideal para viajes de negocios.',
                'city'            => 'Ciudad de Guatemala',
                'address'         => '16 Calle 1-50 Zona 10',
                'price_per_night' => 90.00,
                'max_guests'      => 2,
                'bedrooms'        => 1,
                'bathrooms'       => 1,
                'amenities'       => ['WiFi', 'Aire acondicionado', 'Gym', 'Estacionamiento'],
                'latitude'        => 14.5934,
                'longitude'       => -90.5090,
                'is_active'       => true,
            ]);

            // Espacios aleatorios de los hosts generados
            $randomHosts = User::where('role', 'host')
                ->whereNotIn('id', [$host1->id, $host2->id])
                ->get();

            foreach ($randomHosts as $host) {
                // Cada host tiene entre 1 y 3 espacios
                Space::factory()
                    ->count(rand(1, 3))
                    ->create(['host_id' => $host->id]);
            }

            // Algunos espacios premium
            Space::factory()
                ->count(3)
                ->premium()
                ->create(['host_id' => $host1->id]);
        }

        // ── Reservas ─────────────────────────────────────────
        if (Reservation::count() === 0) {
            $spaces = Space::all();

            // Reservas del usuario demo
            $spaces->take(3)->each(function ($space) use ($guest) {
                Reservation::factory()
                    ->completed()
                    ->create([
                        'space_id'        => $space->id,
                        'guest_id'        => $guest->id,
                        'price_per_night' => $space->price_per_night,
                        'total_price'     => $space->price_per_night * 3,
                        'nights'          => 3,
                    ]);
            });

            // Reservas aleatorias
            Reservation::factory()
                ->count(15)
                ->confirmed()
                ->create();
        }

        $this->command->info('✅ Database seeded successfully!');
        $this->command->table(
            ['Usuario', 'Email', 'Contraseña', 'Rol'],
            [
                ['Admin',     'admin@stayspot.com',  'password', 'admin'],
                ['Carlos',    'carlos@stayspot.com', 'password', 'host'],
                ['María',     'maria@stayspot.com',  'password', 'host'],
                ['Demo User', 'demo@stayspot.com',   'password', 'guest'],
            ]
        );
    }
}
