<?php

use App\Models\Reservation;
use App\Models\Space;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Helper para crear una reserva base
function makeReservationData(array $overrides = []): array
{
    return array_merge([
        'check_in'     => now()->addDays(5)->format('Y-m-d'),
        'check_out'    => now()->addDays(8)->format('Y-m-d'),
        'guests_count' => 2,
    ], $overrides);
}

// ── Disponibilidad ───────────────────────────────────────────

test('can get blocked dates for a space', function () {
    $space = Space::factory()->create();
    $guest = User::factory()->create();

    // Creamos una reserva confirmada
    Reservation::factory()->create([
        'space_id'  => $space->id,
        'guest_id'  => $guest->id,
        'check_in'  => now()->addDays(5)->format('Y-m-d'),
        'check_out' => now()->addDays(8)->format('Y-m-d'),
        'status'    => 'confirmed',
    ]);

    $response = $this->getJson("/api/spaces/{$space->id}/availability");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'space_id',
            'blocked_dates',
        ]);

    // Las fechas del check_in a check_out-1 deben estar bloqueadas
    $blockedDates = $response->json('blocked_dates');
    expect($blockedDates)->toContain(now()->addDays(5)->format('Y-m-d'));
    expect($blockedDates)->toContain(now()->addDays(6)->format('Y-m-d'));
    expect($blockedDates)->toContain(now()->addDays(7)->format('Y-m-d'));
    // El check_out no está bloqueado
    expect($blockedDates)->not->toContain(now()->addDays(8)->format('Y-m-d'));
});

test('price estimate includes discount for weekly stay', function () {
    $space = Space::factory()->create(['price_per_night' => 100]);

    $response = $this->getJson(
        "/api/spaces/{$space->id}/price-estimate?" .
            "check_in=" . now()->addDays(1)->format('Y-m-d') .
            "&check_out=" . now()->addDays(8)->format('Y-m-d') // 7 noches
    );

    $response->assertStatus(200);

    $data = $response->json();
    expect($data['nights'])->toBe(7);
    expect($data['subtotal'])->toBe(700.0);
    expect($data['discount'])->toBe(35.0);   // 5% de 700
    expect($data['total'])->toBe(665.0);
});

// ── Creación de reservas ─────────────────────────────────────

test('guest can create reservation on available space', function () {
    $space = Space::factory()->create(['is_active' => true]);
    $guest = User::factory()->create(['role' => 'guest']);

    $response = $this->actingAs($guest)
        ->postJson('/api/reservations', [
            'space_id'     => $space->id,
            ...makeReservationData(),
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'check_in',
                'check_out',
                'nights',
                'total_price',
                'status',
            ],
        ]);

    expect($response->json('data.status'))->toBe('pending');
});

test('cannot double book the same space', function () {
    $space  = Space::factory()->create(['is_active' => true]);
    $guest1 = User::factory()->create();
    $guest2 = User::factory()->create();

    $dates = makeReservationData([
        'check_in'  => now()->addDays(5)->format('Y-m-d'),
        'check_out' => now()->addDays(8)->format('Y-m-d'),
    ]);

    // Primera reserva — debe funcionar
    $this->actingAs($guest1)
        ->postJson('/api/reservations', [
            'space_id' => $space->id,
            ...$dates,
        ])
        ->assertStatus(201);

    // Segunda reserva en las mismas fechas — debe fallar
    $this->actingAs($guest2)
        ->postJson('/api/reservations', [
            'space_id' => $space->id,
            ...$dates,
        ])
        ->assertStatus(422)
        ->assertJson(['message' => 'El espacio ya no está disponible en las fechas seleccionadas']);
});

test('cannot book own space', function () {
    $host  = User::factory()->host()->create();
    $space = Space::factory()->create(['host_id' => $host->id]);

    $this->actingAs($host)
        ->postJson('/api/reservations', [
            'space_id' => $space->id,
            ...makeReservationData(),
        ])
        ->assertStatus(422)
        ->assertJson(['message' => 'No puedes reservar tu propio espacio']);
});

test('cannot book with past check_in', function () {
    $space = Space::factory()->create();
    $guest = User::factory()->create();

    $this->actingAs($guest)
        ->postJson('/api/reservations', [
            'space_id'     => $space->id,
            'check_in'     => now()->subDays(2)->format('Y-m-d'),
            'check_out'    => now()->addDays(2)->format('Y-m-d'),
            'guests_count' => 1,
        ])
        ->assertStatus(422);
});

test('total price is calculated server side', function () {
    $space = Space::factory()->create(['price_per_night' => 100]);
    $guest = User::factory()->create();

    $this->actingAs($guest)
        ->postJson('/api/reservations', [
            'space_id'     => $space->id,
            'check_in'     => now()->addDays(1)->format('Y-m-d'),
            'check_out'    => now()->addDays(4)->format('Y-m-d'), // 3 noches
            'guests_count' => 1,
            'total_price'  => 9999, // el cliente intenta manipular el precio
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.total_price', 300.0); // 100 * 3 noches
});

// ── Cancelación ──────────────────────────────────────────────

test('guest can cancel reservation with enough notice', function () {
    $guest = User::factory()->create();
    $space = Space::factory()->create();

    // Reserva con check_in en 10 días (hay tiempo suficiente)
    $reservation = Reservation::factory()->create([
        'guest_id'  => $guest->id,
        'space_id'  => $space->id,
        'check_in'  => now()->addDays(10)->format('Y-m-d'),
        'check_out' => now()->addDays(13)->format('Y-m-d'),
        'status'    => 'confirmed',
    ]);

    $this->actingAs($guest)
        ->patchJson("/api/reservations/{$reservation->id}/cancel", [
            'reason' => 'Cambio de planes',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');
});

test('guest cannot cancel reservation with less than 48 hours', function () {
    $guest = User::factory()->create();
    $space = Space::factory()->create();

    // Reserva con check_in en 1 día — menos de 48h
    $reservation = Reservation::factory()->create([
        'guest_id'  => $guest->id,
        'space_id'  => $space->id,
        'check_in'  => now()->addHours(24)->format('Y-m-d'),
        'check_out' => now()->addHours(72)->format('Y-m-d'),
        'status'    => 'confirmed',
    ]);

    $this->actingAs($guest)
        ->patchJson("/api/reservations/{$reservation->id}/cancel")
        ->assertStatus(422)
        ->assertJsonPath(
            'message',
            'No puedes cancelar con menos de 48 horas de anticipación'
        );
});

test('host can confirm pending reservation', function () {
    $host  = User::factory()->host()->create();
    $guest = User::factory()->create();
    $space = Space::factory()->create(['host_id' => $host->id]);

    $reservation = Reservation::factory()->create([
        'space_id' => $space->id,
        'guest_id' => $guest->id,
        'status'   => 'pending',
    ]);

    $this->actingAs($host)
        ->patchJson("/api/reservations/{$reservation->id}/confirm")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'confirmed');
});

test('another host cannot confirm reservation', function () {
    $host1 = User::factory()->host()->create();
    $host2 = User::factory()->host()->create();
    $guest = User::factory()->create();
    $space = Space::factory()->create(['host_id' => $host1->id]);

    $reservation = Reservation::factory()->create([
        'space_id' => $space->id,
        'guest_id' => $guest->id,
        'status'   => 'pending',
    ]);

    // host2 intenta confirmar una reserva del espacio de host1
    $this->actingAs($host2)
        ->patchJson("/api/reservations/{$reservation->id}/confirm")
        ->assertStatus(403);
});
