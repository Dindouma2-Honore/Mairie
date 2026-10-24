<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CivilRequest extends Model
{
    use Auditable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_PROCESSING => 'En cours',
        self::STATUS_READY => 'Prêt à retirer',
        self::STATUS_DELIVERED => 'Remis au demandeur',
        self::STATUS_REJECTED => 'Rejeté',
    ];

    public const ACT_TYPES = [
        'birth' => 'Acte de naissance',
        'marriage' => 'Acte de mariage',
        'death' => 'Acte de décès',
    ];

    public const DOC_TYPES = [
        'extrait' => 'Extrait d’acte',
        'copie_integrale' => 'Copie intégrale',
    ];

    protected $fillable = [
        'reference',
        'act_type',
        'document_type',
        'copies_count',
        'birth_act_id',
        'marriage_act_id',
        'death_act_id',
        'external_act_ref',
        'requester_name',
        'requester_phone',
        'requester_id_number',
        'person_concerned',
        'amount',
        'receipt_id',
        'status',
        'request_date',
        'ready_at',
        'delivered_at',
        'processed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'ready_at' => 'datetime',
            'delivered_at' => 'datetime',
            'amount' => 'integer',
            'copies_count' => 'integer',
        ];
    }

    public static function formatReference(int $year, int $seq): string
    {
        return sprintf('ACT-%d-%04d', $year, $seq);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            self::STATUS_READY => 'badge-info',
            self::STATUS_DELIVERED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_PROCESSING => 'badge-warning',
            default => 'badge',
        };
    }

    public function actTypeLabel(): string
    {
        return self::ACT_TYPES[$this->act_type] ?? $this->act_type;
    }

    public function docTypeLabel(): string
    {
        return self::DOC_TYPES[$this->document_type] ?? $this->document_type;
    }

    public function birthAct(): BelongsTo
    {
        return $this->belongsTo(BirthAct::class);
    }

    public function marriageAct(): BelongsTo
    {
        return $this->belongsTo(MarriageAct::class);
    }

    public function deathAct(): BelongsTo
    {
        return $this->belongsTo(DeathAct::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
