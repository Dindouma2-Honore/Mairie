@extends('layouts.app')
@section('title', 'Contribuables')
@section('content')
<div class="page-head"><div><h1>Contribuables</h1><p>Personnes et entreprises redevables (commerçants, transporteurs, occupants du domaine public…).</p></div>
    <div class="actions"><a class="btn btn-primary" href="{{ route('revenue.taxpayers.create') }}"><x-icon name="plus"/> Nouveau contribuable</a></div></div>
<div class="panel">
    <form class="filters" method="get"><div class="field grow"><label for="q">Recherche</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, référence, téléphone"></div><button class="btn btn-quiet" type="submit"><x-icon name="search"/> Chercher</button></form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Nom</th><th>Référence</th><th>Quartier / repère</th><th>Activité</th><th>Téléphone</th><th class="r">Reçus</th><th></th></tr></thead>
        <tbody>
        @forelse($taxpayers as $t)
            <tr>
                <td class="name">
                    <a href="{{ route('revenue.taxpayers.show', $t) }}"><b>{{ $t->name }}</b></a>
                    @unless($t->is_active)<span class="badge bad">inactif</span>@endunless
                </td>
                <td>{{ $t->reference ?? '—' }}</td>
                <td>{{ $t->quarter ?? '—' }}</td>
                <td>{{ $t->activity ?? '—' }}</td>
                <td>{{ $t->phone ?? '—' }}</td>
                <td class="r num">{{ $t->receipts_count }}</td>
                <td class="r">
                    <a class="btn btn-quiet btn-sm" href="{{ route('revenue.taxpayers.show', $t) }}">Fiche</a>
                    <a class="btn btn-quiet btn-sm" href="{{ route('revenue.taxpayers.edit', $t) }}">Modifier</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty"><b>Aucun contribuable</b></div></td></tr>
        @endforelse
        </tbody></table></div>
    {{ $taxpayers->links() }}
</div>
@endsection
