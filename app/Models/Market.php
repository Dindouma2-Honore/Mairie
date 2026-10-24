<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    use Auditable;

    protected $fillable = [
        'code',
        'name',
        'location',
        'manager_name',
        'manager_phone',
        'sectors',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sectors' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function stalls(): HasMany
    {
        return $this->hasMany(MarketStall::class);
    }

    public function totalStalls(): int
    {
        return $this->stalls()->count();
    }

    public function occupiedStallsCount(): int
    {
        return $this->stalls()->where('status', 'occupied')->count();
    }

    public function availableStallsCount(): int
    {
        return $this->stalls()->where('status', 'available')->count();
    }

    public function occupancyRate(): float
    {
        $total = $this->totalStalls();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->occupiedStallsCount() / $total) * 100, 1);
    }
}
