@extends('layouts.app')
@section('title', 'Guichet des demandes d’actes')
@section('content')
<div class="page-head">
    <div>
        <h1>Guichet des demandes & délivrance d'actes</h1>
        <p>Traitement des demandes d’extraits et de copies d’état civil (Annexe A.4).</p>
    </div>
    @can('civil.birth.create')
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('civil.requests.create') }}">
                <x-icon name="plus"/> Nouvelle demande au guichet
            </a>
        </div>
    @endcan
</div>

<div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">En attente</span>
        <div style="font-size:1.6rem; font-weight:700;">{{ $stats['pending'] }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">En cours d’instruction</span>
        <div style="font-size:1.6rem; font-weight:700; color:#d97706;">{{ $stats['processing'] }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Prêts à retirer</span>
        <div style="font-size:1.6rem; font-weight:700; color:#2563eb;">{{ $stats['ready'] }}</div>
    </div>
    <div class="panel" style="padding: 1.25rem;">
        <span class="muted" style="font-size:0.875rem;">Délivrés ce mois</span>
        <div style="font-size:1.6rem; font-weight:700; color:#047857;">{{ $stats['delivered_this_month'] }}</div>
    </div>
</div>

<div class="panel">
    <form class="filters" method="get">
        <div class="field grow">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Réf demande, nom demandeur, personne concernée…">
        </div>
        <div class="field">
            <label for="act_type">Type d'acte</label>
            <select id="act_type" name="act_type">
                <option value="">Tous les types</option>
                <option value="birth" @selected(request('act_type') === 'birth')>Naissance</option>
                <option value="marriage" @selected(request('act_type') === 'marriage')>Mariage</option>
                <option value="death" @selected(request('act_type') === 'death')>Décès</option>
            </select>
        </div>
        <div class="field">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="">Tous les statuts</option>
                <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                <option value="processing" @selected(request('status') === 'processing')>En cours</option>
                <option value="ready" @selected(request('status') === 'ready')>Prêt à retirer</option>
                <option value="delivered" @selected(request('status') === 'delivered')>Délivré / Remis</option>
            </select>
        </div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Réf. Demande</th>
                    <th>Document demandé</th>
                    <th>Personne concernée</th>
                    <th>Demandeur</th>
                    <th>Date demande</th>
                    <th class="r">Exemplaires</th>
                    <th>Statut</th>
                    <th class="r">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($requests as $r)
                <tr>
                    <td class="name num">
                        <a href="{{ route('civil.requests.show', $r) }}"><b>{{ $r->reference }}</b></a>
                    </td>
                    <td>
                        {{ $r->docTypeLabel() }}<br>
                        <small class="muted">{{ $r->actTypeLabel() }}</small>
                    </td>
                    <td><b>{{ $r->person_concerned }}</b></td>
                    <td>
                        {{ $r->requester_name }}
                        @if($r->requester_phone)<br><small class="muted">{{ $r->requester_phone }}</small>@endif
                    </td>
                    <td class="num">{{ $r->request_date->format('d/m/Y') }}</td>
                    <td class="r num">{{ $r->copies_count }}</td>
                    <td>
                        @if($r->isDelivered())
                            <span class="badge ok">Délivré</span>
                        @elseif($r->isReady())
                            <span class="badge info">Prêt à retirer</span>
                        @elseif($r->status === 'processing')
                            <span class="badge warning" style="background:#fef3c7; color:#92400e;">En cours</span>
                        @else
                            <span class="badge">En attente</span>
                        @endif
                    </td>
                    <td class="r">
                        <a class="btn btn-quiet btn-sm" href="{{ route('civil.requests.show', $r) }}">Gérer</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="empty">
                            <b>Aucune demande enregistrée</b>
                            Saisissez les demandes d'extraits et de copies formulées par les usagers.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}
</div>
@endsection
