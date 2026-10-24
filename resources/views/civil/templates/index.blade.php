@extends('layouts.app')
@section('title', 'Gabarits d’actes')
@section('content')
<div class="page-head"><div><h1>Gabarits d’actes de naissance</h1>
    <p>Collez ici le format officiel de la mairie (HTML). Les jetons <code>{{ '{{nom_enfant}}' }}</code> sont remplacés à l’impression. Tant qu’aucun gabarit n’est actif, un modèle provisoire filigrané est utilisé.</p></div></div>

<div class="panel">
    <div class="panel-head"><h2>Gabarits enregistrés</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Nom</th><th>Ajouté le</th><th>Statut</th><th class="r">Actions</th></tr></thead>
        <tbody>
        @forelse($templates as $t)
            <tr>
                <td class="name">{{ $t->name }}</td><td class="num">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                <td>@if($t->is_active)<span class="badge ok">Actif</span>@else<span class="badge">Inactif</span>@endif</td>
                <td class="r"><div class="actions" style="justify-content:flex-end">
                    <a class="btn btn-quiet btn-sm" href="{{ route('civil.templates.preview', $t) }}" target="_blank" rel="noopener">Aperçu</a>
                    @if($t->is_active)
                        <form method="post" action="{{ route('civil.templates.deactivate', $t) }}">@csrf<button class="btn btn-quiet btn-sm" type="submit">Désactiver</button></form>
                    @else
                        <form method="post" action="{{ route('civil.templates.activate', $t) }}" onsubmit="return confirm('Activer ce gabarit pour toutes les nouvelles impressions ?')">@csrf<button class="btn btn-primary btn-sm" type="submit">Activer</button></form>
                    @endif
                </div></td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty"><b>Aucun gabarit</b>Le modèle provisoire est utilisé.</div></td></tr>
        @endforelse
        </tbody></table></div>
</div>

<div class="grid-2" style="align-items:start">
    <form class="panel" method="post" action="{{ route('civil.templates.store') }}">
        @csrf
        <div class="panel-head"><h2>Ajouter un gabarit</h2></div>
        <div class="panel-body stack">
            <x-field name="name" label="Nom du gabarit" required hint="Ex. « Extrait de naissance — modèle officiel 2026 »." />
            <x-field name="html" label="Contenu HTML" type="textarea" rows="14" required spellcheck="false" style="font-family:ui-monospace,Consolas,monospace;font-size:.85rem" hint="Du HTML et du CSS uniquement : aucun script n’est exécuté. L’aperçu utilise le dernier acte validé." />
            <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
        </div>
    </form>
    <div class="panel">
        <div class="panel-head"><h2>Jetons disponibles</h2></div>
        <div class="table-wrap"><table class="table"><tbody>
            @foreach($tokens as $token => $label)<tr><td><code>{{ '{{' . $token . '}}' }}</code></td><td class="muted">{{ $label }}</td></tr>@endforeach
        </tbody></table></div>
    </div>
</div>
@endsection
