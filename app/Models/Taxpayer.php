<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Taxpayer extends Model
{
    use Auditable;

    protected $fillable = [
        'reference', 'type', 'name', 'phone', 'quarter', 'activity',
        'latitude', 'longitude', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(TaxpayerDebt::class);
    }

    public function totalPaid(): int
    {
        return (int) $this->receipts()->where('status', 'paid')->sum('amount');
    }

    public function totalDue(): int
    {
        return (int) $this->debts()->where('status', '!=', TaxpayerDebt::STATUS_CANCELLED)->sum('amount_due');
    }

    public function totalBalance(): int
    {
        return (int) $this->debts()->where('status', '!=', TaxpayerDebt::STATUS_CANCELLED)->sum('balance');
    }
}
