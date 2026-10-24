@extends('layouts.app')
@section('title', 'Encaisser')
@section('content')
<div class="page-head"><div><h1>Encaisser</h1><p>Émet un reçu numéroté. Fonctionne aussi sans connexion.</p></div></div>

<script type="application/json" id="till-data">{!! json_encode(['categories' => $categories, 'methods' => config('mairie.payment_methods')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

@if(collect($categories)->isEmpty())
    <div class="alert warning"><x-icon name="alert"/><div><b>Aucune catégorie de recette active</b>Un responsable doit d’abord définir les catégories et les tarifs (menu Recettes &amp; fiscalité).</div></div>
@endif

<form id="till" method="post" action="{{ route('revenue.receipts.store') }}" data-offline="receipt.create" class="grid-2" style="align-items:start">
    @csrf
    <div class="panel">
        <div class="panel-head"><h2>Opération</h2></div>
        <div class="panel-body stack">
            <x-field name="category_id" label="Catégorie de recette" :options="collect($categories)->pluck('name', 'id')" required />
            <x-field name="tariff_id" label="Tarif" :options="[]" />
            <div id="amount-wrap" hidden><x-field name="amount" label="Montant (FCFA)" type="number" min="1" step="1" hint="Seulement pour les catégories à montant libre." /></div>
            <x-field name="quantity" label="Quantité" type="number" min="1" max="1000" value="1" />
            <x-field name="taxpayer_id" label="Contribuable (facultatif)" :options="$taxpayers->mapWithKeys(fn ($t) => [$t->id => $t->name . ($t->quarter ? ' — ' . $t->quarter : '')])" />
            <x-field name="payer_name" label="Nom du payeur" hint="Si le payeur n’est pas enregistré comme contribuable." />
            <x-field name="description" label="Observation" />
        </div>
    </div>
    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Paiement</h2></div>
            <div class="panel-body stack">
                <x-field name="payment_method" label="Mode de paiement" :options="config('mairie.payment_methods')" value="espece" required />
                <div id="ref-wrap" hidden><x-field name="transaction_ref" label="Référence de la transaction" hint="Numéro de transaction, de chèque ou de virement." /></div>
            </div>
        </div>
        <div class="total-box"><span>Total à encaisser</span><span class="v"><span id="t-total">0</span> <small>FCFA</small></span></div>
        <div class="panel"><div class="panel-body"><dl class="dl">
            <dt>Catégorie</dt><dd id="t-cat">—</dd>
            <dt>Tarif</dt><dd id="t-tariff">—</dd>
            <dt>Calcul</dt><dd id="t-qty" class="num">—</dd>
            <dt>Paiement</dt><dd id="t-pay">—</dd>
        </dl></div></div>
        <div id="t-mode" class="mode is-online"></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><x-icon name="check"/> Émettre le reçu</button></div>
    </div>
</form>
@endsection
