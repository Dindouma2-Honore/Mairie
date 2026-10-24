@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => null, 'required' => false, 'hint' => null])
@php
    $val = old($name, $value);
    $id = 'f-' . str_replace(['.', '[', ']'], '-', $name);
@endphp
<div class="field @error($name) invalid @enderror">
    <label for="{{ $id }}">{{ $label }}@if($required) <span class="req" aria-hidden="true">*</span>@endif</label>
    @if($options !== null)
        <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes }}>
            <option value="">Choisir…</option>
            @foreach($options as $k => $v)
                <option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $v }}</option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes }}>{{ $val }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $val }}" @required($required) {{ $attributes }}>
    @endif
    @if($hint)<span class="hint">{{ $hint }}</span>@endif
    @error($name)<span class="err" role="alert">{{ $message }}</span>@enderror
</div>
