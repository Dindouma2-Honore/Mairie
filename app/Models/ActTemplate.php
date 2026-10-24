<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class ActTemplate extends Model
{
    use Auditable;

    protected $fillable = ['type', 'name', 'html', 'is_active', 'updated_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function activeFor(string $type): ?self
    {
        return static::where('type', $type)->where('is_active', true)->latest('id')->first();
    }
}
