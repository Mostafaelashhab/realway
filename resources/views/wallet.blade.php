@extends('layout')
@section('title', 'محفظة رحلاتي — EgTrain')

@section('content')
<div class="flex items-center justify-between animate-in" style="margin-bottom:16px">
    <div>
        <h1 style="font-size:22px;font-weight:800;letter-spacing:-.02em;display:flex;align-items:center;gap:8px">
            <x-icon name="ticket" size="24px" style="color:var(--brand)" /> محفظة رحلاتي
        </h1>
        <p style="color:var(--ink-soft);font-size:13px;margin-top:2px">رحلاتك المحفوظة — شغّالة حتى من غير نت</p>
    </div>
    <a href="{{ route('home') }}" class="btn btn-primary pressable" style="padding:9px 14px;font-size:13px"><x-icon name="search" size="16px" /> رحلة جديدة</a>
</div>

<div id="wallet-list" class="stagger"></div>

{{-- حالة فاضية --}}
<div id="wallet-empty" class="card" style="display:none;padding:44px 24px;text-align:center">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--surface-2);display:grid;place-items:center;margin:0 auto 16px;color:var(--ink-faint)"><x-icon name="ticket" size="32px" /></div>
    <h2 style="font-weight:700;font-size:17px;margin-bottom:6px">محفظتك فاضية لسه</h2>
    <p style="color:var(--ink-soft);font-size:14px;max-width:34ch;margin:0 auto 18px">ابحث عن رحلة واحفظها هنا عشان توصلها بسرعة — حتى من غير إنترنت.</p>
    <a href="{{ route('home') }}" class="btn btn-primary pressable"><x-icon name="search" size="16px" /> ابحث عن قطار</a>
</div>

<p style="font-size:12px;color:var(--ink-faint);text-align:center;margin-top:16px;line-height:1.6">
    التذكيرات والمواعيد استرشادية — الحجز والتأكيد من المصدر الرسمي.
</p>

@push('overlays')
    @include('partials.share-sheet')
@endpush
@endsection
