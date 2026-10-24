@extends('layouts.app')
@section('title', 'Congés & permissions')
@section('content')
<div class="page-head"><div><h1>Congés &amp; permissions</h1><p>{{ $seesAll ? 'Toutes les demandes.' : 'Vos demandes.' }}</p></div>
    <div class="actions"><a class="btn btn-primary" href="{{ route('hr.leaves.create') }}"><x-icon name="plus"/> Nouvelle demande</a></div></div>
<div class="panel">
    <form class="filters" method="get"><div class="field"><label for="status">Statut</label><select id="status" name="status"><option value="">Tous</option>@foreach(\App\Models\LeaveRequest::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div><button class="btn btn-quiet" type="submit">Filtrer</button></form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Agent</th><th>Type</th><th>Période</th><th class="r">Jours</th><th>Statut</th>@can('hr.leave.decide')<th>Décision</th>@endcan</tr></thead>
        <tbody>
        @forelse($leaves as $l)
            <tr>
                <td class="name">{{ $l->agent->fullName() }}<span class="row-sub">{{ $l->agent->service?->name }}</span></td>
                <td>{{ \App\Models\LeaveRequest::TYPES[$l->type] ?? $l->type }}@if($l->reason)<span class="row-sub">{{ $l->reason }}</span>@endif</td>
                <td class="num">{{ $l->start_date->format('d/m/Y') }} → {{ $l->end_date->format('d/m/Y') }}</td>
                <td class="r num">{{ $l->days }}</td>
                <td><span class="badge {{ ['approved' => 'ok', 'rejected' => 'bad', 'pending' => 'warn'][$l->status] ?? '' }}">{{ \App\Models\LeaveRequest::STATUSES[$l->status] ?? $l->status }}</span>@if($l->decision_note)<span class="row-sub">{{ $l->decision_note }}</span>@endif</td>
                @can('hr.leave.decide')
                <td>
                    @if($l->status === 'pending' && $l->agent->user_id !== auth()->id())
                        <form method="post" action="{{ route('hr.leaves.decide', $l) }}" class="actions">
                            @csrf
                            <input type="text" name="decision_note" placeholder="Motif (obligatoire si refus)" style="min-height:34px;width:180px">
                            <button class="btn btn-primary btn-sm" name="decision" value="approved" type="submit">Approuver</button>
                            <button class="btn btn-danger btn-sm" name="decision" value="rejected" type="submit">Refuser</button>
                        </form>
                    @elseif($l->decider)<span class="muted small">par {{ $l->decider->name }}</span>@endif
                </td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><b>Aucune demande</b></div></td></tr>
        @endforelse
        </tbody></table></div>
    {{ $leaves->links() }}
</div>
@endsection
