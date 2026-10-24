@extends('layouts.app')
@section('title', 'Reçu ' . $receipt->number)
@section('content')
<div class="page-head">
    <div><h1>Reçu {{ $receipt->number }} @if($receipt->isCancelled())<span class="badge bad">Annulé</span>@else<span class="badge ok">Valide</span>@endif</h1>
        <p>Émis le {{ $receipt->collected_at->format('d/m/Y à H:i') }} par {{ $receipt->collector->name }}@if($receipt->created_offline) · saisi hors ligne @endif</p></div>
    <div class="actions"><a class="btn btn-quiet" href="{{ route('revenue.receipts.print', $receipt) }}" target="_blank" rel="noopener"><x-icon name="print"/> Imprimer</a></div>
</div>
<div class="grid-2">
    <div class="panel"><div class="panel-body">
        <dl class="dl">
            <dt>Catégorie</dt><dd>{{ $receipt->category->name }}</dd>
            <dt>Tarif</dt><dd>{{ $receipt->tariff?->label ?? 'Montant libre' }}</dd>
            <dt>Calcul</dt><dd class="num">{{ $receipt->quantity }} × {{ \App\Support\Money::fcfa($receipt->unit_amount) }}</dd>
            <dt>Montant total</dt><dd class="num"><b>{{ \App\Support\Money::fcfa($receipt->amount) }}</b></dd>
            <dt>Payeur</dt><dd>{{ $receipt->payerLabel() }}</dd>
            <dt>Mode de paiement</dt><dd>{{ config('mairie.payment_methods')[$receipt->payment_method] ?? $receipt->payment_method }}@if($receipt->transaction_ref) · réf. {{ $receipt->transaction_ref }}@endif</dd>
            @if($receipt->description)<dt>Observation</dt><dd>{{ $receipt->description }}</dd>@endif
        </dl>
    </div></div>
    @if($receipt->isCancelled())
        <div class="panel"><div class="panel-body"><h2>Annulation</h2>
            <dl class="dl" style="margin-top:10px"><dt>Par</dt><dd>{{ $receipt->canceller?->name }}</dd><dt>Le</dt><dd>{{ $receipt->cancelled_at?->format('d/m/Y H:i') }}</dd><dt>Motif</dt><dd>{{ $receipt->cancel_reason }}</dd></dl></div></div>
    @else
        @can('revenue.cancel')
        <div class="panel"><div class="panel-head"><h2>Annuler ce reçu</h2></div>
            <form class="panel-body stack" method="post" action="{{ route('revenue.receipts.cancel', $receipt) }}" onsubmit="return confirm('Annuler ce reçu ? Cette action est conservée dans le journal.')">
                @csrf
                <x-field name="reason" label="Motif de l’annulation" required hint="Le reçu n’est jamais supprimé : il reste visible, marqué annulé." />
                <div class="form-actions"><button class="btn btn-danger" type="submit">Annuler le reçu</button></div>
            </form></div>
        @endcan
    @endif
</div>
@endsection
