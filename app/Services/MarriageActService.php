<?php

namespace App\Services;

use App\Models\MarriageAct;
use App\Models\User;
use App\Support\FrenchDate;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MarriageActService
{
    public function __construct(private NumberingService $numbering)
    {
    }

    public static function rules(): array
    {
        return [
            'centre_id' => ['required', 'integer', Rule::exists('civil_centres', 'id')->where('is_active', true)],
            'celebration_date' => ['required', 'date', 'before_or_equal:today'],
            'celebration_place' => ['nullable', 'string', 'max:150'],
            'officer_name' => ['nullable', 'string', 'max:150'],

            // Époux (M.)
            'husband_first_names' => ['required', 'string', 'max:160'],
            'husband_last_name' => ['required', 'string', 'max:120'],
            'husband_birth_date' => ['nullable', 'string', 'max:100'],
            'husband_birth_place' => ['nullable', 'string', 'max:160'],
            'husband_canton' => ['nullable', 'string', 'max:100'],
            'husband_sub_prefecture' => ['nullable', 'string', 'max:100'],
            'husband_father' => ['nullable', 'string', 'max:160'],
            'husband_mother' => ['nullable', 'string', 'max:160'],
            'husband_marital_status' => ['required', Rule::in(['celibataire', 'veuf', 'divorce'])],
            'husband_previous_spouse' => ['nullable', 'string', 'max:160'],
            'husband_birth_act_ref' => ['nullable', 'string', 'max:100'],
            'husband_birth_act_date' => ['nullable', 'string', 'max:60'],
            'husband_birth_act_by' => ['nullable', 'string', 'max:160'],
            'husband_represented_by' => ['nullable', 'string', 'max:160'],

            // Épouse (Mme)
            'wife_first_names' => ['required', 'string', 'max:160'],
            'wife_last_name' => ['required', 'string', 'max:120'],
            'wife_birth_date' => ['nullable', 'string', 'max:100'],
            'wife_birth_place' => ['nullable', 'string', 'max:160'],
            'wife_canton' => ['nullable', 'string', 'max:100'],
            'wife_sub_prefecture' => ['nullable', 'string', 'max:100'],
            'wife_father' => ['nullable', 'string', 'max:160'],
            'wife_mother' => ['nullable', 'string', 'max:160'],
            'wife_marital_status' => ['required', Rule::in(['celibataire', 'veuve', 'divorcee'])],
            'wife_previous_spouse' => ['nullable', 'string', 'max:160'],
            'wife_birth_act_ref' => ['nullable', 'string', 'max:100'],
            'wife_birth_act_date' => ['nullable', 'string', 'max:60'],
            'wife_birth_act_by' => ['nullable', 'string', 'max:160'],
            'wife_represented_by' => ['nullable', 'string', 'max:160'],

            // Témoins
            'husband_witness' => ['nullable', 'string', 'max:160'],
            'husband_witness_residence' => ['nullable', 'string', 'max:160'],
            'wife_witness' => ['nullable', 'string', 'max:160'],
            'wife_witness_residence' => ['nullable', 'string', 'max:160'],

            // Verso : Stipulations particulières
            'dowry_cash' => ['nullable', 'integer', 'min:0'],
            'dowry_nature' => ['nullable', 'string', 'max:255'],
            'dowry_payment_dates' => ['nullable', 'string', 'max:255'],
            'has_marriage_contract' => ['nullable', 'boolean'],
            'contract_date' => ['nullable', 'string', 'max:60'],
            'contract_notary' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function validate(array $payload): array
    {
        $payload['has_marriage_contract'] = ! empty($payload['has_marriage_contract']);

        return Validator::make($payload, self::rules(), [], [
            'centre_id' => 'centre d’état civil',
            'celebration_date' => 'date de célébration',
            'husband_first_names' => 'prénoms de l’époux',
            'husband_last_name' => 'nom de l’époux',
            'wife_first_names' => 'prénoms de l’épouse',
            'wife_last_name' => 'nom de l’épouse',
        ])->validate();
    }

    public function createDraft(array $data, User $user): MarriageAct
    {
        $date = Carbon::parse($data['celebration_date']);
        $dateWords = FrenchDate::format($date);

        return MarriageAct::create($data + [
            'celebration_date_words' => $dateWords,
            'status' => MarriageAct::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
    }

    public function updateDraft(MarriageAct $act, array $data): MarriageAct
    {
        if (! $act->isDraft()) {
            throw new DomainException('Un acte de mariage validé ne peut plus être modifié directement.');
        }

        $date = Carbon::parse($data['celebration_date']);
        $data['celebration_date_words'] = FrenchDate::format($date);

        $act->update($data);

        return $act;
    }

    public function validateAct(MarriageAct $act, User $officer): MarriageAct
    {
        if (! $act->isDraft()) {
            throw new DomainException('Seul un projet / brouillon de mariage peut être validé.');
        }

        if ($act->created_by === $officer->id && ! $officer->hasPermission('civil.birth.self_validate')) {
            throw new DomainException('Séparation des tâches : un autre officier d’état civil doit valider cet acte de mariage.');
        }

        return DB::transaction(function () use ($act, $officer) {
            $year = (int) now()->year;

            $act->update([
                'status' => MarriageAct::STATUS_VALIDATED,
                'registry_year' => $year,
                'registry_number' => $this->numbering->next('marriage:' . $act->centre_id, $year),
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
        } while (strlen($code) < 10 || MarriageAct::where('verification_code', $code)->exists());

        return $code;
    }
}
