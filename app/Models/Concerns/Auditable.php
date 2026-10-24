<?php

namespace App\Models\Concerns;

use App\Support\Audit;

/**
 * Journalise automatiquement création, modification et suppression d'un modèle.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            Audit::log(self::auditEvent($model, 'created'), $model, null, $model->auditValues($model->getAttributes()));
        });

        static::updated(function ($model) {
            $changes = $model->auditValues($model->getChanges());
            if (! $changes) {
                return;
            }
            $old = array_intersect_key($model->getOriginal(), $changes);
            Audit::log(self::auditEvent($model, 'updated'), $model, $old, $changes);
        });

        static::deleted(function ($model) {
            Audit::log(self::auditEvent($model, 'deleted'), $model, $model->auditValues($model->getOriginal()));
        });
    }

    protected static function auditEvent($model, string $action): string
    {
        return strtolower(class_basename($model)) . '.' . $action;
    }

    /** Retire les champs techniques ou secrets avant journalisation. */
    protected function auditValues(array $values): array
    {
        $skip = array_merge(['updated_at', 'created_at', 'remember_token', 'password'], $this->getHidden());

        return array_diff_key($values, array_flip($skip));
    }
}
