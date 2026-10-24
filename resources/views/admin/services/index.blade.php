@extends('layouts.app')
@section('title', 'Services')
@section('content')
<div class="page-head"><div><h1>Services</h1><p>Organisation de la mairie.</p></div></div>
<div class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Code</th><th>Nom</th><th>Responsable</th><th class="r">Agents</th><th>Actif</th><th></th></tr></thead>
        <tbody>
        @foreach($services as $s)
            <tr>
                <td class="num">{{ $s->code }}</td>
                <td><input form="svc-{{ $s->id }}" type="text" name="name" value="{{ $s->name }}" required aria-label="Nom du service {{ $s->code }}"></td>
                <td><input form="svc-{{ $s->id }}" type="text" name="head_name" value="{{ $s->head_name }}" aria-label="Responsable du service {{ $s->code }}"></td>
                <td class="r num">{{ $s->agents_count }}</td>
                <td><input form="svc-{{ $s->id }}" type="checkbox" name="is_active" value="1" @checked($s->is_active) aria-label="Actif" style="width:18px;height:18px"></td>
                <td class="r"><button form="svc-{{ $s->id }}" class="btn btn-quiet btn-sm" type="submit">Enregistrer</button></td>
            </tr>
        @endforeach
        </tbody></table></div>
    @foreach($services as $s)<form id="svc-{{ $s->id }}" method="post" action="{{ route('admin.services.update', $s) }}">@csrf @method('PUT')</form>@endforeach
    <form class="filters" method="post" action="{{ route('admin.services.store') }}" style="border-top:1px solid var(--line-2);border-bottom:0">
        @csrf
        <div class="field"><label>Code</label><input type="text" name="code" required maxlength="20"></div>
        <div class="field grow"><label>Nom</label><input type="text" name="name" required></div>
        <div class="field grow"><label>Responsable</label><input type="text" name="head_name"></div>
        <button class="btn btn-primary" type="submit"><x-icon name="plus"/> Ajouter</button>
    </form>
</div>
@endsection
