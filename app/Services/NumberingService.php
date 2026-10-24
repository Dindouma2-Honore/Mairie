<?php

namespace App\Services;

use App\Models\NumberingBlock;
use App\Models\NumberingSequence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Numérotation sans doublon, même avec plusieurs utilisateurs en parallèle.
 * Les verrous de ligne (SELECT ... FOR UPDATE) sont fournis par MySQL/InnoDB.
 */
class NumberingService
{
    /** Prochain numéro de la séquence (ex. reçus 2026). */
    public function next(string $key, int $year = 0): int
    {
        return DB::transaction(function () use ($key, $year) {
            $seq = $this->lockedSequence($key, $year);
            $seq->last_number++;
            $seq->save();

            return $seq->last_number;
        });
    }

    /**
     * Réserve un lot de numéros pour un collecteur (usage hors ligne).
     * Les numéros non utilisés restent identifiables : jamais réattribués.
     */
    public function allocateBlock(string $key, int $year, User $user, int $size): NumberingBlock
    {
        return DB::transaction(function () use ($key, $year, $user, $size) {
            $seq = $this->lockedSequence($key, $year);
            $start = $seq->last_number + 1;
            $seq->last_number += $size;
            $seq->save();

            return NumberingBlock::create([
                'key' => $key,
                'year' => $year,
                'user_id' => $user->id,
                'start_number' => $start,
                'end_number' => $seq->last_number,
            ]);
        });
    }

    public function userOwnsNumber(string $key, int $year, int $userId, int $number): bool
    {
        return NumberingBlock::where(compact('key', 'year'))
            ->where('user_id', $userId)
            ->where('start_number', '<=', $number)
            ->where('end_number', '>=', $number)
            ->exists();
    }

    private function lockedSequence(string $key, int $year): NumberingSequence
    {
        DB::table('numbering_sequences')->insertOrIgnore(['key' => $key, 'year' => $year, 'last_number' => 0]);

        return NumberingSequence::where('key', $key)->where('year', $year)->lockForUpdate()->firstOrFail();
    }
}
