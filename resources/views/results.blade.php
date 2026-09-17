@extends('layout')
@section('title', $title)
@section('back', '← بحث جديد')

@php use App\Support\Ar; @endphp

@section('content')
<div class="hero">
    <h1>{{ $title }}</h1>
    <p>{{ Ar::date($date) }}@if (count($trains) > 1) — {{ Ar::count(count($trains), '', 'قطرين', 'قطارات', 'قطر') }}@endif</p>
</div>

@if ($error)
    <p class="card note">{{ $error }}</p>
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
                    @if ($c['seats'] === 0)
                        <span class="free none">مفيش كراسي فاضية</span>
                    @else
                        <span class="free">فاضي {{ Ar::count($c['seats'], 'كرسي واحد', 'كرسيين', 'كراسي', 'كرسي') }}</span>
                    @endif
                    <span class="pr">{{ Ar::money($c['price']) }}</span>
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
