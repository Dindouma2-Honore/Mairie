@extends('layouts.app')
@section('title', 'Catégories & tarifs')
@section('content')
<div class="page-head"><div><h1>Catégories &amp; tarifs</h1><p>Les tarifs ne sont jamais modifiés : un nouveau tarif avec une date d’effet remplace l’ancien, dont l’historique est conservé. Saisissez uniquement les tarifs officiels de la mairie.</p></div></div>

<div class="panel">
    <div class="panel-head"><h2>Nouvelle catégorie</h2></div>
    <form class="panel-body" method="post" action="{{ route('revenue.categories.store') }}">
        @csrf
        <div class="form-grid">
            <x-field name="code" label="Code" required hint="Lettres, chiffres, tirets (ex. DROITS-PLACE)." />
            <x-field name="name" label="Nom" required />
            <div class="span-2"><x-field name="description" label="Description" /></div>
        </div>
        <div class="form-actions" style="margin-top:14px">
            <label class="check"><input type="checkbox" name="allow_custom_amount" value="1" @checked(old('allow_custom_amount'))> Autoriser un montant libre</label>
            <button class="btn btn-primary" type="submit">Créer la catégorie</button>
        </div>
    </form>
</div>

@forelse($categories as $c)
<div class="panel">
    <div class="panel-head">
        <h2>{{ $c->name }} <span class="muted small">{{ $c->code }}</span> @unless($c->is_active)<span class="badge">désactivée</span>@endunless @if($c->allow_custom_amount)<span class="badge info">montant libre</span>@endif</h2>
        <form method="post" action="{{ route('revenue.categories.toggle', $c) }}">@csrf<button class="btn btn-quiet btn-sm" type="submit">{{ $c->is_active ? 'Désactiver' : 'Activer' }}</button></form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Libellé</th><th class="r">Montant</th><th>En vigueur du</th><th>Au</th><th></th></tr></thead>
            <tbody>
            @forelse($c->tariffs as $t)
                <tr>
                    <td class="name">{{ $t->label }}</td>
                    <td class="r num">{{ \App\Support\Money::fcfa($t->amount) }}</td>
                    <td class="num">{{ $t->effective_from->format('d/m/Y') }}</td>
                    <td class="num">{{ $t->effective_to?->format('d/m/Y') ?? 'sans fin' }}</td>
                    <td class="r"><form method="post" action="{{ route('revenue.tariffs.toggle', $t) }}">@csrf<button class="link-btn" type="submit">{{ $t->is_active ? 'Désactiver' : 'Réactiver' }}</button></form></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Aucun tarif pour cette catégorie.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <form class="filters" method="post" action="{{ route('revenue.tariffs.store', $c) }}" style="border-top:1px solid var(--line-2);border-bottom:0">
        @csrf
        <div class="field grow"><label>Libellé</label><input type="text" name="label" required></div>
        <div class="field"><label>Montant (FCFA)</label><input type="number" name="amount" min="0" step="1" required></div>
        <div class="field"><label>Date d’effet</label><input type="date" name="effective_from" value="{{ today()->toDateString() }}" required></div>
        <button class="btn btn-quiet" type="submit"><x-icon name="plus"/> Ajouter un tarif</button>
    </form>
</div>
@empty
<div class="panel"><div class="empty"><b>Aucune catégorie</b>Créez la première catégorie de recette ci-dessus.</div></div>
@endforelse
@endsection
