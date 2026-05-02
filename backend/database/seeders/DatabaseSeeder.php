<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Review;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Seeding StaySpot database...');

        $this->seedUsers();
        $this->seedSpaces();
        $this->seedReservations();
        $this->seedReviews();

        $this->printCredentials();
    }

    // ────────────────────────────────────────────────────────────

    private function seedUsers(): void
    {
        $this->command->info('  → Users...');

        // firstOrCreate garantiza idempotencia
        // Si corremos el seeder dos veces no duplica usuarios
        User::firstOrCreate(['email' => 'admin@stayspot.com'], [
            'name'     => 'Admin StaySpot',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'phone'    => '+502 1234-5678',
        ]);

        User::firstOrCreate(['email' => 'carlos@stayspot.com'], [
            'name'     => 'Carlos Anfitrión',
            'password' => Hash::make('password'),
            'role'     => 'host',
            'phone'    => '+502 8765-4321',
        ]);

        User::firstOrCreate(['email' => 'maria@stayspot.com'], [
            'name'     => 'María García',
            'password' => Hash::make('password'),
            'role'     => 'host',
            'phone'    => '+502 5555-1234',
        ]);

        User::firstOrCreate(['email' => 'demo@stayspot.com'], [
            'name'     => 'Usuario Demo',
            'password' => Hash::make('password'),
            'role'     => 'guest',
        ]);

        // Hosts y guests adicionales para que el catálogo se vea lleno
        if (User::where('role', 'host')->count() < 6) {
            User::factory()->count(4)->host()->create();
        }
        if (User::where('role', 'guest')->count() < 6) {
            User::factory()->count(5)->create();
        }

        $this->command->info('     ✓ ' . User::count() . ' users.');
    }

    // ────────────────────────────────────────────────────────────

    private function seedSpaces(): void
    {
        if (Space::count() > 0) {
            $this->command->info('  → Spaces already seeded.');
            return;
        }

        $this->command->info('  → Spaces...');

        $carlos = User::where('email', 'carlos@stayspot.com')->first();
        $maria  = User::where('email', 'maria@stayspot.com')->first();

        $fixedSpaces = [
            [
                'host_id'         => $carlos->id,
                'name'            => 'Casa Colonial en Antigua',
                'description'     => 'Hermosa casa colonial en el corazón de Antigua. ' .
                                     'Patios internos, fuentes y decoración tradicional guatemalteca. ' .
                                     'A 5 minutos del Parque Central a pie.',
                'city'            => 'Antigua Guatemala',
                'address'         => '5a Calle Oriente 12',
                'price_per_night' => 150.00,
                'max_guests'      => 6,
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'amenities'       => ['WiFi', 'Piscina', 'Cocina equipada', 'Jardín', 'Estacionamiento'],
                'latitude'        => 14.5586,
                'longitude'       => -90.7295,
                'is_active'       => true,
            ],
            [
                'host_id'         => $carlos->id,
                'name'            => 'Cabaña Vista al Lago Atitlán',
                'description'     => 'Cabaña rústica con vista espectacular al Lago Atitlán. ' .
                                     'Kayaks incluidos y acceso privado al lago. El lugar perfecto ' .
                                     'para desconectarse del mundo.',
                'city'            => 'Panajachel',
                'address'         => 'Calle Santander km 2',
                'price_per_night' => 200.00,
                'max_guests'      => 4,
                'bedrooms'        => 2,
                'bathrooms'       => 1,
                'amenities'       => ['WiFi', 'Balcón', 'Chimenea', 'Kayaks', 'Vista al lago'],
                'latitude'        => 14.7387,
                'longitude'       => -91.1571,
                'is_active'       => true,
            ],
            [
                'host_id'         => $maria->id,
                'name'            => 'Apartamento Moderno Zona 10',
                'description'     => 'Apartamento de lujo en la Zona Viva. ' .
                                     'Ideal para viajes de negocios o turismo urbano. ' .
                                     'A pasos de restaurantes y centros comerciales.',
                'city'            => 'Ciudad de Guatemala',
                'address'         => '16 Calle 1-50 Zona 10',
                'price_per_night' => 90.00,
                'max_guests'      => 2,
                'bedrooms'        => 1,
                'bathrooms'       => 1,
                'amenities'       => ['WiFi', 'Aire acondicionado', 'Gimnasio', 'Estacionamiento'],
                'latitude'        => 14.5934,
                'longitude'       => -90.5090,
                'is_active'       => true,
            ],
            [
                'host_id'         => $maria->id,
                'name'            => 'Villa con Piscina en Monterrico',
                'description'     => 'Villa frente al mar con piscina privada y acceso directo ' .
                                     'a la playa de arena negra. Perfecta para grupos.',
                'city'            => 'Monterrico',
                'address'         => 'Calle al Pacífico s/n',
                'price_per_night' => 350.00,
                'max_guests'      => 10,
                'bedrooms'        => 4,
                'bathrooms'       => 3,
                'amenities'       => ['WiFi', 'Piscina', 'Playa privada', 'BBQ', 'Cocina equipada'],
                'latitude'        => 13.8891,
                'longitude'       => -90.4805,
                'is_active'       => true,
            ],
            [
                'host_id'         => $carlos->id,
                'name'            => 'Eco-Lodge en Semuc Champey',
                'description'     => 'Alojamiento ecológico a pasos de las piscinas naturales. ' .
                                     'Construcción sustentable, desayuno incluido y guías locales.',
                'city'            => 'Alta Verapaz',
                'address'         => 'Semuc Champey, Lanquín',
                'price_per_night' => 75.00,
                'max_guests'      => 3,
                'bedrooms'        => 1,
                'bathrooms'       => 1,
                'amenities'       => ['Desayuno incluido', 'Tours guiados', 'Hamacas'],
                'latitude'        => 15.5333,
                'longitude'       => -89.9333,
                'is_active'       => true,
            ],
        ];

        foreach ($fixedSpaces as $data) {
            Space::create($data);
        }

        // Espacios adicionales con factory para tener catálogo variado
        $extraHosts = User::where('role', 'host')
            ->whereNotIn('email', ['carlos@stayspot.com', 'maria@stayspot.com'])
            ->get();

        foreach ($extraHosts as $host) {
            Space::factory()->count(rand(1, 2))->create(['host_id' => $host->id]);
        }

        // Espacios premium para mostrar el rango de precios
        Space::factory()->count(2)->premium()->create(['host_id' => $carlos->id]);

        $this->command->info('     ✓ ' . Space::count() . ' spaces.');
    }

    // ────────────────────────────────────────────────────────────

    private function seedReservations(): void
    {
        if (Reservation::count() > 0) {
            $this->command->info('  → Reservations already seeded.');
            return;
        }

        $this->command->info('  → Reservations...');

        $demo   = User::where('email', 'demo@stayspot.com')->first();
        $spaces = Space::take(5)->get();

        // Reservas completadas del demo (para poder escribir reseñas)
        foreach ($spaces->take(3) as $space) {
            $checkIn = Carbon::now()->subDays(rand(20, 60));
            $nights  = rand(2, 5);

            Reservation::create([
                'space_id'        => $space->id,
                'guest_id'        => $demo->id,
                'check_in'        => $checkIn->format('Y-m-d'),
                'check_out'       => $checkIn->copy()->addDays($nights)->format('Y-m-d'),
                'guests_count'    => rand(1, 2),
                'price_per_night' => $space->price_per_night,
                'total_price'     => $space->price_per_night * $nights,
                'nights'          => $nights,
                'status'          => 'completed',
                'stripe_payment_status' => 'succeeded',
                'paid_at'         => Carbon::now()->subDays(rand(15, 55)),
            ]);
        }

        // Reserva confirmada próxima del demo (para probar el pago)
        $lastSpace = $spaces->last();
        Reservation::create([
            'space_id'        => $lastSpace->id,
            'guest_id'        => $demo->id,
            'check_in'        => Carbon::now()->addDays(10)->format('Y-m-d'),
            'check_out'       => Carbon::now()->addDays(14)->format('Y-m-d'),
            'guests_count'    => 2,
            'price_per_night' => $lastSpace->price_per_night,
            'total_price'     => $lastSpace->price_per_night * 4,
            'nights'          => 4,
            'status'          => 'confirmed',
            'stripe_payment_status' => 'succeeded',
            'paid_at'         => Carbon::now()->subDays(1),
        ]);

        // Reserva pendiente del demo (para probar el flujo de pago)
        $firstSpace = $spaces->first();
        Reservation::create([
            'space_id'        => $firstSpace->id,
            'guest_id'        => $demo->id,
            'check_in'        => Carbon::now()->addDays(20)->format('Y-m-d'),
            'check_out'       => Carbon::now()->addDays(23)->format('Y-m-d'),
            'guests_count'    => 2,
            'price_per_night' => $firstSpace->price_per_night,
            'total_price'     => $firstSpace->price_per_night * 3,
            'nights'          => 3,
            'status'          => 'pending',
        ]);

        // Reservas adicionales para que las estadísticas del host se vean bien
        Reservation::factory()->count(10)->completed()->create();
        Reservation::factory()->count(5)->confirmed()->create();
        Reservation::factory()->count(3)->pending()->create();

        $this->command->info('     ✓ ' . Reservation::count() . ' reservations.');
    }

    // ────────────────────────────────────────────────────────────

    private function seedReviews(): void
    {
        if (Review::count() > 0) {
            $this->command->info('  → Reviews already seeded.');
            return;
        }

        $this->command->info('  → Reviews...');

        $demo = User::where('email', 'demo@stayspot.com')->first();

        // Reseñas de las reservas completadas del demo
        $completedDemo = Reservation::where('guest_id', $demo->id)
            ->where('status', 'completed')
            ->get();

        $demoComments = [
            'Increíble experiencia. El espacio estaba inmaculado y Carlos fue un anfitrión excepcional. Sin duda volvería.',
            'Una estadía perfecta. La vista al lago era exactamente como en las fotos. Muy recomendado.',
            'Excelente ubicación y muy cómodo. Todo lo que necesitaba para mis vacaciones estaba ahí.',
        ];

        foreach ($completedDemo as $index => $reservation) {
            Review::create([
                'space_id'       => $reservation->space_id,
                'guest_id'       => $demo->id,
                'reservation_id' => $reservation->id,
                'rating'         => rand(4, 5),
                'comment'        => $demoComments[$index] ?? $demoComments[0],
            ]);
        }

        // Reseñas adicionales para los demás espacios
        $otherCompleted = Reservation::where('status', 'completed')
            ->where('guest_id', '!=', $demo->id)
            ->with('space')
            ->get();

        $genericComments = [
            'Muy buen espacio, limpio y bien equipado. El anfitrión fue muy atento.',
            'Superó nuestras expectativas. La ubicación es perfecta para explorar la zona.',
            'Excelente relación calidad-precio. Lo recomendaría a cualquiera.',
            'Un lugar tranquilo y acogedor. Perfecto para descansar.',
            'Todo tal como se describe. Muy satisfecho con la estadía.',
        ];

        foreach ($otherCompleted->take(10) as $reservation) {
            // Verificamos que no tenga reseña ya (idempotencia)
            if ($reservation->review) continue;

            Review::create([
                'space_id'       => $reservation->space_id,
                'guest_id'       => $reservation->guest_id,
                'reservation_id' => $reservation->id,
                'rating'         => rand(3, 5),
                'comment'        => fake()->randomElement($genericComments),
            ]);
        }

        $this->command->info('     ✓ ' . Review::count() . ' reviews.');
    }

    // ────────────────────────────────────────────────────────────

    private function printCredentials(): void
    {
        $this->command->newLine();
        $this->command->info('✅ Seeding completed!');
        $this->command->newLine();
        $this->command->table(
            ['Nombre', 'Email', 'Contraseña', 'Rol'],
            [
                ['Admin',    'admin@stayspot.com',  'password', 'admin'],
                ['Carlos',   'carlos@stayspot.com', 'password', 'host'],
                ['María',    'maria@stayspot.com',  'password', 'host'],
                ['Demo',     'demo@stayspot.com',   'password', 'guest'],
            ]
        );
    }
}