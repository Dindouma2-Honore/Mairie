@extends('layouts.app')
@section('title', 'Agents')
@section('content')
<div class="page-head"><div><h1>Agents municipaux</h1><p>Dossiers administratifs du personnel.</p></div>
    @can('hr.manage')<div class="actions"><a class="btn btn-primary" href="{{ route('hr.agents.create') }}"><x-icon name="plus"/> Nouvel agent</a></div>@endcan</div>
<div class="panel">
    <form class="filters" method="get">
        <div class="field grow"><label for="q">Recherche</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, prénoms, matricule"></div>
        <div class="field"><label for="service">Service</label><select id="service" name="service"><option value="">Tous</option>@foreach($services as $s)<option value="{{ $s->id }}" @selected((string) request('service') === (string) $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="field"><label for="status">Statut</label><select id="status" name="status"><option value="">Tous</option>@foreach(\App\Models\Agent::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Matricule</th><th>Agent</th><th>Service</th><th>Poste</th><th>Statut</th></tr></thead>
        <tbody>
        @forelse($agents as $a)
            <tr><td class="num">{{ $a->matricule }}</td><td class="name"><a href="{{ route('hr.agents.show', $a) }}">{{ $a->fullName() }}</a> @unless($a->is_active)<span class="badge">inactif</span>@endunless</td><td>{{ $a->service?->name ?? '—' }}</td><td>{{ $a->position ?? '—' }}</td><td>{{ \App\Models\Agent::STATUSES[$a->status] ?? $a->status }}</td></tr>
        @empty
            <tr><td colspan="5"><div class="empty"><b>Aucun agent</b></div></td></tr>
        @endforelse
        </tbody></table></div>
    {{ $agents->links() }}
</div>
@endsection
