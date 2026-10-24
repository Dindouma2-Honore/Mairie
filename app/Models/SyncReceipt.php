<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncReceipt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['client_uuid', 'user_id', 'type', 'result'];

    protected function casts(): array
    {
        return ['result' => 'array'];
    }
}
