@extends('layouts.app')
@section('title', 'Nouvelle demande d’acte')
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('civil.requests.index') }}">Demandes d'actes</a> /</span>
        <h1>Nouvelle demande d’acte au guichet</h1>
        <p>Enregistrement d'une demande d'extrait ou de copie intégrale (Annexe A.4).</p>
    </div>
</div>

<div class="panel" style="max-width: 640px;">
    <form method="post" action="{{ route('civil.requests.store') }}">
        @csrf

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div class="field">
                <label for="act_type">Type d'acte recherché *</label>
                <select id="act_type" name="act_type" required>
                    <option value="birth" @selected(old('act_type', $civilRequest->act_type) === 'birth')>Acte de naissance</option>
                    <option value="marriage" @selected(old('act_type', $civilRequest->act_type) === 'marriage')>Acte de mariage</option>
                    <option value="death" @selected(old('act_type', $civilRequest->act_type) === 'death')>Acte de décès</option>
                </select>
            </div>
            <div class="field">
                <label for="document_type">Format demandé *</label>
                <select id="document_type" name="document_type" required>
                    <option value="extrait" @selected(old('document_type', $civilRequest->document_type) === 'extrait')>Extrait d’acte</option>
                    <option value="copie_integrale" @selected(old('document_type', $civilRequest->document_type) === 'copie_integrale')>Copie intégrale d'acte</option>
                </select>
            </div>
        </div>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div class="field">
                <label for="person_concerned">Nom & Prénoms de l'intéressé(e) *</label>
                <input id="person_concerned" type="text" name="person_concerned" value="{{ old('person_concerned') }}" required placeholder="Personne concernée par l’acte">
            </div>
            <div class="field">
                <label for="copies_count">Nombre d'exemplaires *</label>
                <input id="copies_count" type="number" name="copies_count" value="{{ old('copies_count', 1) }}" min="1" max="20" required>
            </div>
        </div>

        <div class="field" style="margin-bottom:1rem;">
            <label for="external_act_ref">Réf. de registre connue (Année / Numéro)</label>
            <input id="external_act_ref" type="text" name="external_act_ref" value="{{ old('external_act_ref') }}" placeholder="Ex. Registre 2018 N° 0451">
            <small class="muted">Facilite la recherche dans les registres anciens ou papier.</small>
        </div>

        <h2 style="font-size:1.05rem; margin-top:1.5rem; margin-bottom:1rem; color:#0a2347; border-top:1px solid #e2e8f0; padding-top:1rem;">
            Identité du demandeur
        </h2>

        <div class="field" style="margin-bottom:1rem;">
            <label for="requester_name">Nom et prénoms du demandeur *</label>
            <input id="requester_name" type="text" name="requester_name" value="{{ old('requester_name') }}" required placeholder="Ex. Mahamat Ali">
        </div>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="requester_phone">Téléphone du demandeur</label>
                <input id="requester_phone" type="text" name="requester_phone" value="{{ old('requester_phone') }}" placeholder="+235 …">
            </div>
            <div class="field">
                <label for="requester_id_number">N° Pièce d'identité (CNI / Passeport)</label>
                <input id="requester_id_number" type="text" name="requester_id_number" value="{{ old('requester_id_number') }}">
            </div>
        </div>

        <div class="field" style="margin-bottom:1.5rem;">
            <label for="notes">Notes / Observations complémentaires</label>
            <textarea id="notes" name="notes" rows="2" placeholder="Urgent, retrait prévu demain…">{{ old('notes') }}</textarea>
        </div>

        <div style="display:flex; gap:1rem;">
            <button class="btn btn-primary" type="submit">Enregistrer la demande</button>
            <a class="btn btn-quiet" href="{{ route('civil.requests.index') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
