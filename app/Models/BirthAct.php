<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BirthAct extends Model
{
    use Auditable;

    public const PRECISIONS = [
        'exact' => 'Date exacte',
        'month' => 'Mois connu seulement',
        'year' => 'Année connue seulement',
        'approx' => 'Date approximative',
    ];

    protected $fillable = [
        'centre_id', 'registry_year', 'registry_number', 'client_uuid', 'verification_code',
        'child_last_name', 'child_first_names', 'sex', 'birth_date', 'birth_date_precision',
        'birth_time', 'birth_place', 'birth_place_type', 'birth_street',
        'father_name', 'father_birth_info', 'father_birth_place', 'father_birth_date', 'father_profession', 'father_residence', 'father_nationality',
        'mother_name', 'mother_birth_info', 'mother_birth_place', 'mother_birth_date', 'mother_profession', 'mother_residence', 'mother_nationality',
        'declarant_name', 'declarant_quality', 'declaration_date', 'is_late_declaration',
        'judgment_ref', 'marginal_notes',
        'status', 'created_by', 'validated_by', 'validated_at', 'created_offline',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'declaration_date' => 'date',
            'validated_at' => 'datetime',
            'is_late_declaration' => 'boolean',
            'created_offline' => 'boolean',
        ];
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

    public function deliveries(): HasMany
    {
        return $this->hasMany(ActDelivery::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    public function fullName(): string
    {
        return trim(mb_strtoupper($this->child_last_name) . ' ' . $this->child_first_names);
    }

    /** « N° 123/2026 », ou null tant que l'acte n'est pas validé. */
    public function registryLabel(): ?string
    {
        return $this->registry_number
            ? sprintf('%d/%d', $this->registry_number, $this->registry_year)
            : null;
    }

    /** La date de naissance respecte la précision connue (beaucoup de dates sont partielles). */
    public function birthDateLabel(): string
    {
        $d = $this->birth_date;

        return match ($this->birth_date_precision) {
            'year' => (string) $d->year,
            'month' => ucfirst($d->locale('fr')->translatedFormat('F Y')),
            'approx' => 'vers ' . $d->year,
            default => $d->format('d/m/Y'),
        };
    }
}
