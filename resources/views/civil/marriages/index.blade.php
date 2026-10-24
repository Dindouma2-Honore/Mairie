@extends('layouts.app')
@section('title', 'Actes de mariage')
@section('content')
<div class="page-head">
    <div>
        <h1>Actes de mariage</h1>
        <p>Registre officiel des mariages célébrés à la Mairie de Moundou (Annexe A.2).</p>
    </div>
    @can('civil.birth.create')
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('civil.marriages.create') }}">
                <x-icon name="plus"/> Célébrer un mariage
            </a>
        </div>
    @endcan
</div>

<div class="panel">
    <form class="filters" method="get">
        <div class="field grow">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom d’un conjoint, code de vérification…">
        </div>
        <div class="field">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="">Tous les statuts</option>
                <option value="draft" @selected(request('status') === 'draft')>Brouillon</option>
                <option value="validated" @selected(request('status') === 'validated')>Validé</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Annulé</option>
            </select>
        </div>
        @if($centres->isNotEmpty())
            <div class="field">
                <label for="centre">Centre d'état civil</label>
                <select id="centre" name="centre">
                    <option value="">Tous les centres</option>
                    @foreach($centres as $c)
                        <option value="{{ $c->id }}" @selected((string) request('centre') === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Registre</th>
                    <th>Époux (M.)</th>
                    <th>Épouse (Mme)</th>
                    <th>Date célébration</th>
                    <th>Centre</th>
                    <th>Statut</th>
                    <th class="r">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($acts as $a)
                <tr>
                    <td class="name num">
                        <a href="{{ route('civil.marriages.show', $a) }}">
                            <b>{{ $a->registry_number ? $a->registryLabel() : 'Brouillon' }}</b>
                        </a>
                    </td>
                    <td><b>{{ $a->husband_first_names }} {{ $a->husband_last_name }}</b></td>
                    <td><b>{{ $a->wife_first_names }} {{ $a->wife_last_name }}</b></td>
                    <td class="num">{{ $a->celebration_date->format('d/m/Y') }}</td>
                    <td>{{ $a->centre->name }}</td>
                    <td>
                        @if($a->isValidated())
                            <span class="badge ok">Validé</span>
                        @elseif($a->isCancelled())
                            <span class="badge bad">Annulé</span>
                        @else
                            <span class="badge warning" style="background:#fef3c7; color:#92400e;">Brouillon</span>
                        @endif
                    </td>
                    <td class="r">
                        <a class="btn btn-quiet btn-sm" href="{{ route('civil.marriages.show', $a) }}">Détails</a>
                        @if($a->isValidated())
                            <a class="btn btn-quiet btn-sm" href="{{ route('civil.marriages.print', $a) }}" target="_blank" title="Imprimer le formulaire officiel à 3 volets">
                                <x-icon name="print"/> Acte
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty">
                            <b>Aucun acte de mariage enregistré</b>
                            Modifiez vos critères ou saisissez une nouvelle déclaration de mariage.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $acts->links() }}
</div>
@endsection
