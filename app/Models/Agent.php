<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use Auditable;

    public const STATUSES = [
        'fonctionnaire' => 'Fonctionnaire',
        'contractuel' => 'Contractuel',
        'journalier' => 'Journalier',
        'stagiaire' => 'Stagiaire',
    ];

    protected $fillable = [
        'matricule', 'last_name', 'first_names', 'sex', 'birth_date', 'birth_place', 'phone',
        'email', 'address', 'service_id', 'user_id', 'position', 'grade', 'status',
        'hire_date', 'end_date', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function fullName(): string
    {
        return trim(mb_strtoupper($this->last_name) . ' ' . $this->first_names);
    }
}
