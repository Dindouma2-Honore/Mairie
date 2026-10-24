<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Procès-Verbal de Caisse {{ $closing->reference }} — {{ config('mairie.name') }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 15mm 15mm 15mm; }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #111;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #0a2347;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-left { text-align: left; }
        .header-right { text-align: right; }
        .rep { font-size: 10pt; font-weight: bold; text-transform: uppercase; }
        .devise { font-size: 8.5pt; font-style: italic; color: #444; }
        .mairie { font-size: 12pt; font-weight: bold; color: #0a2347; text-transform: uppercase; margin-top: 4px; }
        .service { font-size: 9.5pt; color: #333; }
        
        .title-box {
            text-align: center;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .title-box h1 {
            margin: 0;
            font-size: 14pt;
            color: #0a2347;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .title-box .ref {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        .meta-cell {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 10pt;
        }
        .meta-cell b { color: #0a2347; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 10pt;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
        }
        th {
            background: #f8fafc;
            color: #0a2347;
            font-weight: bold;
            text-align: left;
        }
        .r { text-align: right; }
        .num { font-variant-numeric: tabular-nums; font-family: monospace; font-size: 10.5pt; }

        .diff-box {
            border: 2px dashed #94a3b8;
            padding: 10px 14px;
            margin-bottom: 20px;
            border-radius: 4px;
            background: #fafafa;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }
        .sign-block {
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 12px;
            min-height: 120px;
        }
        .sign-title {
            font-weight: bold;
            font-size: 10pt;
            text-transform: uppercase;
            color: #0a2347;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .btn-print {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #0a2347;
            color: #fff;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        @media print {
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<button class="btn-print" onclick="window.print()">Imprimer le Procès-Verbal</button>

<div class="header">
    <div class="header-left">
        <div class="rep">RÉPUBLIQUE DU TCHAD</div>
        <div class="devise">Unité — Travail — Progrès</div>
        <div class="mairie">{{ config('mairie.name') }}</div>
        <div class="service">Service des Finances et de la Régie des Recettes</div>
    </div>
    <div class="header-right">
        <div class="service">Date d'édition : {{ now()->format('d/m/Y à H:i') }}</div>
        <div class="service">Exercice budgétaire : {{ $closing->year }}</div>
    </div>
</div>

<div class="title-box">
    <h1>Procès-Verbal de Clôture de Caisse</h1>
    <div class="ref">RÉFÉRENCE : {{ $closing->reference }}</div>
</div>

<div class="meta-grid">
    <div class="meta-cell">
        <div><b>Régisseur / Caissier :</b> {{ $closing->collector->name }}</div>
        <div><b>Date de clôture :</b> {{ $closing->closing_date->format('d/m/Y') }}</div>
    </div>
    <div class="meta-cell">
        <div><b>Statut du contrôle :</b> {{ $closing->statusLabel() }}</div>
        <div><b>Nombre de reçus arrêtés :</b> {{ $closing->receipts_count }} reçu(s)</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Mode de règlement</th>
            <th class="r">Montant arrêté (FCFA)</th>
        </tr>
    </thead>
    <tbody>
        @php $bk = $closing->breakdown ?? []; @endphp
        <tr>
            <td><b>Espèces physiques collectées</b></td>
            <td class="r num"><b>{{ \App\Support\Money::fcfa($bk['espece'] ?? 0) }}</b></td>
        </tr>
        <tr>
            <td>Mobile Money (Airtel / Moov Money)</td>
            <td class="r num">{{ \App\Support\Money::fcfa($bk['mobile_money'] ?? 0) }}</td>
        </tr>
        <tr>
            <td>Chèques certifiés</td>
            <td class="r num">{{ \App\Support\Money::fcfa($bk['cheque'] ?? 0) }}</td>
        </tr>
        <tr>
            <td>Virements bancaires</td>
            <td class="r num">{{ \App\Support\Money::fcfa($bk['virement'] ?? 0) }}</td>
        </tr>
        <tr style="background:#f8fafc; font-weight:bold;">
            <td>TOTAL GÉNÉRAL DES RECETTES</td>
            <td class="r num" style="font-size:11pt;">{{ \App\Support\Money::fcfa($closing->total_amount) }}</td>
        </tr>
    </tbody>
</table>

<div class="diff-box">
    <div style="font-size:10.5pt; font-weight:bold; margin-bottom:6px; color:#0a2347;">RÉSULTAT DU CONTRÔLE D'ESPÈCES PHYSIQUES :</div>
    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
        <span>1. Espèces attendues selon journal des reçus :</span>
        <span class="num"><b>{{ \App\Support\Money::fcfa($closing->cash_expected) }}</b></span>
    </div>
    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
        <span>2. Espèces réelles comptées en caisse :</span>
        <span class="num"><b>{{ \App\Support\Money::fcfa($closing->cash_counted) }}</b></span>
    </div>
    <div style="display:flex; justify-content:space-between; border-top:1px solid #cbd5e1; padding-top:6px; margin-top:6px; font-weight:bold;">
        <span>3. ÉCART CONSTATÉ (Comptées - Attendues) :</span>
        <span class="num">{{ $closing->difference > 0 ? '+' : '' }}{{ \App\Support\Money::fcfa($closing->difference) }}</span>
    </div>
</div>

@if($closing->notes)
    <div style="margin-bottom:18px; border:1px solid #e2e8f0; padding:8px 12px; border-radius:4px; font-size:9.5pt;">
        <b>Observations du régisseur :</b><br>
        {{ $closing->notes }}
    </div>
@endif

@if($closing->reviewed_at)
    <div style="margin-bottom:18px; border:1px solid #e2e8f0; padding:8px 12px; border-radius:4px; font-size:9.5pt; background:#f8fafc;">
        <b>Visa du Responsable Financier / Contrôleur :</b><br>
        Contrôlé le {{ $closing->reviewed_at->format('d/m/Y à H:i') }} par <b>{{ $closing->reviewer?->name }}</b>.
        @if($closing->review_notes)
            <br><i>Note : {{ $closing->review_notes }}</i>
        @endif
    </div>
@endif

<div class="signatures">
    <div class="sign-block">
        <div class="sign-title">Le Régisseur / Caissier</div>
        <div style="font-size:9pt; color:#475569;">Nom : {{ $closing->collector->name }}</div>
        <div style="font-size:9pt; color:#475569;">Date : {{ $closing->closing_date->format('d/m/Y') }}</div>
        <div style="margin-top:25px; font-size:9pt; color:#94a3b8; font-style:italic;">Signature :</div>
    </div>
    <div class="sign-block">
        <div class="sign-title">Le Responsable Financier / DAF</div>
        <div style="font-size:9pt; color:#475569;">Nom : {{ $closing->reviewer?->name ?? '_____________________' }}</div>
        <div style="font-size:9pt; color:#475569;">Date : {{ $closing->reviewed_at ? $closing->reviewed_at->format('d/m/Y') : '____/____/________' }}</div>
        <div style="margin-top:25px; font-size:9pt; color:#94a3b8; font-style:italic;">Signature et cachet :</div>
    </div>
</div>

</body>
</html>
