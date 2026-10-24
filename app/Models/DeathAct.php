<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeathAct extends Model
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

    public const MARITAL_STATUSES = [
        'celibataire' => 'Célibataire',
        'marie' => 'Marié(e)',
        'veuf' => 'Veuf(ve)',
        'divorce' => 'Divorcé(e)',
    ];

    protected $fillable = [
        'centre_id',
        'registry_year',
        'registry_number',
        'verification_code',
        'deceased_first_names',
        'deceased_last_name',
        'sex',
        'death_date',
        'death_date_words',
        'death_time',
        'death_place',
        'deceased_profession',
        'deceased_birth_date',
        'deceased_birth_place',
        'deceased_residence',
        'father_name',
        'mother_name',
        'marital_status',
        'spouse_name',
        'birth_act_ref',
        'declarant_name',
        'declarant_residence',
        'declarant_quality',
        'declaration_date',
        'medical_certificate_ref',
        'burial_place',
        'burial_date',
        'status',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'death_date' => 'date',
            'declaration_date' => 'date',
            'burial_date' => 'date',
            'validated_at' => 'datetime',
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

    public function deceasedLabel(): string
    {
        return trim($this->deceased_first_names . ' ' . $this->deceased_last_name);
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
        return $this->hasMany(CivilRequest::class, 'death_act_id');
    }
}
