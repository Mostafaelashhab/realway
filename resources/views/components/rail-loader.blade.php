@props(['label' => 'بنجهّز رحلتك...'])

<div class="rail-loader">
    {{-- حاوية Lottie (تتملّي من app.js) --}}
    <div id="lottie-train" style="width:100%;max-width:340px;margin:0 auto;min-height:150px"></div>

    {{-- SVG احتياطي — يظهر لو Lottie مااشتغلش --}}
    <div id="rail-fallback" style="display:none">
        <svg viewBox="0 0 300 90" width="100%" xmlns="http://www.w3.org/2000/svg">
            <line class="rail-line" x1="10" y1="70" x2="290" y2="70" />
            <circle class="station-dot" cx="30" cy="70" r="4" />
            <circle class="station-dot lit" cx="150" cy="70" r="4" />
            <circle class="station-dot" cx="270" cy="70" r="4" />
            <g class="train-car">
                <rect x="0" y="34" width="46" height="26" rx="7" />
                <rect class="train-win" x="6" y="40" width="9" height="9" rx="2" />
                <rect class="train-win" x="19" y="40" width="9" height="9" rx="2" />
                <rect class="train-win" x="32" y="40" width="9" height="9" rx="2" />
                <circle cx="12" cy="64" r="4" fill="var(--brand-strong)" />
                <circle cx="36" cy="64" r="4" fill="var(--brand-strong)" />
            </g>
        </svg>
    </div>

    <p style="text-align:center;color:var(--ink-soft);font-size:14px;font-weight:500;margin-top:8px">{{ $label }}</p>
</div>
