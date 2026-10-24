<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reçu {{ $receipt->number }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .doc-sheet { width: 148mm; max-width: 100%; padding: 12mm; }
        .doc-sheet h1 { font-size: 1.25rem; text-align: center; margin: 0; }
        .doc-sheet .sub { text-align: center; margin: 2px 0 14px; font-size: .9rem; }
        .doc-sheet table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .doc-sheet td { padding: 5px 0; border-bottom: 1px dotted #888; vertical-align: top; }
        .doc-sheet td:first-child { width: 38%; }
        .doc-sheet .total { font-size: 1.4rem; text-align: right; margin: 12px 0; }
        .doc-sheet .void { color: #b3261e; border: 2px solid #b3261e; padding: 4px 10px; display: inline-block; transform: rotate(-4deg); font-weight: 700; }
        @page { size: A5; margin: 8mm; }
    </style>
</head>
<body>
<div class="doc-bar no-print">
    <span>Reçu {{ $receipt->number }}</span>
    <span><button class="btn btn-quiet" type="button" onclick="window.print()">Imprimer</button> <a class="btn btn-quiet" href="{{ route('revenue.receipts.show', $receipt) }}">Retour</a></span>
</div>
<article class="doc-sheet">
    <h1>{{ config('mairie.name') }}</h1>
    <p class="sub">{{ config('mairie.country') }}@foreach(config('mairie.document_header') as $line)<br>{{ $line }}@endforeach</p>
    <p style="text-align:center"><b>REÇU N° {{ $receipt->number }}</b> @if($receipt->isCancelled())<br><span class="void">ANNULÉ</span>@endif</p>
    <table>
        <tr><td>Date</td><td>{{ $receipt->collected_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Reçu de</td><td>{{ $receipt->payerLabel() }}</td></tr>
        <tr><td>Objet</td><td>{{ $receipt->category->name }}@if($receipt->tariff) — {{ $receipt->tariff->label }}@endif</td></tr>
        <tr><td>Calcul</td><td>{{ $receipt->quantity }} × {{ \App\Support\Money::fcfa($receipt->unit_amount) }}</td></tr>
        <tr><td>Mode de paiement</td><td>{{ config('mairie.payment_methods')[$receipt->payment_method] ?? $receipt->payment_method }}@if($receipt->transaction_ref) (réf. {{ $receipt->transaction_ref }})@endif</td></tr>
        <tr><td>Encaissé par</td><td>{{ $receipt->collector->name }}</td></tr>
    </table>
    <p class="total"><b>{{ \App\Support\Money::fcfa($receipt->amount) }}</b></p>
    <p style="font-size:.8rem;text-align:center;margin:0">Document généré par {{ config('mairie.short_name') }}</p>
</article>
</body>
</html>
