@extends('layout')
@section('title', 'EgTrain — رايح فين؟')

@section('content')
{{-- Hero --}}
<div class="animate-in" style="text-align:center;margin:18px 0 26px">
    <div class="badge badge-brand" style="margin-bottom:14px">
        <x-icon name="bolt" size="14px" /> أسرع من أي حاجة تانية
    </div>
    <h1 style="font-size:clamp(28px,7vw,40px);font-weight:800;letter-spacing:-.03em;line-height:1.1;margin-bottom:8px;text-wrap:balance">
        رايح فين النهارده؟
    </h1>
    <p style="color:var(--ink-soft);font-size:16px;max-width:32ch;margin:0 auto">
        مواعيد قطارات مصر وتخطيط العربيات — في ثانية.
    </p>
</div>

{{-- بطاقة البحث --}}
<form action="{{ route('search') }}" method="GET" data-search-form
      class="card animate-in" style="padding:20px;display:flex;flex-direction:column;gap:14px;position:relative">

    <div style="position:relative;display:flex;flex-direction:column;gap:14px">
        @include('partials.station-select', ['name' => 'from', 'label' => 'من محطة', 'stations' => $stations, 'selected' => $cairo, 'dot' => 'brand'])

        {{-- زر التبديل --}}
        <button type="button" onclick="swapStations()" aria-label="عكس الاتجاه"
                style="position:absolute;inset-inline-end:8px;top:50%;transform:translateY(-50%);z-index:2;width:40px;height:40px;border-radius:12px;background:var(--surface);border:1px solid var(--border);box-shadow:var(--shadow-sm);display:grid;place-items:center;color:var(--brand);cursor:pointer;transition:transform .2s var(--tap)"
                onmouseover="this.style.transform='translateY(-50%) rotate(180deg)'"
                onmouseout="this.style.transform='translateY(-50%)'">
            <x-icon name="swap" />
        </button>

        @include('partials.station-select', ['name' => 'to', 'label' => 'إلى محطة', 'stations' => $stations, 'selected' => $alex, 'dot' => 'accent'])
    </div>

    <div style="display:flex;gap:12px;align-items:flex-end">
        <div style="flex:1">
            <label class="field-label" for="date">التاريخ</label>
            <input type="date" name="date" id="date" value="{{ $date }}" class="input">
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block">
        <x-icon name="search" /> ابحث عن قطار
    </button>
</form>

{{-- المفضّلة --}}
<div style="margin-top:26px;display:none">
    <div class="field-label" style="display:flex;align-items:center;gap:6px">
        <x-icon name="star" size="16px" style="color:var(--accent)" /> رحلاتك المفضّلة
    </div>
    <div id="fav-routes" style="display:flex;flex-wrap:wrap;gap:8px"></div>
</div>

{{-- الأخيرة --}}
<div style="margin-top:22px;display:none">
    <div class="field-label" style="display:flex;align-items:center;gap:6px">
        <x-icon name="clock" size="16px" /> عمليات بحث أخيرة
    </div>
    <div id="recent-routes" class="recent-scroll" style="display:flex;gap:10px;overflow-x:auto;padding-bottom:6px"></div>
</div>

{{-- خطوط مقترحة --}}
<div style="margin-top:26px">
    <div class="field-label" style="display:flex;align-items:center;gap:6px">
        <x-icon name="route" size="16px" /> خطوط مشهورة
    </div>
    <div class="stagger" style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">
        @foreach ([
            ['القاهرة','الإسكندرية','606534187276566625','606535384603557938'],
            ['القاهرة','أسوان','606534187276566625','606535392467877956'],
            ['القاهرة','المنصورة','606534187276566625','606535388638478397'],
            ['القاهرة','بورسعيد','606534187276566625','606535386352582751'],
        ] as $r)
            <a href="{{ route('search', ['from' => $r[2], 'to' => $r[3]]) }}"
               class="card card-hover" style="padding:14px 16px;display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px">
                <span>{{ $r[0] }}</span>
                <x-icon name="arrow-l" size="16px" style="color:var(--ink-faint)" />
                <span>{{ $r[1] }}</span>
            </a>
        @endforeach
    </div>
</div>

@push('overlays')
    @include('partials.station-picker', ['stations' => $stations])
@endpush
@endsection
