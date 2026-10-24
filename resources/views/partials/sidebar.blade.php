@php $groups = \App\Support\Nav::for(auth()->user()); @endphp
<aside class="sidebar" id="sidebar" aria-label="Navigation principale">
    <div class="brand">
        <div class="brand-mark" aria-hidden="true">M</div>
        <div><b>{{ config('mairie.short_name') }}</b><small>{{ config('mairie.name') }}</small></div>
    </div>

    <nav class="nav" id="nav">
        @foreach($groups as $g)
            @if(isset($g['route']))
                <a href="{{ $g['url'] }}" @class(['active' => $g['active']]) @if($g['active']) aria-current="page" @endif>
                    <x-icon :name="$g['icon']"/> {{ $g['label'] }}
                </a>
            @else
                <details data-group="{{ $g['key'] }}" @class(['has-active' => $g['open']]) @if($g['open']) open @endif>
                    <summary>
                        <x-icon :name="$g['icon']"/>
                        <span class="label">{{ $g['label'] }}</span>
                        <x-icon name="chev" class="chev"/>
                    </summary>
                    <div class="sub">
                        @foreach($g['items'] as $item)
                            @if(!empty($item['soon']))
                                <span class="is-soon">{{ $item['label'] }} <em>bientôt</em></span>
                            @else
                                <a href="{{ $item['url'] }}" @class(['active' => $item['active']]) @if($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </nav>

    <div class="side-foot">Version de travail · données de la mairie</div>
</aside>
