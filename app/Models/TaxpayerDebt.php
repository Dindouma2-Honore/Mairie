<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxpayerDebt extends Model
{
    use Auditable;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_UNPAID => 'Impayé',
        self::STATUS_PARTIAL => 'Partiel',
        self::STATUS_PAID => 'Soldé',
        self::STATUS_CANCELLED => 'Annulé',
    ];

    protected $fillable = [
        'reference',
        'taxpayer_id',
        'category_id',
        'title',
        'period',
        'amount_due',
        'amount_paid',
        'balance',
        'due_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_due' => 'integer',
            'amount_paid' => 'integer',
            'balance' => 'integer',
        ];
    }

    public static function formatReference(int $year, int $seq): string
    {
        return sprintf('AVIS-%d-%05d', $year, $seq);
    }

    public function isOverdue(): bool
    {
        return $this->balance > 0 && $this->due_date < today() && $this->status !== self::STATUS_CANCELLED;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        if ($this->isOverdue()) {
            return 'badge-danger';
        }

        return match ($this->status) {
            self::STATUS_PAID => 'badge-success',
            self::STATUS_PARTIAL => 'badge-warning',
            self::STATUS_CANCELLED => 'badge-muted',
            default => 'badge-warning',
        };
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RevenueCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
