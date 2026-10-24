<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tariff extends Model
{
    use Auditable;

    protected $fillable = ['category_id', 'label', 'amount', 'effective_from', 'effective_to', 'is_active'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RevenueCategory::class, 'category_id');
    }

    public function isValidOn(CarbonInterface $date): bool
    {
        return $this->is_active
            && $this->effective_from->startOfDay()->lte($date)
            && (! $this->effective_to || $this->effective_to->endOfDay()->gte($date));
    }
}
