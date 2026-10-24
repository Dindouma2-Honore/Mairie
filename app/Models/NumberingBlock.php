<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberingBlock extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['key', 'year', 'user_id', 'start_number', 'end_number'];
}
