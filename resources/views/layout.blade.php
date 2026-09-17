<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
<meta name="theme-color" content="#1d4ed8" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0b1020" media="(prefers-color-scheme: dark)">
<title>@yield('title', 'EgTrain')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap">
<style>
:root {
    --bg:#f5f6f8; --card:#fff; --ink:#0f172a; --ink-2:#4b5565; --ink-3:#8b95a5;
    --line:#e5e8ee; --brand:#1d4ed8; --brand-ink:#1d4ed8; --brand-soft:#eef3ff;
    --ok:#047857; --ok-soft:#ecfdf5; --shadow:0 1px 2px rgba(15,23,42,.04), 0 10px 30px -18px rgba(15,23,42,.35);
    --r:18px;
}
@media (prefers-color-scheme: dark) {
    :root {
        --bg:#0b1020; --card:#131a2b; --ink:#e8ecf4; --ink-2:#a3adc0; --ink-3:#6b7689;
        --line:#212a3d; --brand:#3b82f6; --brand-ink:#93b4fd; --brand-soft:#16233f;
        --ok:#34d399; --ok-soft:#10241f; --shadow:0 1px 2px rgba(0,0,0,.3), 0 10px 30px -18px rgba(0,0,0,.8);
    }
}
* { box-sizing:border-box; }
html {
    -webkit-text-size-adjust:100%;
    touch-action:pan-x pan-y;          /* يقفل الزوم بالقرص والدبل-تاب، والتمرير شغال عادي */
}
body {
    margin:0; background:var(--bg); color:var(--ink);
    font:400 16px/1.65 "IBM Plex Sans Arabic", system-ui, -apple-system, sans-serif;
    font-variant-numeric:tabular-nums; -webkit-font-smoothing:antialiased;
}
a { color:var(--brand-ink); text-decoration:none; }

