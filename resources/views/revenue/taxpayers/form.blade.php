@extends('layouts.app')
@section('title', $taxpayer->exists ? 'Modifier un contribuable' : 'Nouveau contribuable')
@section('content')
<div class="page-head"><h1>{{ $taxpayer->exists ? 'Modifier' : 'Nouveau' }} contribuable</h1></div>
<form class="panel" method="post" action="{{ $taxpayer->exists ? route('revenue.taxpayers.update', $taxpayer) : route('revenue.taxpayers.store') }}">
    @csrf @if($taxpayer->exists) @method('PUT') @endif
    <div class="panel-body">
        <div class="form-grid">
            <x-field name="type" label="Type" :options="['personne' => 'Personne', 'entreprise' => 'Entreprise']" :value="$taxpayer->type" required />
            <x-field name="name" label="Nom / raison sociale" :value="$taxpayer->name" required />
            <x-field name="reference" label="Référence" :value="$taxpayer->reference" hint="Numéro attribué par la mairie, si existant." />
            <x-field name="phone" label="Téléphone" type="tel" :value="$taxpayer->phone" />
            <div class="span-2"><x-field name="quarter" label="Quartier / repère" :value="$taxpayer->quarter" /></div>
            <x-field name="activity" label="Activité" :value="$taxpayer->activity" />
            <x-field name="latitude" label="Latitude" type="number" step="any" :value="$taxpayer->latitude" />
            <x-field name="longitude" label="Longitude" type="number" step="any" :value="$taxpayer->longitude" />
        </div>
        <div class="form-actions" style="margin-top:16px">
            <input type="hidden" name="is_active" value="0">
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $taxpayer->is_active))> Actif</label>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn btn-quiet" href="{{ route('revenue.taxpayers.index') }}">Annuler</a>
        </div>
    </div>
</form>
@endsection
