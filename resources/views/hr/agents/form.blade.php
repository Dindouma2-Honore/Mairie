@extends('layouts.app')
@section('title', $agent->exists ? 'Modifier un agent' : 'Nouvel agent')
@section('content')
<div class="page-head"><h1>{{ $agent->exists ? 'Modifier le dossier de ' . $agent->fullName() : 'Nouvel agent' }}</h1></div>
<form class="panel" method="post" action="{{ $agent->exists ? route('hr.agents.update', $agent) : route('hr.agents.store') }}">
    @csrf @if($agent->exists) @method('PUT') @endif
    <div class="panel-body">
        <div class="form-grid">
            <x-field name="matricule" label="Matricule" :value="$agent->matricule" hint="Laissez vide pour une attribution automatique." />
            <x-field name="last_name" label="Nom" :value="$agent->last_name" required />
            <x-field name="first_names" label="Prénoms" :value="$agent->first_names" required />
            <x-field name="sex" label="Sexe" :options="['M' => 'Masculin', 'F' => 'Féminin']" :value="$agent->sex" />
            <x-field name="birth_date" label="Date de naissance" type="date" :value="optional($agent->birth_date)->toDateString()" />
            <x-field name="birth_place" label="Lieu de naissance" :value="$agent->birth_place" />
            <x-field name="phone" label="Téléphone" type="tel" :value="$agent->phone" />
            <x-field name="email" label="E-mail" type="email" :value="$agent->email" />
            <x-field name="address" label="Adresse / quartier" :value="$agent->address" />
            <x-field name="service_id" label="Service" :options="$services" :value="$agent->service_id" />
            <x-field name="position" label="Poste" :value="$agent->position" />
            <x-field name="grade" label="Grade" :value="$agent->grade" />
            <x-field name="status" label="Statut" :options="\App\Models\Agent::STATUSES" :value="$agent->status" required />
            <x-field name="hire_date" label="Date d’embauche" type="date" :value="optional($agent->hire_date)->toDateString()" />
            <x-field name="end_date" label="Date de fin" type="date" :value="optional($agent->end_date)->toDateString()" />
        </div>
        <div class="form-actions" style="margin-top:16px">
            <input type="hidden" name="is_active" value="0">
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $agent->is_active))> Agent en activité</label>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn btn-quiet" href="{{ $agent->exists ? route('hr.agents.show', $agent) : route('hr.agents.index') }}">Annuler</a>
        </div>
    </div>
</form>
@endsection
