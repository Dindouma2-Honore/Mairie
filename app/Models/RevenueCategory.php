<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevenueCategory extends Model
{
    use Auditable;

    protected $fillable = ['code', 'name', 'description', 'allow_custom_amount', 'is_active'];

    protected function casts(): array
    {
        return ['allow_custom_amount' => 'boolean', 'is_active' => 'boolean'];
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class, 'category_id');
    }

    /** Tarifs en vigueur à une date donnée. */
    public function tariffsOn(CarbonInterface $date)
    {
        return $this->tariffs()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->orderBy('label')
            ->get();
    }
}
