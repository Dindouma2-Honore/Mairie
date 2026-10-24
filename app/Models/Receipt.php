<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use Auditable;

    protected $fillable = [
        'number', 'number_year', 'number_seq', 'client_uuid', 'category_id', 'tariff_id',
        'taxpayer_id', 'market_stall_id', 'payer_name', 'description', 'quantity', 'unit_amount', 'amount',
        'payment_method', 'transaction_ref', 'status', 'cash_closing_id', 'collected_by', 'collected_at',
        'created_offline', 'cancelled_by', 'cancelled_at', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_offline' => 'boolean',
            'amount' => 'integer',
            'unit_amount' => 'integer',
        ];
    }

    public static function formatNumber(int $year, int $seq): string
    {
        return sprintf('REC-%d-%06d', $year, $seq);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isClosed(): bool
    {
        return $this->cash_closing_id !== null;
    }

    public function cashClosing(): BelongsTo
    {
        return $this->belongsTo(CashClosing::class, 'cash_closing_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RevenueCategory::class, 'category_id');
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function stall(): BelongsTo
    {
        return $this->belongsTo(MarketStall::class, 'market_stall_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payerLabel(): string
    {
        return $this->taxpayer?->name ?? $this->payer_name ?? 'Non renseigné';
    }
}
