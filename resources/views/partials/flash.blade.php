@if(session('temporary_password'))
    @php $tp = session('temporary_password'); @endphp
    <div class="alert warning" role="status">
        <x-icon name="key"/>
        <div>
            <b>Mot de passe temporaire — affiché une seule fois</b>
            Compte <strong>{{ $tp['email'] }}</strong> : <span class="secret">{{ $tp['password'] }}</span><br>
            <span class="muted">Transmettez-le à l’utilisateur. Il devra le changer à sa première connexion.</span>
        </div>
    </div>
@endif
@foreach(['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'alert'] as $type => $icon)
    @if(session($type))
        <div class="alert {{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}"><x-icon :name="$icon"/><div>{{ session($type) }}</div></div>
    @endif
@endforeach
@if($errors->any())
    <div class="alert error" role="alert">
        <x-icon name="alert"/>
        <div><b>Certains champs sont à corriger</b>{{ $errors->count() > 1 ? $errors->count() . ' erreurs signalées dans le formulaire.' : $errors->first() }}</div>
    </div>
@endif
