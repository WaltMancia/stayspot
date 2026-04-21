<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'guest_id',
        'check_in',
        'check_out',
        'guests_count',
        'price_per_night',
        'total_price',
        'nights',
        'status',
        'stripe_payment_id',
        'stripe_payment_status',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'check_in'        => 'date',  // Carbon instance automáticamente
            'check_out'       => 'date',
            'price_per_night' => 'decimal:2',
            'total_price'     => 'decimal:2',
        ];
    }

    // ── Relaciones ───────────────────────────────────────────

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    // El huésped puede dejar reseña si la estadía terminó
    public function canBeReviewed(): bool
    {
        return $this->status === 'completed' && !$this->review;
    }
}
