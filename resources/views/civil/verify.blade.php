@extends('layouts.guest')
@section('title', 'Vérification d’un acte')
@section('content')
<h2>Vérification d’un document</h2>
<p class="muted">Code contrôlé : <b>{{ $code }}</b></p>
@if($act)
    <div class="alert success"><x-icon name="check"/><div><b>Document authentique</b>Un acte validé correspond à ce code dans les registres de {{ config('mairie.name') }}.</div></div>
    <dl class="dl">
        <dt>Acte n°</dt><dd>{{ $act->registryLabel() }}</dd>
        <dt>Centre</dt><dd>{{ $act->centre->name }}</dd>
        <dt>Enfant</dt><dd>{{ mb_strtoupper($act->child_last_name) }} {{ mb_substr($act->child_first_names, 0, 1) }}.</dd>
        <dt>Validé le</dt><dd>{{ $act->validated_at?->format('d/m/Y') }}</dd>
    </dl>
    <p class="muted small" style="margin-top:12px">Pour protéger les personnes, seules des informations limitées sont affichées. Comparez-les avec le document présenté.</p>
@else
    <div class="alert error"><x-icon name="alert"/><div><b>Aucun acte ne correspond à ce code</b>Vérifiez la saisie. En cas de doute, ne considérez pas le document comme authentique et contactez la mairie.</div></div>
@endif
@endsection
