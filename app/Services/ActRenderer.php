<?php

namespace App\Services;

use App\Models\ActDelivery;
use App\Models\ActTemplate;
use App\Models\BirthAct;
use App\Support\FrenchDate;

/**
 * Génère le HTML imprimable d'un acte.
 *
 * - Sans gabarit actif en base : gabarit Blade provisoire (resources/views/civil/templates/birth.blade.php).
 * - Avec un gabarit actif : le HTML fourni par la mairie, où les {{jetons}} sont remplacés
 *   par des valeurs échappées (aucun code exécutable dans un gabarit).
 */
class ActRenderer
{
    public const TOKENS = [
        'commune' => 'Nom de la mairie',
        'centre' => 'Centre d’état civil',
        'numero_acte' => 'Numéro d’acte (ex. 123/2026)',
        'annee_registre' => 'Année du registre',
        'nom_enfant' => 'Nom de l’enfant',
        'prenoms_enfant' => 'Prénoms de l’enfant',
        'sexe' => 'Sexe',
        'date_naissance' => 'Date de naissance (selon précision)',
        'heure_naissance' => 'Heure de naissance',
        'lieu_naissance' => 'Lieu de naissance',
        'rue_naissance' => 'Rue ou quartier de naissance',
        'date_naissance_lettres' => 'Date de naissance en toutes lettres (selon précision)',
        'pere_nom' => 'Nom du père',
        'pere_naissance' => 'Naissance du père (ancien champ libre)',
        'pere_lieu_naissance' => 'Lieu de naissance du père',
        'pere_date_naissance' => 'Date de naissance du père',
        'pere_profession' => 'Profession du père',
        'pere_residence' => 'Résidence du père',
        'mere_nom' => 'Nom de la mère',
        'mere_naissance' => 'Naissance de la mère (ancien champ libre)',
        'mere_lieu_naissance' => 'Lieu de naissance de la mère',
        'mere_date_naissance' => 'Date de naissance de la mère',
        'mere_profession' => 'Profession de la mère',
        'mere_residence' => 'Résidence de la mère',
        'declarant' => 'Déclarant (nom et qualité)',
        'declarant_nom' => 'Nom du déclarant',
        'declarant_qualite' => 'Qualité du déclarant',
        'date_declaration' => 'Date de déclaration',
        'date_declaration_lettres' => 'Date de déclaration en toutes lettres',
        'jugement' => 'Référence du jugement supplétif',
        'mentions_marginales' => 'Mentions marginales',
        'officier' => 'Officier d’état civil',
        'date_edition' => 'Date d’édition',
        'type_copie' => 'Type de copie',
        'numero_copie' => 'Numéro de la copie délivrée',
        'code_verification' => 'Code de vérification',
        'url_verification' => 'Adresse de vérification',
    ];

    public function data(BirthAct $act, ActDelivery $delivery): array
    {
        $act->loadMissing('centre', 'validator');

        return [
            'commune' => config('mairie.name'),
            'centre' => $act->centre->name,
            'numero_acte' => $act->registryLabel() ?? '',
            'annee_registre' => (string) $act->registry_year,
            'nom_enfant' => mb_strtoupper($act->child_last_name),
            'prenoms_enfant' => $act->child_first_names,
            'sexe' => $act->sex === 'F' ? 'Féminin' : 'Masculin',
            'date_naissance' => $act->birthDateLabel(),
            'heure_naissance' => $act->birth_time ? substr($act->birth_time, 0, 5) : '',
            'lieu_naissance' => $act->birth_place,
            'rue_naissance' => (string) $act->birth_street,
            'date_naissance_lettres' => FrenchDate::withPrecision($act->birth_date, $act->birth_date_precision ?: 'exact'),
            'pere_nom' => (string) $act->father_name,
            'pere_naissance' => (string) $act->father_birth_info,
            // Repli sur l'ancien champ libre pour les actes saisis avant la séparation lieu / date.
            'pere_lieu_naissance' => (string) ($act->father_birth_place ?: $act->father_birth_info),
            'pere_date_naissance' => (string) $act->father_birth_date,
            'pere_profession' => (string) $act->father_profession,
            'pere_residence' => (string) $act->father_residence,
            'mere_nom' => (string) $act->mother_name,
            'mere_naissance' => (string) $act->mother_birth_info,
            'mere_lieu_naissance' => (string) ($act->mother_birth_place ?: $act->mother_birth_info),
            'mere_date_naissance' => (string) $act->mother_birth_date,
            'mere_profession' => (string) $act->mother_profession,
            'mere_residence' => (string) $act->mother_residence,
            'declarant' => trim($act->declarant_name . ($act->declarant_quality ? ' (' . $act->declarant_quality . ')' : '')),
            'declarant_nom' => (string) $act->declarant_name,
            'declarant_qualite' => (string) $act->declarant_quality,
            'date_declaration' => $act->declaration_date->format('d/m/Y'),
            'date_declaration_lettres' => FrenchDate::long($act->declaration_date),
            'jugement' => (string) $act->judgment_ref,
            'mentions_marginales' => (string) $act->marginal_notes,
            'officier' => (string) $act->validator?->name,
            'date_edition' => now()->format('d/m/Y'),
            'type_copie' => ActDelivery::TYPES[$delivery->copy_type] ?? $delivery->copy_type,
            'numero_copie' => (string) $delivery->id,
            'code_verification' => (string) $act->verification_code,
            'url_verification' => route('verify.act', $act->verification_code),
        ];
    }

    public function render(BirthAct $act, ActDelivery $delivery): string
    {
        $data = $this->data($act, $delivery);
        $template = ActTemplate::activeFor('birth');

        if ($template && filled($template->html)) {
            $replace = [];
            foreach ($data as $token => $value) {
                $replace['{{' . $token . '}}'] = e($value);
            }

            return strtr($template->html, $replace);
        }

        return view('civil.templates.birth', ['d' => $data, 'act' => $act])->render();
    }
}
