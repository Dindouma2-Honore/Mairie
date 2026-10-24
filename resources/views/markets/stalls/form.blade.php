@extends('layouts.app')
@section('title', $stall->exists ? 'Modifier l’emplacement ' . $stall->code : 'Nouvel emplacement — ' . $market->name)
@section('content')
<div class="page-head">
    <div>
        <span class="crumb">
            <a href="{{ route('markets.index') }}">Marchés</a> /
            <a href="{{ route('markets.show', $market) }}">{{ $market->name }}</a> /
        </span>
        <h1>{{ $stall->exists ? 'Modifier ' . $stall->code : 'Nouvel emplacement' }}</h1>
    </div>
</div>

<div class="panel" style="max-width: 640px;">
    <form method="post" action="{{ $stall->exists ? route('markets.stalls.update', $stall) : route('markets.stalls.store', $market) }}">
        @csrf
        @if($stall->exists)
            @method('PUT')
        @endif

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div class="field">
                <label for="code">Numéro / Code unique *</label>
                <input id="code" type="text" name="code" value="{{ old('code', $stall->code) }}" required placeholder="Ex. MC-A-012, MC-BTQ-05">
            </div>
            <div class="field">
                <label for="name">Désignation *</label>
                <input id="name" type="text" name="name" value="{{ old('name', $stall->name) }}" required placeholder="Ex. Étal 12, Boutique N°5">
            </div>
        </div>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div class="field">
                <label for="sector">Allée / Secteur</label>
                <input id="sector" type="text" name="sector" value="{{ old('sector', $stall->sector) }}" placeholder="Ex. Allée A, Bloc Nord">
            </div>
            <div class="field">
                <label for="type">Type d'emplacement *</label>
                <select id="type" name="type" required>
                    @foreach(\App\Models\MarketStall::TYPES as $key => $lbl)
                        <option value="{{ $key }}" @selected(old('type', $stall->type) === $key)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div class="field">
                <label for="base_fee">Droit de place de référence (FCFA) *</label>
                <input id="base_fee" type="number" name="base_fee" value="{{ old('base_fee', $stall->base_fee) }}" min="0" step="1" required placeholder="Ex. 500, 15000">
            </div>
            <div class="field">
                <label for="periodicity">Périodicité de perception *</label>
                <select id="periodicity" name="periodicity" required>
                    @foreach(\App\Models\MarketStall::PERIODICITIES as $key => $lbl)
                        <option value="{{ $key }}" @selected(old('periodicity', $stall->periodicity) === $key)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="field" style="margin-bottom:1rem;">
            <label for="dimensions">Dimensions / Superficie</label>
            <input id="dimensions" type="text" name="dimensions" value="{{ old('dimensions', $stall->dimensions) }}" placeholder="Ex. 2m x 2m, 12 m²">
        </div>

        <div class="field" style="margin-bottom:1.5rem;">
            <label for="notes">Notes / Observations</label>
            <textarea id="notes" name="notes" rows="3" placeholder="Emplacement avec compteur électrique, proche entrée principale…">{{ old('notes', $stall->notes) }}</textarea>
        </div>

        <div style="display:flex; gap:1rem;">
            <button class="btn btn-primary" type="submit">{{ $stall->exists ? 'Mettre à jour' : 'Enregistrer l’emplacement' }}</button>
            <a class="btn btn-quiet" href="{{ $stall->exists ? route('markets.stalls.show', $stall) : route('markets.show', $market) }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
