@extends('layouts.app')
@section('title', $act->exists ? 'Modifier l’acte de décès' : 'Déclarer un décès')
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('civil.deaths.index') }}">Actes de décès</a> /</span>
        <h1>{{ $act->exists ? 'Modifier l’acte de décès' : 'Déclaration de décès' }}</h1>
        <p>Formulaire officiel à 4 volets de la République du Tchad (Annexe A.3).</p>
    </div>
</div>

<div class="panel" style="max-width: 840px;">
    <form method="post" action="{{ $act->exists ? route('civil.deaths.update', $act) : route('civil.deaths.store') }}">
        @csrf
        @if($act->exists)
            @method('PUT')
        @endif

        <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem; color:#0a2347; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
            1. Centre d'état civil & Date
        </h2>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="centre_id">Centre d'état civil *</label>
                <select id="centre_id" name="centre_id" required>
                    @foreach($centres as $id => $name)
                        <option value="{{ $id }}" @selected(old('centre_id', $act->centre_id) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="declaration_date">Date de déclaration *</label>
                <input id="declaration_date" type="date" name="declaration_date" value="{{ old('declaration_date', $act->declaration_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" required>
            </div>
        </div>

        <h2 style="font-size:1.15rem; margin-bottom:1rem; color:#0a2347; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
            2. Identité du Défunt(e)
        </h2>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="deceased_last_name">Nom de famille *</label>
                <input id="deceased_last_name" type="text" name="deceased_last_name" value="{{ old('deceased_last_name', $act->deceased_last_name) }}" required>
            </div>
            <div class="field">
                <label for="deceased_first_names">Prénoms *</label>
                <input id="deceased_first_names" type="text" name="deceased_first_names" value="{{ old('deceased_first_names', $act->deceased_first_names) }}" required>
            </div>
            <div class="field">
                <label for="sex">Sexe *</label>
                <select id="sex" name="sex" required>
                    <option value="M" @selected(old('sex', $act->sex) === 'M')>Masculin</option>
                    <option value="F" @selected(old('sex', $act->sex) === 'F')>Féminin</option>
                </select>
            </div>
            <div class="field">
                <label for="deceased_profession">Profession</label>
                <input id="deceased_profession" type="text" name="deceased_profession" value="{{ old('deceased_profession', $act->deceased_profession) }}">
            </div>
            <div class="field">
                <label for="death_date">Date du décès *</label>
                <input id="death_date" type="date" name="death_date" value="{{ old('death_date', $act->death_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" required>
            </div>
            <div class="field">
                <label for="death_time">Heure du décès</label>
                <input id="death_time" type="time" name="death_time" value="{{ old('death_time', $act->death_time) }}">
            </div>
            <div class="field">
                <label for="death_place">Lieu du décès (Quartier, hôpital…) *</label>
                <input id="death_place" type="text" name="death_place" value="{{ old('death_place', $act->death_place) }}" required placeholder="Ex. Hôpital Régional de Moundou">
            </div>
            <div class="field">
                <label for="deceased_residence">Dernier domicile</label>
                <input id="deceased_residence" type="text" name="deceased_residence" value="{{ old('deceased_residence', $act->deceased_residence) }}" placeholder="Ex. Dombao, Moundou">
            </div>
            <div class="field">
                <label for="deceased_birth_date">Date de naissance (ou âge estimé)</label>
                <input id="deceased_birth_date" type="text" name="deceased_birth_date" value="{{ old('deceased_birth_date', $act->deceased_birth_date) }}" placeholder="Ex. 15/05/1958 ou 68 ans">
            </div>
            <div class="field">
                <label for="deceased_birth_place">Lieu de naissance</label>
                <input id="deceased_birth_place" type="text" name="deceased_birth_place" value="{{ old('deceased_birth_place', $act->deceased_birth_place) }}">
            </div>
            <div class="field">
                <label for="father_name">Nom du père (Fils/Fille de)</label>
                <input id="father_name" type="text" name="father_name" value="{{ old('father_name', $act->father_name) }}">
            </div>
            <div class="field">
                <label for="mother_name">Nom de la mère (et de)</label>
                <input id="mother_name" type="text" name="mother_name" value="{{ old('mother_name', $act->mother_name) }}">
            </div>
            <div class="field">
                <label for="marital_status">Situation matrimoniale *</label>
                <select id="marital_status" name="marital_status" required>
                    @foreach(\App\Models\DeathAct::MARITAL_STATUSES as $k => $l)
                        <option value="{{ $k }}" @selected(old('marital_status', $act->marital_status) === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="spouse_name">Nom du conjoint(e)</label>
                <input id="spouse_name" type="text" name="spouse_name" value="{{ old('spouse_name', $act->spouse_name) }}">
            </div>
            <div class="field">
                <label for="birth_act_ref">Réf. acte de naissance du défunt</label>
                <input id="birth_act_ref" type="text" name="birth_act_ref" value="{{ old('birth_act_ref', $act->birth_act_ref) }}" placeholder="Ex. N° 451 du 12/03/1975">
            </div>
        </div>

        <h2 style="font-size:1.15rem; margin-bottom:1rem; color:#0a2347; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
            3. Déclarant & Justificatifs
        </h2>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="declarant_name">Nom complet du déclarant *</label>
                <input id="declarant_name" type="text" name="declarant_name" value="{{ old('declarant_name', $act->declarant_name) }}" required>
            </div>
            <div class="field">
                <label for="declarant_quality">Qualité / Lien de parenté</label>
                <input id="declarant_quality" type="text" name="declarant_quality" value="{{ old('declarant_quality', $act->declarant_quality) }}" placeholder="Ex. Fils aîné, Épouse, Frère…">
            </div>
            <div class="field">
                <label for="declarant_residence">Domicile du déclarant</label>
                <input id="declarant_residence" type="text" name="declarant_residence" value="{{ old('declarant_residence', $act->declarant_residence) }}">
            </div>
            <div class="field">
                <label for="medical_certificate_ref">Réf. constat / Certificat médical de décès</label>
                <input id="medical_certificate_ref" type="text" name="medical_certificate_ref" value="{{ old('medical_certificate_ref', $act->medical_certificate_ref) }}" placeholder="Ex. CMD-2026-089 Hôpital Moundou">
            </div>
            <div class="field">
                <label for="burial_place">Lieu d'inhumation / Cimetière</label>
                <input id="burial_place" type="text" name="burial_place" value="{{ old('burial_place', $act->burial_place) }}" placeholder="Ex. Cimetière municipal de Dombao">
            </div>
            <div class="field">
                <label for="burial_date">Date d'inhumation</label>
                <input id="burial_date" type="date" name="burial_date" value="{{ old('burial_date', $act->burial_date?->format('Y-m-d')) }}">
            </div>
        </div>

        <div style="display:flex; gap:1rem;">
            <button class="btn btn-primary" type="submit">{{ $act->exists ? 'Mettre à jour' : 'Enregistrer la déclaration de décès' }}</button>
            <a class="btn btn-quiet" href="{{ route('civil.deaths.index') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