header {
    position:sticky; top:0; z-index:10; padding-top:env(safe-area-inset-top,0);
    background:color-mix(in srgb, var(--bg) 78%, transparent);
    backdrop-filter:saturate(1.6) blur(14px); border-bottom:1px solid var(--line);
}
header .bar { max-width:760px; margin:0 auto; padding:12px 18px; display:flex; align-items:center; gap:10px; }
.brand { display:flex; align-items:center; gap:9px; font-weight:700; font-size:17px; color:var(--ink); letter-spacing:-.01em; }
.brand .mark {
    width:32px; height:32px; border-radius:10px; display:grid; place-items:center;
    background:linear-gradient(140deg, var(--brand), color-mix(in srgb, var(--brand) 55%, #7c3aed));
    color:#fff; box-shadow:0 4px 12px -4px color-mix(in srgb, var(--brand) 60%, transparent);
}
.back { margin-inline-start:auto; font-size:14px; color:var(--ink-2); display:flex; align-items:center; gap:5px; }

main { max-width:760px; margin:0 auto; padding:22px 18px 48px; }

.hero { margin:10px 0 22px; }
.hero h1 { font-size:clamp(25px,6.5vw,33px); font-weight:700; letter-spacing:-.03em; line-height:1.25; margin:0 0 6px; }
.hero p { margin:0; color:var(--ink-2); font-size:15px; }

.card { background:var(--card); border:1px solid var(--line); border-radius:var(--r); box-shadow:var(--shadow); }

/* تبويبات بالـ CSS من غير جافاسكريبت */
.tabs > input { position:absolute; width:1px; height:1px; opacity:0; }
.tabbar { display:flex; gap:4px; padding:5px; background:var(--card); border:1px solid var(--line);
    border-radius:14px; margin-bottom:14px; box-shadow:var(--shadow); }
.tabbar label { flex:1; text-align:center; padding:9px 10px; border-radius:10px; font-size:14.5px;
    font-weight:600; color:var(--ink-2); cursor:pointer; transition:background .15s, color .15s; }
#t1:checked ~ .tabbar label[for="t1"], #t2:checked ~ .tabbar label[for="t2"] { background:var(--brand-soft); color:var(--brand-ink); }
#t1:focus-visible ~ .tabbar label[for="t1"], #t2:focus-visible ~ .tabbar label[for="t2"] { outline:2px solid var(--brand); outline-offset:2px; }
.panel { display:none; }
#t1:checked ~ .p1, #t2:checked ~ .p2 { display:block; }

form.card { padding:18px; }
.field { margin-bottom:13px; }
.field:last-of-type { margin-bottom:17px; }
label.lbl { display:block; font-size:12.5px; font-weight:600; color:var(--ink-3); margin-bottom:6px; letter-spacing:.01em; }
.lbl-hint { font-weight:500; color:var(--brand-ink); }
input, select {
    width:100%; max-width:100%; min-width:0; height:52px; padding:12px 13px;
    font:inherit; font-size:16px; line-height:1.5; color:var(--ink);
    background:var(--bg); border:1px solid var(--line); border-radius:12px; transition:border-color .15s, box-shadow .15s;
}

/* حقل التاريخ: iOS بيرسمه بمقاس داخلي بتاعه ويطلع بره الكارت — بنصفّر رسم النظام */
/* التاريخ نفسه مكتوب لاتيني (18/09/2026)، فالحقل بيتعامل كصندوق LTR:
   القيمة على الشمال والأيقونة على اليمين — والمعنى العربي في اللابل فوقيه. */
input[type="date"] {
    appearance:none; -webkit-appearance:none; direction:ltr; text-align:left;
}
input[type="date"]::-webkit-date-and-time-value { text-align:start; margin:0; padding:0; }
input[type="date"]::-webkit-datetime-edit { padding:0; line-height:1.5; }
input[type="date"]::-webkit-datetime-edit-fields-wrapper { padding:0; }
input[type="date"]::-webkit-calendar-picker-indicator { margin:0; padding:0; opacity:.45; cursor:pointer; }
input[type="date"]::-webkit-inner-spin-button, input[type="date"]::-webkit-clear-button { display:none; }
select {
    appearance:none; padding-inline-end:38px;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238b95a5' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:left 13px center; background-size:16px;
}
input:focus, select:focus { outline:none; border-color:var(--brand); box-shadow:0 0 0 3.5px color-mix(in srgb, var(--brand) 16%, transparent); }
input::placeholder { color:var(--ink-3); }
.two { display:flex; gap:11px; }
.two > * { flex:1; min-width:0; }
button {
    width:100%; padding:13px; font:inherit; font-size:15.5px; font-weight:600; color:#fff; cursor:pointer;
    background:var(--brand); border:0; border-radius:12px; transition:filter .15s, transform .08s;
}
button:hover { filter:brightness(1.08); }
button:active { transform:scale(.985); }
button:disabled { cursor:progress; filter:none; opacity:.92; }
button.is-loading { display:flex; align-items:center; justify-content:center; gap:9px; }
button.is-loading::before {
    content:""; width:15px; height:15px; border-radius:50%; flex:none;
    border:2px solid rgba(255,255,255,.35); border-top-color:#fff; animation:spin .6s linear infinite;
}
@keyframes spin { to { transform:rotate(360deg); } }
body.busy { cursor:progress; }
/* شريط تقدّم رفيع تحت الهيدر طول ما البحث شغال */
body.busy::after {
    content:""; position:fixed; inset-inline:0; top:0; height:3px; z-index:50;
    background:linear-gradient(90deg, transparent, var(--brand), transparent);
    animation:sweep 1.1s ease-in-out infinite;
}
@keyframes sweep { from { transform:translateX(-100%); } to { transform:translateX(100%); } }
@media (prefers-reduced-motion: reduce) {
    button.is-loading::before, body.busy::after { animation-duration:0s; }
}

/* قايمة المحطات المنسّقة */
.combo { position:relative; }
.combo-panel {
    display:none; position:absolute; z-index:20; inset-inline:0; top:100%; margin-top:6px;
    background:var(--card); border:1px solid var(--line); border-radius:13px; overflow:hidden;
    max-height:none; box-shadow:0 12px 34px -12px rgba(15,23,42,.35), 0 0 0 1px rgba(15,23,42,.02);
    animation:pop .13s ease-out;
}
.combo-panel.open { display:block; }
@keyframes pop { from { opacity:0; transform:translateY(-5px); } }
.combo-item {
    padding:11px 14px; font-size:15px; cursor:pointer; border-bottom:1px solid var(--line);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.combo-item:last-child { border-bottom:0; }
.combo-item:hover, .combo-item.active { background:var(--brand-soft); color:var(--brand-ink); font-weight:600; }

/* نتيجة القطر */
.train { padding:18px; margin-bottom:14px; }
.thead { display:flex; align-items:flex-start; gap:10px; margin-bottom:16px; }
.tno { font-size:19px; font-weight:700; letter-spacing:-.02em; }
.chip { display:inline-block; padding:3px 9px; border-radius:99px; font-size:12.5px; font-weight:600;
    background:var(--brand-soft); color:var(--brand-ink); }
.price { margin-inline-start:auto; text-align:start; white-space:nowrap; }
.price b { display:block; font-size:18px; font-weight:700; letter-spacing:-.02em; }
.price span { font-size:11.5px; color:var(--ink-3); }

.rail { display:flex; align-items:center; gap:12px; padding:14px 0; border-block:1px solid var(--line); }
.stop-t { min-width:0; }
.stop-t b { display:block; font-size:16px; font-weight:600; }
.stop-t span { display:block; font-size:13px; color:var(--ink-2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rail .mid { flex:1; position:relative; display:flex; align-items:center; justify-content:center; }
.rail .mid::before { content:""; position:absolute; inset-inline:0; top:50%; border-top:1.5px dashed var(--line); }
.rail .mid span { position:relative; background:var(--card); padding:0 8px; font-size:12px; color:var(--ink-3); }
.rail .end { text-align:end; }

.sect { font-size:12.5px; font-weight:600; color:var(--ink-3); margin:16px 0 9px; }
.cls { padding:12px 13px; border:1px solid var(--line); border-radius:13px; margin-bottom:8px; background:var(--bg); }
.cls-h { display:flex; align-items:baseline; gap:8px; flex-wrap:wrap; }
.cls-h b { font-size:15px; font-weight:600; }
.cls-h .pr { margin-inline-start:auto; font-weight:700; }
.free { display:inline-block; padding:2px 9px; border-radius:99px; font-size:12.5px; font-weight:600;
    background:var(--ok-soft); color:var(--ok); }
.free.none { background:var(--line); color:var(--ink-3); }
details { margin-top:9px; }
summary { list-style:none; cursor:pointer; font-size:13.5px; color:var(--brand-ink); font-weight:500;
    padding:5px 0; display:flex; align-items:center; gap:6px; }
summary::-webkit-details-marker { display:none; }
summary::before { content:"＋"; font-size:12px; opacity:.7; }
details[open] summary::before { content:"－"; }
.seatgrid { display:flex; flex-wrap:wrap; gap:6px; padding:4px 0 6px; }
.seat { padding:3px 8px; border-radius:8px; font-size:13px; font-weight:500; background:var(--ok-soft); color:var(--ok); }

.stops { display:flex; flex-wrap:wrap; gap:6px; }
.stops span { padding:4px 10px; border-radius:99px; font-size:13px; background:var(--bg); border:1px solid var(--line); color:var(--ink-2); }
.stops span.edge { background:var(--brand-soft); border-color:transparent; color:var(--brand-ink); font-weight:600; }

.editbox { margin-bottom:18px; padding:4px 14px 14px; }
.edit-summary { padding:12px 2px; font-weight:600; color:var(--ink-2); }
.edit-summary::before { content:"⌕"; font-size:15px; }
.editbox .tabbar, .editbox form.card { box-shadow:none; }
.pick { padding:5px 12px; border-radius:99px; font-size:13.5px; background:var(--brand-soft);
    color:var(--brand-ink); font-weight:500; }
.banner { padding:14px 16px; margin-bottom:14px; font-size:13.5px; line-height:1.7; color:var(--ink-2);
    border-inline-start:3px solid var(--brand); }
.banner b { color:var(--ink); font-weight:600; }
.banner.warn { border-inline-start-color:#d97706; }
.note { padding:16px 18px; color:var(--ink-2); font-size:14.5px; }
footer { max-width:760px; margin:0 auto; padding:8px 18px 40px; color:var(--ink-3); font-size:12px;
    line-height:1.7; text-align:center; }

/* الموبايل الضيق: حقلين المحطة جنب بعض مكانش بيسع اسم محطة عربي */
@media (max-width: 480px) {
    main { padding:18px 14px 44px; }
    header .bar, footer { padding-inline:14px; }
    .two { display:block; }
    .two .field { margin-bottom:13px; }
    .stop-t span { white-space:normal; }          /* اسم المحطة يلف بدل ما يتقص */
    .rail { gap:8px; }
    .rail .mid span { padding:0 5px; }
    .train, form.card { padding:15px; }
    .thead { gap:8px; }
    .tno { font-size:18px; }
    .price b { font-size:17px; }
}

/* شاشات صغيرة جدًا */
@media (max-width: 340px) {
    .rail { flex-wrap:wrap; }
    .rail .mid { order:3; flex-basis:100%; margin-top:6px; }
    .rail .mid::before { display:none; }
}
</style>
</head>
<body>
<header>
    <div class="bar">
        <a class="brand" href="{{ route('home') }}">
            <span class="mark">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="3" width="16" height="13" rx="3"/><path d="M4 11h16M8 20l-2 2M16 20l2 2"/><circle cx="8.5" cy="13.5" r=".6" fill="currentColor"/><circle cx="15.5" cy="13.5" r=".6" fill="currentColor"/>
                </svg>
            </span>
            EgTrain
        </a>
        @hasSection('back')<a class="back" href="{{ route('home') }}">@yield('back')</a>@endif
    </div>
</header>

<main>@yield('content')</main>

<footer>
    تطبيق مستقل غير رسمي — الأسعار والكراسي الفاضية بتيجي من نظام حجز السكة الحديد لحظة البحث، والحجز من المصدر الرسمي.
</footer>
</body>
</html>
