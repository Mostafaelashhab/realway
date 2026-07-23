@props(['name' => '', 'size' => null])

@php
    // أيقونات SVG مخصّصة — كلها نفس سُمك الخط، تدعم light/dark عبر currentColor
    $paths = [
        'train'    => '<rect x="5" y="3" width="14" height="14" rx="3"/><path d="M5 11h14"/><path d="M9 3v8M15 3v8"/><circle cx="8.5" cy="14" r=".6" fill="currentColor" stroke="none"/><circle cx="15.5" cy="14" r=".6" fill="currentColor" stroke="none"/><path d="M7 17l-2 4M17 17l2 4"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'swap'     => '<path d="M7 4 4 7l3 3"/><path d="M4 7h13"/><path d="m17 20 3-3-3-3"/><path d="M20 17H7"/>',
        'clock'    => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
        'seat'     => '<path d="M6 4v9h9"/><path d="M6 13a3 3 0 0 0 3 3h6"/><path d="M18 8v11"/><path d="M6 20h.01"/>',
        'window'   => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M12 4v16M4 12h16"/>',
        'pin'      => '<path d="M12 21s-6-5.2-6-10a6 6 0 1 1 12 0c0 4.8-6 10-6 10Z"/><circle cx="12" cy="11" r="2.2"/>',
        'arrow-l'  => '<path d="M15 5l-7 7 7 7"/>',
        'arrow-r'  => '<path d="M9 5l7 7-7 7"/>',
        'chevron'  => '<path d="m9 6 6 6-6 6"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'     => '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5Z"/>',
        'star'     => '<path d="m12 3.5 2.6 5.3 5.9.9-4.2 4.1 1 5.8-5.3-2.8-5.3 2.8 1-5.8L3.5 9.7l5.9-.9L12 3.5Z"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="16" rx="3"/><path d="M4 9h16M8 3v4M16 3v4"/>',
        'info'     => '<circle cx="12" cy="12" r="8"/><path d="M12 11v5M12 8h.01"/>',
        'alert'    => '<path d="M12 4 3 19h18L12 4Z"/><path d="M12 10v4M12 17h.01"/>',
        'check'    => '<path d="m5 12 4 4L19 7"/>',
        'x'        => '<path d="M6 6l12 12M18 6 6 18"/>',
        'live'     => '<path d="M5 12a7 7 0 0 1 14 0"/><path d="M8 12a4 4 0 0 1 8 0"/><circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/>',
        'crowd'    => '<circle cx="9" cy="8" r="3"/><path d="M4 20a5 5 0 0 1 10 0"/><path d="M16 6a3 3 0 0 1 0 6M20 20a5 5 0 0 0-4-4.9"/>',
        'route'    => '<circle cx="6" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.5 6H15a3 3 0 0 1 0 6H9a3 3 0 0 0 0 6h.5"/>',
        'ticket'   => '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H6a2 2 0 0 1-2-2 2 2 0 0 0 0-4Z"/><path d="M14 6v12" stroke-dasharray="2 2"/>',
        'bolt'     => '<path d="M13 3 4 14h6l-1 7 9-11h-6l1-7Z"/>',
    ];
    $inner = $paths[$name] ?? '';
    $style = trim(($size ? "width:$size;height:$size;" : '').($attributes->get('style') ?? ''));
@endphp

<svg viewBox="0 0 24 24" {{ $attributes->except('style')->class('icon') }} @if($style) style="{{ $style }}" @endif aria-hidden="true">{!! $inner !!}</svg>
