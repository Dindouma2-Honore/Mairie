@extends('layouts.app')
@section('title', 'Recouvrement & Impayés')
@section('content')
<div class="page-head">
    <div>
        <h1>Recouvrement & Restes à Payer</h1>
        <p>Suivi des redevances municipales, restes à recouvrer et avis de mise en recouvrement (Section 5.13).</p>
    </div>
    @can('revenue.recovery')
        <div class="actions">
            <details class="user" style="position:relative;">
                <summary class="btn btn-primary" style="list-style:none; cursor:pointer;">
                    <x-icon name="plus"/> Émettre un avis de redevance
                </summary>
                <div style="position:absolute; right:0; top:100%; background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:1.25rem; width:360px; box-shadow:0 10px 25px rgba(0,0,0,0.18); z-index:50;">
                    <h3 style="font-size:1.05rem; margin-top:0; margin-bottom:1rem; color:#0a2347;">Créer une créance municipale</h3>
                    <form method="post" action="{{ route('revenue.recovery.store') }}">
                        @csrf
                        <div class="field" style="margin-bottom:0.75rem;">
                            <label for="taxpayer_id">Contribuable redevable *</label>
                            <select id="taxpayer_id" name="taxpayer_id" required>
                                <option value="">Sélectionner un contribuable…</option>
                                @foreach($taxpayers as $tp)
                                    <option value="{{ $tp->id }}">{{ $tp->name }} @if($tp->quarter)({{ $tp->quarter }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0.75rem;">
                            <label for="category_id">Nature / Catégorie *</label>
                            <select id="category_id" name="category_id" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0.75rem;">
                            <label for="title">Motif / Redevance *</label>
                            <input id="title" type="text" name="title" required placeholder="Ex. Droit de place étal marché">
                        </div>
                        <div class="field" style="margin-bottom:0.75rem;">
                            <label for="period">Période concernée *</label>
                            <input id="period" type="text" name="period" required placeholder="Ex. Octobre 2026">
                        </div>
                        <div class="field" style="margin-bottom:0.75rem;">
                            <label for="amount_due">Montant attendu (FCFA) *</label>
                            <input id="amount_due" type="number" name="amount_due" min="1" step="1" required>
                        </div>
                        <div class="field" style="margin-bottom:1rem;">
                            <label for="due_date">Date limite de paiement *</label>
                            <input id="due_date" type="date" name="due_date" value="{{ today()->addDays(15)->format('Y-m-d') }}" required>
                        </div>
                        <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">Générer l'avis de recouvrement</button>
                    </form>
                </div>
            </details>
        </div>
    @endcan
</div>

<div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Total émis (Avis)</span>
        <div style="font-size:1.6rem; font-weight:700;">{{ \App\Support\Money::fcfa($totalDue) }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Montant recouvré</span>
        <div style="font-size:1.6rem; font-weight:700; color: #047857;">{{ \App\Support\Money::fcfa($totalPaid) }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Reste à recouvrer (Impayés)</span>
        <div style="font-size:1.6rem; font-weight:700; color: {{ $totalBalance > 0 ? '#dc2626' : '#047857' }};">
            {{ \App\Support\Money::fcfa($totalBalance) }}
        </div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Taux de recouvrement</span>
        <div style="font-size:1.6rem; font-weight:700; color: {{ $recoveryRate >= 80 ? '#047857' : ($recoveryRate >= 50 ? '#d97706' : '#dc2626') }};">
            {{ $recoveryRate }} %
        </div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Avis en retard</span>
        <div style="font-size:1.6rem; font-weight:700; color: {{ $overdueCount > 0 ? '#dc2626' : '#047857' }};">
            {{ $overdueCount }}
        </div>
    </div>
</div>

<div class="panel">
    <form class="filters" method="get">
        <div class="field grow">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Contribuable, téléphone, réf. avis…">
        </div>
        <div class="field">
            <label for="category">Catégorie</label>
            <select id="category" name="category">
                <option value="">Toutes les catégories</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) request('category') === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="quarter">Quartier</label>
            <select id="quarter" name="quarter">
                <option value="">Tous les quartiers</option>
                @foreach($quarters as $q)
                    <option value="{{ $q }}" @selected(request('quarter') === $q)>{{ $q }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="">Tous les statuts</option>
                <option value="overdue" @selected(request('status') === 'overdue')>En retard (Échéance dépassée)</option>
                <option value="unpaid" @selected(request('status') === 'unpaid')>Totalement impayé</option>
                <option value="partial" @selected(request('status') === 'partial')>Paiement partiel</option>
                <option value="paid" @selected(request('status') === 'paid')>Soldé</option>
            </select>
        </div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Réf. Avis</th>
                    <th>Contribuable</th>
                    <th>Quartier</th>
                    <th>Libellé / Période</th>
                    <th>Date d'échéance</th>
                    <th class="r">Montant dû</th>
                    <th class="r">Recouvré</th>
                    <th class="r">Reste dû</th>
                    <th>Statut</th>
                    <th class="r">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($debts as $d)
                <tr>
                    <td class="name num"><b>{{ $d->reference }}</b></td>
                    <td>
                        <a href="{{ route('revenue.taxpayers.show', $d->taxpayer) }}"><b>{{ $d->taxpayer->name }}</b></a>
                        @if($d->taxpayer->phone)<br><small class="muted">{{ $d->taxpayer->phone }}</small>@endif
                    </td>
                    <td>{{ $d->taxpayer->quarter ?? '—' }}</td>
                    <td>{{ $d->title }} <small class="muted">({{ $d->period }})</small></td>
                    <td class="num">
                        {{ $d->due_date->format('d/m/Y') }}
                        @if($d->isOverdue())
                            <br><small style="color:#dc2626; font-weight:bold;">Dépassée</small>
                        @endif
                    </td>
                    <td class="r num">{{ \App\Support\Money::fcfa($d->amount_due) }}</td>
                    <td class="r num" style="color:#047857;">{{ \App\Support\Money::fcfa($d->amount_paid) }}</td>
                    <td class="r num" style="font-weight:700; color:{{ $d->balance > 0 ? '#dc2626' : '#047857' }};">
                        {{ \App\Support\Money::fcfa($d->balance) }}
                    </td>
                    <td>
                        @if($d->isOverdue())
                            <span class="badge bad">En retard</span>
                        @elseif($d->status === 'paid')
                            <span class="badge ok">Soldé</span>
                        @elseif($d->status === 'partial')
                            <span class="badge warning" style="background:#fef3c7; color:#92400e;">Partiel</span>
                        @else
                            <span class="badge info">Non payé</span>
                        @endif
                    </td>
                    <td class="r">
                        <a class="btn btn-quiet btn-sm" href="{{ route('revenue.recovery.print_notice', $d) }}" target="_blank" title="Imprimer l'avis d'imposition / lettre de relance">
                            <x-icon name="print"/> Avis
                        </a>
                        @if($d->balance > 0)
                            <a class="btn btn-primary btn-sm" href="{{ route('revenue.receipts.create') }}?taxpayer_id={{ $d->taxpayer_id }}&amount={{ $d->balance }}" title="Encaisser le règlement">
                                <x-icon name="coins"/> Régler
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">
                        <div class="empty">
                            <b>Aucune redevance enregistrée</b>
                            Modifiez vos critères de recherche ou émettez un nouvel avis de redevance.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $debts->links() }}
</div>
@endsection
