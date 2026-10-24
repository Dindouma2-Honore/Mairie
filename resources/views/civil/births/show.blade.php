@extends('layouts.app')
@section('title', $act->fullName())
@section('content')
<div class="page-head">
    <div><h1>{{ $act->fullName() }} @if($act->isValidated())<span class="badge ok">Validé</span>@else<span class="badge warn">Brouillon</span>@endif</h1>
        <p>@if($act->registryLabel())Acte n° {{ $act->registryLabel() }} · @endif{{ $act->centre->name }} · saisi par {{ $act->creator->name }}</p></div>
    <div class="actions">
        @if($act->isDraft())
            @can('civil.birth.create')<a class="btn btn-quiet" href="{{ route('civil.births.edit', $act) }}">Modifier</a>@endcan
        @endif
    </div>
</div>

@if($act->isDraft())
    @can('civil.birth.validate')
    <div class="alert info"><x-icon name="shield"/><div style="flex:1"><b>Ce brouillon attend la validation d’un officier d’état civil</b>Relisez toutes les informations : après validation, l’acte n’est plus modifiable.
        @if($act->created_by === auth()->id() && ! auth()->user()->hasPermission('civil.birth.self_validate'))<br>Vous avez saisi ce brouillon : un autre officier doit le valider.@endif</div>
        <form method="post" action="{{ route('civil.births.validate', $act) }}" onsubmit="return confirm('Valider cet acte et lui attribuer un numéro de registre ?')">@csrf<button class="btn btn-primary" type="submit">Valider l’acte</button></form></div>
    @endcan
@endif

<div class="grid-2">
    <div class="panel"><div class="panel-head"><h2>Enfant</h2></div><div class="panel-body"><dl class="dl">
        <dt>Nom</dt><dd>{{ mb_strtoupper($act->child_last_name) }}</dd><dt>Prénoms</dt><dd>{{ $act->child_first_names }}</dd>
        <dt>Sexe</dt><dd>{{ $act->sex === 'F' ? 'Féminin' : 'Masculin' }}</dd>
        <dt>Né(e)</dt><dd>{{ $act->birthDateLabel() }}@if($act->birth_time) à {{ substr($act->birth_time, 0, 5) }}@endif</dd>
        <dt>Lieu</dt><dd>{{ $act->birth_place }}</dd>
    </dl></div></div>
    <div class="panel"><div class="panel-head"><h2>Parents</h2></div><div class="panel-body"><dl class="dl">
        <dt>Père</dt><dd>{{ $act->father_name ?: '—' }}@if($act->father_profession) <span class="muted">· {{ $act->father_profession }}</span>@endif</dd>
        <dt>Mère</dt><dd>{{ $act->mother_name }}@if($act->mother_profession) <span class="muted">· {{ $act->mother_profession }}</span>@endif</dd>
        <dt>Déclarant</dt><dd>{{ $act->declarant_name ?: '—' }}@if($act->declarant_quality) ({{ $act->declarant_quality }})@endif</dd>
        <dt>Déclaré le</dt><dd>{{ $act->declaration_date->format('d/m/Y') }}@if($act->is_late_declaration) <span class="badge warn">tardive</span>@endif</dd>
        @if($act->judgment_ref)<dt>Jugement</dt><dd>{{ $act->judgment_ref }}</dd>@endif
        @if($act->marginal_notes)<dt>Mentions</dt><dd>{{ $act->marginal_notes }}</dd>@endif
    </dl></div></div>
</div>

@if($act->isValidated())
<div class="grid-2">
    @can('civil.birth.print')
    <div class="panel"><div class="panel-head"><h2>Délivrer une copie</h2></div>
        <form class="panel-body stack" method="post" action="{{ route('civil.births.deliver', $act) }}">
            @csrf
            <x-field name="copy_type" label="Type de copie" :options="\App\Models\ActDelivery::TYPES" value="extrait" required />
            <x-field name="requester_name" label="Demandeur" />
            <x-field name="receipt_number" label="N° du reçu de paiement" hint="Facultatif. Le reçu doit être valide (ex. REC-2026-000123)." />
            <div class="form-actions"><button class="btn btn-primary" type="submit"><x-icon name="print"/> Générer et imprimer</button></div>
        </form></div>
    @endcan
    <div class="panel"><div class="panel-head"><h2>Délivrances</h2><span class="muted small">Code de vérification : <b>{{ $act->verification_code }}</b></span></div>
        <ul class="feed">
            @forelse($act->deliveries->sortByDesc('id') as $d)
                <li><span>{{ \App\Models\ActDelivery::TYPES[$d->copy_type] ?? $d->copy_type }} · {{ $d->requester_name ?: 'demandeur non renseigné' }}<br><span class="muted small">{{ $d->created_at->format('d/m/Y H:i') }} · {{ $d->deliverer->name }} · {{ $d->print_count }} impression(s)</span></span>
                    @can('civil.birth.print')<a href="{{ route('civil.deliveries.print', $d) }}" target="_blank" rel="noopener">Réimprimer</a>@endcan</li>
            @empty
                <li class="muted">Aucune copie délivrée.</li>
            @endforelse
        </ul></div>
</div>
@endif
@endsection
