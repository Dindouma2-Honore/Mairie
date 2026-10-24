@extends('layouts.app')
@section('title', 'Actes de naissance')
@section('content')
<div class="page-head"><div><h1>Actes de naissance</h1><p>Brouillons, actes validés et délivrances.</p></div>
    @can('civil.birth.create')<div class="actions"><a class="btn btn-primary" href="{{ route('civil.births.create') }}"><x-icon name="plus"/> Déclarer une naissance</a></div>@endcan</div>
<div class="panel">
    <form class="filters" method="get">
        <div class="field grow"><label for="q">Recherche</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom de l’enfant, des parents, code de vérification"></div>
        <div class="field"><label for="centre">Centre</label><select id="centre" name="centre"><option value="">Tous</option>@foreach($centres as $c)<option value="{{ $c->id }}" @selected((string) request('centre') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="field"><label for="status">Statut</label><select id="status" name="status"><option value="">Tous</option><option value="draft" @selected(request('status') === 'draft')>Brouillons</option><option value="validated" @selected(request('status') === 'validated')>Validés</option></select></div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>N° d’acte</th><th>Enfant</th><th>Naissance</th><th>Mère</th><th>Centre</th><th>Statut</th></tr></thead>
        <tbody>
        @forelse($acts as $a)
            <tr>
                <td class="num">{{ $a->registryLabel() ?? '—' }}</td>
                <td class="name"><a href="{{ route('civil.births.show', $a) }}">{{ $a->fullName() }}</a></td>
                <td class="num">{{ $a->birthDateLabel() }}</td>
                <td>{{ $a->mother_name }}</td>
                <td>{{ $a->centre->name }}</td>
                <td>@if($a->isValidated())<span class="badge ok">Validé</span>@else<span class="badge warn">Brouillon</span>@endif @if($a->created_offline)<span class="badge info">hors ligne</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><b>Aucun acte</b>Modifiez la recherche ou déclarez une naissance.</div></td></tr>
        @endforelse
        </tbody></table></div>
    {{ $acts->links() }}
</div>
@endsection
