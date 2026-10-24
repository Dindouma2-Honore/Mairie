@extends('layouts.app')
@section('title', $agent->fullName())
@section('content')
<div class="page-head"><div><h1>{{ $agent->fullName() }} @unless($agent->is_active)<span class="badge">inactif</span>@endunless</h1><p>Matricule {{ $agent->matricule }}@if($agent->service) · {{ $agent->service->name }}@endif</p></div>
    @can('hr.manage')<div class="actions"><a class="btn btn-quiet" href="{{ route('hr.agents.edit', $agent) }}">Modifier</a></div>@endcan</div>
<div class="grid-2">
    <div class="panel"><div class="panel-head"><h2>Dossier</h2></div><div class="panel-body"><dl class="dl">
        <dt>Statut</dt><dd>{{ \App\Models\Agent::STATUSES[$agent->status] ?? $agent->status }}</dd>
        <dt>Poste</dt><dd>{{ $agent->position ?: '—' }}</dd><dt>Grade</dt><dd>{{ $agent->grade ?: '—' }}</dd>
        <dt>Embauché le</dt><dd>{{ $agent->hire_date?->format('d/m/Y') ?? '—' }}</dd>
        @if($agent->end_date)<dt>Fin</dt><dd>{{ $agent->end_date->format('d/m/Y') }}</dd>@endif
        <dt>Naissance</dt><dd>{{ $agent->birth_date?->format('d/m/Y') ?? '—' }}@if($agent->birth_place) à {{ $agent->birth_place }}@endif</dd>
        <dt>Téléphone</dt><dd>{{ $agent->phone ?: '—' }}</dd><dt>E-mail</dt><dd>{{ $agent->email ?: '—' }}</dd><dt>Adresse</dt><dd>{{ $agent->address ?: '—' }}</dd>
    </dl></div></div>
    <div class="panel"><div class="panel-head"><h2>Derniers congés</h2></div>
        <ul class="feed">
            @forelse($agent->leaves as $l)
                <li><span>{{ \App\Models\LeaveRequest::TYPES[$l->type] ?? $l->type }}<br><span class="muted small">{{ $l->start_date->format('d/m/Y') }} → {{ $l->end_date->format('d/m/Y') }} ({{ $l->days }} j)</span></span>
                    <span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad', 'pending' => 'warn'][$l->status] ?? '' }}">{{ \App\Models\LeaveRequest::STATUSES[$l->status] ?? $l->status }}</span></li>
            @empty
                <li class="muted">Aucune demande.</li>
            @endforelse
        </ul></div>
</div>
@endsection
