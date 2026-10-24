@extends('layouts.app')
@section('title', $act->exists ? 'Modifier le brouillon' : 'Déclarer une naissance')
@section('content')
@php $v = fn ($k) => $act->{$k}; @endphp
<div class="page-head"><div><h1>{{ $act->exists ? 'Modifier le brouillon d’acte' : 'Déclarer une naissance' }}</h1>
    <p>La saisie crée un brouillon. Le numéro de registre est attribué à la validation par l’officier d’état civil. Fonctionne aussi sans connexion.</p></div></div>

<form method="post" action="{{ $act->exists ? route('civil.births.update', $act) : route('civil.births.store') }}" @unless($act->exists) data-offline="birth_act.draft" @endunless>
    @csrf @if($act->exists) @method('PUT') @endif

    <fieldset class="panel"><legend class="panel-title">Centre et déclaration</legend>
        <div class="panel-body"><div class="form-grid">
            <x-field name="centre_id" label="Centre d’état civil" :options="$centres" :value="$act->centre_id" required />
            <x-field name="declaration_date" label="Date de la déclaration" type="date" :value="optional($act->declaration_date)->toDateString()" required />
            <x-field name="declarant_name" label="Nom du déclarant" :value="$act->declarant_name" />
            <x-field name="declarant_quality" label="Qualité du déclarant" :value="$act->declarant_quality" hint="Père, mère, sage-femme, parent…" />
            <div class="full">
                <input type="hidden" name="is_late_declaration" value="0">
                <label class="check"><input type="checkbox" name="is_late_declaration" value="1" @checked(old('is_late_declaration', $act->is_late_declaration))> Déclaration tardive</label>
            </div>
            <div class="span-2"><x-field name="judgment_ref" label="Référence du jugement supplétif" :value="$act->judgment_ref" hint="Pour les déclarations tardives passées par un jugement." /></div>
        </div></div>
    </fieldset>

    <fieldset class="panel"><legend class="panel-title">Enfant</legend>
        <div class="panel-body"><div class="form-grid">
            <x-field name="child_last_name" label="Nom" :value="$act->child_last_name" required />
            <x-field name="child_first_names" label="Prénoms" :value="$act->child_first_names" required />
            <x-field name="sex" label="Sexe" :options="['M' => 'Masculin', 'F' => 'Féminin']" :value="$act->sex" required />
            <x-field name="birth_date" label="Date de naissance" type="date" :value="optional($act->birth_date)->toDateString()" :max="today()->toDateString()" required />
            <x-field name="birth_date_precision" label="Précision de la date" :options="\App\Models\BirthAct::PRECISIONS" :value="$act->birth_date_precision ?? 'exact'" required hint="Si seul le mois ou l’année est connu, saisissez le 1er jour et choisissez la précision." />
            <x-field name="birth_time" label="Heure de naissance" type="time" :value="$act->birth_time ? substr($act->birth_time, 0, 5) : null" />
            <x-field name="birth_place" label="Lieu de naissance" :value="$act->birth_place" required />
            <x-field name="birth_place_type" label="Type de lieu" :options="['hopital' => 'Hôpital / centre de santé', 'domicile' => 'Domicile', 'autre' => 'Autre']" :value="$act->birth_place_type" />
            <x-field name="birth_street" label="Rue ou quartier de naissance" :value="$act->birth_street" hint="Case « Rue » du formulaire officiel." />
        </div></div>
    </fieldset>

    <fieldset class="panel"><legend class="panel-title">Père</legend>
        <div class="panel-body"><div class="form-grid">
            <x-field name="father_name" label="Nom et prénoms" :value="$act->father_name" />
            <x-field name="father_birth_place" label="Né(e) à" :value="$act->father_birth_place ?? $act->father_birth_info" />
            <x-field name="father_birth_date" label="Né(e) le" :value="$act->father_birth_date" hint="Date exacte ou approximative, ex. « 12/03/1985 » ou « vers 1985 »." />
            <x-field name="father_profession" label="Profession" :value="$act->father_profession" />
            <x-field name="father_residence" label="Résidence" :value="$act->father_residence" />
            <x-field name="father_nationality" label="Nationalité" :value="$act->father_nationality" />
        </div></div>
    </fieldset>

    <fieldset class="panel"><legend class="panel-title">Mère</legend>
        <div class="panel-body"><div class="form-grid">
            <x-field name="mother_name" label="Nom et prénoms" :value="$act->mother_name" required />
            <x-field name="mother_birth_place" label="Née à" :value="$act->mother_birth_place ?? $act->mother_birth_info" />
            <x-field name="mother_birth_date" label="Née le" :value="$act->mother_birth_date" hint="Date exacte ou approximative, ex. « 12/03/1985 » ou « vers 1985 »." />
            <x-field name="mother_profession" label="Profession" :value="$act->mother_profession" />
            <x-field name="mother_residence" label="Résidence" :value="$act->mother_residence" />
            <x-field name="mother_nationality" label="Nationalité" :value="$act->mother_nationality" />
        </div></div>
    </fieldset>

    <fieldset class="panel"><legend class="panel-title">Mentions</legend>
        <div class="panel-body"><x-field name="marginal_notes" label="Mentions marginales" type="textarea" :value="$act->marginal_notes" /></div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><x-icon name="check"/> {{ $act->exists ? 'Enregistrer' : 'Enregistrer le brouillon' }}</button>
        <a class="btn btn-quiet" href="{{ $act->exists ? route('civil.births.show', $act) : route('civil.births.index') }}">Annuler</a>
    </div>
</form>
@endsection
