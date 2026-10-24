@extends('layouts.app')
@section('title', 'Journal d’audit')
@section('content')
<div class="page-head"><div><h1>Journal d’audit</h1><p>Qui a fait quoi, quand. Ce journal est en ajout seul : il ne peut pas être modifié depuis l’application.</p></div></div>
<div class="panel">
    <form class="filters" method="get">
        <div class="field"><label for="user">Utilisateur</label><select id="user" name="user"><option value="">Tous</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>@endforeach</select></div>
        <div class="field grow"><label for="event">Évènement</label><input id="event" type="text" name="event" value="{{ request('event') }}" placeholder="ex. receipt, auth, user.roles"></div>
        <div class="field"><label for="from">Du</label><input id="from" type="date" name="from" value="{{ request('from') }}"></div>
        <div class="field"><label for="to">Au</label><input id="to" type="date" name="to" value="{{ request('to') }}"></div>
        <button class="btn btn-quiet" type="submit"><x-icon name="search"/> Filtrer</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Date</th><th>Utilisateur</th><th>Évènement</th><th>Objet</th><th>Détail</th><th>IP</th></tr></thead>
        <tbody>
        @forelse($logs as $l)
            <tr>
                <td class="num">{{ $l->created_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $l->user?->name ?? 'Système' }}</td>
                <td><code>{{ $l->event }}</code></td>
                <td>{{ $l->subject_type ? class_basename($l->subject_type) . ' #' . $l->subject_id : '—' }}</td>
                <td class="small">@if($l->new_values)<details><summary>Voir</summary><pre style="white-space:pre-wrap;margin:6px 0 0;font-size:.78rem">{{ json_encode(['avant' => $l->old_values, 'après' => $l->new_values], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre></details>@else — @endif</td>
                <td class="muted small">{{ $l->ip }}</td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><b>Aucun évènement</b></div></td></tr>
        @endforelse
        </tbody></table></div>
    {{ $logs->links() }}
</div>
@endsection
