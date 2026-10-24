@extends('layouts.app')
@section('title', 'Synchronisation')
@section('content')
<div class="page-head">
    <div><h1>Synchronisation</h1><p>Saisies faites sur cet appareil, avec ou sans connexion.</p></div>
</div>
@if(request('saved'))
    <div class="alert warning" role="status"><x-icon name="sync"/><div><b>Saisie gardée sur cet appareil</b>Elle sera envoyée automatiquement dès que la connexion revient. Ne videz pas les données du navigateur d’ici là.</div></div>
@endif
<div id="queue-root"><div class="panel"><div class="empty">Chargement…</div></div></div>
@endsection
