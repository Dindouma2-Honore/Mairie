@extends('layouts.app')
@section('title', 'Marchés municipaux & Domaine public')
@section('content')
<div class="page-head">
    <div>
        <h1>Marchés municipaux & Domaine public</h1>
        <p>Gestion des marchés de Moundou, des secteurs, des emplacements, étals et boutiques (Annexe B).</p>
    </div>
    @can('revenue.configure')
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('markets.create') }}">
                <x-icon name="plus"/> Nouveau marché municipal
            </a>
        </div>
    @endcan
</div>

<div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Marchés recensés</span>
        <div style="font-size:1.6rem; font-weight:700;">{{ $stats['total_markets'] }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Total des emplacements</span>
        <div style="font-size:1.6rem; font-weight:700;">{{ $stats['total_stalls'] }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Emplacements occupés</span>
        <div style="font-size:1.6rem; font-weight:700; color:#047857;">{{ $stats['total_occupied'] }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Places disponibles</span>
        <div style="font-size:1.6rem; font-weight:700; color:#2563eb;">{{ $stats['total_available'] }}</div>
    </div>
</div>

<div class="panel">
    <form class="filters" method="get">
        <div class="field grow">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom du marché, code, quartier…">
        </div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom du marché</th>
                    <th>Localisation / Quartier</th>
                    <th>Responsable / Régisseur</th>
                    <th class="r">Emplacements</th>
                    <th class="r">Occupés</th>
                    <th class="r">Disponibles</th>
                    <th class="r">Taux d'occupation</th>
                    <th class="r">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($markets as $m)
                @php
                    $rate = $m->stalls_count > 0 ? round(($m->occupied_stalls_count / $m->stalls_count) * 100, 1) : 0;
                @endphp
                <tr>
                    <td class="name num"><b>{{ $m->code }}</b></td>
                    <td>
                        <a href="{{ route('markets.show', $m) }}"><b>{{ $m->name }}</b></a>
                        @unless($m->is_active)<span class="badge bad">Fermé</span>@endunless
                    </td>
                    <td>{{ $m->location ?? '—' }}</td>
                    <td>
                        {{ $m->manager_name ?? '—' }}
                        @if($m->manager_phone)<br><small class="muted">{{ $m->manager_phone }}</small>@endif
                    </td>
                    <td class="r num"><b>{{ $m->stalls_count }}</b></td>
                    <td class="r num" style="color:#047857;">{{ $m->occupied_stalls_count }}</td>
                    <td class="r num" style="color:#2563eb;">{{ $m->available_stalls_count }}</td>
                    <td class="r num">
                        <span style="font-weight:700; color:{{ $rate >= 80 ? '#047857' : ($rate >= 50 ? '#d97706' : '#2563eb') }};">
                            {{ $rate }} %
                        </span>
                    </td>
                    <td class="r">
                        <a class="btn btn-quiet btn-sm" href="{{ route('markets.show', $m) }}">Gérer</a>
                        @can('revenue.configure')
                            <a class="btn btn-quiet btn-sm" href="{{ route('markets.edit', $m) }}">Modifier</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="empty">
                            <b>Aucun marché municipal enregistré</b>
                            Ajoutez votre premier marché pour recenser ses allées et emplacements.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $markets->links() }}
</div>
@endsection
