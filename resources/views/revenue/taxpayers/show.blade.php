@extends('layouts.app')
@section('title', 'Fiche contribuable : ' . $taxpayer->name)
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('revenue.taxpayers.index') }}">Contribuables</a> /</span>
        <h1>{{ $taxpayer->name }}</h1>
        <p>
            @if($taxpayer->reference)<span class="muted">Réf. <b>{{ $taxpayer->reference }}</b> · </span>@endif
            Type : <b>{{ ucfirst($taxpayer->type) }}</b> ·
            Quartier : <b>{{ $taxpayer->quarter ?? 'Non précisé' }}</b>
            @if(! $taxpayer->is_active)<span class="badge bad">Désactivé</span>@endif
        </p>
    </div>
    <div class="actions">
        <a class="btn btn-quiet" href="{{ route('revenue.taxpayers.edit', $taxpayer) }}">
            <x-icon name="edit"/> Modifier
        </a>
        @can('revenue.create')
            <a class="btn btn-primary" href="{{ route('revenue.receipts.create') }}?taxpayer_id={{ $taxpayer->id }}">
                <x-icon name="plus"/> Encaisser
            </a>
        @endcan
    </div>
</div>

<div class="stats-grid" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Total des paiements effectués</span>
        <div style="font-size:1.6rem; font-weight:700; color: #047857;">{{ \App\Support\Money::fcfa($totalPaid) }}</div>
        <small class="muted">{{ $taxpayer->receipts->where('status', 'paid')->count() }} reçu(s) valides</small>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Total des redevances dues</span>
        <div style="font-size:1.6rem; font-weight:700; color: #0f172a;">{{ \App\Support\Money::fcfa($totalDue) }}</div>
        <small class="muted">{{ $taxpayer->debts->count() }} avis / redevances</small>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Reste à payer (Solde débiteur)</span>
        <div style="font-size:1.6rem; font-weight:700; color: {{ $totalBalance > 0 ? '#dc2626' : '#047857' }};">
            {{ \App\Support\Money::fcfa($totalBalance) }}
        </div>
        <small class="muted">{{ $totalBalance > 0 ? 'Impayés à recouvrer' : 'Situation fiscale à jour' }}</small>
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: 340px 1fr; gap: 1.5rem; margin-bottom:1.5rem;">
    <div class="panel">
        <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem;">Informations du contribuable</h2>
        <dl style="margin:0; font-size:0.95rem; line-height:1.8;">
            <dt class="muted" style="font-size:0.85rem;">Activité / Profession :</dt>
            <dd style="margin:0 0 0.5rem 0; font-weight:600;">{{ $taxpayer->activity ?? 'Non renseignée' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Téléphone :</dt>
            <dd style="margin:0 0 0.5rem 0; font-weight:600;">
                @if($taxpayer->phone)
                    <a href="tel:{{ $taxpayer->phone }}">{{ $taxpayer->phone }}</a>
                @else
                    <span class="muted">Non renseigné</span>
                @endif
            </dd>

            <dt class="muted" style="font-size:0.85rem;">Quartier / Repère :</dt>
            <dd style="margin:0 0 0.5rem 0; font-weight:600;">{{ $taxpayer->quarter ?? 'Non renseigné' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Localisation GPS :</dt>
            <dd style="margin:0 0 0.5rem 0;">
                @if($taxpayer->latitude && $taxpayer->longitude)
                    <span class="num">{{ number_format($taxpayer->latitude, 5) }}, {{ number_format($taxpayer->longitude, 5) }}</span>
                    <br>
                    <a href="https://www.openstreetmap.org/?mlat={{ $taxpayer->latitude }}&mlon={{ $taxpayer->longitude }}#map=16/{{ $taxpayer->latitude }}/{{ $taxpayer->longitude }}" target="_blank" class="btn btn-quiet btn-sm" style="margin-top:0.35rem; display:inline-flex;">
                        <x-icon name="map"/> Voir sur la carte
                    </a>
                @else
                    <span class="muted">Aucune position GPS enregistrée</span>
                @endif
            </dd>
        </dl>
    </div>

    <div>
        <!-- Section Redevances & Recouvrement -->
        <div class="panel" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h2 style="font-size:1.15rem; margin:0;">Redevances & Avis de paiement</h2>
                @can('revenue.recovery')
                    <details class="user" style="position:relative;">
                        <summary class="btn btn-quiet btn-sm" style="list-style:none; cursor:pointer;">
                            <x-icon name="plus"/> Nouvelle redevance
                        </summary>
                        <div style="position:absolute; right:0; top:100%; background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:1.25rem; width:340px; box-shadow:0 8px 20px rgba(0,0,0,0.15); z-index:50;">
                            <h3 style="font-size:1rem; margin-top:0; margin-bottom:1rem;">Émettre une redevance</h3>
                            <form method="post" action="{{ route('revenue.recovery.store') }}">
                                @csrf
                                <input type="hidden" name="taxpayer_id" value="{{ $taxpayer->id }}">
                                
                                <div class="field" style="margin-bottom:0.75rem;">
                                    <label for="category_id">Catégorie de recette *</label>
                                    <select id="category_id" name="category_id" required>
                                        @foreach(\App\Models\RevenueCategory::where('is_active', true)->orderBy('name')->get() as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field" style="margin-bottom:0.75rem;">
                                    <label for="title">Libellé / Objet *</label>
                                    <input id="title" type="text" name="title" required placeholder="Ex. Droit de place Octobre 2026">
                                </div>
                                <div class="field" style="margin-bottom:0.75rem;">
                                    <label for="period">Période concernée *</label>
                                    <input id="period" type="text" name="period" required placeholder="Ex. 2026-10 ou Année 2026">
                                </div>
                                <div class="field" style="margin-bottom:0.75rem;">
                                    <label for="amount_due">Montant dû (FCFA) *</label>
                                    <input id="amount_due" type="number" name="amount_due" min="1" step="1" required>
                                </div>
                                <div class="field" style="margin-bottom:1rem;">
                                    <label for="due_date">Date d'échéance *</label>
                                    <input id="due_date" type="date" name="due_date" value="{{ today()->addMonth()->format('Y-m-d') }}" required>
                                </div>
                                <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">Créer l'avis de paiement</button>
                            </form>
                        </div>
                    </details>
                @endcan
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Réf. Avis</th>
                            <th>Libellé / Période</th>
                            <th>Échéance</th>
                            <th class="r">Montant dû</th>
                            <th class="r">Payé</th>
                            <th class="r">Reste</th>
                            <th>Statut</th>
                            <th class="r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($taxpayer->debts as $d)
                        <tr>
                            <td class="name num"><b>{{ $d->reference }}</b></td>
                            <td>{{ $d->title }} <small class="muted">({{ $d->period }})</small></td>
                            <td class="num">{{ $d->due_date->format('d/m/Y') }}</td>
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
                                    <span class="badge info">Impayé</span>
                                @endif
                            </td>
                            <td class="r">
                                <a class="btn btn-quiet btn-sm" href="{{ route('revenue.recovery.print_notice', $d) }}" target="_blank" title="Imprimer l'avis de paiement">
                                    <x-icon name="print"/> Avis
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"><div class="empty"><b>Aucune redevance enregistrée</b>Aucun impayé au dossier.</div></td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section Historique des Reçus -->
        <div class="panel">
            <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem;">Historique des reçus émis ({{ $taxpayer->receipts->count() }})</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Reçu</th>
                            <th>Date</th>
                            <th>Catégorie</th>
                            <th>Mode</th>
                            <th class="r">Montant</th>
                            <th>Statut</th>
                            <th class="r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($taxpayer->receipts as $r)
                        <tr>
                            <td class="name"><a href="{{ route('revenue.receipts.show', $r) }}">{{ $r->number }}</a></td>
                            <td class="num">{{ $r->collected_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $r->category->name }}</td>
                            <td>{{ config('mairie.payment_methods.' . $r->payment_method, $r->payment_method) }}</td>
                            <td class="r num"><b>{{ \App\Support\Money::fcfa($r->amount) }}</b></td>
                            <td>
                                @if($r->isCancelled())
                                    <span class="badge bad">Annulé</span>
                                @else
                                    <span class="badge ok">Payé</span>
                                @endif
                            </td>
                            <td class="r">
                                <a class="btn btn-quiet btn-sm" href="{{ route('revenue.receipts.print', $r) }}" target="_blank" title="Imprimer">
                                    <x-icon name="print"/>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><div class="empty"><b>Aucun reçu</b>Aucun paiement enregistré pour ce contribuable.</div></td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
