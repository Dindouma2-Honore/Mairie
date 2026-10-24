@extends('layouts.app')
@section('title', 'Clôture de caisse ' . $closing->reference)
@section('content')
<div class="page-head">
    <div>
        <span class="crumb"><a href="{{ route('revenue.closings.index') }}">Clôtures de caisse</a> /</span>
        <h1>Clôture {{ $closing->reference }}</h1>
        <p>Effectuée le {{ $closing->closing_date->format('d/m/Y') }} par <b>{{ $closing->collector->name }}</b>.</p>
    </div>
    <div class="actions">
        <a class="btn btn-quiet" href="{{ route('revenue.closings.print', $closing) }}" target="_blank">
            <x-icon name="print"/> Imprimer le Procès-Verbal A4
        </a>
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: 1fr 340px; gap: 1.5rem;">
    <div>
        <div class="panel" style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h2 style="font-size:1.15rem; margin:0;">Bilan financier de la clôture</h2>
                @if($closing->isValidated())
                    <span class="badge ok">Clôture validée</span>
                @elseif($closing->isRejected())
                    <span class="badge bad">Clôture rejetée</span>
                @else
                    <span class="badge warning" style="background:#fef3c7; color:#92400e;">En attente de contrôle</span>
                @endif
            </div>

            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom:1.5rem;">
                <div style="background:#f8fafc; padding:1rem; border-radius:6px; border:1px solid #e2e8f0;">
                    <span class="muted" style="font-size:0.875rem;">Total général</span>
                    <div style="font-size:1.35rem; font-weight:700;">{{ \App\Support\Money::fcfa($closing->total_amount) }}</div>
                    <small class="muted">{{ $closing->receipts_count }} reçu(s)</small>
                </div>
                <div style="background:#f8fafc; padding:1rem; border-radius:6px; border:1px solid #e2e8f0;">
                    <span class="muted" style="font-size:0.875rem;">Espèces attendues</span>
                    <div style="font-size:1.35rem; font-weight:700; color:#047857;">{{ \App\Support\Money::fcfa($closing->cash_expected) }}</div>
                </div>
                <div style="background:#f8fafc; padding:1rem; border-radius:6px; border:1px solid #e2e8f0;">
                    <span class="muted" style="font-size:0.875rem;">Espèces comptées</span>
                    <div style="font-size:1.35rem; font-weight:700;">{{ \App\Support\Money::fcfa($closing->cash_counted) }}</div>
                </div>
            </div>

            <div style="padding:1rem; border-radius:6px; margin-bottom:1.5rem; background: {{ $closing->difference == 0 ? '#f0fdf4' : ($closing->difference > 0 ? '#eff6ff' : '#fef2f2') }}; border:1px solid {{ $closing->difference == 0 ? '#86efac' : ($closing->difference > 0 ? '#93c5fd' : '#fca5a5') }};">
                <b>Écart de caisse :</b>
                <span style="font-size:1.15rem; font-weight:700; color:{{ $closing->difference == 0 ? '#047857' : ($closing->difference > 0 ? '#2563eb' : '#dc2626') }};">
                    {{ $closing->difference > 0 ? '+' : '' }}{{ \App\Support\Money::fcfa($closing->difference) }}
                    @if($closing->difference == 0)
                        (Caisse parfaitement équilibrée)
                    @elseif($closing->difference > 0)
                        (Excédent d'espèces)
                    @else
                        (Déficit de caisse constaté)
                    @endif
                </span>
            </div>

            <h3 style="font-size:1rem; margin-bottom:0.75rem;">Ventilation par mode de règlement</h3>
            <table class="table" style="margin-bottom:1.5rem;">
                <thead>
                    <tr>
                        <th>Mode</th>
                        <th class="r">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @php $bk = $closing->breakdown ?? []; @endphp
                    <tr>
                        <td>Espèces</td>
                        <td class="r num">{{ \App\Support\Money::fcfa($bk['espece'] ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Mobile Money</td>
                        <td class="r num">{{ \App\Support\Money::fcfa($bk['mobile_money'] ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Chèque</td>
                        <td class="r num">{{ \App\Support\Money::fcfa($bk['cheque'] ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Virement bancaire</td>
                        <td class="r num">{{ \App\Support\Money::fcfa($bk['virement'] ?? 0) }}</td>
                    </tr>
                </tbody>
            </table>

            @if($closing->notes)
                <div style="margin-bottom:1.5rem;">
                    <h3 style="font-size:1rem; margin-bottom:0.5rem;">Observations du régisseur</h3>
                    <p style="background:#f8fafc; padding:1rem; border-radius:6px; border:1px solid #e2e8f0; margin:0;">
                        {{ $closing->notes }}
                    </p>
                </div>
            @endif
        </div>

        <div class="panel">
            <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem;">Reçus inclus dans cette clôture ({{ $closing->receipts->count() }})</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Reçu</th>
                            <th>Date</th>
                            <th>Catégorie</th>
                            <th>Mode</th>
                            <th class="r">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($closing->receipts as $r)
                            <tr>
                                <td><a href="{{ route('revenue.receipts.show', $r) }}">{{ $r->number }}</a></td>
                                <td class="num">{{ $r->collected_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $r->category->name }}</td>
                                <td>{{ config('mairie.payment_methods.' . $r->payment_method, $r->payment_method) }}</td>
                                <td class="r num">{{ \App\Support\Money::fcfa($r->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="panel">
            <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem;">Contrôle financier (Visa)</h2>

            @if($closing->isValidated())
                <div style="background:#f0fdf4; border:1px solid #86efac; padding:1rem; border-radius:6px; margin-bottom:1rem;">
                    <b style="color:#047857;">Clôture approuvée</b><br>
                    <small class="muted">Validée le {{ $closing->reviewed_at->format('d/m/Y à H:i') }} par {{ $closing->reviewer?->name }}.</small>
                    @if($closing->review_notes)
                        <p style="margin:0.5rem 0 0 0; font-size:0.9rem;"><i>« {{ $closing->review_notes }} »</i></p>
                    @endif
                </div>
            @elseif($closing->isRejected())
                <div style="background:#fef2f2; border:1px solid #fca5a5; padding:1rem; border-radius:6px; margin-bottom:1rem;">
                    <b style="color:#dc2626;">Clôture rejetée</b><br>
                    <small class="muted">Rejetée le {{ $closing->reviewed_at->format('d/m/Y à H:i') }} par {{ $closing->reviewer?->name }}.</small>
                    <p style="margin:0.5rem 0 0 0; font-size:0.9rem; color:#991b1b;">
                        <b>Motif :</b> {{ $closing->review_notes }}
                    </p>
                    <small class="muted" style="display:block; margin-top:0.5rem;">Les reçus ont été libérés pour correction et reclôture.</small>
                </div>
            @else
                <p class="muted" style="font-size:0.9rem;">
                    Statut actuel : <b>En attente de vérification</b> par le responsable financier ou la direction.
                </p>

                @can('revenue.closing.audit')
                    @if($closing->collector_id === auth()->id())
                        <div style="background:#fffbeb; border:1px solid #fcd34d; padding:0.75rem; border-radius:6px; font-size:0.875rem; color:#92400e; margin-bottom:1rem;">
                            <b>Séparation des tâches :</b> Vous avez émis cette clôture et ne pouvez pas la viser vous-même. Un autre contrôleur habilité doit statuer.
                        </div>
                    @else
                        <div style="margin-top:1.5rem; border-top:1px solid #e2e8f0; pt:1rem;">
                            <form method="post" action="{{ route('revenue.closings.validate', $closing) }}" style="margin-bottom:1rem;">
                                @csrf
                                <div class="field" style="margin-bottom:0.75rem;">
                                    <label for="review_notes">Observations de validation</label>
                                    <input id="review_notes" type="text" name="review_notes" placeholder="Conforme / Reçu vérifié">
                                </div>
                                <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">
                                    <x-icon name="check"/> Valider la clôture
                                </button>
                            </form>

                            <form method="post" action="{{ route('revenue.closings.reject', $closing) }}" onsubmit="return confirm('Confirmez-vous le rejet de cette clôture ? Tous les reçus seront libérés pour modification.')">
                                @csrf
                                <div class="field" style="margin-bottom:0.75rem;">
                                    <label for="reject_reason" style="color:#dc2626;">Motif du rejet *</label>
                                    <textarea id="reject_reason" name="review_notes" rows="3" required placeholder="Expliquer l'erreur ou l'écart non admissible…"></textarea>
                                </div>
                                <button class="btn btn-danger" type="submit" style="width:100%; justify-content:center;">
                                    <x-icon name="x"/> Rejeter la clôture
                                </button>
                            </form>
                        </div>
                    @endif
                @else
                    <p class="muted" style="font-size:0.85rem;">Seul un responsable financier ou la direction peut viser cette clôture.</p>
                @endcan
            @endif
        </div>
    </div>
</div>
@endsection
