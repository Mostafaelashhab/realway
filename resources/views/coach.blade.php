@extends('layout')
@section('title', 'تخطيط ' . $coach->name_ar)

@php
    $xs = $seats->pluck('x');
    $minX = $xs->min() ?? 0; $maxX = $xs->max() ?? 0;
    $rows = ($seats->max('row_index') ?? 0) + 1;
    $scale = 0.6; $seatSize = 32; $rowH = 40; $aisle = 22; $pad = 10;
    $midRow = ceil($rows / 2);
    $width = ($maxX - $minX) * $scale + $seatSize + $pad * 2;
    $height = $rows * $rowH + $aisle + $pad * 2;
    $rowTop = fn ($r) => $pad + $r * $rowH + ($r >= $midRow ? $aisle : 0);
@endphp

@section('content')
<a href="javascript:history.back()" class="chip" style="margin-bottom:14px"><x-icon name="arrow-r" size="15px" /> رجوع</a>

<div class="card animate-in" style="padding:20px">
    <div class="flex items-center justify-between" style="gap:10px;flex-wrap:wrap;margin-bottom:2px">
        <h1 style="font-size:18px;font-weight:800;letter-spacing:-.02em">{{ $coach->name_ar }}</h1>
        <span class="badge badge-brand">{{ optional($coach->coachClass)->label_ar ?: optional($coach->coachClass)->name_ar }}</span>
    </div>
    <p style="color:var(--ink-soft);font-size:13px;margin-bottom:16px">
        {{ $coach->seats_count }} كرسي · منهم <b style="color:var(--accent)">{{ $windowOpen }} جنب الشباك</b>
    </p>

    <div class="flex items-center" style="gap:14px;flex-wrap:wrap;font-size:12px;color:var(--ink-soft);margin-bottom:12px">
        <span class="flex items-center" style="gap:6px"><i style="width:15px;height:15px;border-radius:5px;background:var(--brand-tint);border:1px solid var(--brand);display:inline-block"></i> كرسي</span>
        <span class="flex items-center" style="gap:6px"><i style="width:15px;height:15px;border-radius:5px;border:2px solid var(--accent);display:inline-block"></i> شباك</span>
        <label class="chip" style="margin-inline-start:auto">
            <input type="checkbox" id="winOnly" onchange="toggleWin()" style="accent-color:var(--accent)"> الشباك بس
        </label>
    </div>

    <div style="overflow-x:auto;padding-bottom:8px">
        <div style="position:relative;margin:0 auto;direction:ltr;width:{{ $width }}px;height:{{ $height }}px;background:var(--surface-2);border-radius:14px;border:1px solid var(--border)">
            <div style="position:absolute;left:50%;transform:translateX(-50%);top:{{ $rowTop($midRow - 1) + $seatSize + 3 }}px;font-size:9px;letter-spacing:.25em;color:var(--ink-faint);text-transform:uppercase">AISLE</div>
            @foreach ($seats as $seat)
                <div class="seat {{ $seat->is_window ? 'is-window' : '' }}"
                     style="position:absolute;display:flex;align-items:center;justify-content:center;border-radius:9px;font-size:11px;font-weight:700;
                            width:{{ $seatSize }}px;height:{{ $seatSize }}px;
                            left:{{ ($seat->x - $minX) * $scale + $pad }}px;top:{{ $rowTop($seat->row_index) }}px;
                            background:var(--brand-tint);color:var(--brand);border:1px solid var(--brand);
                            {{ $seat->is_window ? 'box-shadow:0 0 0 2px var(--accent)' : '' }}">
                    {{ $seat->number }}
                </div>
            @endforeach
        </div>
    </div>

    <p style="font-size:12px;color:var(--ink-faint);margin-top:12px;display:flex;align-items:center;gap:6px">
        <x-icon name="info" size="14px" /> ده التخطيط. التوفّر اللحظي بييجي من زر "اختار كرسي" في رحلة فعلية.
    </p>
</div>

<script>
    function toggleWin() {
        const on = document.getElementById('winOnly').checked;
        document.querySelectorAll('.seat').forEach(s => {
            s.style.opacity = (on && !s.classList.contains('is-window')) ? '0.18' : '1';
        });
    }
</script>
@endsection
