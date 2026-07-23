@extends('layout')
@section('title', ($fromStation->name_ar ?? '') . ' ← ' . ($toStation->name_ar ?? ''))

@section('content')
{{-- شريط بحث مصغّر --}}
<form action="{{ route('search') }}" method="GET" data-search-form class="card animate-in" style="padding:14px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;position:relative">
        @include('partials.station-select', ['name' => 'from', 'label' => 'من', 'stations' => $stations, 'selected' => $from, 'dot' => 'brand'])
        @include('partials.station-select', ['name' => 'to', 'label' => 'إلى', 'stations' => $stations, 'selected' => $to, 'dot' => 'accent'])
    </div>
    <div style="display:flex;gap:10px;margin-top:10px">
        <input type="date" name="date" value="{{ $date }}" class="input" style="flex:1">
        <button type="submit" class="btn btn-primary" aria-label="ابحث"><x-icon name="search" /></button>
    </div>
</form>

{{-- ترويسة --}}
<div class="flex items-center justify-between" style="margin-bottom:12px">
    <div>
        <h1 style="font-size:20px;font-weight:800;letter-spacing:-.02em;display:flex;align-items:center;gap:8px">
            {{ optional($fromStation)->name_ar ?? 'محطة' }}
            <x-icon name="arrow-l" size="18px" style="color:var(--accent)" />
            {{ optional($toStation)->name_ar ?? 'محطة' }}
        </h1>
        <p style="color:var(--ink-soft);font-size:13px;margin-top:2px">
            {{ \Carbon\Carbon::parse($date)->translatedFormat('l j F') }} · <span id="trip-count">{{ $trips->count() }}</span> قطر
        </p>
    </div>
    <button type="button" onclick="toggleFav({from:'{{ $from }}',to:'{{ $to }}',fromName:'{{ optional($fromStation)->name_ar }}',toName:'{{ optional($toStation)->name_ar }}'}, this)"
            class="btn btn-ghost btn-icon pressable" aria-label="حفظ في المفضّلة">
        <x-icon name="star" />
    </button>
</div>

@if ($trips->count())
{{-- شريط الفلاتر --}}
<div id="filter-bar" style="display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;margin-bottom:14px;scrollbar-width:none">
    <button type="button" class="chip filter-sort chip-active" data-sort="best" onclick="setSort(this,'best')"><x-icon name="star" size="15px" /> الأفضل</button>
    <button type="button" class="chip filter-sort" data-sort="fastest" onclick="setSort(this,'fastest')"><x-icon name="bolt" size="15px" /> الأسرع</button>
    <button type="button" class="chip filter-sort" data-sort="cheapest" onclick="setSort(this,'cheapest')"><x-icon name="tag" size="15px" /> الأرخص</button>
    <button type="button" class="chip filter-sort" data-sort="earliest" onclick="setSort(this,'earliest')"><x-icon name="sunrise" size="15px" /> الأبكر</button>
    <button type="button" class="chip filter-sort" data-sort="latest" onclick="setSort(this,'latest')"><x-icon name="moon" size="15px" /> الأأخر</button>
    <button type="button" id="ac-chip" class="chip" onclick="toggleAc(this)"><x-icon name="snow" size="15px" /> مكيّف</button>
    <button type="button" class="chip" onclick="openFiltersSheet()"><x-icon name="sort" size="15px" /> فلاتر</button>
</div>
@endif

