@extends('layouts.app')
@section('title', $market->exists ? 'Modifier ' . $market->name : 'Nouveau marché municipal')
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('markets.index') }}">Marchés municipaux</a> /</span>
        <h1>{{ $market->exists ? 'Modifier ' . $market->name : 'Nouveau marché municipal' }}</h1>
    </div>
</div>

<div class="panel" style="max-width: 640px;">
    <form method="post" action="{{ $market->exists ? route('markets.update', $market) : route('markets.store') }}">
        @csrf
        @if($market->exists)
            @method('PUT')
        @endif

        <div class="field" style="margin-bottom:1rem;">
            <label for="code">Code unique du marché *</label>
            <input id="code" type="text" name="code" value="{{ old('code', $market->code) }}" required placeholder="Ex. MC-MOUNDOU, MG-GUELDJEM">
            <small class="muted">Identifiant court utilisé dans la numérotation des emplacements.</small>
        </div>

        <div class="field" style="margin-bottom:1rem;">
            <label for="name">Nom du marché *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $market->name) }}" required placeholder="Ex. Marché Central de Moundou">
        </div>

        <div class="field" style="margin-bottom:1rem;">
            <label for="location">Quartier / Localisation</label>
            <input id="location" type="text" name="location" value="{{ old('location', $market->location) }}" placeholder="Ex. Centre-ville / Dombao">
        </div>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div class="field">
                <label for="manager_name">Nom du régisseur / responsable</label>
                <input id="manager_name" type="text" name="manager_name" value="{{ old('manager_name', $market->manager_name) }}">
            </div>
            <div class="field">
                <label for="manager_phone">Téléphone du responsable</label>
                <input id="manager_phone" type="text" name="manager_phone" value="{{ old('manager_phone', $market->manager_phone) }}">
            </div>
        </div>

        <div class="field" style="margin-bottom:1rem;">
            <label for="sectors_list">Secteurs / Allées (séparés par des virgules)</label>
            @php
                $sectorsVal = old('sectors_list', is_array($market->sectors) ? implode(', ', $market->sectors) : '');
            @endphp
            <input id="sectors_list" type="text" name="sectors_list" value="{{ $sectorsVal }}" placeholder="Ex. Allée A, Allée B, Bloc Boucherie, Hangar Céréales">
            <small class="muted">Facilite le classement des étals et boutiques lors de la saisie.</small>
        </div>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="field">
                <label for="latitude">Latitude GPS</label>
                <input id="latitude" type="number" step="any" name="latitude" value="{{ old('latitude', $market->latitude) }}" placeholder="Ex. 8.5667">
            </div>
            <div class="field">
                <label for="longitude">Longitude GPS</label>
                <input id="longitude" type="number" step="any" name="longitude" value="{{ old('longitude', $market->longitude) }}" placeholder="Ex. 16.0833">
            </div>
        </div>

        <div class="field" style="margin-bottom:1.5rem;">
            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $market->is_active ?? true))>
                <span>Marché en activité</span>
            </label>
        </div>

        <div style="display:flex; gap:1rem;">
            <button class="btn btn-primary" type="submit">{{ $market->exists ? 'Mettre à jour' : 'Créer le marché' }}</button>
            <a class="btn btn-quiet" href="{{ route('markets.index') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
