@extends('layouts.app')
@section('title', 'Emplacement ' . $stall->code . ' — ' . $stall->market->name)
@section('content')
<div class="page-head">
    <div>
        <span class="crumb">
            <a href="{{ route('markets.index') }}">Marchés</a> /
            <a href="{{ route('markets.show', $stall->market) }}">{{ $stall->market->name }}</a> /
        </span>
        <h1>{{ $stall->name }} <span class="muted" style="font-weight:normal;">({{ $stall->code }})</span></h1>
        <p>
            Type : <b>{{ $stall->typeLabel() }}</b> ·
            Secteur : <b>{{ $stall->sector ?? 'Non précisé' }}</b> ·
            Redevance : <b>{{ \App\Support\Money::fcfa($stall->base_fee) }}</b> / {{ $stall->periodicityLabel() }}
        </p>
    </div>
    <div class="actions">
        <a class="btn btn-quiet" href="{{ route('markets.stalls.edit', $stall) }}">
            <x-icon name="edit"/> Modifier
        </a>
        @if($stall->currentOccupant && auth()->user()->hasPermission('revenue.create'))
            <a class="btn btn-primary" href="{{ route('revenue.receipts.create') }}?taxpayer_id={{ $stall->current_occupant_id }}&amount={{ $stall->base_fee }}&market_stall_id={{ $stall->id }}">
                <x-icon name="coins"/> Encaisser droit de place
            </a>
        @endif
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: 360px 1fr; gap: 1.5rem;">
    <div>
        <!-- Fiche Situation de l'Emplacement -->
        <div class="panel" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h2 style="font-size:1.15rem; margin:0;">État de l'emplacement</h2>
                @if($stall->isAvailable())
                    <span class="badge ok">Disponible</span>
                @elseif($stall->isOccupied())
                    <span class="badge info">Occupé</span>
                @else
                    <span class="badge bad">Suspendu</span>
                @endif
            </div>

            @if($stall->isOccupied() && $stall->currentOccupant)
                <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:6px; padding:1rem; margin-bottom:1rem;">
                    <span class="muted" style="font-size:0.85rem;">Occupant officiel actuel :</span>
                    <div style="font-size:1.1rem; font-weight:700; margin-top:2px;">
                        <a href="{{ route('revenue.taxpayers.show', $stall->currentOccupant) }}">
                            {{ $stall->currentOccupant->name }}
                        </a>
                    </div>
                    @if($stall->currentOccupant->phone)
                        <div style="font-size:0.9rem; margin-top:4px;">
                            Téléphone : <a href="tel:{{ $stall->currentOccupant->phone }}">{{ $stall->currentOccupant->phone }}</a>
                        </div>
                    @endif
                    @php $activeOcc = $stall->occupancies->firstWhere('status', 'active'); @endphp
                    @if($activeOcc)
                        <div style="font-size:0.85rem; color:#475569; margin-top:6px; border-top:1px dashed #cbd5e1; pt:4px;">
                            Activité : <b>{{ $activeOcc->activity }}</b><br>
                            Installé depuis le : <b>{{ $activeOcc->start_date->format('d/m/Y') }}</b><br>
                            Redevance convenue : <b>{{ \App\Support\Money::fcfa($activeOcc->agreed_fee) }}</b>
                        </div>
                    @endif

                    <form method="post" action="{{ route('markets.stalls.release', $stall) }}" onsubmit="return confirm('Confirmez-vous la libération de cet emplacement ? L’occupation active sera clôturée.')" style="margin-top:0.75rem;">
                        @csrf
                        <button class="btn btn-danger btn-sm" type="submit" style="width:100%; justify-content:center;">
                            Libérer l'emplacement
                        </button>
                    </form>
                </div>
            @else
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:1rem; margin-bottom:1rem; text-align:center;">
                    <b style="color:#2563eb;">Emplacement libre</b><br>
                    <small class="muted">Disponible pour une nouvelle attribution à un commerçant.</small>
                </div>
            @endif

            <dl style="margin:0; font-size:0.9rem; line-height:1.7;">
                <dt class="muted">Dimensions :</dt>
                <dd style="margin:0 0 0.5rem 0; font-weight:600;">{{ $stall->dimensions ?? 'Non précisées' }}</dd>

                <dt class="muted">Tarif de référence :</dt>
                <dd style="margin:0 0 0.5rem 0; font-weight:600;">{{ \App\Support\Money::fcfa($stall->base_fee) }} ({{ $stall->periodicityLabel() }})</dd>

                @if($stall->notes)
                    <dt class="muted">Notes :</dt>
                    <dd style="margin:0 0 0.5rem 0;">{{ $stall->notes }}</dd>
                @endif
            </dl>
        </div>

        <!-- Formulaire d'Attribution d'occupant (Prévention double occupation) -->
        <div class="panel">
            <h2 style="font-size:1.1rem; margin-top:0; margin-bottom:0.75rem;">
                {{ $stall->isOccupied() ? 'Changer d’occupant' : 'Attribuer cet emplacement' }}
            </h2>

            @if($stall->isOccupied())
                <div style="background:#fffbeb; border:1px solid #fcd34d; padding:0.6rem; border-radius:4px; font-size:0.8rem; color:#92400e; margin-bottom:0.75rem;">
                    <b>Attention :</b> Cet emplacement est actuellement occupé. Une nouvelle attribution mettra automatiquement fin à l'occupation en cours (Annexe B).
                </div>
            @endif

            <form method="post" action="{{ route('markets.stalls.assign', $stall) }}">
                @csrf
                <div class="field" style="margin-bottom:0.75rem;">
                    <label for="taxpayer_id">Commerçant / Contribuable *</label>
                    <select id="taxpayer_id" name="taxpayer_id" required>
                        <option value="">Sélectionner un commerçant…</option>
                        @foreach($taxpayers as $tp)
                            <option value="{{ $tp->id }}">{{ $tp->name }} @if($tp->quarter)({{ $tp->quarter }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-bottom:0.75rem;">
                    <label for="activity">Activité exercée sur place *</label>
                    <input id="activity" type="text" name="activity" required placeholder="Ex. Vente de pagnes, épices…">
                </div>
                <div class="field" style="margin-bottom:0.75rem;">
                    <label for="start_date">Date de début d'occupation *</label>
                    <input id="start_date" type="date" name="start_date" value="{{ today()->format('Y-m-d') }}" required>
                </div>
                <div class="field" style="margin-bottom:0.75rem;">
                    <label for="agreed_fee">Droit de place convenu (FCFA) *</label>
                    <input id="agreed_fee" type="number" name="agreed_fee" value="{{ $stall->base_fee }}" min="0" step="1" required>
                </div>
                <div class="field" style="margin-bottom:1rem;">
                    <label for="authorization_ref">Réf. autorisation municipale</label>
                    <input id="authorization_ref" type="text" name="authorization_ref" placeholder="Ex. AUT-2026-089">
                </div>
                <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">
                    Valider l'attribution
                </button>
            </form>
        </div>
    </div>

    <div>
        <!-- Section Paiements des Droits de Place -->
        <div class="panel" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h2 style="font-size:1.15rem; margin:0;">Droits de place encaissés ({{ $stall->receipts->count() }})</h2>
                @if($stall->currentOccupant && auth()->user()->hasPermission('revenue.create'))
                    <a class="btn btn-primary btn-sm" href="{{ route('revenue.receipts.create') }}?taxpayer_id={{ $stall->current_occupant_id }}&amount={{ $stall->base_fee }}&market_stall_id={{ $stall->id }}">
                        <x-icon name="plus"/> Nouvel encaissement
                    </a>
                @endif
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Reçu</th>
                            <th>Date encaissement</th>
                            <th>Mode</th>
                            <th>Collecteur</th>
                            <th class="r">Montant</th>
                            <th class="r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($stall->receipts as $r)
                        <tr>
                            <td class="name"><a href="{{ route('revenue.receipts.show', $r) }}">{{ $r->number }}</a></td>
                            <td class="num">{{ $r->collected_at->format('d/m/Y H:i') }}</td>
                            <td>{{ config('mairie.payment_methods.' . $r->payment_method, $r->payment_method) }}</td>
                            <td>{{ $r->collector->name }}</td>
                            <td class="r num"><b>{{ \App\Support\Money::fcfa($r->amount) }}</b></td>
                            <td class="r">
                                <a class="btn btn-quiet btn-sm" href="{{ route('revenue.receipts.print', $r) }}" target="_blank">
                                    <x-icon name="print"/>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"><div class="empty"><b>Aucun paiement enregistré</b>Aucun droit de place encaissé pour cet emplacement.</div></td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section Historique des Occupations -->
        <div class="panel">
            <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem;">Historique des attributions ({{ $stall->occupancies->count() }})</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Occupant</th>
                            <th>Activité</th>
                            <th>Période</th>
                            <th class="r">Tarif convenu</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($stall->occupancies as $occ)
                        <tr>
                            <td>
                                <a href="{{ route('revenue.taxpayers.show', $occ->taxpayer) }}">
                                    <b>{{ $occ->taxpayer->name }}</b>
                                </a>
                            </td>
                            <td>{{ $occ->activity }}</td>
                            <td class="num">
                                Du {{ $occ->start_date->format('d/m/Y') }}
                                @if($occ->end_date)
                                    au {{ $occ->end_date->format('d/m/Y') }}
                                @else
                                    <i>(en cours)</i>
                                @endif
                            </td>
                            <td class="r num">{{ \App\Support\Money::fcfa($occ->agreed_fee) }}</td>
                            <td>
                                @if($occ->isActive())
                                    <span class="badge ok">En cours</span>
                                @else
                                    <span class="badge">Clôturé</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="empty"><b>Aucun historique</b>Cet emplacement n'a jamais été attribué.</div></td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