{{-- قائمة القطارات --}}
<div id="results-list">
@forelse ($trips as $row)
    @php
        $t = $row['trip'];
        $isRec = $recommendedTrain && $t->train_number === $recommendedTrain;
        $shareData = [
            'train'  => $t->train_number,
            'from'   => optional($fromStation)->name_ar,
            'to'     => optional($toStation)->name_ar,
            'depart' => $t->departLabel(),
            'arrive' => $t->arriveLabel(),
            'price'  => rtrim(rtrim(number_format($t->start_price, 2), '0'), '.'),
            'url'    => route('train', ['number' => $t->train_number, 'from' => $from, 'to' => $to, 'date' => $date]),
        ];
    @endphp
    <div class="trip-card card {{ $isRec ? 'is-recommended' : '' }}" style="padding:0;margin-bottom:12px;overflow:hidden;animation:fadeInUp .4s var(--tap) both;animation-delay:{{ $loop->index * 0.04 }}s"
         data-train="{{ $t->train_number }}"
         data-price="{{ $row['price'] }}" data-duration="{{ $row['duration'] }}"
         data-stops="{{ $row['stops'] }}" data-depart="{{ $row['depart'] }}"
         data-ac="{{ $row['is_ac'] ? '1' : '0' }}" data-type="{{ $row['type'] }}"
         data-arrive="{{ $t->arriveLabel() }}" data-rec="{{ $isRec ? '1' : '0' }}">

        @if ($isRec && count($recommendReasons))
            <div class="rec-ribbon" style="background:var(--brand);color:#fff;padding:8px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <span style="font-weight:800;font-size:13px;display:flex;align-items:center;gap:5px"><x-icon name="star" size="15px" /> مرشّح ليك</span>
                <span style="display:flex;gap:6px;flex-wrap:wrap">
                    @foreach ($recommendReasons as $r)
                        <span style="font-size:12px;background:rgba(255,255,255,.18);padding:2px 8px;border-radius:6px">{{ $r }}</span>
                    @endforeach
                </span>
            </div>
        @endif

        <div style="padding:16px">
            {{-- رحلة --}}
            <div class="flex items-center" style="gap:14px">
                <div style="text-align:center;min-width:66px">
                    <div class="tnum" style="font-size:19px;font-weight:800;letter-spacing:-.02em">{{ $t->departLabel() ?? '--' }}</div>
                    <div style="font-size:11px;color:var(--ink-faint);margin-top:1px">قيام</div>
                </div>
                <div style="flex:1;text-align:center">
                    <div style="font-size:11px;color:var(--ink-soft);margin-bottom:5px">{{ intdiv($t->duration_min, 60) }}س {{ $t->duration_min % 60 }}د</div>
                    <div style="position:relative;height:2px;background:var(--border);border-radius:2px">
                        <span style="position:absolute;top:50%;inset-inline-start:0;transform:translateY(-50%);width:8px;height:8px;border-radius:50%;background:var(--brand)"></span>
                        <span style="position:absolute;top:50%;inset-inline-start:50%;transform:translate(-50%,-50%);color:var(--ink-faint)"><x-icon name="train" size="15px" style="background:var(--surface);padding:0 2px" /></span>
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
                <a href="{{ route('train', ['number' => $t->train_number, 'from' => $from, 'to' => $to, 'date' => $date]) }}"
                   class="badge badge-brand pressable" style="text-decoration:none"><x-icon name="train" size="13px" /> قطر {{ $t->train_number }}</a>
                @if ($row['is_ac'])<span class="badge badge-success"><x-icon name="snow" size="13px" /> مكيّف</span>@endif
                <span style="color:var(--ink-soft);font-size:13px;font-weight:600">من {{ rtrim(rtrim(number_format($t->start_price, 2), '0'), '.') }} ج</span>

                <div style="margin-inline-start:auto;display:flex;gap:8px">
                    <button type="button" class="btn btn-ghost btn-icon pressable compare-btn"
                            onclick="toggleCompare(this)" aria-label="قارن" title="قارن">
                        <x-icon name="swap" size="16px" />
                    </button>
                    <button type="button" class="btn btn-ghost btn-icon pressable"
                            onclick='shareTrip(@json($shareData))'
                            aria-label="مشاركة"><x-icon name="route" size="16px" /></button>
                    <a href="{{ route('live-seats', ['train' => $t->train_number, 'from' => $from, 'to' => $to, 'date' => $date]) }}"
                       class="btn btn-accent pressable" style="padding:9px 14px;font-size:13px"><x-icon name="ticket" size="16px" /> كرسي</a>
                </div>
            </div>
        </div>
    </div>
@empty
@endforelse
</div>

{{-- حالة "مفيش نتايج بعد الفلترة" --}}
<div id="filter-empty" class="card" style="display:none;padding:32px 24px;text-align:center">
    <div style="width:56px;height:56px;border-radius:16px;background:var(--surface-2);display:grid;place-items:center;margin:0 auto 12px;color:var(--ink-faint)"><x-icon name="route" size="28px" /></div>
    <h2 style="font-weight:700;font-size:16px;margin-bottom:4px">مفيش قطار بالفلاتر دي</h2>
    <p style="color:var(--ink-soft);font-size:14px;margin-bottom:14px">جرّب تشيل بعض الفلاتر.</p>
    <button type="button" class="btn btn-ghost" onclick="clearFilters()">مسح الفلاتر</button>
</div>

