@extends('layouts.app')
@section('title', $act->exists ? 'Modifier l’acte de mariage' : 'Déclarer un mariage')
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('civil.marriages.index') }}">Actes de mariage</a> /</span>
        <h1>{{ $act->exists ? 'Modifier l’acte de mariage' : 'Célébration & Déclaration de mariage' }}</h1>
        <p>Formulaire officiel à 3 volets de la République du Tchad (Annexe A.2).</p>
    </div>
</div>

<div class="panel" style="max-width: 840px;">
    <form method="post" action="{{ $act->exists ? route('civil.marriages.update', $act) : route('civil.marriages.store') }}">
        @csrf
        @if($act->exists)
            @method('PUT')
        @endif

        <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem; color:#0a2347; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
            1. Célébration & Centre d'état civil
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
                <label for="celebration_date">Date de célébration *</label>
                <input id="celebration_date" type="date" name="celebration_date" value="{{ old('celebration_date', $act->celebration_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" required>
            </div>
            <div class="field">
                <label for="celebration_place">Lieu / Commune</label>
                <input id="celebration_place" type="text" name="celebration_place" value="{{ old('celebration_place', $act->celebration_place ?? 'Mairie de Moundou') }}">
            </div>
            <div class="field">
                <label for="officer_name">Officier d'état civil célébrant</label>
                <input id="officer_name" type="text" name="officer_name" value="{{ old('officer_name', $act->officer_name) }}" placeholder="Ex. Le Maire ou Adjoint délégué">
            </div>
        </div>

        <h2 style="font-size:1.15rem; margin-bottom:1rem; color:#0a2347; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
            2. Identité de l'Époux (M.)
        </h2>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="husband_last_name">Nom de famille *</label>
                <input id="husband_last_name" type="text" name="husband_last_name" value="{{ old('husband_last_name', $act->husband_last_name) }}" required>
            </div>
            <div class="field">
                <label for="husband_first_names">Prénoms *</label>
                <input id="husband_first_names" type="text" name="husband_first_names" value="{{ old('husband_first_names', $act->husband_first_names) }}" required>
            </div>
            <div class="field">
                <label for="husband_birth_date">Date de naissance</label>
                <input id="husband_birth_date" type="text" name="husband_birth_date" value="{{ old('husband_birth_date', $act->husband_birth_date) }}" placeholder="Ex. 12/04/1990 ou Vers 1990">
            </div>
            <div class="field">
                <label for="husband_birth_place">Lieu de naissance</label>
                <input id="husband_birth_place" type="text" name="husband_birth_place" value="{{ old('husband_birth_place', $act->husband_birth_place) }}" placeholder="Ex. Moundou">
            </div>
            <div class="field">
                <label for="husband_canton">Canton</label>
                <input id="husband_canton" type="text" name="husband_canton" value="{{ old('husband_canton', $act->husband_canton) }}">
            </div>
            <div class="field">
                <label for="husband_sub_prefecture">Sous-Préfecture</label>
                <input id="husband_sub_prefecture" type="text" name="husband_sub_prefecture" value="{{ old('husband_sub_prefecture', $act->husband_sub_prefecture) }}">
            </div>
            <div class="field">
                <label for="husband_father">Nom du père (Fils de)</label>
                <input id="husband_father" type="text" name="husband_father" value="{{ old('husband_father', $act->husband_father) }}">
            </div>
            <div class="field">
                <label for="husband_mother">Nom de la mère (et de)</label>
                <input id="husband_mother" type="text" name="husband_mother" value="{{ old('husband_mother', $act->husband_mother) }}">
            </div>
            <div class="field">
                <label for="husband_marital_status">Situation matrimoniale antérieure *</label>
                <select id="husband_marital_status" name="husband_marital_status" required>
                    <option value="celibataire" @selected(old('husband_marital_status', $act->husband_marital_status) === 'celibataire')>Célibataire</option>
                    <option value="veuf" @selected(old('husband_marital_status', $act->husband_marital_status) === 'veuf')>Veuf</option>
                    <option value="divorce" @selected(old('husband_marital_status', $act->husband_marital_status) === 'divorce')>Divorcé</option>
                </select>
            </div>
            <div class="field">
                <label for="husband_previous_spouse">Veuf ou divorcé de</label>
                <input id="husband_previous_spouse" type="text" name="husband_previous_spouse" value="{{ old('husband_previous_spouse', $act->husband_previous_spouse) }}">
            </div>
            <div class="field">
                <label for="husband_birth_act_ref">Réf. acte de naissance N°</label>
                <input id="husband_birth_act_ref" type="text" name="husband_birth_act_ref" value="{{ old('husband_birth_act_ref', $act->husband_birth_act_ref) }}">
            </div>
            <div class="field">
                <label for="husband_represented_by">Représenté par</label>
                <input id="husband_represented_by" type="text" name="husband_represented_by" value="{{ old('husband_represented_by', $act->husband_represented_by) }}">
            </div>
        </div>

        <h2 style="font-size:1.15rem; margin-bottom:1rem; color:#0a2347; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
            3. Identité de l'Épouse (Mme)
        </h2>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="wife_last_name">Nom de famille *</label>
                <input id="wife_last_name" type="text" name="wife_last_name" value="{{ old('wife_last_name', $act->wife_last_name) }}" required>
            </div>
            <div class="field">
                <label for="wife_first_names">Prénoms *</label>
                <input id="wife_first_names" type="text" name="wife_first_names" value="{{ old('wife_first_names', $act->wife_first_names) }}" required>
            </div>
            <div class="field">
                <label for="wife_birth_date">Date de naissance</label>
                <input id="wife_birth_date" type="text" name="wife_birth_date" value="{{ old('wife_birth_date', $act->wife_birth_date) }}" placeholder="Ex. 25/08/1995 ou Vers 1995">
            </div>
            <div class="field">
                <label for="wife_birth_place">Lieu de naissance</label>
                <input id="wife_birth_place" type="text" name="wife_birth_place" value="{{ old('wife_birth_place', $act->wife_birth_place) }}">
            </div>
            <div class="field">
                <label for="wife_canton">Canton</label>
                <input id="wife_canton" type="text" name="wife_canton" value="{{ old('wife_canton', $act->wife_canton) }}">
            </div>
            <div class="field">
                <label for="wife_sub_prefecture">Sous-Préfecture</label>
                <input id="wife_sub_prefecture" type="text" name="wife_sub_prefecture" value="{{ old('wife_sub_prefecture', $act->wife_sub_prefecture) }}">
            </div>
            <div class="field">
                <label for="wife_father">Nom du père (Fille de)</label>
                <input id="wife_father" type="text" name="wife_father" value="{{ old('wife_father', $act->wife_father) }}">
            </div>
            <div class="field">
                <label for="wife_mother">Nom de la mère (et de)</label>
                <input id="wife_mother" type="text" name="wife_mother" value="{{ old('wife_mother', $act->wife_mother) }}">
            </div>
            <div class="field">
                <label for="wife_marital_status">Situation matrimoniale antérieure *</label>
                <select id="wife_marital_status" name="wife_marital_status" required>
                    <option value="celibataire" @selected(old('wife_marital_status', $act->wife_marital_status) === 'celibataire')>Célibataire</option>
                    <option value="veuve" @selected(old('wife_marital_status', $act->wife_marital_status) === 'veuve')>Veuve</option>
                    <option value="divorcee" @selected(old('wife_marital_status', $act->wife_marital_status) === 'divorcee')>Divorcée</option>
                </select>
            </div>
            <div class="field">
                <label for="wife_previous_spouse">Veuve ou divorcée de</label>
                <input id="wife_previous_spouse" type="text" name="wife_previous_spouse" value="{{ old('wife_previous_spouse', $act->wife_previous_spouse) }}">
            </div>
            <div class="field">
                <label for="wife_birth_act_ref">Réf. acte de naissance N°</label>
                <input id="wife_birth_act_ref" type="text" name="wife_birth_act_ref" value="{{ old('wife_birth_act_ref', $act->wife_birth_act_ref) }}">
            </div>
            <div class="field">
                <label for="wife_represented_by">Représentée par</label>
                <input id="wife_represented_by" type="text" name="wife_represented_by" value="{{ old('wife_represented_by', $act->wife_represented_by) }}">
            </div>
        </div>

        <h2 style="font-size:1.15rem; margin-bottom:1rem; color:#0a2347; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
            4. Témoins
        </h2>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="husband_witness">Témoin de l'époux</label>
                <input id="husband_witness" type="text" name="husband_witness" value="{{ old('husband_witness', $act->husband_witness) }}">
            </div>
            <div class="field">
                <label for="husband_witness_residence">Domicile du témoin époux</label>
                <input id="husband_witness_residence" type="text" name="husband_witness_residence" value="{{ old('husband_witness_residence', $act->husband_witness_residence) }}">
            </div>
            <div class="field">
                <label for="wife_witness">Témoin de l'épouse</label>
                <input id="wife_witness" type="text" name="wife_witness" value="{{ old('wife_witness', $act->wife_witness) }}">
            </div>
            <div class="field">
                <label for="wife_witness_residence">Domicile du témoin épouse</label>
                <input id="wife_witness_residence" type="text" name="wife_witness_residence" value="{{ old('wife_witness_residence', $act->wife_witness_residence) }}">
            </div>
        </div>

        <h2 style="font-size:1.15rem; margin-bottom:1rem; color:#0a2347; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
            5. Verso : Stipulations particulières (Dot & Contrat)
        </h2>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div class="field">
                <label for="dowry_cash">Montant de la dot en espèces (FCFA)</label>
                <input id="dowry_cash" type="number" name="dowry_cash" value="{{ old('dowry_cash', $act->dowry_cash ?? 0) }}" min="0" step="1">
            </div>
            <div class="field">
                <label for="dowry_nature">Dot en nature (description)</label>
                <input id="dowry_nature" type="text" name="dowry_nature" value="{{ old('dowry_nature', $act->dowry_nature) }}" placeholder="Ex. 5 têtes de bétail, pagnes…">
            </div>
        </div>
        <div class="field" style="margin-bottom:1rem;">
            <label for="dowry_payment_dates">Dates des versements de la dot</label>
            <input id="dowry_payment_dates" type="text" name="dowry_payment_dates" value="{{ old('dowry_payment_dates', $act->dowry_payment_dates) }}" placeholder="Ex. Versée le jour du mariage">
        </div>

        <div class="field" style="margin-bottom:1rem;">
            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                <input type="checkbox" name="has_marriage_contract" value="1" @checked(old('has_marriage_contract', $act->has_marriage_contract))>
                <span>Un contrat de mariage préalable a été conclu par acte notarié</span>
            </label>
        </div>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="contract_date">Date du contrat notarié</label>
                <input id="contract_date" type="text" name="contract_date" value="{{ old('contract_date', $act->contract_date) }}">
            </div>
            <div class="field">
                <label for="contract_notary">Maître (Nom du Notaire)</label>
                <input id="contract_notary" type="text" name="contract_notary" value="{{ old('contract_notary', $act->contract_notary) }}">
            </div>
        </div>

        <div style="display:flex; gap:1rem;">
            <button class="btn btn-primary" type="submit">{{ $act->exists ? 'Mettre à jour' : 'Enregistrer le projet d’acte' }}</button>
            <a class="btn btn-quiet" href="{{ route('civil.marriages.index') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
