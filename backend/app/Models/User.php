<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // HasApiTokens → añade soporte para tokens de Sanctum
    // HasFactory   → permite usar UserFactory::new()->create()
    // Notifiable   → permite enviar notificaciones al usuario
    use HasApiTokens, HasFactory, Notifiable;

    // $fillable define qué campos se pueden asignar masivamente
    // SEGURIDAD: protege contra mass assignment attacks
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
    ];

    // $hidden define qué campos NUNCA se incluyen en JSON
    // El password y remember_token NUNCA deben salir en la API
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // $casts convierte automáticamente los tipos al leer/escribir
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // 'hashed' hashea automáticamente el password al asignarlo
            // User::create(['password' => 'plain']) → guarda el hash
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ── Relaciones Eloquent ──────────────────────────────────

    // Un usuario puede tener muchos espacios (si es host)
    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class, 'host_id');
    }

    // Un usuario puede tener muchas reservas (como huésped)
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'guest_id');
    }

    // ── Helpers / Accessors ──────────────────────────────────

    // isHost() es más legible que $user->role === 'host'
    public function isHost(): bool
    {
        return $this->role === 'host';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    // Un host tiene muchas reseñas A TRAVÉS de sus espacios
    // hasManyThrough(ModelFinal, ModelIntermedio, FK_en_intermedio, FK_en_final)
    public function receivedReviews(): HasManyThrough
    {
        return $this->hasManyThrough(
            Review::class,   // modelo final
            Space::class,    // modelo intermedio
            'host_id',       // FK en spaces que apunta a users
            'space_id',      // FK en reviews que apunta a spaces
        );
    }
}
