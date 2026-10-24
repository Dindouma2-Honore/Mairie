<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberingSequence extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'year', 'last_number'];
}
