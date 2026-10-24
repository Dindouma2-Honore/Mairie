@extends('layouts.app')
@section('title', 'Acte de mariage : ' . $act->coupleLabel())
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('civil.marriages.index') }}">Actes de mariage</a> /</span>
        <h1>{{ $act->coupleLabel() }}</h1>
        <p>
            @if($act->isValidated())
                Registre : <b>{{ $act->registryLabel() }}</b> · Code de vérification : <b class="num">{{ $act->verification_code }}</b>
            @else
                <span class="badge warning" style="background:#fef3c7; color:#92400e;">Brouillon non validé</span>
            @endif
            · Célébré le {{ $act->celebration_date->format('d/m/Y') }} à {{ $act->celebration_place }}
        </p>
    </div>
    <div class="actions">
        @if($act->isDraft())
            <a class="btn btn-quiet" href="{{ route('civil.marriages.edit', $act) }}">
                <x-icon name="edit"/> Modifier
            </a>
            @can('civil.birth.validate')
                <form method="post" action="{{ route('civil.marriages.validate', $act) }}" style="display:inline;" onsubmit="return confirm('Confirmez-vous la validation de cet acte de mariage ? Cette action attribuera un numéro de registre définitif et verrouillera l’acte.')">
                    @csrf
                    <button class="btn btn-primary" type="submit">
                        <x-icon name="check"/> Valider l'acte de mariage
                    </button>
                </form>
            @endcan
        @elseif($act->isValidated())
            <a class="btn btn-primary" href="{{ route('civil.marriages.print', $act) }}" target="_blank">
                <x-icon name="print"/> Imprimer le formulaire officiel à 3 volets
            </a>
        @endif
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom:1.5rem;">
    <!-- Époux -->
    <div class="panel">
        <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem; color:#0a2347;">Époux (M.)</h2>
        <dl style="margin:0; font-size:0.95rem; line-height:1.8;">
            <dt class="muted" style="font-size:0.85rem;">Nom et prénoms :</dt>
            <dd style="margin:0 0 0.5rem 0; font-weight:700; font-size:1.05rem;">{{ $act->husband_first_names }} {{ $act->husband_last_name }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Date et lieu de naissance :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->husband_birth_date ?? '—' }} à {{ $act->husband_birth_place ?? '—' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Filiation :</dt>
            <dd style="margin:0 0 0.5rem 0;">Fils de <b>{{ $act->husband_father ?? '—' }}</b> et de <b>{{ $act->husband_mother ?? '—' }}</b></dd>

            <dt class="muted" style="font-size:0.85rem;">Situation matrimoniale antérieure :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ ucfirst($act->husband_marital_status) }} @if($act->husband_previous_spouse)(de {{ $act->husband_previous_spouse }})@endif</dd>

            <dt class="muted" style="font-size:0.85rem;">Acte de naissance :</dt>
            <dd style="margin:0 0 0.5rem 0;">N° {{ $act->husband_birth_act_ref ?? 'Non fourni' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Témoin :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->husband_witness ?? '—' }} @if($act->husband_witness_residence)({{ $act->husband_witness_residence }})@endif</dd>
        </dl>
    </div>

    <!-- Épouse -->
    <div class="panel">
        <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem; color:#0a2347;">Épouse (Mme)</h2>
        <dl style="margin:0; font-size:0.95rem; line-height:1.8;">
            <dt class="muted" style="font-size:0.85rem;">Nom et prénoms :</dt>
            <dd style="margin:0 0 0.5rem 0; font-weight:700; font-size:1.05rem;">{{ $act->wife_first_names }} {{ $act->wife_last_name }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Date et lieu de naissance :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->wife_birth_date ?? '—' }} à {{ $act->wife_birth_place ?? '—' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Filiation :</dt>
            <dd style="margin:0 0 0.5rem 0;">Fille de <b>{{ $act->wife_father ?? '—' }}</b> et de <b>{{ $act->wife_mother ?? '—' }}</b></dd>

            <dt class="muted" style="font-size:0.85rem;">Situation matrimoniale antérieure :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ ucfirst($act->wife_marital_status) }} @if($act->wife_previous_spouse)(de {{ $act->wife_previous_spouse }})@endif</dd>

            <dt class="muted" style="font-size:0.85rem;">Acte de naissance :</dt>
            <dd style="margin:0 0 0.5rem 0;">N° {{ $act->wife_birth_act_ref ?? 'Non fourni' }}</dd>

            <dt class="muted" style="font-size:0.85rem;">Témoin :</dt>
            <dd style="margin:0 0 0.5rem 0;">{{ $act->wife_witness ?? '—' }} @if($act->wife_witness_residence)({{ $act->wife_witness_residence }})@endif</dd>
        </dl>
    </div>
</div>

<div class="panel">
    <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem; color:#0a2347;">Stipulations particulières (Verso de l'acte)</h2>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
        <div>
            <b>Dot :</b>
            <ul style="margin:0.5rem 0; padding-left:1.25rem; font-size:0.95rem; line-height:1.7;">
                <li>Montant en espèces : <b>{{ $act->dowry_cash > 0 ? \App\Support\Money::fcfa($act->dowry_cash) : 'Néant' }}</b></li>
                <li>Montant en nature : <b>{{ $act->dowry_nature ?? 'Néant' }}</b></li>
                <li>Modalités de versement : {{ $act->dowry_payment_dates ?? 'Comptant' }}</li>
            </ul>
        </div>
        <div>
            <b>Régime / Contrat de mariage :</b>
            <p style="margin:0.5rem 0; font-size:0.95rem;">
                @if($act->has_marriage_contract)
                    <span class="badge ok">Contrat notarié conclu</span><br>
                    Date : {{ $act->contract_date }} · Notaire : {{ $act->contract_notary }}
                @else
                    <span class="badge">Aucun contrat de mariage séparé</span>
                @endif
            </p>
        </div>
    </div>
</div>
@endsection
