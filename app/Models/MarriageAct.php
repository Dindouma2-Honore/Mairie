<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarriageAct extends Model
{
    use Auditable;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Brouillon',
        self::STATUS_VALIDATED => 'Validé',
        self::STATUS_CANCELLED => 'Annulé',
    ];

    protected $fillable = [
        'centre_id',
        'registry_year',
        'registry_number',
        'verification_code',
        'celebration_date',
        'celebration_date_words',
        'celebration_place',
        'officer_name',
        'husband_first_names',
        'husband_last_name',
        'husband_birth_date',
        'husband_birth_place',
        'husband_canton',
        'husband_sub_prefecture',
        'husband_father',
        'husband_mother',
        'husband_marital_status',
        'husband_previous_spouse',
        'husband_birth_act_ref',
        'husband_birth_act_date',
        'husband_birth_act_by',
        'husband_represented_by',
        'wife_first_names',
        'wife_last_name',
        'wife_birth_date',
        'wife_birth_place',
        'wife_canton',
        'wife_sub_prefecture',
        'wife_father',
        'wife_mother',
        'wife_marital_status',
        'wife_previous_spouse',
        'wife_birth_act_ref',
        'wife_birth_act_date',
        'wife_birth_act_by',
        'wife_represented_by',
        'husband_witness',
        'husband_witness_residence',
        'wife_witness',
        'wife_witness_residence',
        'dowry_cash',
        'dowry_nature',
        'dowry_payment_dates',
        'has_marriage_contract',
        'contract_date',
        'contract_notary',
        'status',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'celebration_date' => 'date',
            'validated_at' => 'datetime',
            'has_marriage_contract' => 'boolean',
            'dowry_cash' => 'integer',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function registryLabel(): string
    {
        if (! $this->registry_number) {
            return 'En attente';
        }

        return sprintf('%s · N° %04d / %d', $this->centre?->code ?? 'EC', $this->registry_number, $this->registry_year);
    }

    public function coupleLabel(): string
    {
        return trim($this->husband_first_names . ' ' . $this->husband_last_name) . ' & ' . trim($this->wife_first_names . ' ' . $this->wife_last_name);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            self::STATUS_VALIDATED => 'badge-success',
            self::STATUS_CANCELLED => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(CivilCentre::class, 'centre_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CivilRequest::class, 'marriage_act_id');
    }
}
