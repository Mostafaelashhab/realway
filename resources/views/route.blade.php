@php
    $fromN = $fromStation->name_ar; $toN = $toStation->name_ar;
    $fmtDur = fn ($m) => intdiv($m, 60) . 'س ' . ($m % 60) . 'د';
    $money = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
@endphp
@extends('layout')
@section('title', "قطارات $fromN $toN — مواعيد وأسعار | EgTrain")

@section('content')
<nav style="font-size:12px;color:var(--ink-soft);margin-bottom:12px">
    <a href="{{ route('home') }}" style="color:inherit">الرئيسية</a> ›
    <a href="{{ route('station', $fromStation->id) }}" style="color:inherit">{{ $fromN }}</a> ›
    <span style="color:var(--ink)">{{ $toN }}</span>
</nav>

<div class="animate-in">
    <h1 style="font-size:clamp(22px,5vw,30px);font-weight:800;letter-spacing:-.02em;line-height:1.2;margin-bottom:6px;text-wrap:balance">
        قطارات {{ $fromN }} <span style="color:var(--accent)">←</span> {{ $toN }}
    </h1>
    <p style="color:var(--ink-soft);font-size:15px;margin-bottom:18px;max-width:60ch;line-height:1.7">
        كل مواعيد وأسعار القطارات من {{ $fromN }} لـ {{ $toN }}: عدد القطارات، أسرع رحلة، أرخص تذكرة، ومدة الرحلة — في مكان واحد.
    </p>
</div>

@if ($stats['count'])
{{-- إحصائيات الخط --}}
<div class="stagger" style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:18px">
    <div class="card" style="padding:14px">
        <div style="font-size:12px;color:var(--ink-soft);margin-bottom:2px">عدد القطارات</div>
        <div class="tnum" style="font-size:22px;font-weight:800">{{ $stats['count'] }}</div>
    </div>
    <div class="card" style="padding:14px">
        <div style="font-size:12px;color:var(--ink-soft);margin-bottom:2px">أسرع رحلة</div>
        <div class="tnum" style="font-size:22px;font-weight:800">{{ $fmtDur($stats['fastest']) }}</div>
    </div>
    <div class="card" style="padding:14px">
        <div style="font-size:12px;color:var(--ink-soft);margin-bottom:2px">تبدأ الأسعار من</div>
        <div class="tnum" style="font-size:22px;font-weight:800;color:var(--brand)">{{ $money($stats['cheapest']) }} <span style="font-size:13px;font-weight:500;color:var(--ink-soft)">ج</span></div>
    </div>
    <div class="card" style="padding:14px">
        <div style="font-size:12px;color:var(--ink-soft);margin-bottom:2px">المسافة</div>
        <div class="tnum" style="font-size:22px;font-weight:800">{{ $stats['distance'] }} <span style="font-size:13px;font-weight:500;color:var(--ink-soft)">كم</span></div>
    </div>
</div>

<a href="{{ route('search', ['from' => $from, 'to' => $to, 'date' => $date]) }}" class="btn btn-primary btn-block btn-lg pressable" style="margin-bottom:22px">
    <x-icon name="search" /> شوف كل المواعيد واحجز يومك
</a>

{{-- قائمة القطارات --}}
<h2 style="font-size:17px;font-weight:800;margin-bottom:12px">كل القطارات على الخط</h2>
<div style="display:flex;flex-direction:column;gap:8px;margin-bottom:24px">
    @foreach ($trains as $row)
        @php $t = $row['trip']; @endphp
        <a href="{{ route('train', ['number' => $t->train_number, 'from' => $from, 'to' => $to, 'date' => $date]) }}"
           class="card card-hover" style="padding:12px 14px;display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit">
            <span class="tnum" style="font-weight:800;font-size:15px;min-width:56px">{{ $t->departLabel() }}</span>
            <div style="flex:1">
                <div style="font-size:13px;color:var(--ink-soft)">{{ $fmtDur($t->duration_min) }} · {{ $t->stops_count }} محطة</div>
            </div>
            @if ($row['is_ac'])<span class="badge badge-success"><x-icon name="snow" size="12px" /> مكيّف</span>@endif
            <span class="badge badge-brand tnum">{{ $t->train_number }}</span>
            <span style="font-size:13px;font-weight:700;color:var(--brand)">{{ $money($t->start_price) }} ج</span>
        </a>
    @endforeach
</div>

{{-- دليل الخط --}}
<div class="card" style="padding:20px;margin-bottom:18px">
    <h2 style="font-size:16px;font-weight:800;margin-bottom:10px">دليل السفر: {{ $fromN }} إلى {{ $toN }}</h2>
    <p style="color:var(--ink);font-size:14px;line-height:1.8">
        الخط ده بيشغّله <b>{{ $stats['count'] }} قطر</b> يوميًا (استرشادي)، والمسافة حوالي <b>{{ $stats['distance'] }} كم</b>.
        أسرع رحلة بتاخد حوالي <b>{{ $fmtDur($stats['fastest']) }}</b>، والأسعار بتبدأ من <b>{{ $money($stats['cheapest']) }} جنيه</b>
        وبتوصل لـ <b>{{ $money($stats['priciest']) }} جنيه</b> حسب الدرجة.
        @if ($stats['has_ac']) فيه قطارات <b>مكيّفة</b> على الخط. @endif
        للحجز والتأكيد النهائي، ارجع لنظام حجز السكة الحديد الرسمي.
    </p>
</div>

{{-- FAQ (SEO) --}}
<div class="card" style="padding:20px;margin-bottom:18px">
    <h2 style="font-size:16px;font-weight:800;margin-bottom:12px">أسئلة شائعة</h2>
    @php $faqs = [
        ['كام قطر من '.$fromN.' لـ '.$toN.'؟', 'حوالي '.$stats['count'].' قطر (حسب اليوم، استرشادي).'],
        ['أرخص تذكرة قد إيه؟', 'الأسعار بتبدأ من '.$money($stats['cheapest']).' جنيه تقريبًا.'],
        ['أسرع قطر بياخد قد إيه؟', 'أسرع رحلة حوالي '.$fmtDur($stats['fastest']).'.'],
        ['المسافة كام كيلومتر؟', 'حوالي '.$stats['distance'].' كم.'],
    ]; @endphp
    <div style="display:flex;flex-direction:column;gap:12px">
        @foreach ($faqs as $f)
            <div>
                <div style="font-weight:700;font-size:14px;margin-bottom:3px">{{ $f[0] }}</div>
                <div style="color:var(--ink-soft);font-size:14px">{{ $f[1] }}</div>
            </div>
        @endforeach
    </div>
</div>

<livewire:community scope-type="route" :scope-key="$from.'-'.$to" title="نصايح الركّاب على الخط" />

{{-- JSON-LD للـ SEO --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqs)->map(fn ($f) => [
        '@type' => 'Question', 'name' => $f[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
    ])->all(),
], JSON_UNESCAPED_UNICODE) !!}
</script>

@else
<div class="card" style="padding:40px 24px;text-align:center">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--surface-2);display:grid;place-items:center;margin:0 auto 14px;color:var(--ink-faint)"><x-icon name="route" size="32px" /></div>
    <h2 style="font-weight:700;font-size:17px;margin-bottom:6px">لسه مفيش قطارات محفوظة على الخط ده</h2>
    <a href="{{ route('search', ['from' => $from, 'to' => $to]) }}" class="btn btn-primary pressable" style="margin-top:12px"><x-icon name="search" size="16px" /> ابحث الآن</a>
</div>
@endif
@endsection
