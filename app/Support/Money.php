<?php

namespace App\Support;

class Money
{
    /** Les montants sont stockés en FCFA entiers (pas de décimales). */
    public static function fcfa(int|float|null $amount): string
    {
        return number_format((int) $amount, 0, ',', "\u{202F}") . ' ' . config('mairie.currency', 'FCFA');
    }
}
