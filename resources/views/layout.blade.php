<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#12336b">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="EgTrain">
    <title>@yield('title', 'EgTrain — قطارات مصر')</title>
    {{-- ظبط الثيم قبل الرسم (منع الوميض) --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('egtrain-theme');
                if (!t) t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{-- شاشة تحميل القطر (overlay) --}}
    <div id="rail-loading">
        <div style="width:100%;padding:0 32px">
            <x-rail-loader />
        </div>
    </div>
    <style>
        #rail-loading { position:fixed; inset:0; z-index:90; background:var(--bg);
            display:none; align-items:center; justify-content:center; }
        #rail-loading.show { display:flex; animation: fadeInUp .2s var(--tap) both; }
    </style>

    <header style="position:sticky;top:0;z-index:40;background:color-mix(in srgb, var(--bg) 82%, transparent);backdrop-filter:saturate(1.4) blur(14px);border-bottom:1px solid var(--border)">
        <div class="mx-auto flex items-center gap-3" style="max-width:960px;padding:12px 18px">
            <a href="{{ route('home') }}" class="flex items-center gap-2" style="font-weight:800;font-size:18px;letter-spacing:-.02em;color:var(--ink)">
                <span style="display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:var(--brand);color:#fff">
                    <x-icon name="train" size="20px" />
                </span>
                EgTrain
            </a>
            <button type="button" id="install-btn" onclick="installApp()" class="btn btn-primary pressable" style="margin-inline-start:auto;display:none;padding:9px 14px;font-size:13px">
                <x-icon name="bolt" size="16px" /> ثبّت التطبيق
            </button>
            <a href="{{ route('wallet') }}" class="btn btn-ghost btn-icon pressable" aria-label="محفظة رحلاتي" style="margin-inline-start:auto"><x-icon name="ticket" /></a>
            <button type="button" onclick="toggleTheme()" class="btn btn-ghost btn-icon" aria-label="تبديل الوضع الليلي">
                <span class="block dark:hidden"><x-icon name="moon" /></span>
                <span class="hidden dark:block"><x-icon name="sun" /></span>
            </button>
        </div>
    </header>

    <main class="mx-auto page-enter" style="max-width:960px;padding:20px 18px 60px">
        @yield('content')
    </main>

    {{-- الـ overlays (منتقي المحطة...) على مستوى body عشان الـ position:fixed يشتغل صح --}}
    @stack('overlays')

    <footer class="mx-auto" style="max-width:960px;padding:24px 18px 40px;text-align:center;color:var(--ink-faint);font-size:12px">
        <div class="flex items-center justify-center gap-1.5" style="margin-bottom:4px">
            <x-icon name="train" size="16px" /> EgTrain
        </div>
        <div style="margin-bottom:6px">قطارات مصر — بضمير الركّاب · {{ \App\Models\Station::count() }} محطة · {{ \App\Models\Train::count() }} قطر</div>
        <div style="max-width:44ch;margin:0 auto;line-height:1.6;opacity:.85">
            تطبيق <b>مستقل وغير رسمي</b> ومش تابع للهيئة القومية لسكك حديد مصر.
            المواعيد استرشادية وقد تتغير — الحجز والتأكيد من المصدر الرسمي.
        </div>
    </footer>
</body>
</html>
