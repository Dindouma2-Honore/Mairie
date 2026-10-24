@extends('layouts.app')
@section('title', 'Utilisateurs')
@section('content')
<div class="page-head"><div><h1>Utilisateurs</h1><p>Comptes d’accès à l’application. Pas de compte partagé : un compte par personne.</p></div>
    <div class="actions"><a class="btn btn-primary" href="{{ route('admin.users.create') }}"><x-icon name="plus"/> Nouveau compte</a></div></div>
<div class="panel">
    <form class="filters" method="get"><div class="field grow"><label for="q">Recherche</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou e-mail"></div><button class="btn btn-quiet" type="submit"><x-icon name="search"/> Chercher</button></form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Nom</th><th>Rôles</th><th>Service</th><th>Dernière connexion</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        @foreach($users as $u)
            <tr>
                <td class="name">{{ $u->name }}<span class="row-sub">{{ $u->email }}</span></td>
                <td>@foreach($u->roles as $r)<span class="badge info">{{ $r->name }}</span> @endforeach</td>
                <td>{{ $u->service?->name ?? '—' }}</td>
                <td class="num">{{ $u->last_login_at?->format('d/m/Y H:i') ?? 'jamais' }}</td>
                <td>@if($u->is_active)<span class="badge ok">Actif</span>@else<span class="badge">Désactivé</span>@endif @if($u->must_change_password)<span class="badge warn">mot de passe à changer</span>@endif</td>
                <td class="r"><div class="actions" style="justify-content:flex-end">
                    <a href="{{ route('admin.users.edit', $u) }}">Modifier</a>
                    <form method="post" action="{{ route('admin.users.reset', $u) }}" onsubmit="return confirm('Réinitialiser le mot de passe de {{ addslashes($u->name) }} ?')">@csrf<button class="link-btn" type="submit">Réinitialiser</button></form>
                </div></td>
            </tr>
        @endforeach
        </tbody></table></div>
    {{ $users->links() }}
</div>
@endsection
