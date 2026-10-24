@extends('layouts.app')
@section('title', $user->exists ? 'Modifier un compte' : 'Nouveau compte')
@section('content')
<div class="page-head"><h1>{{ $user->exists ? 'Modifier ' . $user->name : 'Nouveau compte' }}</h1></div>
<form class="panel" method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" style="max-width:860px">
    @csrf @if($user->exists) @method('PUT') @endif
    <div class="panel-body">
        <div class="form-grid">
            <x-field name="name" label="Nom complet" :value="$user->name" required />
            <x-field name="email" label="Adresse e-mail" type="email" :value="$user->email" required />
            <x-field name="phone" label="Téléphone" type="tel" :value="$user->phone" />
            <x-field name="service_id" label="Service" :options="$services" :value="$user->service_id" />
        </div>
        <fieldset style="margin-top:18px">
            <legend class="label" style="font-weight:600;margin-bottom:8px">Rôles <span class="req" style="color:var(--brick)">*</span></legend>
            <div class="choice-list">
                @foreach($roles as $r)
                    <label class="choice"><input type="checkbox" name="roles[]" value="{{ $r->id }}" @checked(in_array($r->id, (array) old('roles', $selected)))>
                        <span><b>{{ $r->name }}</b><span class="row-sub">{{ $r->description }}</span></span></label>
                @endforeach
            </div>
            @error('roles')<span class="err" role="alert">{{ $message }}</span>@enderror
        </fieldset>
        <div class="form-actions" style="margin-top:16px">
            <input type="hidden" name="is_active" value="0">
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Compte actif</label>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn btn-quiet" href="{{ route('admin.users.index') }}">Annuler</a>
        </div>
        @unless($user->exists)<p class="muted small" style="margin-top:10px">Un mot de passe temporaire sera généré et affiché une seule fois. L’utilisateur devra le changer à sa première connexion.</p>@endunless
    </div>
</form>
@endsection
