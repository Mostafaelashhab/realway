@props(['stations'])

{{-- بيانات المحطات للـ JS (id, name, code) --}}
<script>
    window.EGT_STATIONS = {!! $stations->map(fn ($s) => ['i' => $s->id, 'n' => $s->name_ar, 'c' => $s->code])->values()->toJson() !!};
</script>

{{-- Overlay مخصّص: bottom-sheet على الموبايل / modal على الديسكتوب --}}
<div id="picker-backdrop" class="sheet-backdrop" onclick="closeStationPicker()"></div>
<div id="picker-sheet" class="sheet" role="dialog" aria-modal="true" aria-label="اختر المحطة">
    <div class="sheet-handle"></div>
    <div class="sheet-head">
        <div class="sheet-title" id="picker-title">اختر المحطة</div>
        <button type="button" class="btn btn-ghost btn-icon pressable" onclick="closeStationPicker()" aria-label="إغلاق" style="margin-inline-start:auto">
            <x-icon name="x" size="18px" />
        </button>
    </div>
    <div class="picker-search-wrap">
        <input type="text" id="picker-search" class="input" placeholder="ابحث عن محطة..." autocomplete="off"
               oninput="filterStations(this.value)" onkeydown="pickerKeydown(event)">
    </div>
    <div class="sheet-body">
        <div id="picker-list" class="picker-list"></div>
    </div>
</div>
