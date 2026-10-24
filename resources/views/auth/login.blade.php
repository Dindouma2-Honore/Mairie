@extends('layouts.guest')
@section('title', 'Connexion')
@section('content')
<h2>Connexion</h2>
<p class="muted">Utilisez le compte fourni par l’administrateur de la mairie.</p>
<form method="post" action="{{ route('login.attempt') }}" class="stack">
    @csrf
    <x-field name="email" label="Adresse e-mail" type="email" required autocomplete="username" autofocus />
    <x-field name="password" label="Mot de passe" type="password" required autocomplete="current-password" />
    <label class="check"><input type="checkbox" name="remember" value="1"> Rester connecté sur cet appareil</label>
    <button class="btn btn-primary" type="submit">Se connecter</button>
</form>
@endsection
