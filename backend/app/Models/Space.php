<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'host_id',
        'name',
        'description',
        'city',
        'address',
        'country',
        'price_per_night',
        'max_guests',
        'bedrooms',
        'bathrooms',
        'amenities',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            // 'array' → convierte el JSON de amenidades a array PHP automáticamente
            // Al guardar: array → JSON string en BD
            // Al leer: JSON string → array PHP
            'amenities'       => 'array',
            'is_active'       => 'boolean',
            'price_per_night' => 'decimal:2',
            'latitude'        => 'decimal:8',
            'longitude'       => 'decimal:8',
        ];
    }

    // ── Relaciones ───────────────────────────────────────────

    // BelongsTo → muchos espacios pertenecen a un host
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // ── Scopes — queries reutilizables ───────────────────────
    // Un scope es un método que añade condiciones a una query
    // Se usa así: Space::active()->inCity('Antigua')->get()
    // Equivale a los métodos de Specification en Java

    // scopeActive → Space::active()->get()
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function scopePriceBetween(
        Builder $query,
        ?float $min,
        ?float $max
    ): Builder {
        if ($min !== null) {
            $query->where('price_per_night', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price_per_night', '<=', $max);
        }
        return $query;
    }

    // ── Accessors — atributos calculados ─────────────────────
    // Se acceden como propiedades: $space->average_rating
    // Equivale a los @property de Python o getters de Java

    // Calcula el rating promedio
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    // ── Métodos de dominio ───────────────────────────────────

    public function isAvailableFor(string $checkIn, string $checkOut): bool
    {
        // Verifica si hay reservas que se solapan con las fechas pedidas
        // La lógica de solapamiento: una reserva se solapa si
        // su check_in < checkOut pedido AND su check_out > checkIn pedido
        return !$this->reservations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->exists();
    }

    public function belongsToHost(int $userId): bool
    {
        return $this->host_id === $userId;
    }
}
