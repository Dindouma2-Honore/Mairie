@extends('layouts.app')
@section('title', 'Reçus émis')
@section('content')
<div class="page-head">
    <div><h1>Reçus émis</h1><p>Total des reçus valides de la sélection : <b class="num">{{ \App\Support\Money::fcfa($total) }}</b></p></div>
    @can('revenue.create')<div class="actions"><a class="btn btn-primary" href="{{ route('revenue.receipts.create') }}"><x-icon name="plus"/> Encaisser</a></div>@endcan
</div>
<div class="panel">
    <form class="filters" method="get">
        <div class="field grow"><label for="q">Recherche</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="N° de reçu, payeur…"></div>
        <div class="field"><label for="category">Catégorie</label><select id="category" name="category"><option value="">Toutes</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected((string) request('category') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="field"><label for="status">Statut</label><select id="status" name="status"><option value="">Tous</option><option value="paid" @selected(request('status') === 'paid')>Valides</option><option value="cancelled" @selected(request('status') === 'cancelled')>Annulés</option></select></div>
        <div class="field"><label for="from">Du</label><input id="from" type="date" name="from" value="{{ request('from') }}"></div>
        <div class="field"><label for="to">Au</label><input id="to" type="date" name="to" value="{{ request('to') }}"></div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>N°</th><th>Date</th><th>Catégorie</th><th>Payeur</th><th>Collecteur</th><th class="r">Montant</th><th>Statut</th></tr></thead>
            <tbody>
            @forelse($receipts as $r)
                <tr>
                    <td class="name"><a href="{{ route('revenue.receipts.show', $r) }}">{{ $r->number }}</a> @if($r->created_offline)<span class="badge info" title="Saisi hors ligne">hors ligne</span>@endif</td>
                    <td class="num">{{ $r->collected_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->category->name }}</td>
                    <td>{{ $r->payerLabel() }}</td>
                    <td>{{ $r->collector->name }}</td>
                    <td class="r num">{{ \App\Support\Money::fcfa($r->amount) }}</td>
                    <td>@if($r->isCancelled())<span class="badge bad">Annulé</span>@else<span class="badge ok">Valide</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty"><b>Aucun reçu</b>Modifiez les filtres ou émettez un premier reçu.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $receipts->links() }}
</div>
@endsection
