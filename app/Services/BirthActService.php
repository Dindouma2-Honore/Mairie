<?php

namespace App\Services;

use App\Models\BirthAct;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BirthActService
{
    public function __construct(private NumberingService $numbering)
    {
    }

    public static function rules(): array
    {
        return [
            'centre_id' => ['required', 'integer', Rule::exists('civil_centres', 'id')->where('is_active', true)],
            'child_last_name' => ['required', 'string', 'max:120'],
            'child_first_names' => ['required', 'string', 'max:160'],
            'sex' => ['required', Rule::in(['M', 'F'])],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'birth_date_precision' => ['required', Rule::in(array_keys(BirthAct::PRECISIONS))],
            'birth_time' => ['nullable', 'date_format:H:i'],
            'birth_place' => ['required', 'string', 'max:160'],
            'birth_place_type' => ['nullable', Rule::in(['hopital', 'domicile', 'autre'])],
            'birth_street' => ['nullable', 'string', 'max:160'],

            'father_name' => ['nullable', 'string', 'max:160'],
            'father_birth_info' => ['nullable', 'string', 'max:160'],
            'father_birth_place' => ['nullable', 'string', 'max:160'],
            'father_birth_date' => ['nullable', 'string', 'max:60'],
            'father_profession' => ['nullable', 'string', 'max:120'],
            'father_residence' => ['nullable', 'string', 'max:160'],
            'father_nationality' => ['nullable', 'string', 'max:80'],

            'mother_name' => ['required', 'string', 'max:160'],
            'mother_birth_info' => ['nullable', 'string', 'max:160'],
            'mother_birth_place' => ['nullable', 'string', 'max:160'],
            'mother_birth_date' => ['nullable', 'string', 'max:60'],
            'mother_profession' => ['nullable', 'string', 'max:120'],
            'mother_residence' => ['nullable', 'string', 'max:160'],
            'mother_nationality' => ['nullable', 'string', 'max:80'],

            'declarant_name' => ['nullable', 'string', 'max:160'],
            'declarant_quality' => ['nullable', 'string', 'max:120'],
            'declaration_date' => ['required', 'date', 'before_or_equal:today'],
            'is_late_declaration' => ['nullable', 'boolean'],
            'judgment_ref' => ['nullable', 'string', 'max:160'],
            'marginal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function validate(array $payload): array
    {
        $payload['is_late_declaration'] = ! empty($payload['is_late_declaration']);

        return Validator::make($payload, self::rules(), [], [
            'child_last_name' => 'nom de l’enfant',
            'child_first_names' => 'prénoms de l’enfant',
            'sex' => 'sexe',
            'birth_date' => 'date de naissance',
            'birth_place' => 'lieu de naissance',
            'birth_street' => 'rue de naissance',
            'mother_name' => 'nom de la mère',
            'declaration_date' => 'date de déclaration',
            'centre_id' => 'centre d’état civil',
        ])->validate();
    }

    /** Crée un brouillon (utilisable hors ligne : aucun numéro de registre n'est attribué ici). */
    public function createDraft(array $data, User $user, ?string $clientUuid = null, bool $offline = false): BirthAct
    {
        return BirthAct::create($data + [
            'status' => 'draft',
            'created_by' => $user->id,
            'client_uuid' => $clientUuid,
            'created_offline' => $offline,
        ]);
    }

    public function updateDraft(BirthAct $act, array $data): BirthAct
    {
        if (! $act->isDraft()) {
            throw new DomainException('Un acte validé ne peut plus être modifié. Une correction passe par une procédure dédiée.');
        }
        $act->update($data);

        return $act;
    }

    /**
     * Validation par l'officier d'état civil : attribue le numéro de registre
     * et le code de vérification. Séparation des tâches : le créateur ne valide pas son propre acte.
     */
    public function validateAct(BirthAct $act, User $officer): BirthAct
    {
        if (! $act->isDraft()) {
            throw new DomainException('Seul un brouillon peut être validé.');
        }
        if ($act->created_by === $officer->id && ! $officer->hasPermission('civil.birth.self_validate')) {
            throw new DomainException('Séparation des tâches : un autre officier doit valider cet acte.');
        }

        return DB::transaction(function () use ($act, $officer) {
            $year = (int) now()->year;

            $act->update([
                'status' => 'validated',
                'registry_year' => $year,
                'registry_number' => $this->numbering->next('birth:' . $act->centre_id, $year),
                'verification_code' => $this->uniqueCode(),
                'validated_by' => $officer->id,
                'validated_at' => now(),
            ]);

            return $act;
        });
    }

    private function uniqueCode(): string
    {
        do {
            // Alphabet sans caractères ambigus (0/O, 1/I) pour une saisie fiable.
            $code = collect(str_split(Str::upper(Str::random(40))))
                ->filter(fn ($c) => str_contains('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', $c))
                ->take(10)->implode('');
        } while (strlen($code) < 10 || BirthAct::where('verification_code', $code)->exists());

        return $code;
    }
}
