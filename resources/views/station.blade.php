@extends('layout')
@section('title', 'محطة ' . $station->name_ar)

@section('content')
<a href="javascript:history.back()" class="chip pressable" style="margin-bottom:14px"><x-icon name="arrow-r" size="15px" /> رجوع</a>

{{-- ترويسة المحطة --}}
<div class="card animate-in" style="padding:22px;margin-bottom:14px">
    <div class="flex items-center" style="gap:14px">
        <span style="display:grid;place-items:center;width:52px;height:52px;border-radius:16px;background:var(--brand-tint);color:var(--brand)"><x-icon name="pin" size="28px" /></span>
        <div style="flex:1">
            <h1 style="font-size:22px;font-weight:800;letter-spacing:-.02em">{{ $station->name_ar }}</h1>
            <div class="flex items-center" style="gap:8px;margin-top:3px">
                <span style="font-size:13px;color:var(--ink-soft)">{{ $station->name_en }}</span>
                @if ($station->code)<span class="badge badge-brand tnum">كود {{ $station->code }}</span>@endif
            </div>
        </div>
    </div>
</div>

{{-- وجهات مشهورة --}}
@if ($popularRoutes->count())
<div class="card animate-in" style="padding:20px;margin-bottom:14px">
    <h2 style="font-size:15px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:6px"><x-icon name="route" size="18px" /> وجهات مشهورة من هنا</h2>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        @foreach ($popularRoutes as $r)
            <a href="{{ route('search', ['from' => $station->id, 'to' => $r['to'], 'date' => $date]) }}" class="chip pressable">
                {{ $station->name_ar }} <span style="color:var(--accent)">←</span> {{ $r['name'] }}
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- لوحة المواعيد --}}
@if ($departures->count())
<div class="card animate-in" style="padding:20px;margin-bottom:14px">
    <h2 style="font-size:15px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:6px"><x-icon name="clock" size="18px" /> مواعيد القيام</h2>
    <div style="display:flex;flex-direction:column;gap:6px">
        @foreach ($departures as $d)
            <a href="{{ route('train', ['number' => $d['train'], 'from' => $station->id, 'to' => $d['to'], 'date' => $d['date']]) }}"
               class="flex items-center pressable" style="gap:12px;padding:11px 14px;background:var(--surface-2);border-radius:12px;text-decoration:none;color:inherit">
                <span class="tnum" style="font-weight:800;font-size:15px;min-width:64px">{{ $d['depart'] }}</span>
                <span style="flex:1;font-weight:600;font-size:14px">{{ $d['toName'] }}</span>
                <span class="badge badge-brand tnum">{{ $d['train'] }}</span>
                <x-icon name="chevron" size="15px" style="color:var(--ink-faint)" />
            </a>
        @endforeach
    </div>
</div>
@endif

@if (! $popularRoutes->count() && ! $departures->count())
<div class="card" style="padding:36px 24px;text-align:center">
    <div style="width:60px;height:60px;border-radius:18px;background:var(--surface-2);display:grid;place-items:center;margin:0 auto 12px;color:var(--ink-faint)"><x-icon name="route" size="30px" /></div>
    <h2 style="font-weight:700;font-size:16px;margin-bottom:4px">لسه مفيش مواعيد محفوظة للمحطة دي</h2>
    <p style="color:var(--ink-soft);font-size:14px">هتظهر أول ما حد يبحث خط بيعدي عليها.</p>
</div>
@endif

<livewire:community scope-type="station" :scope-key="$station->id" title="نصايح وأسئلة عن المحطة" />

<p style="font-size:12px;color:var(--ink-faint);text-align:center;margin-top:6px;display:flex;align-items:center;justify-content:center;gap:6px">
    <x-icon name="info" size="13px" /> الخريطة والموقع الجغرافي قريبًا
</p>
@endsection
