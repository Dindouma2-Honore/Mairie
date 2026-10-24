<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActDelivery extends Model
{
    use Auditable;

    public const TYPES = [
        'extrait' => 'Extrait d’acte de naissance',
        'copie_integrale' => 'Copie intégrale',
    ];

    protected $fillable = ['birth_act_id', 'copy_type', 'requester_name', 'receipt_id', 'delivered_by', 'print_count'];

    public function birthAct(): BelongsTo
    {
        return $this->belongsTo(BirthAct::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }
}
