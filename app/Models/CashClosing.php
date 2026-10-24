<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashClosing extends Model
{
    use Auditable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente de contrôle',
        self::STATUS_VALIDATED => 'Validée',
        self::STATUS_REJECTED => 'Rejetée',
    ];

    protected $fillable = [
        'reference',
        'year',
        'sequence',
        'collector_id',
        'closing_date',
        'receipts_count',
        'total_amount',
        'cash_expected',
        'cash_counted',
        'difference',
        'breakdown',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'reviewed_at' => 'datetime',
            'breakdown' => 'array',
            'total_amount' => 'integer',
            'cash_expected' => 'integer',
            'cash_counted' => 'integer',
            'difference' => 'integer',
            'receipts_count' => 'integer',
        ];
    }

    public static function formatReference(int $year, int $sequence): string
    {
        return sprintf('CAI-%d-%05d', $year, $sequence);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            self::STATUS_VALIDATED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'cash_closing_id');
    }
}