{{-- حالة فاضية (مفيش قطارات أصلاً) --}}
@if (! $trips->count())
    <div class="card animate-in" style="padding:40px 24px;text-align:center">
        <div style="width:72px;height:72px;border-radius:22px;background:var(--brand-tint);display:grid;place-items:center;margin:0 auto 16px;color:var(--brand)">
            <x-icon name="route" size="36px" />
        </div>
        <h2 style="font-weight:800;font-size:18px;margin-bottom:6px">مفيش قطار مباشر على الخط ده</h2>
        <p style="color:var(--ink-soft);font-size:14px;max-width:38ch;margin:0 auto 20px">
            يمكن مفيش قطر مباشر بين المحطتين في التاريخ ده. جرّب يوم قريب أو محطة تانية.
        </p>
        {{-- أيام مقترحة --}}
        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:8px">
            @foreach ([1,2,3] as $d)
                @php $alt = \Carbon\Carbon::parse($date)->addDays($d); @endphp
                <a href="{{ route('search', ['from' => $from, 'to' => $to, 'date' => $alt->toDateString()]) }}"
                   class="chip">{{ $alt->translatedFormat('l j/n') }}</a>
            @endforeach
        </div>
        <a href="{{ route('home') }}" class="btn btn-primary" style="margin-top:12px"><x-icon name="search" size="16px" /> بحث جديد</a>
    </div>
@endif

{{-- شريط المقارنة العائم --}}
<div id="compare-bar" style="position:fixed;bottom:16px;inset-inline:0;z-index:50;display:none;justify-content:center;pointer-events:none">
    <button type="button" onclick="openCompare()" class="btn btn-primary pressable" style="pointer-events:auto;box-shadow:var(--shadow-lg);padding:12px 22px">
        <x-icon name="swap" size="18px" /> قارن <span id="compare-count">0</span> قطر
    </button>
</div>

@push('overlays')
    @include('partials.station-picker', ['stations' => $stations])
    @include('partials.share-sheet')

    {{-- شيت الفلاتر المتقدمة (على مستوى body عشان position:fixed يشتغل صح) --}}
    <div id="filters-backdrop" class="sheet-backdrop" onclick="closeFiltersSheet()"></div>
    <div id="filters-sheet" class="sheet" role="dialog" aria-modal="true" aria-label="فلاتر">
        <div class="sheet-handle"></div>
        <div class="sheet-head">
            <div class="sheet-title">فلاتر متقدمة</div>
            <button type="button" class="btn btn-ghost btn-icon pressable" onclick="closeFiltersSheet()" aria-label="إغلاق" style="margin-inline-start:auto"><x-icon name="x" size="18px" /></button>
        </div>
        <div class="sheet-body" style="padding:0 18px 20px">
            <div style="margin-bottom:18px">
                <label class="field-label">أقصى سعر: <span id="price-val" style="color:var(--brand)"></span> جنيه</label>
                <input type="range" id="price-range" min="0" max="0" step="5" style="width:100%;accent-color:var(--brand)" oninput="onPriceInput(this.value)">
            </div>
            <div style="margin-bottom:18px">
                <label class="field-label">أقصى عدد وقفات: <span id="stops-val" style="color:var(--brand)"></span></label>
                <input type="range" id="stops-range" min="0" max="0" step="1" style="width:100%;accent-color:var(--brand)" oninput="onStopsInput(this.value)">
            </div>
            <div id="type-filters" style="margin-bottom:18px">
                <label class="field-label">نوع القطر</label>
                <div id="type-chips" style="display:flex;gap:8px;flex-wrap:wrap"></div>
            </div>
            <div style="display:flex;gap:10px">
                <button type="button" class="btn btn-ghost btn-block" onclick="clearFilters()">مسح</button>
                <button type="button" class="btn btn-primary btn-block" onclick="closeFiltersSheet()">تم</button>
            </div>
        </div>
    </div>

    {{-- شيت المقارنة --}}
    <div id="compare-backdrop" class="sheet-backdrop" onclick="closeCompare()"></div>
    <div id="compare-sheet" class="sheet" role="dialog" aria-modal="true" aria-label="مقارنة">
        <div class="sheet-handle"></div>
        <div class="sheet-head">
            <div class="sheet-title">مقارنة القطارات</div>
            <button type="button" class="btn btn-ghost btn-icon pressable" onclick="closeCompare()" aria-label="إغلاق" style="margin-inline-start:auto"><x-icon name="x" size="18px" /></button>
        </div>
        <div class="sheet-body" style="padding:0 14px 20px">
            <div id="compare-content"></div>
        </div>
    </div>
@endpush
@endsection
