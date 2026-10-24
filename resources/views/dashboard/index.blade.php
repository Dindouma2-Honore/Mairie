@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('content')
@php $fcfa = fn ($n) => \App\Support\Money::fcfa($n); @endphp
<div class="page-head">
    <div><h1>Bonjour, {{ \Illuminate\Support\Str::before($user->name, ' ') }}</h1><p>{{ now()->locale('fr')->translatedFormat('l j F Y') }}</p></div>
    <div class="actions">
        @can('revenue.create')<a class="btn btn-primary" href="{{ route('revenue.receipts.create') }}"><x-icon name="plus"/> Encaisser</a>@endcan
        @can('civil.birth.create')<a class="btn btn-quiet" href="{{ route('civil.births.create') }}"><x-icon name="plus"/> Déclarer une naissance</a>@endcan
    </div>
</div>

@if(isset($revenue))
<div class="panel">
    <div class="figures">
        <div class="figure"><div class="k">Encaissé aujourd’hui</div><div class="v">{{ $fcfa($revenue['today']) }}</div><div class="k">{{ $revenue['today_count'] }} reçu(s)</div></div>
        <div class="figure"><div class="k">Encaissé ce mois</div><div class="v">{{ $fcfa($revenue['month']) }}</div></div>
        <div class="figure"><div class="k">Reçus annulés (mois)</div><div class="v">{{ $revenue['cancelled'] }}</div></div>
        <div class="figure"><div class="k">Saisis hors ligne (jour)</div><div class="v">{{ $revenue['pending_offline'] }}</div></div>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-head"><h2>Recettes des 14 derniers jours</h2></div>
        <div class="panel-body">
            @php
                $max = max(1, (int) $series->max('value'));
                $w = 560; $h = 170; $pad = 22; $n = $series->count(); $slot = ($w - 2 * $pad) / max(1, $n); $bw = $slot * .62;
            @endphp
            <svg class="chart" viewBox="0 0 {{ $w }} {{ $h + 24 }}" role="img" aria-label="Montants encaissés par jour sur les 14 derniers jours">
                <line x1="{{ $pad }}" y1="{{ $h }}" x2="{{ $w - $pad }}" y2="{{ $h }}"/>
                @foreach($series as $i => $d)
                    @php $bh = $d['value'] > 0 ? max(3, ($d['value'] / $max) * ($h - 14)) : 0; $x = $pad + $i * $slot + ($slot - $bw) / 2; @endphp
                    <rect class="bar {{ $loop->last ? 'today' : '' }}" x="{{ round($x, 1) }}" y="{{ round($h - $bh, 1) }}" width="{{ round($bw, 1) }}" height="{{ round($bh, 1) }}" rx="2"><title>{{ $d['label'] }} : {{ $fcfa($d['value']) }}</title></rect>
                    @if($i % 2 === 0)<text x="{{ round($x + $bw / 2, 1) }}" y="{{ $h + 16 }}" text-anchor="middle">{{ $d['label'] }}</text>@endif
                @endforeach
            </svg>
        </div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Par catégorie (mois)</h2></div>
        <div class="panel-body">
            @php $top = max(1, (int) ($by_category->max('total') ?? 1)); @endphp
            <ul class="bars">
                @forelse($by_category as $c)
                    <li><span>{{ $c->name }}</span><b class="num">{{ $fcfa($c->total) }}</b><span class="track"><i style="width:{{ round($c->total / $top * 100) }}%"></i></span></li>
                @empty
                    <li class="muted">Aucune recette ce mois-ci.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endif

<div class="grid-2" style="margin-top:20px">
    @if(isset($civil))
    <div class="panel"><div class="panel-head"><h2>État civil</h2><a href="{{ route('civil.births.index') }}">Voir les actes</a></div>
        <div class="figures"><div class="figure"><div class="k">Déclarations ce mois</div><div class="v">{{ $civil['month'] }}</div></div><div class="figure"><div class="k">Brouillons à valider</div><div class="v">{{ $civil['drafts'] }}</div></div></div></div>
    @endif
    @if(isset($hr))
    <div class="panel"><div class="panel-head"><h2>Personnel</h2><a href="{{ route('hr.agents.index') }}">Voir les agents</a></div>
        <div class="figures"><div class="figure"><div class="k">Agents actifs</div><div class="v">{{ $hr['agents'] }}</div></div></div></div>
    @endif
    @if(isset($pending_leaves))
    <div class="panel"><div class="panel-head"><h2>Congés à traiter</h2><a href="{{ route('hr.leaves.index', ['status' => 'pending']) }}">Tout voir</a></div>
        <ul class="feed">
            @forelse($pending_leaves as $l)<li><span>{{ $l->agent->fullName() }}</span><span class="muted">{{ $l->start_date->format('d/m') }} → {{ $l->end_date->format('d/m') }}</span></li>@empty<li class="muted">Aucune demande en attente.</li>@endforelse
        </ul></div>
    @endif
    @if(isset($pending_closings) && $pending_closings->isNotEmpty())
    <div class="panel" style="border-left: 4px solid #b45309;"><div class="panel-head"><h2>Clôtures de caisse à viser ({{ $pending_closings->count() }})</h2><a href="{{ route('revenue.closings.index', ['status' => 'pending']) }}">Gérer</a></div>
        <ul class="feed">
            @foreach($pending_closings as $pc)
                <li>
                    <span><a href="{{ route('revenue.closings.show', $pc) }}"><b>{{ $pc->reference }}</b></a> · {{ $pc->collector->name }}</span>
                    <b class="num">{{ $fcfa($pc->total_amount) }}</b>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    @if(isset($recovery_stats))
    <div class="panel"><div class="panel-head"><h2>Recouvrement & Impayés</h2><a href="{{ route('revenue.recovery.index') }}">Détails</a></div>
        <div class="figures">
            <div class="figure"><div class="k">Impayés à recouvrer</div><div class="v" style="color:{{ $recovery_stats['total_balance'] > 0 ? '#dc2626' : '#047857' }}">{{ $fcfa($recovery_stats['total_balance']) }}</div></div>
            <div class="figure"><div class="k">Avis en retard</div><div class="v" style="color:{{ $recovery_stats['overdue_count'] > 0 ? '#dc2626' : '#047857' }}">{{ $recovery_stats['overdue_count'] }}</div></div>
        </div>
    </div>
    @endif
    @if(isset($activity))
    <div class="panel"><div class="panel-head"><h2>Activité récente</h2><a href="{{ route('admin.audit.index') }}">Journal</a></div>
        <ul class="feed">
            @foreach($activity as $a)<li><span>{{ $a->user?->name ?? 'Système' }} · <code>{{ $a->event }}</code></span><span class="muted">{{ $a->created_at->diffForHumans() }}</span></li>@endforeach
        </ul></div>
    @endif
</div>

@if(! isset($revenue) && ! isset($civil) && ! isset($hr) && ! isset($pending_leaves) && ! isset($activity))
    <div class="panel"><div class="empty"><b>Bienvenue</b>Utilisez le menu pour accéder aux modules qui vous sont ouverts.</div></div>
@endif
@endsection
