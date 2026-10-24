<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Dates « en toutes lettres » pour les actes d'état civil
 * (ex. 22/10/2026 → « vingt-deux octobre deux mille vingt-six »).
 */
class FrenchDate
{
    private const UNITS = [
        'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix',
        'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf',
    ];

    private const TENS = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

    private const MONTHS = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin',
        7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    /** Nombre entier de 0 à 9999 en lettres (orthographe française rectifiée non utilisée : traits d'union classiques). */
    public static function words(int $n): string
    {
        if ($n < 0 || $n > 9999) {
            return (string) $n;
        }
        if ($n === 0) {
            return 'zéro';
        }

        $out = [];
        $thousands = intdiv($n, 1000);
        $rest = $n % 1000;

        if ($thousands) {
            $out[] = $thousands === 1 ? 'mille' : self::below1000($thousands, false) . ' mille';
        }
        if ($rest) {
            $out[] = self::below1000($rest, true);
        }

        return implode(' ', $out);
    }

    /** @param bool $final vrai si rien ne suit : « quatre-vingts » et « deux cents » prennent alors leur s */
    private static function below1000(int $n, bool $final): string
    {
        $hundreds = intdiv($n, 100);
        $rest = $n % 100;
        $parts = [];

        if ($hundreds) {
            $parts[] = $hundreds === 1 ? 'cent' : self::UNITS[$hundreds] . ' cent' . (($rest === 0 && $final) ? 's' : '');
        }
        if ($rest) {
            $parts[] = self::below100($rest, $final);
        }

        return implode(' ', $parts);
    }

    private static function below100(int $n, bool $final): string
    {
        if ($n < 20) {
            return self::UNITS[$n];
        }

        $t = intdiv($n, 10);
        $u = $n % 10;

        if ($t === 7 || $t === 9) {                       // soixante-dix…, quatre-vingt-dix…
            $base = $t === 7 ? 'soixante' : 'quatre-vingt';
            $rest = 10 + $u;

            return ($t === 7 && $u === 1) ? 'soixante et onze' : $base . '-' . self::UNITS[$rest];
        }
        if ($t === 8) {
            return $u === 0 ? 'quatre-vingt' . ($final ? 's' : '') : 'quatre-vingt-' . self::UNITS[$u];
        }

        $base = self::TENS[$t];
        if ($u === 0) {
            return $base;
        }

        return $base . ($u === 1 ? ' et un' : '-' . self::UNITS[$u]);
    }

    public static function year(int $year): string
    {
        return self::words($year);
    }

    /** Date complète : « vingt-deux octobre deux mille vingt-six » (le 1er du mois : « premier »). */
    public static function long(CarbonInterface $date): string
    {
        $day = $date->day === 1 ? 'premier' : self::words($date->day);

        return $day . ' ' . self::MONTHS[$date->month] . ' ' . self::year($date->year);
    }

    /** Tient compte de la précision connue : exact | month | year | approx. */
    public static function withPrecision(CarbonInterface $date, string $precision): string
    {
        return match ($precision) {
            'year' => self::year($date->year),
            'month' => self::MONTHS[$date->month] . ' ' . self::year($date->year),
            'approx' => 'vers ' . self::year($date->year),
            default => self::long($date),
        };
    }
}
