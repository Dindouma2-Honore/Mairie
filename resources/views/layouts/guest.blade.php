<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a2347">
    <title>@yield('title', 'Connexion') — {{ config('mairie.short_name') }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body data-user="">
@include('partials.icons')
<div class="guest">
    <section class="guest-side" aria-hidden="true">
        <div class="brand" style="border:0;padding:0">
            <div class="brand-mark">M</div>
            <div><b>{{ config('mairie.short_name') }}</b><small>{{ config('mairie.name') }}</small></div>
        </div>
        <div>
            <h1>Les services de la mairie, au même endroit.</h1>
            <p>Encaissements, état civil et personnel. L’application continue de fonctionner quand la connexion s’interrompt.</p>
        </div>
        <small>{{ config('mairie.country') }}</small>
        <span class="stripe"></span>
    </section>
    <main class="guest-form"><div class="box">@include('partials.flash') @yield('content')</div></main>
</div>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
