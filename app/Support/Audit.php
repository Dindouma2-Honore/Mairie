<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Journal d'audit en ajout seul : qui a fait quoi, quand, sur quel objet.
 */
class Audit
{
    public static function log(string $event, ?Model $subject = null, ?array $old = null, ?array $new = null): void
    {
        try {
            $request = app()->bound('request') ? request() : null;

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => $event,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'old_values' => $old ?: null,
                'new_values' => $new ?: null,
                'ip' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            // L'audit ne doit jamais casser l'opération métier, mais l'échec doit rester visible.
            report($e);
        }
    }
}
