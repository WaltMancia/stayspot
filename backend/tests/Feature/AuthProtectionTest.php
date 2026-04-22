<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthProtectionTest extends TestCase
{
    public function test_me_returns_401_without_token(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'No autenticado. Por favor inicia sesión.',
            ]);
    }

    public function test_logout_returns_401_without_token(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'No autenticado. Por favor inicia sesión.',
            ]);
    }

    public function test_refresh_returns_401_without_token(): void
    {
        $this->postJson('/api/auth/refresh')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'No autenticado. Por favor inicia sesión.',
            ]);
    }
}
