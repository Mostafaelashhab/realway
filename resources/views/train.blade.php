@extends('layout')
@section('title', 'قطر ' . $number)

@section('content')
<a href="javascript:history.back()" class="chip pressable" style="margin-bottom:14px"><x-icon name="arrow-r" size="15px" /> رجوع</a>

{{-- ترويسة القطر --}}
<div class="card animate-in" style="padding:20px;margin-bottom:14px">
    <div class="flex items-center justify-between" style="gap:10px;flex-wrap:wrap;margin-bottom:12px">
        <div class="flex items-center" style="gap:10px">
            <span style="display:grid;place-items:center;width:44px;height:44px;border-radius:13px;background:var(--brand);color:#fff"><x-icon name="train" size="24px" /></span>
            <div>
                <div style="font-size:20px;font-weight:800;letter-spacing:-.02em">قطر {{ $number }}</div>
                <div style="font-size:13px;color:var(--ink-soft)">{{ optional($fromStation)->name_ar }} ← {{ optional($toStation)->name_ar }}</div>
            </div>
        </div>
        @if ($trip->train_type)<span class="badge badge-brand">{{ $trip->train_type }}</span>@endif
    </div>

    {{-- مؤشرات --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
        <div style="background:var(--surface-2);border-radius:14px;padding:12px;text-align:center">
            <div class="tnum" style="font-size:18px;font-weight:800">{{ intdiv($trip->duration_min,60) }}:{{ str_pad($trip->duration_min%60,2,'0',STR_PAD_LEFT) }}</div>
            <div style="font-size:11px;color:var(--ink-soft)">مدة الرحلة</div>
        </div>
        <div style="background:var(--surface-2);border-radius:14px;padding:12px;text-align:center">
            <div class="tnum" style="font-size:18px;font-weight:800">{{ $trip->distance_km }}</div>
            <div style="font-size:11px;color:var(--ink-soft)">كيلومتر</div>
        </div>
        <div style="background:var(--surface-2);border-radius:14px;padding:12px;text-align:center">
            <div class="tnum" style="font-size:18px;font-weight:800">{{ $trip->stops_count }}</div>
            <div style="font-size:11px;color:var(--ink-soft)">محطة</div>
        </div>
    </div>
</div>

{{-- خط سير المحطات --}}
<div class="card animate-in" style="padding:20px;margin-bottom:14px">
    <h2 style="font-size:15px;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:6px"><x-icon name="route" size="18px" /> خط السير</h2>
    <div style="position:relative;padding-inline-start:8px">
        @foreach ($stops as $i => $s)
            @php $first = $i === 0; $last = $i === $stops->count() - 1; @endphp
            <div style="display:flex;gap:14px;position:relative;padding-bottom:{{ $last ? '0' : '20px' }}">
                {{-- الخط --}}
                @unless($last)
                    <span style="position:absolute;inset-inline-start:6px;top:16px;bottom:0;width:2px;background:var(--border)"></span>
                @endunless
                {{-- النقطة --}}
                <span style="position:relative;z-index:1;width:14px;height:14px;border-radius:50%;flex:none;margin-top:3px;
                    {{ $first ? 'background:var(--brand)' : ($last ? 'background:var(--accent)' : 'background:var(--surface);border:2px solid var(--border)') }}"></span>
                <div style="flex:1">
                    <a href="{{ route('station', $s['id']) }}" style="text-decoration:none;color:inherit;font-weight:{{ $first || $last ? '700' : '600' }};font-size:{{ $first || $last ? '15px' : '14px' }}">{{ $s['name'] }}</a>
                    @if ($first)<div style="font-size:12px;color:var(--brand);font-weight:700">القيام · {{ $trip->departLabel() }}</div>
                    @elseif ($last)<div style="font-size:12px;color:var(--accent-strong);font-weight:700">الوصول · {{ $trip->arriveLabel() }}</div>@endif
                </div>
                @if ($s['code'])<span style="font-size:11px;color:var(--ink-faint);align-self:flex-start;margin-top:4px" class="tnum">{{ $s['code'] }}</span>@endif
            </div>
        @endforeach
    </div>
    <p style="font-size:11px;color:var(--ink-faint);margin-top:12px;display:flex;align-items:center;gap:6px"><x-icon name="info" size="13px" /> مواعيد المحطات في النص تقريبية حسب ترتيب الخط.</p>
</div>

{{-- الدرجات والأسعار --}}
<div class="card animate-in" style="padding:20px;margin-bottom:14px">
    <h2 style="font-size:15px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:6px"><x-icon name="seat" size="18px" /> درجات القطر</h2>
    <div style="display:flex;flex-direction:column;gap:8px">
        @forelse ($classes as $c)
            <div class="flex items-center" style="gap:10px;padding:12px 14px;background:var(--surface-2);border-radius:14px">
                <span style="font-weight:700;font-size:14px">{{ $c['label'] }}</span>
                @if ($c['ac'])<span class="badge badge-success"><x-icon name="snow" size="13px" /> مكيّف</span>@endif
                @if ($c['coach_type'])
                    <a href="{{ route('coach', $c['coach_type']) }}" class="chip pressable" style="margin-inline-start:auto;font-size:12px">شوف الكراسي</a>
                @endif
            </div>
        @empty
            <p style="color:var(--ink-soft);font-size:14px">مفيش تفاصيل درجات.</p>
        @endforelse
    </div>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div><span style="font-size:12px;color:var(--ink-soft)">يبدأ من</span> <span style="font-size:20px;font-weight:800">{{ rtrim(rtrim(number_format($trip->start_price,2),'0'),'.') }}</span> <span style="font-size:13px;color:var(--ink-soft)">جنيه</span></div>
        <a href="{{ route('seats', ['train' => $number, 'from' => $trip->from_id, 'to' => $trip->to_id, 'date' => $date]) }}" class="btn btn-accent pressable"><x-icon name="ticket" size="16px" /> اختار كرسي</a>
    </div>
</div>

@php
    $tripUrl = route('train', ['number' => $number, 'from' => $trip->from_id, 'to' => $trip->to_id, 'date' => $date]);
    $shareData = [
        'train'  => $number,
        'from'   => optional($fromStation)->name_ar,
        'to'     => optional($toStation)->name_ar,
        'depart' => $trip->departLabel(),
        'arrive' => $trip->arriveLabel(),
        'price'  => rtrim(rtrim(number_format($trip->start_price, 2), '0'), '.'),
        'url'    => $tripUrl,
    ];
    $walletData = [
        'id'        => $number.'-'.$trip->from_id.'-'.$trip->to_id,
        'train'     => $number,
        'fromName'  => optional($fromStation)->name_ar,
        'toName'    => optional($toStation)->name_ar,
        'date'      => $date,
        'dateLabel' => \Carbon\Carbon::parse($date)->translatedFormat('l j F'),
        'depart24'  => optional($trip->depart_at)->format('H:i'),
        'arrive24'  => optional($trip->arrive_at)->format('H:i'),
        'price'     => rtrim(rtrim(number_format($trip->start_price, 2), '0'), '.'),
        'url'       => $tripUrl,
    ];
@endphp
<div style="display:flex;gap:10px">
    <button type="button" class="btn btn-primary btn-block pressable" onclick='saveToWallet(@json($walletData))'>
        <x-icon name="ticket" size="16px" /> احفظ في محفظتي
    </button>
    <button type="button" class="btn btn-ghost pressable" onclick='shareTrip(@json($shareData))' aria-label="مشاركة">
        <x-icon name="route" size="16px" />
    </button>
</div>

@push('overlays')
    @include('partials.share-sheet')
@endpush
@endsection
