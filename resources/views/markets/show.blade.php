@extends('layouts.app')
@section('title', $market->name)
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('markets.index') }}">Marchés municipaux</a> /</span>
        <h1>{{ $market->name }}</h1>
        <p>
            Code : <b>{{ $market->code }}</b> ·
            Localisation : <b>{{ $market->location ?? 'Non précisée' }}</b>
            @if($market->manager_name) · Responsable : <b>{{ $market->manager_name }}</b>@endif
            @if($market->manager_phone) ({{ $market->manager_phone }})@endif
        </p>
    </div>
    <div class="actions">
        @can('revenue.configure')
            <a class="btn btn-quiet" href="{{ route('markets.edit', $market) }}">
                <x-icon name="edit"/> Modifier le marché
            </a>
        @endcan
        <a class="btn btn-primary" href="{{ route('markets.stalls.create', $market) }}">
            <x-icon name="plus"/> Ajouter un emplacement
        </a>
    </div>
</div>

<div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Total des emplacements</span>
        <div style="font-size:1.6rem; font-weight:700;">{{ $market->totalStalls() }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Emplacements occupés</span>
        <div style="font-size:1.6rem; font-weight:700; color:#047857;">{{ $market->occupiedStallsCount() }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Places disponibles</span>
        <div style="font-size:1.6rem; font-weight:700; color:#2563eb;">{{ $market->availableStallsCount() }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Taux d'occupation</span>
        <div style="font-size:1.6rem; font-weight:700; color:{{ $market->occupancyRate() >= 80 ? '#047857' : '#2563eb' }};">
            {{ $market->occupancyRate() }} %
        </div>
    </div>
</div>

<div class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2 style="font-size:1.15rem; margin:0;">Registre des étals, places et boutiques</h2>
    </div>

    <form class="filters" method="get">
        <div class="field grow">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Code emplacement, nom, occupant…">
        </div>
        @if($sectors->isNotEmpty())
            <div class="field">
                <label for="sector">Secteur / Allée</label>
                <select id="sector" name="sector">
                    <option value="">Tous les secteurs</option>
                    @foreach($sectors as $sec)
                        <option value="{{ $sec }}" @selected(request('sector') === $sec)>{{ $sec }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="field">
            <label for="type">Type d'emplacement</label>
            <select id="type" name="type">
                <option value="">Tous les types</option>
                @foreach(\App\Models\MarketStall::TYPES as $key => $lbl)
                    <option value="{{ $key }}" @selected(request('type') === $key)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="">Tous</option>
                <option value="available" @selected(request('status') === 'available')>Disponible</option>
                <option value="occupied" @selected(request('status') === 'occupied')>Occupé</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Suspendu</option>
            </select>
        </div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Désignation</th>
                    <th>Secteur</th>
                    <th>Type</th>
                    <th class="r">Droit de place</th>
                    <th>Occupant actuel</th>
                    <th>Statut</th>
                    <th class="r">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($stalls as $s)
                <tr>
                    <td class="name num">
                        <a href="{{ route('markets.stalls.show', $s) }}"><b>{{ $s->code }}</b></a>
                    </td>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->sector ?? '—' }}</td>
                    <td>{{ $s->typeLabel() }}</td>
                    <td class="r num">
                        {{ \App\Support\Money::fcfa($s->base_fee) }}
                        <small class="muted">/ {{ $s->periodicityLabel() }}</small>
                    </td>
                    <td>
                        @if($s->currentOccupant)
                            <a href="{{ route('revenue.taxpayers.show', $s->currentOccupant) }}">
                                <b>{{ $s->currentOccupant->name }}</b>
                            </a>
                            @if($s->currentOccupant->phone)<br><small class="muted">{{ $s->currentOccupant->phone }}</small>@endif
                        @else
                            <span class="muted"><i>Aucun occupant</i></span>
                        @endif
                    </td>
                    <td>
                        @if($s->isAvailable())
                            <span class="badge ok">Disponible</span>
                        @elseif($s->isOccupied())
                            <span class="badge info">Occupé</span>
                        @else
                            <span class="badge bad">Suspendu</span>
                        @endif
                    </td>
                    <td class="r">
                        <a class="btn btn-quiet btn-sm" href="{{ route('markets.stalls.show', $s) }}">Fiche</a>
                        @if($s->currentOccupant && auth()->user()->hasPermission('revenue.create'))
                            <a class="btn btn-primary btn-sm" href="{{ route('revenue.receipts.create') }}?taxpayer_id={{ $s->current_occupant_id }}&amount={{ $s->base_fee }}&market_stall_id={{ $s->id }}" title="Encaisser les droits de place">
                                <x-icon name="coins"/> Encaisser
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="empty">
                            <b>Aucun emplacement trouvé</b>
                            Ajoutez des emplacements (étals, boutiques) pour ce marché.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $stalls->links() }}
</div>
@endsection
