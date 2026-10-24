<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketOccupancy extends Model
{
    use Auditable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'stall_id',
        'taxpayer_id',
        'activity',
        'start_date',
        'end_date',
        'agreed_fee',
        'authorization_ref',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'agreed_fee' => 'integer',
        ];
    }

    public function stall(): BelongsTo
    {
        return $this->belongsTo(MarketStall::class, 'stall_id');
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class, 'taxpayer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
