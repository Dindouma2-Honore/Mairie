<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use Auditable;

    public const TYPES = [
        'conge_annuel' => 'Congé annuel',
        'maladie' => 'Congé maladie',
        'maternite' => 'Congé de maternité',
        'permission' => 'Permission d’absence',
        'autre' => 'Autre',
    ];

    public const STATUSES = [
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Refusé',
    ];

    protected $fillable = [
        'client_uuid', 'agent_id', 'type', 'start_date', 'end_date', 'days', 'reason',
        'status', 'created_by', 'decided_by', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'decided_at' => 'datetime'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
