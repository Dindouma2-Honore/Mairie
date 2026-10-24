<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketStall extends Model
{
    use Auditable;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_AVAILABLE => 'Disponible',
        self::STATUS_OCCUPIED => 'Occupé',
        self::STATUS_SUSPENDED => 'Suspendu',
    ];

    public const TYPES = [
        'etal' => 'Étal',
        'place' => 'Place / Carré',
        'boutique' => 'Boutique',
        'hangar' => 'Hangar',
        'magasin' => 'Magasin de stockage',
    ];

    public const PERIODICITIES = [
        'journalier' => 'Journalier',
        'hebdomadaire' => 'Hebdomadaire',
        'mensuel' => 'Mensuel',
        'annuel' => 'Annuel',
    ];

    protected $fillable = [
        'market_id',
        'code',
        'name',
        'sector',
        'type',
        'dimensions',
        'tariff_id',
        'base_fee',
        'periodicity',
        'status',
        'current_occupant_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_fee' => 'integer',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function currentOccupant(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class, 'current_occupant_id');
    }

    public function occupancies(): HasMany
    {
        return $this->hasMany(MarketOccupancy::class, 'stall_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'market_stall_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isOccupied(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'badge-success',
            self::STATUS_OCCUPIED => 'badge-info',
            default => 'badge-danger',
        };
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function periodicityLabel(): string
    {
        return self::PERIODICITIES[$this->periodicity] ?? ucfirst($this->periodicity);
    }
}
