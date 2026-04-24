<?php

use App\Models\Space;
use App\Models\User;

// beforeEach se ejecuta antes de cada test
beforeEach(function () {
    // RefreshDatabase limpia la BD entre tests
    // Es como el rollback de TestContainers pero integrado en Laravel
});

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Tests de seguridad IDOR ──────────────────────────────────

test('guest cannot edit another host space', function () {
    $host1 = User::factory()->host()->create();
    $host2 = User::factory()->host()->create();

    $space = Space::factory()->create(['host_id' => $host1->id]);

    // host2 intenta editar el espacio de host1
    $response = $this->actingAs($host2)
        ->putJson("/api/spaces/{$space->id}", [
            'name' => 'Espacio robado',
        ]);

    // Debe recibir 403 Forbidden — no 200
    $response->assertStatus(403);

    // Verificamos que el nombre no cambió en la BD
    expect($space->fresh()->name)->not->toBe('Espacio robado');
});

test('guest cannot delete another host space', function () {
    $host1 = User::factory()->host()->create();
    $host2 = User::factory()->host()->create();

    $space = Space::factory()->create(['host_id' => $host1->id]);

    $this->actingAs($host2)
        ->deleteJson("/api/spaces/{$space->id}")
        ->assertStatus(403);

    // El espacio sigue activo
    expect($space->fresh()->is_active)->toBeTrue();
});

// ── Tests de mass assignment ──────────────────────────────────

test('cannot change host_id via mass assignment', function () {
    $host1 = User::factory()->host()->create();
    $host2 = User::factory()->host()->create();

    // host1 crea un espacio
    $response = $this->actingAs($host1)
        ->postJson('/api/spaces', [
            'name'            => 'Mi Casa',
            'city'            => 'Antigua',
            'price_per_night' => 100,
            'max_guests'      => 4,
            'bedrooms'        => 2,
            'bathrooms'       => 1,
            'host_id'         => $host2->id, // intento de mass assignment
        ]);

    $response->assertStatus(201);

    // El host_id debe ser el de host1, no el de host2
    $space = Space::find($response->json('data.id'));
    expect($space->host_id)->toBe($host1->id);
});

// ── Tests de validación ──────────────────────────────────────

test('cannot create space with negative price', function () {
    $host = User::factory()->host()->create();

    $this->actingAs($host)
        ->postJson('/api/spaces', [
            'name'            => 'Casa Test',
            'city'            => 'Guatemala',
            'price_per_night' => -50, // precio negativo
            'max_guests'      => 2,
            'bedrooms'        => 1,
            'bathrooms'       => 1,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['price_per_night']);
});

test('guest user cannot create space', function () {
    $guest = User::factory()->create(['role' => 'guest']);

    $this->actingAs($guest)
        ->postJson('/api/spaces', [
            'name'            => 'Casa Test',
            'city'            => 'Guatemala',
            'price_per_night' => 100,
            'max_guests'      => 2,
            'bedrooms'        => 1,
            'bathrooms'       => 1,
        ])
        ->assertStatus(403);
});

test('unauthenticated user cannot create space', function () {
    $this->postJson('/api/spaces', [
        'name'            => 'Casa Test',
        'city'            => 'Guatemala',
        'price_per_night' => 100,
        'max_guests'      => 2,
        'bedrooms'        => 1,
        'bathrooms'       => 1,
    ])
        ->assertStatus(401);
});

// ── Tests de lógica de negocio ───────────────────────────────

test('cannot delete space with active reservations', function () {
    $host = User::factory()->host()->create();
    $guest = User::factory()->create();
    $space = Space::factory()->create(['host_id' => $host->id]);

    // Creamos una reserva activa
    $space->reservations()->create([
        'guest_id'        => $guest->id,
        'check_in'        => now()->addDays(5)->format('Y-m-d'),
        'check_out'       => now()->addDays(8)->format('Y-m-d'),
        'guests_count'    => 2,
        'price_per_night' => $space->price_per_night,
        'total_price'     => $space->price_per_night * 3,
        'nights'          => 3,
        'status'          => 'confirmed',
    ]);

    $this->actingAs($host)
        ->deleteJson("/api/spaces/{$space->id}")
        ->assertStatus(422);

    // El espacio sigue activo
    expect($space->fresh()->is_active)->toBeTrue();
});

test('host can delete space without active reservations', function () {
    $host = User::factory()->host()->create();
    $space = Space::factory()->create(['host_id' => $host->id]);

    $this->actingAs($host)
        ->deleteJson("/api/spaces/{$space->id}")
        ->assertStatus(200);

    // Soft delete — is_active = false
    expect($space->fresh()->is_active)->toBeFalse();
});

// ── Tests de sanitización XSS ────────────────────────────────

test('xss is sanitized in space name', function () {
    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)
        ->postJson('/api/spaces', [
            'name'            => '<script>alert("xss")</script>Casa Test',
            'city'            => 'Guatemala',
            'price_per_night' => 100,
            'max_guests'      => 2,
            'bedrooms'        => 1,
            'bathrooms'       => 1,
        ]);

    $response->assertStatus(201);

    $space = Space::find($response->json('data.id'));

    // El script debe haber sido eliminado por strip_tags
    expect($space->name)->not->toContain('<script>');
    expect($space->name)->not->toContain('alert');
});
