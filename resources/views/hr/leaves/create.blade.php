@extends('layouts.app')
@section('title', 'Nouvelle demande de congé')
@section('content')
<div class="page-head"><div><h1>Nouvelle demande</h1><p>Congé ou permission d’absence. Cette demande nécessite une connexion internet.</p></div></div>
@if($agents->isEmpty())
    <div class="alert warning"><x-icon name="alert"/><div><b>Aucun dossier d’agent n’est lié à votre compte</b>Demandez au service du personnel de lier votre compte à votre dossier.</div></div>
@else
<form class="panel" method="post" action="{{ route('hr.leaves.store') }}" style="max-width:720px">
    @csrf
    <div class="panel-body stack">
        <x-field name="agent_id" label="Agent" :options="$agents" :value="$agents->count() === 1 ? $agents->keys()->first() : null" required />
        <x-field name="type" label="Type" :options="\App\Models\LeaveRequest::TYPES" required />
        <div class="form-grid">
            <x-field name="start_date" label="Du" type="date" required />
            <x-field name="end_date" label="Au (inclus)" type="date" required />
        </div>
        <x-field name="reason" label="Motif" />
        <div class="form-actions"><button class="btn btn-primary" type="submit">Envoyer la demande</button><a class="btn btn-quiet" href="{{ route('hr.leaves.index') }}">Annuler</a></div>
    </div>
</form>
@endif
@endsection
