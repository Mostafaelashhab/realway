@props(['name', 'label', 'stations', 'selected' => '', 'dot' => 'brand'])

@php
    $selectedName = optional($stations->firstWhere('id', $selected))->name_ar ?? '';
    $dotColor = $dot === 'accent' ? 'var(--accent)' : 'var(--brand)';
@endphp

<div>
    <label class="field-label">{{ $label }}</label>
    <button type="button" id="trigger-{{ $name }}"
            class="field-trigger {{ $selectedName ? '' : 'empty' }}"
            onclick="openStationPicker('{{ $name }}')"
            aria-haspopup="dialog">
        <span class="dot" style="background:{{ $dotColor }};box-shadow:0 0 0 4px color-mix(in srgb, {{ $dotColor }} 15%, transparent)"></span>
        <span id="label-{{ $name }}">{{ $selectedName ?: 'اختر المحطة' }}</span>
    </button>
    <input type="hidden" name="{{ $name }}" id="hidden-{{ $name }}" value="{{ $selected }}">
</div>
