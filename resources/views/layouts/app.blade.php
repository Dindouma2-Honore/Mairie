<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a2347">
    <title>@yield('title', 'Tableau de bord') — {{ config('mairie.short_name') }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body data-user="{{ auth()->id() }}"
      data-url-sync="{{ route('offline.sync', [], false) }}"
      data-url-csrf="{{ route('offline.csrf', [], false) }}"
      data-url-blocks="{{ route('offline.blocks', [], false) }}"
      data-url-pages="{{ route('offline.urls', [], false) }}"
      data-url-queue="{{ route('sync.index', [], false) }}"
      data-url-login="{{ route('login', [], false) }}"
      data-block-low="{{ config('mairie.receipt_block_low_watermark') }}"
      data-collector="{{ auth()->user()->hasPermission('revenue.create') ? 1 : 0 }}"
      data-mairie="{{ config('mairie.name') }}">
@include('partials.icons')
<a class="skip" href="#main">Aller au contenu</a>

<div class="app">
    @include('partials.sidebar')

    <div class="main">
        <header class="topbar">
            <button class="burger" type="button" id="burger" aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false"><x-icon name="menu"/></button>
            <span class="crumb">@yield('title', 'Tableau de bord')</span>
            <span class="spacer"></span>

            <a class="conn" id="conn" href="{{ route('sync.index') }}" title="État de la connexion et saisies en attente">
                <i aria-hidden="true"></i><span class="conn-text">En ligne</span><span class="count" id="conn-count" hidden>0</span>
            </a>

            <details class="user">
                <summary aria-label="Menu du compte">
                    <span class="avatar" aria-hidden="true">{{ auth()->user()->initials() }}</span>
                    <span class="who-name">{{ auth()->user()->name }}</span>
                </summary>
                <div class="menu">
                    <div class="who"><b>{{ auth()->user()->name }}</b><br><span class="muted">{{ auth()->user()->email }}</span></div>
                    <a href="{{ route('password.edit') }}"><x-icon name="key"/> Changer le mot de passe</a>
                    <form method="post" action="{{ route('logout') }}" data-logout>
                        @csrf
                        <button type="submit"><x-icon name="logout"/> Se déconnecter</button>
                    </form>
                </div>
            </details>
        </header>

        <main class="content" id="main">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<div class="toasts" id="toasts" role="status" aria-live="polite"></div>
@stack('scripts')
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
