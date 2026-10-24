<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($preview ?? false) ? 'Aperçu du gabarit' : 'Acte de naissance' }} — {{ $act->fullName() }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>.doc-sheet { width: 210mm; max-width: 100%; min-height: 148mm; padding: 14mm; } @page { size: A4; margin: 8mm; }</style>
</head>
<body>
<div class="doc-bar no-print">
    <span>@if($preview ?? false)<b>APERÇU</b> — aucune impression n’est enregistrée.@else Copie n° {{ $delivery->id }} — {{ $act->fullName() }}@endif</span>
    <span>
        <button class="btn btn-quiet" type="button" onclick="window.print()"><x-icon name="print"/> Imprimer</button>
        <a class="btn btn-quiet" href="{{ ($preview ?? false) ? route('civil.templates.index') : route('civil.births.show', $act) }}">Retour</a>
    </span>
</div>
<div class="doc-sheet">{!! $html !!}</div>
</body>
</html>
