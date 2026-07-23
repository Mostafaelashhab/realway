@extends('layout')
@section('title', 'رحلاتي وإنجازاتي — EgTrain')

@section('content')
<div class="flex items-center justify-between animate-in" style="margin-bottom:18px">
    <div>
        <h1 style="font-size:22px;font-weight:800;letter-spacing:-.02em;display:flex;align-items:center;gap:8px">
            <x-icon name="star" size="24px" style="color:var(--accent)" /> رحلاتي وإنجازاتي
        </h1>
        <p style="color:var(--ink-soft);font-size:13px;margin-top:2px">ملخّص رحلاتك على القطر — كله محفوظ على جهازك</p>
    </div>
    <button type="button" id="share-stats-btn" onclick="shareStats()" class="btn btn-ghost btn-icon pressable" aria-label="مشاركة" style="display:none"><x-icon name="route" /></button>
</div>

{{-- الإحصائيات --}}
<div id="stats-wrap" style="display:none">
    <div class="stagger" style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:14px">
        <div class="card" style="padding:16px;text-align:center">
            <div id="st-trips" class="tnum" style="font-size:30px;font-weight:800;color:var(--brand)">0</div>
            <div style="font-size:12px;color:var(--ink-soft)">رحلة</div>
        </div>
        <div class="card" style="padding:16px;text-align:center">
            <div id="st-km" class="tnum" style="font-size:30px;font-weight:800;color:var(--accent)">0</div>
            <div style="font-size:12px;color:var(--ink-soft)">كيلومتر</div>
        </div>
        <div class="card" style="padding:16px;text-align:center">
            <div id="st-stations" class="tnum" style="font-size:30px;font-weight:800">0</div>
            <div style="font-size:12px;color:var(--ink-soft)">محطة زرتها</div>
        </div>
        <div class="card" style="padding:16px;text-align:center">
            <div id="st-hours" class="tnum" style="font-size:30px;font-weight:800">0</div>
            <div style="font-size:12px;color:var(--ink-soft)">ساعة على القطر</div>
        </div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
            <span style="color:var(--ink-soft);font-size:14px">أكتر خط بتسافره</span>
            <span id="st-route" style="font-weight:700;font-size:14px">—</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0">
            <span style="color:var(--ink-soft);font-size:14px">أطول رحلة</span>
            <span id="st-longest" style="font-weight:700;font-size:14px">—</span>
        </div>
    </div>

    {{-- الإنجازات --}}
    <h2 style="font-size:16px;font-weight:800;margin:18px 0 12px">الإنجازات</h2>
    <div id="achievements" class="stagger" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px"></div>
</div>

{{-- حالة فاضية --}}
<div id="stats-empty" style="display:none">
    <div class="card" style="padding:44px 24px;text-align:center">
        <div style="width:64px;height:64px;border-radius:20px;background:var(--surface-2);display:grid;place-items:center;margin:0 auto 16px;color:var(--ink-faint)"><x-icon name="star" size="32px" /></div>
        <h2 style="font-weight:700;font-size:17px;margin-bottom:6px">لسه مسجّلتش أي رحلة</h2>
        <p style="color:var(--ink-soft);font-size:14px;max-width:34ch;margin:0 auto 18px">أول ما تسافر، اضغط "سجّلت الرحلة دي" في صفحة القطر — وهتشوف إحصائياتك وإنجازاتك بتكبر.</p>
        <a href="{{ route('home') }}" class="btn btn-primary pressable"><x-icon name="search" size="16px" /> ابحث عن قطار</a>
    </div>
</div>

@push('overlays')
    @include('partials.share-sheet')
@endpush
@endsection
