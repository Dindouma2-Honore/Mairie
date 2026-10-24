<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Avis de Paiement {{ $debt->reference }} — {{ config('mairie.name') }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
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
            margin-bottom: 25px;
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
            border: 2px solid #0a2347;
            padding: 12px;
            margin-bottom: 25px;
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
            font-size: 11.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }

        .recipient-card {
            border: 1px solid #94a3b8;
            background: #fafafa;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 25px;
        }
        .recipient-card h3 {
            margin: 0 0 8px 0;
            font-size: 10.5pt;
            text-transform: uppercase;
            color: #0a2347;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 10.5pt;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
        }
        th {
            background: #f8fafc;
            color: #0a2347;
            font-weight: bold;
            text-align: left;
        }
        .r { text-align: right; }
        .num { font-variant-numeric: tabular-nums; font-family: monospace; font-size: 11pt; }

        .due-box {
            border: 2px solid #dc2626;
            background: #fef2f2;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .due-box .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-box {
            font-size: 9.5pt;
            color: #334155;
            line-height: 1.5;
            background: #f8fafc;
            border-left: 4px solid #0a2347;
            padding: 10px 14px;
            margin-bottom: 30px;
        }

        .signatures {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .sign-block {
            width: 250px;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 12px;
            min-height: 100px;
            text-align: center;
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

<button class="btn-print" onclick="window.print()">Imprimer l'Avis de Recouvrement</button>

<div class="header">
    <div class="header-left">
        <div class="rep">RÉPUBLIQUE DU TCHAD</div>
        <div class="devise">Unité — Travail — Progrès</div>
        <div class="mairie">{{ config('mairie.name') }}</div>
        <div class="service">Direction Financière — Service du Recouvrement</div>
    </div>
    <div class="header-right">
        <div class="service">Édité le : {{ now()->format('d/m/Y') }}</div>
        <div class="service">Moundou, le {{ now()->format('d/m/Y') }}</div>
    </div>
</div>

<div class="title-box">
    <h1>Avis de Mise en Recouvrement</h1>
    <div class="ref">RÉFÉRENCE : {{ $debt->reference }}</div>
</div>

<div class="recipient-card">
    <h3>Redevable / Contribuable concerné</h3>
    <table style="border:none; margin:0; width:100%;">
        <tr style="border:none;">
            <td style="border:none; padding:4px 0; width:50%;">
                <b>Nom / Raison sociale :</b> {{ $debt->taxpayer->name }}<br>
                <b>Référence contribuable :</b> {{ $debt->taxpayer->reference ?? 'Non attribuée' }}<br>
                <b>Activité :</b> {{ $debt->taxpayer->activity ?? 'Commerçant / Particulier' }}
            </td>
            <td style="border:none; padding:4px 0;">
                <b>Quartier / Repère :</b> {{ $debt->taxpayer->quarter ?? 'Non précisé' }}<br>
                <b>Téléphone :</b> {{ $debt->taxpayer->phone ?? '—' }}<br>
                <b>Statut :</b> {{ ucfirst($debt->taxpayer->type) }}
            </td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th>Désignation de la taxe / Redevance</th>
            <th>Période d'imposition</th>
            <th>Date d'échéance</th>
            <th class="r">Montant exigible</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <b>{{ $debt->title }}</b><br>
                <small style="color:#64748b;">Catégorie : {{ $debt->category->name }}</small>
            </td>
            <td>{{ $debt->period }}</td>
            <td class="num"><b>{{ $debt->due_date->format('d/m/Y') }}</b></td>
            <td class="r num">{{ \App\Support\Money::fcfa($debt->amount_due) }}</td>
        </tr>
        @if($debt->amount_paid > 0)
            <tr>
                <td colspan="3" class="r">Acomptes / Paiements partiels déjà perçus :</td>
                <td class="r num" style="color:#047857;">- {{ \App\Support\Money::fcfa($debt->amount_paid) }}</td>
            </tr>
        @endif
        <tr style="background:#f8fafc; font-weight:bold;">
            <td colspan="3" class="r" style="font-size:11pt;">RESTE NET À RECOUVRER :</td>
            <td class="r num" style="font-size:12pt; color:#dc2626;">{{ \App\Support\Money::fcfa($debt->balance) }}</td>
        </tr>
    </tbody>
</table>

<div class="due-box">
    <div class="row">
        <div>
            <b style="color:#991b1b; text-transform:uppercase; font-size:10pt;">Délai de règlement impératif :</b><br>
            À acquitter au plus tard le <b>{{ $debt->due_date->format('d/m/Y') }}</b>
        </div>
        <div style="font-size:14pt; font-weight:bold; color:#b91c1c;">
            {{ \App\Support\Money::fcfa($debt->balance) }}
        </div>
    </div>
</div>

<div class="info-box">
    <b>Modalités de paiement :</b><br>
    Le règlement s'effectue au guichet de la Régie Municipale de la Mairie de Moundou en espèces, par Mobile Money ou chèque certifié.<br>
    <i>Attention : Tout paiement doit donner lieu à la remise immédiate d'un reçu officiel imprimé, numéroté et sécurisé par la plateforme Mairie360.</i>
</div>

<div class="signatures">
    <div class="sign-block">
        <div style="font-weight:bold; font-size:9.5pt; text-transform:uppercase; color:#0a2347; margin-bottom:40px;">
            Pour le Service du Recouvrement
        </div>
        <div style="font-size:9pt; color:#475569;">Signature et Cachet</div>
    </div>
</div>

</body>
</html>
