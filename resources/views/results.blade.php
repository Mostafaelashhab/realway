@extends('layout')
@section('title', $title)
@section('back', '← بحث جديد')

@php use App\Support\Ar; @endphp

@section('content')
<details class="card editbox" @if ($error) open @endif>
    <summary class="edit-summary">تعديل البحث</summary>
    <div style="padding:0 2px 2px">@include('_search')</div>
</details>

<div class="hero">
    <h1>{{ $title }}</h1>
    <p>{{ Ar::date($date) }}@if (count($trains) > 1) — {{ Ar::count(count($trains), '', 'قطرين', 'قطارات', 'قطر') }}@endif</p>
</div>

@if ($error)
    <div class="card note">
        {{ $error }}
        @if ($suggest)
            <div class="sect" style="margin:12px 0 7px">تقصد واحدة من دول؟</div>
            <div class="stops">
                @foreach ($suggest as $name)
                    @php $fix = ['from' => $fromText, 'to' => $toText, 'date' => $date]; $fix[$suggestField] = $name; @endphp
                    <a class="pick" href="{{ route('search', $fix) }}">{{ $name }}</a>
                @endforeach
            </div>
        @endif
    </div>
@endif

@if ($fallback)
    <div class="card banner warn">
        <b>نظام السكة الحديد مش راد دلوقتي</b>
        دي مواعيد محفوظة عندنا من الجدول الأسبوعي@if ($fallbackAt) (آخر تحديث {{ Ar::date($fallbackAt) }})@endif —
        ممكن تكون اتغيّرت، و<b>الأسعار التفصيلية والكراسي مش متاحة</b> من غير النظام.
        جرّب تاني بعد شوية.
    </div>
@endif

@if ($scheduleOnly && $trains)
    <div class="card banner">
        <b>الكراسي الفاضية بتبان قبل الرحلة بيوم.</b>
        السكة الحديد بتقفل الحجز أونلاين لنفس اليوم، فمفيش توفّر للنهاردة —
        دوّر بتاريخ بكرة أو بعده وهتشوف الكراسي وأرقامها.
        المواعيد والدرجات والأسعار اللي تحت حقيقية، مأخوذة من الجدول الأسبوعي (نفس اليوم الأسبوع الجاي).
    </div>
@endif

@foreach ($trains as $t)
    <div class="card train">
        <div class="thead">
            <div>
                <div class="tno">قطر {{ Ar::num($t['number']) }}</div>
                @if ($t['name'])<span class="chip">{{ $t['name'] }}</span>@endif
            </div>
            <div class="price">
                <b>{{ Ar::money($t['start_price']) }}</b>
                <span>أقل سعر</span>
            </div>
        </div>

        <div class="rail">
            <div class="stop-t">
                <b>{{ Ar::time($t['depart']) }}</b>
                <span>{{ $t['from'] }}</span>
            </div>
            <div class="mid"><span>{{ Ar::duration($t['duration_min']) }}</span></div>
            <div class="stop-t end">
                <b>{{ Ar::time($t['arrive']) }}</b>
                <span>{{ $t['to'] }}</span>
            </div>
        </div>

        <div class="sect">الدرجات والأسعار</div>
        @forelse ($t['classes'] as $c)
            <div class="cls">
                <div class="cls-h">
                    <b>{{ $c['name'] }}</b>
                    @if ($c['seats'] === null)
                        <span class="free none">{{ $fallback ? 'التوفّر مش متاح' : 'التوفّر مش متاح للنهاردة' }}</span>
                    @elseif ($c['seats'] === 0)
                        <span class="free none">مفيش كراسي فاضية</span>
                    @else
                        <span class="free">فاضي {{ Ar::count($c['seats'], 'كرسي واحد', 'كرسيين', 'كراسي', 'كرسي') }}</span>
                    @endif
                    @if ($c['price'] !== null)<span class="pr">{{ Ar::money($c['price']) }}</span>@endif
                </div>
                @foreach ($c['coaches'] as $coach)
                    <details>
                        <summary>عربية {{ Ar::num($coach['coach']) }} — {{ Ar::count(count($coach['seats']), 'كرسي واحد', 'كرسيين', 'كراسي', 'كرسي') }}</summary>
                        <div class="seatgrid">
                            @foreach ($coach['seats'] as $s)<span class="seat">{{ Ar::num($s) }}</span>@endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        @empty
            <p class="note" style="padding:0">مفيش تفاصيل درجات للقطر ده.</p>
        @endforelse

        <div class="sect">
            بيقف في {{ Ar::count(count($t['stops']), 'محطة واحدة', 'محطتين', 'محطات', 'محطة') }}
            @if ($t['distance_km']) — {{ Ar::num($t['distance_km']) }} كم @endif
        </div>
        <div class="stops">
            @foreach ($t['stops'] as $i => $stop)
                <span @class(['edge' => $i === 0 || $i === count($t['stops']) - 1])>{{ $stop }}</span>
            @endforeach
        </div>
    </div>
@endforeach
@endsection
