@extends('layout')
@section('title', 'كراسي قطر ' . $train)

@section('content')
<a href="javascript:history.back()" class="chip" style="margin-bottom:14px"><x-icon name="arrow-r" size="15px" /> رجوع</a>

<div class="card animate-in" style="padding:20px">
    <div class="flex items-center justify-between" style="gap:10px;flex-wrap:wrap;margin-bottom:4px">
        <h1 style="font-size:18px;font-weight:800;letter-spacing:-.02em;display:flex;align-items:center;gap:8px">
            <span class="badge badge-brand"><x-icon name="train" size="14px" /> {{ $train }}</span>
            {{ optional($fromStation)->name_ar }}
            <x-icon name="arrow-l" size="16px" style="color:var(--accent)" />
            {{ optional($toStation)->name_ar }}
        </h1>
        <span class="badge badge-success">
            <span style="width:7px;height:7px;border-radius:50%;background:var(--success);display:inline-block;animation:pulse 1.4s infinite"></span>
            مباشر
        </span>
    </div>

    @if ($unavailable)
        <div style="text-align:center;padding:32px 16px">
            <div style="width:60px;height:60px;border-radius:18px;background:var(--warning-tint);color:var(--warning);display:grid;place-items:center;margin:0 auto 14px">
                <x-icon name="alert" size="28px" />
            </div>
            <h2 style="font-weight:700;font-size:16px;margin-bottom:6px">تعذّر جلب الكراسي دلوقتي</h2>
            <p style="color:var(--ink-soft);font-size:14px;max-width:34ch;margin:0 auto">
                يمكن الرحلة اتقفلت أو القطر مش بيمشي اليوم ده. المواعيد لسه متاحة من صفحة النتايج.
            </p>
        </div>
    @else
        {{-- تبويبات العربيات --}}
        <div style="display:flex;gap:8px;overflow-x:auto;margin:16px 0;padding-bottom:4px">
            @foreach ($coaches as $c)
                @php $open = collect($c['seats'])->where('available', true)->count(); @endphp
                <a href="{{ route('live-seats', ['train' => $train, 'from' => $from, 'to' => $to, 'date' => $date, 'coach' => $c['id']]) }}"
                   class="chip {{ $c['id'] === $selected['id'] ? 'chip-active' : '' }}" style="flex-direction:column;align-items:center;padding:8px 14px;min-width:78px;gap:2px">
                    <span style="font-weight:700">عربية {{ $c['number'] }}</span>
                    <span style="font-size:11px;font-weight:500;opacity:.85">{{ $c['class_ar'] }} · {{ $open }}</span>
                </a>
            @endforeach
        </div>

        @php
            $seats = collect($selected['seats']);
            $minX = $seats->min('x') ?? 0; $maxX = $seats->max('x') ?? 0;
            $rows = ($seats->max('row') ?? 0) + 1;
            $scale = 0.6; $seatSize = 32; $rowH = 40; $aisle = 22; $pad = 10;
            $midRow = ceil($rows / 2);
            $width = ($maxX - $minX) * $scale + $seatSize + $pad * 2;
            $height = $rows * $rowH + $aisle + $pad * 2;
            $openCount = $seats->where('available', true)->count();
            $winOpen = $seats->where('available', true)->where('window', true)->count();
        @endphp

        <div class="flex items-center" style="gap:8px;flex-wrap:wrap;margin-bottom:14px">
            <span class="badge badge-brand">{{ $selected['class_ar'] }}</span>
            @if ($selected['price_egp'])
                <span style="font-size:13px;font-weight:700;color:var(--ink)">{{ rtrim(rtrim(number_format($selected['price_egp'], 2), '0'), '.') }} ج</span>
            @endif
            <span style="font-size:13px;color:var(--ink-soft)">·
                <b style="color:var(--success)">{{ $openCount }}</b> فاضي ·
                <b style="color:var(--accent)">{{ $winOpen }}</b> جنب الشباك
            </span>
        </div>

        {{-- أسطورة + فلتر --}}
        <div class="flex items-center" style="gap:14px;flex-wrap:wrap;font-size:12px;color:var(--ink-soft);margin-bottom:12px">
            <span class="flex items-center" style="gap:6px"><i style="width:15px;height:15px;border-radius:5px;background:var(--success);display:inline-block"></i> فاضي</span>
            <span class="flex items-center" style="gap:6px"><i style="width:15px;height:15px;border-radius:5px;background:var(--surface-2);border:1px solid var(--border);display:inline-block"></i> محجوز</span>
            <span class="flex items-center" style="gap:6px"><i style="width:15px;height:15px;border-radius:5px;border:2px solid var(--accent);display:inline-block"></i> شباك</span>
            <label class="chip" style="margin-inline-start:auto">
                <input type="checkbox" id="winOnly" onchange="toggleWin()" style="accent-color:var(--accent)"> الشباك الفاضي بس
            </label>
        </div>

        {{-- العربية --}}
        <div style="overflow-x:auto;padding-bottom:8px">
            <div style="position:relative;margin:0 auto;direction:ltr;width:{{ $width }}px;height:{{ $height }}px;background:var(--surface-2);border-radius:14px;border:1px solid var(--border)">
                <div style="position:absolute;left:50%;transform:translateX(-50%);top:{{ $pad + ($midRow - 1) * $rowH + $seatSize + 3 }}px;font-size:9px;letter-spacing:.25em;color:var(--ink-faint);text-transform:uppercase">AISLE</div>
                @foreach ($seats as $seat)
                    @php
                        $top = $pad + $seat['row'] * $rowH + ($seat['row'] >= $midRow ? $aisle : 0);
                        $left = ($seat['x'] - $minX) * $scale + $pad;
                        $avail = $seat['available'];
                    @endphp
                    <div class="seat {{ $avail ? 'is-avail' : '' }} {{ $seat['window'] ? 'is-window' : '' }}"
                         style="position:absolute;display:flex;align-items:center;justify-content:center;border-radius:9px;font-size:11px;font-weight:700;
                                width:{{ $seatSize }}px;height:{{ $seatSize }}px;left:{{ $left }}px;top:{{ $top }}px;
                                {{ $avail ? 'background:var(--success);color:#fff' : 'background:var(--surface);color:var(--ink-faint);border:1px solid var(--border)' }}
                                {{ $seat['window'] ? 'box-shadow:0 0 0 2px var(--accent)' : '' }}">
                        {{ $seat['number'] }}
                    </div>
                @endforeach
            </div>
        </div>

        <p style="font-size:12px;color:var(--ink-faint);margin-top:12px;display:flex;align-items:center;gap:6px">
            <x-icon name="info" size="14px" /> التوفّر بيتحدّث لحظيًا. لو حصل أي تأخير في التحديث، المواعيد والتخطيط يفضلوا شغّالين.
        </p>
    @endif
</div>

<style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}</style>
<script>
    function toggleWin() {
        const on = document.getElementById('winOnly').checked;
        document.querySelectorAll('.seat').forEach(s => {
            const show = !on || (s.classList.contains('is-window') && s.classList.contains('is-avail'));
            s.style.opacity = show ? '1' : '0.18';
        });
    }
</script>
@endsection
