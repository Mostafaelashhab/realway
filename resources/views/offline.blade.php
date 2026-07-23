@extends('layout')
@section('title', 'غير متصل — EgTrain')

@section('content')
<div class="card animate-in" style="padding:44px 24px;text-align:center;margin-top:20px">
    <div style="width:72px;height:72px;border-radius:22px;background:var(--brand-tint);display:grid;place-items:center;margin:0 auto 18px;color:var(--brand)">
        <x-icon name="train" size="38px" />
    </div>
    <h1 style="font-size:20px;font-weight:800;margin-bottom:8px">إنت مش متصل بالنت دلوقتي</h1>
    <p style="color:var(--ink-soft);font-size:14px;max-width:34ch;margin:0 auto 20px;line-height:1.6">
        الصفحات اللي فتحتها قبل كده ومحفظة رحلاتك شغّالين offline. جرّب تفتح المحفظة أو ترجع للصفحات اللي زرتها.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
        <a href="{{ route('wallet') }}" class="btn btn-primary pressable"><x-icon name="ticket" size="16px" /> محفظة رحلاتي</a>
        <button type="button" onclick="location.reload()" class="btn btn-ghost pressable">حاول تاني</button>
    </div>
</div>
@endsection
