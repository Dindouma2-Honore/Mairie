@extends('layouts.app')
@section('title', 'Demande ' . $civilRequest->reference)
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('civil.requests.index') }}">Demandes d'actes</a> /</span>
        <h1>Demande {{ $civilRequest->reference }}</h1>
        <p>
            Reçue le {{ $civilRequest->request_date->format('d/m/Y') }} · Demandeur : <b>{{ $civilRequest->requester_name }}</b>
        </p>
    </div>
    <div class="actions">
        <a class="btn btn-quiet" href="{{ route('civil.requests.index') }}">Retour aux demandes</a>
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: 1fr 340px; gap: 1.5rem;">
    <div>
        <div class="panel" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h2 style="font-size:1.15rem; margin:0;">Détails de la demande</h2>
                @if($civilRequest->isDelivered())
                    <span class="badge ok">Remis au demandeur</span>
                @elseif($civilRequest->isReady())
                    <span class="badge info">Prêt à retirer</span>
                @elseif($civilRequest->status === 'processing')
                    <span class="badge warning" style="background:#fef3c7; color:#92400e;">En cours</span>
                @else
                    <span class="badge">En attente</span>
                @endif
            </div>

            <dl style="margin:0; font-size:0.95rem; line-height:1.8;">
                <dt class="muted" style="font-size:0.85rem;">Type de document :</dt>
                <dd style="margin:0 0 0.5rem 0; font-weight:700;">{{ $civilRequest->docTypeLabel() }} — {{ $civilRequest->actTypeLabel() }}</dd>

                <dt class="muted" style="font-size:0.85rem;">Personne concernée :</dt>
                <dd style="margin:0 0 0.5rem 0; font-weight:700; font-size:1.05rem;">{{ $civilRequest->person_concerned }}</dd>

                <dt class="muted" style="font-size:0.85rem;">Nombre d'exemplaires :</dt>
                <dd style="margin:0 0 0.5rem 0;">{{ $civilRequest->copies_count }} exemplaire(s)</dd>

                @if($civilRequest->external_act_ref)
                    <dt class="muted" style="font-size:0.85rem;">Réf. acte source / Registre :</dt>
                    <dd style="margin:0 0 0.5rem 0;">{{ $civilRequest->external_act_ref }}</dd>
                @endif

                <dt class="muted" style="font-size:0.85rem;">Demandeur :</dt>
                <dd style="margin:0 0 0.5rem 0;">
                    <b>{{ $civilRequest->requester_name }}</b>
                    @if($civilRequest->requester_phone)<br>Tél. : <a href="tel:{{ $civilRequest->requester_phone }}">{{ $civilRequest->requester_phone }}</a>@endif
                    @if($civilRequest->requester_id_number)<br>CNI/Passeport : {{ $civilRequest->requester_id_number }}@endif
                </dd>

                @if($civilRequest->notes)
                    <dt class="muted" style="font-size:0.85rem;">Observations :</dt>
                    <dd style="margin:0 0 0.5rem 0;">{{ $civilRequest->notes }}</dd>
                @endif
            </dl>
        </div>
    </div>

    <div>
        <div class="panel">
            <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem;">Traitement & Remise</h2>

            <form method="post" action="{{ route('civil.requests.update_status', $civilRequest) }}">
                @csrf
                <div class="field" style="margin-bottom:1rem;">
                    <label for="status">Statut de la demande</label>
                    <select id="status" name="status" required>
                        @foreach(\App\Models\CivilRequest::STATUSES as $k => $l)
                            <option value="{{ $k }}" @selected($civilRequest->status === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="margin-bottom:1rem;">
                    <label for="notes">Commentaire interne</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Document imprimé, attend le retrait…">{{ $civilRequest->notes }}</textarea>
                </div>

                <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">
                    Mettre à jour le statut
                </button>
            </form>

            @if($civilRequest->delivered_at)
                <div style="margin-top:1rem; padding:0.75rem; background:#f0fdf4; border:1px solid #86efac; border-radius:6px; font-size:0.85rem; color:#047857;">
                    <b>Délivré le :</b> {{ $civilRequest->delivered_at->format('d/m/Y à H:i') }}<br>
                    @if($civilRequest->processor)Par : {{ $civilRequest->processor->name }}@endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
