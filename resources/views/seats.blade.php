@extends('layout')
@section('title', 'تخطيط عربيات قطر ' . $train)

@section('content')
<a href="javascript:history.back()" class="chip pressable" style="margin-bottom:14px"><x-icon name="arrow-r" size="15px" /> رجوع</a>

<div class="card animate-in" style="padding:20px">
    <div class="flex items-center justify-between" style="gap:10px;flex-wrap:wrap;margin-bottom:4px">
        <h1 style="font-size:18px;font-weight:800;letter-spacing:-.02em;display:flex;align-items:center;gap:8px">
            <span class="badge badge-brand"><x-icon name="train" size="14px" /> {{ $train }}</span>
            {{ optional($fromStation)->name_ar }}
            <x-icon name="arrow-l" size="16px" style="color:var(--accent)" />
            {{ optional($toStation)->name_ar }}
        </h1>
        <span class="chip" style="cursor:default"><x-icon name="info" size="14px" /> للتخطيط فقط</span>
    </div>

    {{-- تنبيه: تخطيط فقط --}}
    <div style="background:var(--brand-tint);border-radius:12px;padding:12px 14px;margin:12px 0 16px;display:flex;gap:10px;align-items:flex-start">
        <span style="color:var(--brand);margin-top:1px"><x-icon name="info" size="18px" /></span>
        <p style="font-size:13px;color:var(--ink);line-height:1.6;margin:0">
            اختيار الكرسي هنا <b>للتخطيط والاسترشاد فقط</b> — عشان تعرف شكل العربية ومكان الشباك.
            الحجز والتأكيد النهائي بيتمّوا من <b>نظام حجز السكة الحديد الرسمي</b>.
        </p>
    </div>

    @if (! $selected)
        <div style="text-align:center;padding:28px 16px">
            <div style="width:56px;height:56px;border-radius:16px;background:var(--surface-2);display:grid;place-items:center;margin:0 auto 12px;color:var(--ink-faint)"><x-icon name="seat" size="28px" /></div>
            <p style="font-weight:700;font-size:15px">مفيش تخطيط عربيات للقطر ده</p>
            <p style="color:var(--ink-soft);font-size:13px;margin-top:4px">تقدر تشوف مواعيده ودرجاته من صفحة القطر.</p>
        </div>
    @else
        {{-- تبويبات الدرجات --}}
        @if ($coaches->count() > 1)
        <div style="display:flex;gap:8px;overflow-x:auto;margin-bottom:16px;padding-bottom:4px">
            @foreach ($coaches as $c)
                <a href="{{ route('seats', ['train' => $train, 'from' => $from, 'to' => $to, 'date' => $date, 'coach' => $c['class_id']]) }}"
                   class="chip {{ $c['class_id'] === $selected['class_id'] ? 'chip-active' : '' }}" style="white-space:nowrap">
                    {{ $c['label'] }}
                </a>
            @endforeach
        </div>
        @endif

        {{-- معلومات العربية --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px">
            <div style="background:var(--surface-2);border-radius:12px;padding:10px;text-align:center">
                <div style="font-size:11px;color:var(--ink-soft);margin-bottom:2px">الدرجة</div>
                <div style="font-weight:700;font-size:13px">{{ $selected['label'] }}</div>
            </div>
            <div style="background:var(--surface-2);border-radius:12px;padding:10px;text-align:center">
                <div style="font-size:11px;color:var(--ink-soft);margin-bottom:2px">التكييف</div>
                <div style="font-weight:700;font-size:13px">{{ $selected['ac'] ? 'مكيّفة' : 'عادية' }}</div>
            </div>
            <div style="background:var(--surface-2);border-radius:12px;padding:10px;text-align:center">
                <div style="font-size:11px;color:var(--ink-soft);margin-bottom:2px">إجمالي الكراسي</div>
                <div class="tnum" style="font-weight:700;font-size:13px">{{ $selected['seats_count'] }}</div>
            </div>
            <div style="background:var(--surface-2);border-radius:12px;padding:10px;text-align:center">
                <div style="font-size:11px;color:var(--ink-soft);margin-bottom:2px">جنب الشباك</div>
                <div class="tnum" style="font-weight:700;font-size:13px;color:var(--accent)">{{ $selected['window_count'] }}</div>
            </div>
        </div>

        @php
            $seats = collect($selected['seats']);
            $minX = $seats->min('x') ?? 0; $maxX = $seats->max('x') ?? 0;
            $rows = ($seats->max('row_index') ?? 0) + 1;
            $scale = 0.6; $seatSize = 32; $rowH = 40; $aisle = 22; $pad = 10;
            $midRow = ceil($rows / 2);
            $width = ($maxX - $minX) * $scale + $seatSize + $pad * 2;
            $height = $rows * $rowH + $aisle + $pad * 2;
        @endphp

        {{-- أسطورة + فلتر الشباك (بنية فقط — مفيش توفّر) --}}
        <div class="flex items-center" style="gap:14px;flex-wrap:wrap;font-size:12px;color:var(--ink-soft);margin-bottom:12px">
            <span class="flex items-center" style="gap:6px"><i style="width:15px;height:15px;border-radius:5px;background:var(--brand-tint);border:1px solid var(--brand);display:inline-block"></i> كرسي</span>
            <span class="flex items-center" style="gap:6px"><i style="width:15px;height:15px;border-radius:5px;border:2px solid var(--accent);display:inline-block"></i> جنب الشباك</span>
            <label class="chip" style="margin-inline-start:auto"><input type="checkbox" id="winOnly" onchange="toggleWin()" style="accent-color:var(--accent)"> الشباك بس</label>
        </div>

        {{-- تخطيط العربية --}}
        <div style="overflow-x:auto;padding-bottom:8px">
            <div style="position:relative;margin:0 auto;direction:ltr;width:{{ $width }}px;height:{{ $height }}px;background:var(--surface-2);border-radius:14px;border:1px solid var(--border)">
                <div style="position:absolute;left:50%;transform:translateX(-50%);top:{{ $pad + ($midRow - 1) * $rowH + $seatSize + 3 }}px;font-size:9px;letter-spacing:.25em;color:var(--ink-faint);text-transform:uppercase">AISLE</div>
                @foreach ($seats as $seat)
                    @php
                        $top = $pad + $seat->row_index * $rowH + ($seat->row_index >= $midRow ? $aisle : 0);
                        $left = ($seat->x - $minX) * $scale + $pad;
                    @endphp
                    <div class="seat {{ $seat->is_window ? 'is-window' : '' }}"
                         style="position:absolute;display:flex;align-items:center;justify-content:center;border-radius:9px;font-size:11px;font-weight:700;
                                width:{{ $seatSize }}px;height:{{ $seatSize }}px;left:{{ $left }}px;top:{{ $top }}px;
                                background:var(--brand-tint);color:var(--brand);border:1px solid var(--brand);
                                {{ $seat->is_window ? 'box-shadow:0 0 0 2px var(--accent)' : '' }}">
                        {{ $seat->number }}
                    </div>
                @endforeach
            </div>
        </div>

        {{-- إشعار معلوماتي --}}
        <p style="font-size:12px;color:var(--ink-faint);margin-top:14px;line-height:1.6;display:flex;gap:6px;align-items:flex-start">
            <x-icon name="info" size="14px" style="margin-top:2px;flex:none" />
            <span>التخطيط ده معروض عشان يساعد الركّاب يفهموا شكل العربية وترتيب الكراسي. توفّر الكراسي الفعلي بيعتمد على نظام الحجز الرسمي للسكة الحديد.</span>
        </p>
    @endif
</div>

<script>
    function toggleWin() {
        const on = document.getElementById('winOnly').checked;
        document.querySelectorAll('.seat').forEach(s => {
            s.style.opacity = (on && !s.classList.contains('is-window')) ? '0.18' : '1';
        });
    }
</script>
@endsection
