@extends('layouts.app')
@section('title', 'Effectuer une clôture de caisse')
@section('content')
<div class="page-head">
    <div>
        <h1>Effectuer une clôture de caisse</h1>
        <p>Rassemblement des reçus municipaux non encore clôturés pour le régisseur <b>{{ auth()->user()->name }}</b>.</p>
    </div>
    <div class="actions">
        <a class="btn btn-quiet" href="{{ route('revenue.closings.index') }}">Retour aux clôtures</a>
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: 1fr 340px; gap: 1.5rem;">
    <div class="panel">
        <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem;">1. Récapitulatif des recettes à clôturer</h2>

        <form method="get" class="filters" style="margin-bottom:1.5rem; background:#f8fafc; padding:1rem; border-radius:6px;">
            <div class="field">
                <label for="date">Arrêter les comptes à la date du :</label>
                <input id="date" type="date" name="date" value="{{ $date->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" onchange="this.form.submit()">
            </div>
            <button class="btn btn-quiet" type="submit">Actualiser la sélection</button>
        </form>

        @if($receipts_count === 0)
            <div class="empty" style="padding:2.5rem 1rem;">
                <b style="font-size:1.1rem; color:#64748b;">Aucun reçu en attente de clôture</b>
                Tous vos reçus émis jusqu'au {{ $date->format('d/m/Y') }} sont déjà clôturés, ou aucun encaissement n'a été enregistré.
            </div>
        @else
            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom:1.5rem;">
                <div style="background:#f1f5f9; padding:1rem; border-radius:6px;">
                    <span class="muted" style="font-size:0.875rem;">Nombre de reçus inclus</span>
                    <div style="font-size:1.5rem; font-weight:700;">{{ $receipts_count }} reçu(s)</div>
                </div>
                <div style="background:#f1f5f9; padding:1rem; border-radius:6px;">
                    <span class="muted" style="font-size:0.875rem;">Total général encaissé</span>
                    <div style="font-size:1.5rem; font-weight:700; color:#0f172a;">{{ \App\Support\Money::fcfa($total_amount) }}</div>
                </div>
            </div>

            <h3 style="font-size:1rem; margin-bottom:0.75rem;">Ventilation par mode de paiement</h3>
            <table class="table" style="margin-bottom:1.5rem;">
                <thead>
                    <tr>
                        <th>Mode de paiement</th>
                        <th class="r">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><b>Espèces physiques attendues en caisse</b></td>
                        <td class="r num" style="color:#047857; font-weight:700;">{{ \App\Support\Money::fcfa($breakdown['espece']) }}</td>
                    </tr>
                    <tr>
                        <td>Mobile Money</td>
                        <td class="r num">{{ \App\Support\Money::fcfa($breakdown['mobile_money']) }}</td>
                    </tr>
                    <tr>
                        <td>Chèque</td>
                        <td class="r num">{{ \App\Support\Money::fcfa($breakdown['cheque']) }}</td>
                    </tr>
                    <tr>
                        <td>Virement bancaire</td>
                        <td class="r num">{{ \App\Support\Money::fcfa($breakdown['virement']) }}</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="font-size:1rem; margin-bottom:0.75rem;">Reçus concernés (les plus récents)</h3>
            <div class="table-wrap" style="max-height: 250px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:6px;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Reçu</th>
                            <th>Date / Heure</th>
                            <th>Mode</th>
                            <th class="r">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($receipts as $r)
                            <tr>
                                <td>{{ $r->number }}</td>
                                <td>{{ $r->collected_at->format('d/m/Y H:i') }}</td>
                                <td>{{ config('mairie.payment_methods.' . $r->payment_method, $r->payment_method) }}</td>
                                <td class="r num">{{ \App\Support\Money::fcfa($r->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div>
        <div class="panel">
            <h2 style="font-size:1.15rem; margin-top:0; margin-bottom:1rem;">2. Comptage des espèces</h2>

            @if($receipts_count > 0)
                <form method="post" action="{{ route('revenue.closings.store') }}">
                    @csrf
                    <input type="hidden" name="closing_date" value="{{ $date->format('Y-m-d') }}">

                    <div class="field" style="margin-bottom:1rem;">
                        <label for="cash_expected">Espèces attendues (serveur)</label>
                        <input id="cash_expected" type="text" value="{{ \App\Support\Money::fcfa($cash_expected) }}" disabled style="background:#e2e8f0; font-weight:700;">
                    </div>

                    <div class="field" style="margin-bottom:1rem;">
                        <label for="cash_counted">Espèces physiques comptées (FCFA) *</label>
                        <input id="cash_counted" type="number" name="cash_counted" value="{{ old('cash_counted', $cash_expected) }}" min="0" step="1" required style="font-size:1.15rem; font-weight:700;">
                        <small class="muted">Saisir le montant exact d'espèces dans votre caisse.</small>
                    </div>

                    <div id="diff_box" style="padding:0.75rem; border-radius:6px; margin-bottom:1rem; background:#f8fafc; border:1px solid #cbd5e1;">
                        <span class="muted" style="font-size:0.8rem;">Écart de caisse calculé :</span>
                        <div id="diff_text" style="font-size:1.15rem; font-weight:700; color:#047857;">0 FCFA</div>
                    </div>

                    <div class="field" style="margin-bottom:1.5rem;">
                        <label for="notes">Observations du régisseur <span id="notes_req" style="display:none; color:#dc2626;">* (obligatoire en cas d'écart)</span></label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Explications si écart constaté ou remarques de passation…">{{ old('notes') }}</textarea>
                    </div>

                    <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">
                        <x-icon name="check"/> Valider & Clôturer la caisse
                    </button>
                    <small class="muted" style="display:block; margin-top:0.5rem; text-align:center;">
                        Une référence unique sera attribuée et les reçus seront verrouillés pour contrôle.
                    </small>
                </form>
            @else
                <p class="muted">Aucune action requise tant qu'aucun reçu n'est en attente.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        const expected = {{ (int) $cash_expected }};
        const countedInput = document.getElementById('cash_counted');
        const diffText = document.getElementById('diff_text');
        const diffBox = document.getElementById('diff_box');
        const notesReq = document.getElementById('notes_req');
        const notesInput = document.getElementById('notes');

        function updateDiff() {
            if (!countedInput) return;
            const counted = parseInt(countedInput.value, 10) || 0;
            const diff = counted - expected;

            if (diff === 0) {
                diffText.textContent = "0 FCFA (Équilibre parfait)";
                diffText.style.color = "#047857";
                diffBox.style.borderColor = "#86efac";
                diffBox.style.background = "#f0fdf4";
                notesReq.style.display = "none";
                notesInput.required = false;
            } else if (diff > 0) {
                diffText.textContent = "+" + diff.toLocaleString('fr-FR') + " FCFA (Excédent)";
                diffText.style.color = "#2563eb";
                diffBox.style.borderColor = "#93c5fd";
                diffBox.style.background = "#eff6ff";
                notesReq.style.display = "inline";
                notesInput.required = true;
            } else {
                diffText.textContent = diff.toLocaleString('fr-FR') + " FCFA (Déficit)";
                diffText.style.color = "#dc2626";
                diffBox.style.borderColor = "#fca5a5";
                diffBox.style.background = "#fef2f2";
                notesReq.style.display = "inline";
                notesInput.required = true;
            }
        }

        if (countedInput) {
            countedInput.addEventListener('input', updateDiff);
            updateDiff();
        }
    })();
</script>
@endpush
@endsection
