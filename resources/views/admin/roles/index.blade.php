@extends('layouts.app')
@section('title', 'Rôles & droits')
@section('content')
<div class="page-head"><div><h1>Rôles &amp; droits</h1><p>Matrice des habilitations (lecture seule). Elle est versionnée dans le code et à faire valider par la mairie ; chaque utilisateur ne reçoit que les droits nécessaires.</p></div></div>
<div class="panel"><div class="table-wrap"><table class="table">
    <thead><tr><th>Droit</th>@foreach($roles as $r)<th style="text-align:center" title="{{ $r->description }}">{{ $r->name }}<span class="row-sub">{{ $r->users_count }} compte(s)</span></th>@endforeach</tr></thead>
    <tbody>
    @foreach($permissions as $module => $list)
        <tr><td colspan="{{ $roles->count() + 1 }}" style="background:var(--paper);font-weight:650;text-transform:capitalize">{{ $module }}</td></tr>
        @foreach($list as $p)
            <tr><td>{{ $p->label }}<span class="row-sub"><code>{{ $p->slug }}</code></span></td>
                @foreach($roles as $r)<td style="text-align:center">@if($r->permissions->contains('slug', $p->slug))<x-icon name="check" style="color:var(--leaf)" aria-label="Accordé"/>@else<span class="muted">—</span>@endif</td>@endforeach</tr>
        @endforeach
    @endforeach
    </tbody></table></div></div>
@endsection
