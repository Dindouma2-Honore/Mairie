<?php

namespace App\Services;

use App\Models\DeathAct;
use App\Models\User;
use App\Support\FrenchDate;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeathActService
{
    public function __construct(private NumberingService $numbering)
    {
    }

    public static function rules(): array
    {
        return [
            'centre_id' => ['required', 'integer', Rule::exists('civil_centres', 'id')->where('is_active', true)],
            'deceased_first_names' => ['required', 'string', 'max:160'],
            'deceased_last_name' => ['required', 'string', 'max:120'],
            'sex' => ['required', Rule::in(['M', 'F'])],
            'death_date' => ['required', 'date', 'before_or_equal:today'],
            'death_time' => ['nullable', 'date_format:H:i'],
            'death_place' => ['required', 'string', 'max:160'],
            'deceased_profession' => ['nullable', 'string', 'max:120'],
            'deceased_birth_date' => ['nullable', 'string', 'max:100'],
            'deceased_birth_place' => ['nullable', 'string', 'max:160'],
            'deceased_residence' => ['nullable', 'string', 'max:160'],
            'father_name' => ['nullable', 'string', 'max:160'],
            'mother_name' => ['nullable', 'string', 'max:160'],
            'marital_status' => ['required', Rule::in(['celibataire', 'marie', 'veuf', 'divorce'])],
            'spouse_name' => ['nullable', 'string', 'max:160'],
            'birth_act_ref' => ['nullable', 'string', 'max:120'],
            'declarant_name' => ['required', 'string', 'max:160'],
            'declarant_residence' => ['nullable', 'string', 'max:160'],
            'declarant_quality' => ['nullable', 'string', 'max:120'],
            'declaration_date' => ['required', 'date', 'before_or_equal:today'],
            'medical_certificate_ref' => ['nullable', 'string', 'max:120'],
            'burial_place' => ['nullable', 'string', 'max:160'],
            'burial_date' => ['nullable', 'date'],
        ];
    }

    public function validate(array $payload): array
    {
        return Validator::make($payload, self::rules(), [], [
            'centre_id' => 'centre d’état civil',
            'deceased_first_names' => 'prénoms du défunt',
            'deceased_last_name' => 'nom du défunt',
            'death_date' => 'date du décès',
            'death_place' => 'lieu du décès',
            'declarant_name' => 'nom du déclarant',
            'declaration_date' => 'date de déclaration',
        ])->validate();
    }

    public function createDraft(array $data, User $user): DeathAct
    {
        $date = Carbon::parse($data['death_date']);
        $dateWords = FrenchDate::format($date);

        return DeathAct::create($data + [
            'death_date_words' => $dateWords,
            'status' => DeathAct::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
    }

    public function updateDraft(DeathAct $act, array $data): DeathAct
    {
        if (! $act->isDraft()) {
            throw new DomainException('Un acte de décès validé ne peut plus être modifié directement.');
        }

        $date = Carbon::parse($data['death_date']);
        $data['death_date_words'] = FrenchDate::format($date);

        $act->update($data);

        return $act;
    }

    public function validateAct(DeathAct $act, User $officer): DeathAct
    {
        if (! $act->isDraft()) {
            throw new DomainException('Seul un brouillon peut être validé.');
        }

        if ($act->created_by === $officer->id && ! $officer->hasPermission('civil.birth.self_validate')) {
            throw new DomainException('Séparation des tâches : un autre officier d’état civil doit valider cet acte.');
        }

        return DB::transaction(function () use ($act, $officer) {
            $year = (int) now()->year;

            $act->update([
                'status' => DeathAct::STATUS_VALIDATED,
                'registry_year' => $year,
                'registry_number' => $this->numbering->next('death:' . $act->centre_id, $year),
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
            $code = collect(str_split(Str::upper(Str::random(40))))
                ->filter(fn ($c) => str_contains('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', $c))
                ->take(10)->implode('');
        } while (strlen($code) < 10 || DeathAct::where('verification_code', $code)->exists());

        return $code;
    }
}
