@extends('layouts.app')
@section('title', 'Clôtures de caisse')
@section('content')
<div class="page-head">
    <div>
        <h1>Clôtures de caisse & Contrôle financier</h1>
        <p>Vérification des encaissements, des espèces comptées et des procès-verbaux de caisse (Section 5.14).</p>
    </div>
    @can('revenue.closing.create')
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('revenue.closings.create') }}">
                <x-icon name="plus"/> Nouvelle clôture de caisse
            </a>
        </div>
    @endcan
</div>

<div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">En attente de contrôle</span>
        <div style="font-size:1.75rem; font-weight:700; color: #b45309;">{{ $stats['pending_count'] }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Total encaissé validé</span>
        <div style="font-size:1.75rem; font-weight:700; color: #047857;">{{ \App\Support\Money::fcfa($stats['total_amount']) }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Écarts cumulés (validés)</span>
        <div style="font-size:1.75rem; font-weight:700; color: {{ $stats['total_difference'] < 0 ? '#b91c1c' : ($stats['total_difference'] > 0 ? '#2563eb' : '#047857') }};">
            {{ $stats['total_difference'] > 0 ? '+' : '' }}{{ \App\Support\Money::fcfa($stats['total_difference']) }}
        </div>
    </div>
</div>

<div class="panel">
    <form class="filters" method="get">
        @if($canAudit)
            <div class="field">
                <label for="collector">Régisseur / Collecteur</label>
                <select id="collector" name="collector">
                    <option value="">Tous les régisseurs</option>
                    @foreach($collectors as $col)
                        <option value="{{ $col->id }}" @selected((string) request('collector') === (string) $col->id)>{{ $col->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="field">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="">Tous les statuts</option>
                <option value="pending" @selected(request('status') === 'pending')>En attente de contrôle</option>
                <option value="validated" @selected(request('status') === 'validated')>Validée</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejetée</option>
            </select>
        </div>
        <div class="field">
            <label for="from">Du</label>
            <input id="from" type="date" name="from" value="{{ request('from') }}">
        </div>
        <div class="field">
            <label for="to">Au</label>
            <input id="to" type="date" name="to" value="{{ request('to') }}">
        </div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Date clôture</th>
                    <th>Régisseur</th>
                    <th class="r">Reçus</th>
                    <th class="r">Total</th>
                    <th class="r">Espèces (attendues / comptées)</th>
                    <th class="r">Écart</th>
                    <th>Statut</th>
                    <th class="r">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($closings as $c)
                <tr>
                    <td class="name">
                        <a href="{{ route('revenue.closings.show', $c) }}"><b>{{ $c->reference }}</b></a>
                    </td>
                    <td class="num">{{ $c->closing_date->format('d/m/Y') }}</td>
                    <td>{{ $c->collector->name }}</td>
                    <td class="r num">{{ $c->receipts_count }}</td>
                    <td class="r num"><b>{{ \App\Support\Money::fcfa($c->total_amount) }}</b></td>
                    <td class="r num">
                        {{ \App\Support\Money::fcfa($c->cash_expected) }} /
                        <b>{{ \App\Support\Money::fcfa($c->cash_counted) }}</b>
                    </td>
                    <td class="r num">
                        @if($c->difference == 0)
                            <span class="badge ok">0 FCFA</span>
                        @elseif($c->difference > 0)
                            <span class="badge info">+{{ \App\Support\Money::fcfa($c->difference) }}</span>
                        @else
                            <span class="badge bad">{{ \App\Support\Money::fcfa($c->difference) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($c->isValidated())
                            <span class="badge ok">Validée</span>
                        @elseif($c->isRejected())
                            <span class="badge bad">Rejetée</span>
                        @else
                            <span class="badge warning" style="background:#fef3c7; color:#92400e;">À contrôler</span>
                        @endif
                    </td>
                    <td class="r">
                        <a class="btn btn-quiet btn-sm" href="{{ route('revenue.closings.show', $c) }}">Détails</a>
                        <a class="btn btn-quiet btn-sm" href="{{ route('revenue.closings.print', $c) }}" target="_blank" title="Imprimer le PV de caisse">
                            <x-icon name="print"/> PV
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="empty">
                            <b>Aucune clôture de caisse trouvée</b>
                            Toutes les recettes sont à jour ou aucun filtre ne correspond.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $closings->links() }}
</div>
@endsection
