<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'host_id', 'name', 'description', 'city', 'address',
        'country', 'price_per_night', 'max_guests', 'bedrooms',
        'bathrooms', 'amenities', 'latitude', 'longitude', 'is_active',
    ];

    // NUNCA en $fillable: host_id si viene del cliente, is_active
    // El host_id siempre lo asigna el servidor
    // Estos campos se asignan explícitamente en el service

    protected function casts(): array
    {
        return [
            'amenities'       => 'array',
            'is_active'       => 'boolean',
            'price_per_night' => 'decimal:2',
            'latitude'        => 'decimal:8',
            'longitude'       => 'decimal:8',
        ];
    }

    // ── Accessors y Mutators (PHP 8 / Laravel 10+ style) ────

    // Mutator: normaliza el nombre al guardarlo
    // trim elimina espacios, ucwords capitaliza cada palabra
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => $value,
            set: fn(string $value) => ucwords(strtolower(trim($value)))
        );
    }

    // Mutator: normaliza la ciudad
    protected function city(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => $value,
            set: fn(string $value) => ucwords(strtolower(trim($value)))
        );
    }

    // Accessor: precio formateado para mostrar en UI
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => '$' . number_format($this->price_per_night, 2)
        );
    }

    // ── Relaciones ───────────────────────────────────────────

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

    // ── Scopes ───────────────────────────────────────────────

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

    // ── Accessors calculados ─────────────────────────────────

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->avg('rating');
        return $avg ? round((float) $avg, 1) : null;
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    // ── Métodos de dominio ───────────────────────────────────

    public function isAvailableFor(
        string $checkIn,
        string $checkOut
    ): bool {
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