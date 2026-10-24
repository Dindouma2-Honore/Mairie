@extends('layouts.app')
@section('title', 'Mot de passe')
@section('content')
<div class="page-head"><div><h1>Changer le mot de passe</h1><p>Au moins 8 caractères, avec des lettres et des chiffres.</p></div></div>
<form method="post" action="{{ route('password.update') }}" class="panel" style="max-width:520px">
    @csrf @method('PUT')
    <div class="panel-body stack">
        <x-field name="current_password" label="Mot de passe actuel" type="password" required autocomplete="current-password" />
        <x-field name="password" label="Nouveau mot de passe" type="password" required autocomplete="new-password" />
        <x-field name="password_confirmation" label="Confirmer le nouveau mot de passe" type="password" required autocomplete="new-password" />
        <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
    </div>
</form>
@endsection
