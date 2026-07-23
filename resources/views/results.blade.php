@extends('layout')
@section('title', ($fromStation->name_ar ?? '') . ' ← ' . ($toStation->name_ar ?? ''))

@section('content')
{{-- شريط بحث مصغّر --}}
<form action="{{ route('search') }}" method="GET" data-search-form class="card animate-in" style="padding:14px;margin-bottom:18px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;position:relative">
        @include('partials.station-select', ['name' => 'from', 'label' => 'من', 'stations' => $stations, 'selected' => $from, 'dot' => 'brand'])
        @include('partials.station-select', ['name' => 'to', 'label' => 'إلى', 'stations' => $stations, 'selected' => $to, 'dot' => 'accent'])
    </div>
    <div style="display:flex;gap:10px;margin-top:10px">
        <input type="date" name="date" value="{{ $date }}" class="input" style="flex:1">
        <button type="submit" class="btn btn-primary" aria-label="ابحث"><x-icon name="search" /></button>
    </div>
</form>

{{-- ترويسة النتائج --}}
<div class="flex items-center justify-between" style="margin-bottom:14px">
    <div>
        <h1 style="font-size:20px;font-weight:800;letter-spacing:-.02em;display:flex;align-items:center;gap:8px">
            {{ optional($fromStation)->name_ar ?? 'محطة' }}
            <x-icon name="arrow-l" size="18px" style="color:var(--accent)" />
            {{ optional($toStation)->name_ar ?? 'محطة' }}
        </h1>
        <p style="color:var(--ink-soft);font-size:13px;margin-top:2px">
            {{ \Carbon\Carbon::parse($date)->translatedFormat('l j F') }} · {{ $trips->count() }} قطر
        </p>
    </div>
    <button type="button" onclick="toggleFav({from:'{{ $from }}',to:'{{ $to }}',fromName:'{{ optional($fromStation)->name_ar }}',toName:'{{ optional($toStation)->name_ar }}'}, this)"
            class="btn btn-ghost btn-icon" aria-label="حفظ في المفضّلة">
        <x-icon name="star" />
    </button>
</div>

{{-- قائمة القطارات --}}
@forelse ($trips as $row)
    @php $t = $row['trip']; @endphp
    <div class="card card-hover stagger-item" style="padding:16px;margin-bottom:12px;animation:fadeInUp .4s var(--tap) both;animation-delay:{{ $loop->index * 0.04 }}s">
        {{-- رحلة --}}
        <div class="flex items-center" style="gap:14px">
            <div style="text-align:center;min-width:66px">
                <div class="tnum" style="font-size:19px;font-weight:800;letter-spacing:-.02em">{{ $t->departLabel() ?? '--' }}</div>
                <div style="font-size:11px;color:var(--ink-faint);margin-top:1px">قيام</div>
            </div>

            <div style="flex:1;text-align:center">
                <div style="font-size:11px;color:var(--ink-soft);margin-bottom:5px">
                    {{ intdiv($t->duration_min, 60) }}س {{ $t->duration_min % 60 }}د
                </div>
                <div style="position:relative;height:2px;background:var(--border);border-radius:2px">
                    <span style="position:absolute;top:50%;inset-inline-start:0;transform:translateY(-50%);width:8px;height:8px;border-radius:50%;background:var(--brand)"></span>
                    <span style="position:absolute;top:50%;inset-inline-start:50%;transform:translate(-50%,-50%);color:var(--ink-faint)">
                        <x-icon name="train" size="15px" style="background:var(--surface);padding:0 2px" />
                    </span>
                    <span style="position:absolute;top:50%;inset-inline-end:0;transform:translateY(-50%);width:8px;height:8px;border-radius:50%;background:var(--accent)"></span>
                </div>
                <div style="font-size:11px;color:var(--ink-faint);margin-top:5px">{{ $t->distance_km }} كم · {{ $t->stops_count }} محطة</div>
            </div>

            <div style="text-align:center;min-width:66px">
                <div class="tnum" style="font-size:19px;font-weight:800;letter-spacing:-.02em">{{ $t->arriveLabel() ?? '--' }}</div>
                <div style="font-size:11px;color:var(--ink-faint);margin-top:1px">وصول</div>
            </div>
        </div>

        {{-- تفاصيل --}}
        <div class="flex items-center" style="gap:8px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border);flex-wrap:wrap">
            <span class="badge badge-brand"><x-icon name="train" size="13px" /> قطر {{ $t->train_number }}</span>
            <span style="color:var(--ink-soft);font-size:13px;font-weight:600">من {{ rtrim(rtrim(number_format($t->start_price, 2), '0'), '.') }} ج</span>

            @foreach ($row['classes'] as $c)
                @if ($c['coach_type'])
                    <a href="{{ route('coach', $c['coach_type']) }}" class="chip" style="font-size:12px;padding:4px 10px">{{ $c['label'] }}</a>
                @endif
            @endforeach

            <a href="{{ route('live-seats', ['train' => $t->train_number, 'from' => $from, 'to' => $to, 'date' => $date]) }}"
               class="btn btn-accent" style="margin-inline-start:auto;padding:9px 14px;font-size:13px">
                <x-icon name="ticket" size="16px" /> اختار كرسي
            </a>
        </div>
    </div>
@empty
    {{-- حالة فاضية --}}
    <div class="card animate-in" style="padding:40px 24px;text-align:center">
        <div style="width:64px;height:64px;border-radius:20px;background:var(--surface-2);display:grid;place-items:center;margin:0 auto 16px;color:var(--ink-faint)">
            <x-icon name="route" size="32px" />
        </div>
        <h2 style="font-weight:700;font-size:17px;margin-bottom:6px">مفيش قطارات على الخط ده اليوم</h2>
        <p style="color:var(--ink-soft);font-size:14px;max-width:36ch;margin:0 auto 18px">
            يمكن مفيش قطر مباشر بين المحطتين دول في التاريخ ده. جرّب محطة أقرب أو تاريخ تاني.
        </p>
        <a href="{{ route('home') }}" class="btn btn-primary"><x-icon name="search" size="16px" /> بحث جديد</a>
    </div>
@endforelse

@push('overlays')
    @include('partials.station-picker', ['stations' => $stations])
@endpush
@endsection
