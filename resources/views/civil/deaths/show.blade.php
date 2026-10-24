@extends('layouts.app')
@section('title', 'Acte de décès : ' . $act->deceasedLabel())
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('civil.deaths.index') }}">Actes de décès</a> /</span>
        <h1>{{ $act->deceasedLabel() }}</h1>
        <p>
            @if($act->isValidated())
                Registre : <b>{{ $act->registryLabel() }}</b> · Code de vérification : <b class="num">{{ $act->verification_code }}</b>
            @else
                <span class="badge warning" style="background:#fef3c7; color:#92400e;">Brouillon non validé</span>
            @endif
            · Décédé(e) le {{ $act->death_date->format('d/m/Y') }} à {{ $act->death_place }}
        </p>
    </div>
    <div class="actions">
        @if($act->isDraft())
            <a class="btn btn-quiet" href="{{ route('civil.deaths.edit', $act) }}">
                <x-icon name="edit"/> Modifier
            </a>
            @can('civil.birth.validate')
                <form method="post" action="{{ route('civil.deaths.validate', $act) }}" style="display:inline;" onsubmit="return confirm('Confirmez-vous la validation de cet acte de décès ? Cette action attribuera un numéro de registre définitif et verrouillera l’acte.')">
                    @csrf
                    <button class="btn btn-primary" type="submit">
                        <x-icon name="check"/> Valider l'acte de décès
                    </button>
                </form>
            @endcan
        @elseif($act->isValidated())
            <a class="btn btn-primary" href="{{ route('civil.deaths.print', $act) }}" target="_blank">
                <x-icon name="print"/> Imprimer le formulaire officiel à 4 volets
            </a>
        @endif
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Défunt -->
    <div class="panel">
        <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem; color:#0a2347;">Identité du Défunt(e)</h2>
        <dl style="margin:0; font-size:0.95rem; line-height:1.8;">
            <dt class="muted" style="font-size:0.85rem;">Nom et prénoms :</dt>
            <dd style="margin:0 0 0.5rem 0; font-weight:700; font-size:1.05rem;">{{ $act->deceased_first_names }} {{ $act->deceased_last_name }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Sexe & Profession :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->sex === 'M' ? 'Masculin' : 'Féminin' }} · {{ $act->deceased_profession ?? 'Sans profession' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Date et heure du décès :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->death_date->format('d/m/Y') }} @if($act->death_time)à {{ $act->death_time }}@endif</dd>

            <dt class="muted" style="font-size:0.85rem;">Lieu du décès :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->death_place }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Dernier domicile :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->deceased_residence ?? '—' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Naissance :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->deceased_birth_date ?? '—' }} à {{ $act->deceased_birth_place ?? '—' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Filiation :</dt>
            <dd style="margin:0 0 0.5rem 0;">Fils/Fille de <b>{{ $act->father_name ?? '—' }}</b> et de <b>{{ $act->mother_name ?? '—' }}</b></dd>

            <dt class="muted" style="font-size:0.85rem;">Situation matrimoniale :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ \App\Models\DeathAct::MARITAL_STATUSES[$act->marital_status] ?? $act->marital_status }} @if($act->spouse_name)(de {{ $act->spouse_name }})@endif</dd>

            @if($act->birth_act_ref)
                <dt class="muted" style="font-size:0.85rem;">Réf. acte de naissance :</dt>
                <dd style="margin:0 0 0.5rem 0;">{{ $act->birth_act_ref }}</dd>
            @endif
        </dl>
    </div>

    <!-- Déclarant & Inhumation -->
    <div class="panel">
        <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem; color:#0a2347;">Déclaration & Inhumation</h2>
        <dl style="margin:0; font-size:0.95rem; line-height:1.8;">
            <dt class="muted" style="font-size:0.85rem;">Déclarant :</dt>
            <dd style="margin:0 0 0.5rem 0; font-weight:700;">{{ $act->declarant_name }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Qualité / Lien :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->declarant_quality ?? 'Déclarant' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Domicile du déclarant :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->declarant_residence ?? '—' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Date de déclaration :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->declaration_date->format('d/m/Y') }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Certificat médical de décès :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->medical_certificate_ref ?? 'Non mentionné' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Inhumation :</dt>
            <dd style="margin:0 0 0.5rem 0;">
                Lieu : <b>{{ $act->burial_place ?? 'Non précisé' }}</b>
                @if($act->burial_date) · Date : {{ $act->burial_date->format('d/m/Y') }}@endif
            </dd>
        </dl>
    </div>
</div>
@endsection
