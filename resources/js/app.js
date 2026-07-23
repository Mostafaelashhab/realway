import lottie from 'lottie-web/build/player/lottie_light';

/* ============ EgTrain — تفاعلات الواجهة ============ */

/* أيقونات SVG للاستخدام داخل JS (نفس لغة الأيقونات — بدون إيموجي) */
const JS_ICONS = {
    x: '<path d="M6 6l12 12M18 6 6 18"/>',
    pin: '<path d="M12 21s-6-5.2-6-10a6 6 0 1 1 12 0c0 4.8-6 10-6 10Z"/><circle cx="12" cy="11" r="2.2"/>',
    'pin-on': '<path d="M9 4h6l-1 6 3 3v2h-5v5l-1 1-1-1v-5H4v-2l3-3-1-6Z" fill="currentColor" stroke="none"/>',
    star: '<path d="m12 3.5 2.6 5.3 5.9.9-4.2 4.1 1 5.8-5.3-2.8-5.3 2.8 1-5.8L3.5 9.7l5.9-.9Z"/>',
    train: '<rect x="5" y="3" width="14" height="14" rx="3"/><path d="M5 11h14M9 3v8M15 3v8M7 17l-2 4M17 17l2 4"/>',
    clock: '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
    tag: '<path d="M4 13V5a1 1 0 0 1 1-1h8l7 7-9 9-7-7Z"/>',
    check: '<path d="m5 12 4 4L19 7"/>',
    ticket: '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H6a2 2 0 0 1-2-2 2 2 0 0 0 0-4Z"/>',
    trash: '<path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/>',
};
function jsIcon(name, size = 16, style = '') {
    return `<svg viewBox="0 0 24 24" style="width:${size}px;height:${size}px;stroke:currentColor;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round;vertical-align:-3px;${style}" aria-hidden="true">${JS_ICONS[name] || ''}</svg>`;
}

/* --- أنيميشن القطر (Lottie) --- */
let trainAnim = null;
let trainLoaded = false;
function initTrainLottie() {
    if (trainLoaded) return;
    const c = document.getElementById('lottie-train');
    if (!c) return;
    trainLoaded = true;
    fetch('/lottie/train.json')
        .then((r) => (r.ok ? r.json() : Promise.reject()))
        .then((data) => {
            trainAnim = lottie.loadAnimation({ container: c, renderer: 'svg', loop: true, autoplay: true, animationData: data });
        })
        .catch(() => {
            // Lottie فشل → رجّع للـ SVG الاحتياطي
            c.style.display = 'none';
            const fb = document.getElementById('rail-fallback');
            if (fb) fb.style.display = 'block';
        });
}

/* --- الوضع الداكن --- */
window.toggleTheme = function () {
    const cur = document.documentElement.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('egtrain-theme', next); } catch (e) {}
};

/* ============ منتقي المحطات المخصّص (bottom-sheet/modal) ============ */
let pickerTarget = null;
let pickerFiltered = [];
let pickerActive = -1;
const RECENT_STATIONS_KEY = 'egtrain-recent-stations';

function normalizeAr(s) {
    return (s || '').replace(/[أإآ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').replace(/\s+/g, ' ').trim();
}

window.openStationPicker = function (name) {
    pickerTarget = name;
    const other = name === 'from' ? 'to' : 'from';
    document.getElementById('picker-title').textContent = name === 'from' ? 'محطة القيام' : 'محطة الوصول';
    const backdrop = document.getElementById('picker-backdrop');
    const sheet = document.getElementById('picker-sheet');
    backdrop.classList.add('open');
    sheet.classList.add('open');
    document.body.style.overflow = 'hidden';
    const search = document.getElementById('picker-search');
    search.value = '';
    filterStations('');
    // ركّز بعد الأنيميشن (يمنع القفزة على الموبايل)
    setTimeout(() => search.focus({ preventScroll: true }), 320);
};

window.closeStationPicker = function () {
    document.getElementById('picker-backdrop')?.classList.remove('open');
    document.getElementById('picker-sheet')?.classList.remove('open');
    document.body.style.overflow = '';
    pickerTarget = null;
    pickerActive = -1;
};

function recentStations() {
    try { return JSON.parse(localStorage.getItem(RECENT_STATIONS_KEY) || '[]'); } catch (e) { return []; }
}
function pushRecentStation(st) {
    let list = recentStations().filter((s) => s.i !== st.i);
    list.unshift(st);
    try { localStorage.setItem(RECENT_STATIONS_KEY, JSON.stringify(list.slice(0, 5))); } catch (e) {}
}

// محطات مشهورة (تظهر أول ما تفتح المنتقي)
const POPULAR_IDS = ['606534187276566625', '606535384603557938', '606535383991189573',
    '606535383991189582', '606535388638478397', '606535392467877956', '606535391914229829', '606535386352582751'];

let pickerQuery = '';
let pickerHeading = '';

window.filterStations = function (q) {
    const all = window.EGT_STATIONS || [];
    const nq = normalizeAr(q);
    pickerQuery = q.trim();
    if (!nq) {
        const recents = recentStations();
        if (recents.length) {
            pickerFiltered = recents; pickerHeading = 'أخيرة';
        } else {
            const byId = Object.fromEntries(all.map((s) => [s.i, s]));
            pickerFiltered = POPULAR_IDS.map((id) => byId[id]).filter(Boolean); pickerHeading = 'محطات مشهورة';
        }
    } else {
        // المطابقة في البداية أولًا، بعدين أي مكان
        const starts = [], contains = [];
        all.forEach((s) => {
            const n = normalizeAr(s.n);
            if (n.startsWith(nq)) starts.push(s);
            else if (n.includes(nq) || (s.c || '').includes(q)) contains.push(s);
        });
        pickerFiltered = [...starts, ...contains].slice(0, 60);
        pickerHeading = '';
    }
    pickerActive = -1;
    renderPickerList();
};

function highlight(name) {
    if (!pickerQuery) return name;
    const nn = normalizeAr(name), nq = normalizeAr(pickerQuery);
    const idx = nn.indexOf(nq);
    if (idx < 0) return name;
    return name.slice(0, idx) + '<mark>' + name.slice(idx, idx + pickerQuery.length) + '</mark>' + name.slice(idx + pickerQuery.length);
}

function renderPickerList() {
    const box = document.getElementById('picker-list');
    if (!pickerFiltered.length) {
        box.innerHTML = '<div class="picker-empty">مفيش محطة بالاسم ده</div>';
        return;
    }
    box.innerHTML = (pickerHeading ? `<div class="field-label" style="padding:6px 14px 4px">${pickerHeading}</div>` : '') +
        pickerFiltered.map((s, i) => `
            <button type="button" class="picker-row" data-i="${i}" onclick='selectStation(${JSON.stringify(s).replace(/'/g, "&#39;")})'>
                <span class="pin"><svg viewBox="0 0 24 24" class="icon"><path d="M12 21s-6-5.2-6-10a6 6 0 1 1 12 0c0 4.8-6 10-6 10Z"/><circle cx="12" cy="11" r="2.2"/></svg></span>
                <span class="name">${highlight(s.n)}</span>
                <span class="code">${s.c || ''}</span>
            </button>`).join('');
}

window.selectStation = function (st) {
    if (!pickerTarget) return;
    document.getElementById('hidden-' + pickerTarget).value = st.i;
    const label = document.getElementById('label-' + pickerTarget);
    label.textContent = st.n;
    document.getElementById('trigger-' + pickerTarget).classList.remove('empty');
    pushRecentStation(st);
    closeStationPicker();
};

window.pickerKeydown = function (e) {
    const rows = Array.from(document.querySelectorAll('#picker-list .picker-row'));
    if (e.key === 'Escape') { closeStationPicker(); return; }
    if (e.key === 'ArrowDown') { e.preventDefault(); pickerActive = Math.min(pickerActive + 1, rows.length - 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); pickerActive = Math.max(pickerActive - 1, 0); }
    else if (e.key === 'Enter') { e.preventDefault(); if (pickerActive >= 0) rows[pickerActive]?.click(); else rows[0]?.click(); return; }
    else return;
    rows.forEach((r, i) => r.classList.toggle('active', i === pickerActive));
    rows[pickerActive]?.scrollIntoView({ block: 'nearest' });
};

/* --- تبديل الاتجاه --- */
window.swapStations = function () {
    const fh = document.getElementById('hidden-from');
    const th = document.getElementById('hidden-to');
    const fl = document.getElementById('label-from');
    const tl = document.getElementById('label-to');
    const ft = document.getElementById('trigger-from');
    const tt = document.getElementById('trigger-to');
    if (!fh || !th) return;
    [fh.value, th.value] = [th.value, fh.value];
    [fl.textContent, tl.textContent] = [tl.textContent, fl.textContent];
    ft.classList.toggle('empty', !fh.value);
    tt.classList.toggle('empty', !th.value);
};

/* --- عمليات البحث الأخيرة + المفضّلة (localStorage) --- */
const RECENTS_KEY = 'egtrain-recents';
const FAVS_KEY = 'egtrain-favs';

function readStore(k) { try { return JSON.parse(localStorage.getItem(k) || '[]'); } catch (e) { return []; } }
function writeStore(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }

window.saveRecent = function (route) {
    let list = readStore(RECENTS_KEY).filter((r) => !(r.from === route.from && r.to === route.to));
    list.unshift(route);
    writeStore(RECENTS_KEY, list.slice(0, 6));
};

window.toggleFav = function (route, btn) {
    let list = readStore(FAVS_KEY);
    const i = list.findIndex((r) => r.from === route.from && r.to === route.to);
    if (i >= 0) { list.splice(i, 1); btn?.classList.remove('is-fav'); }
    else { list.unshift(route); btn?.classList.add('is-fav'); }
    writeStore(FAVS_KEY, list.slice(0, 12));
    showToast(i >= 0 ? 'اتشال من المفضّلة' : 'اتحفظ في المفضّلة');
    renderQuickRoutes();
};

function favChip(r) {
    const a = document.createElement('a');
    a.href = `/search?from=${r.from}&to=${r.to}`;
    a.className = 'chip';
    a.innerHTML = `<span style="color:var(--accent)">${jsIcon('star', 14)}</span><span>${r.fromName}</span><span style="opacity:.5">←</span><span>${r.toName}</span>`;
    return a;
}

// كارت بحث أخير (مع تثبيت وحذف)
function recentCard(r, idx) {
    const card = document.createElement('div');
    card.className = 'recent-card';
    card.innerHTML = `
        <a href="/search?from=${r.from}&to=${r.to}" style="flex:1;min-width:0;text-decoration:none;color:inherit">
            <div style="font-size:11px;color:var(--ink-faint);margin-bottom:2px">${r.pinned ? 'مثبّت' : 'آخر بحث'}</div>
            <div style="font-weight:700;font-size:14px;white-space:nowrap">${r.fromName} <span style="color:var(--accent)">←</span> ${r.toName}</div>
        </a>
        <div style="display:flex;gap:4px">
            <button type="button" class="recent-act ${r.pinned ? 'chip-active' : ''}" aria-label="تثبيت" data-pin="${idx}">${jsIcon(r.pinned ? 'pin-on' : 'pin', 15)}</button>
            <button type="button" class="recent-act" aria-label="حذف" data-del="${idx}">${jsIcon('x', 15)}</button>
        </div>`;
    card.querySelector('[data-pin]').onclick = (e) => { e.preventDefault(); pinRecent(idx); };
    card.querySelector('[data-del]').onclick = (e) => { e.preventDefault(); deleteRecent(idx); };
    return card;
}

function sortedRecents() {
    return readStore(RECENTS_KEY)
        .map((r, i) => ({ ...r, _i: i }))
        .sort((a, b) => (b.pinned ? 1 : 0) - (a.pinned ? 1 : 0));
}

window.deleteRecent = function (idx) {
    const list = readStore(RECENTS_KEY);
    list.splice(idx, 1);
    writeStore(RECENTS_KEY, list);
    renderQuickRoutes();
};
window.pinRecent = function (idx) {
    const list = readStore(RECENTS_KEY);
    if (list[idx]) { list[idx].pinned = !list[idx].pinned; writeStore(RECENTS_KEY, list); }
    renderQuickRoutes();
};

window.renderQuickRoutes = function () {
    const favBox = document.getElementById('fav-routes');
    const recBox = document.getElementById('recent-routes');
    if (favBox) {
        const favs = readStore(FAVS_KEY);
        favBox.parentElement.style.display = favs.length ? '' : 'none';
        favBox.innerHTML = '';
        favs.forEach((r) => favBox.appendChild(favChip(r)));
    }
    if (recBox) {
        const recs = sortedRecents();
        recBox.parentElement.style.display = recs.length ? '' : 'none';
        recBox.innerHTML = '';
        recs.forEach((r) => recBox.appendChild(recentCard(r, r._i)));
    }
};

/* --- التوست --- */
window.showToast = function (msg) {
    let host = document.getElementById('toast-host');
    if (!host) {
        host = document.createElement('div');
        host.id = 'toast-host';
        host.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:80;display:flex;flex-direction:column;gap:8px;align-items:center';
        document.body.appendChild(host);
    }
    const t = document.createElement('div');
    t.className = 'toast animate-in';
    t.textContent = msg;
    host.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 2200);
};

/* --- شاشة تحميل القطر (قطر بيتحرك) عند أي انتقال --- */
window.showRailLoader = function () {
    const el = document.getElementById('rail-loading');
    if (el) el.classList.add('show');
    // ابدأ القطر من أوله كل مرة (بدل ما يفضل واقف في آخر إطار)
    if (trainAnim) { try { trainAnim.resize(); trainAnim.goToAndPlay(0, true); } catch (e) {} }
};
window.hideRailLoader = function () {
    document.getElementById('rail-loading')?.classList.remove('show');
};

/* مدة ظهور القطر المضمونة (عشان تشوف الأنيميشن حتى لو التحميل سريع) */
const RAIL_MIN_MS = 500;

/* أظهر القطر على أي انتقال داخلي، وبعدين روح */
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')
        || a.target === '_blank' || a.hasAttribute('download') || e.metaKey || e.ctrlKey || e.shiftKey) return;
    let url;
    try {
        url = new URL(a.href, location.href);
        if (url.origin !== location.origin) return;
        if (url.pathname === location.pathname && url.search === location.search) return;
    } catch (err) { return; }
    e.preventDefault();
    showRailLoader();
    setTimeout(() => { window.location.href = url.href; }, RAIL_MIN_MS);
});

/* اخفي اللودر لما الصفحة تخلص تحميل + لو رجع بزرار الـ back (bfcache) */
window.addEventListener('pageshow', hideRailLoader);
window.addEventListener('load', hideRailLoader);

/* ============ فلاتر النتائج (فوري) ============ */
const filterState = { sort: 'best', ac: false, maxPrice: Infinity, maxStops: Infinity, type: null };

function tripCards() { return Array.from(document.querySelectorAll('.trip-card')); }

window.applyFilters = function () {
    const list = document.getElementById('results-list');
    if (!list) return;
    let visible = [];
    tripCards().forEach((c) => {
        const ok = (!filterState.ac || c.dataset.ac === '1')
            && (+c.dataset.price <= filterState.maxPrice)
            && (+c.dataset.stops <= filterState.maxStops)
            && (!filterState.type || c.dataset.type === filterState.type);
        c.style.display = ok ? '' : 'none';
        if (ok) visible.push(c);
    });

    const s = filterState.sort;
    visible.sort((a, b) => {
        if (s === 'fastest') return +a.dataset.duration - +b.dataset.duration;
        if (s === 'cheapest') return +a.dataset.price - +b.dataset.price;
        if (s === 'earliest') return a.dataset.depart.localeCompare(b.dataset.depart);
        if (s === 'latest') return b.dataset.depart.localeCompare(a.dataset.depart);
        // best: المرشّح الأول، وبعدين الترتيب الأصلي
        return (+b.dataset.rec - +a.dataset.rec) || (+a.dataset.idx - +b.dataset.idx);
    });
    visible.forEach((c) => list.appendChild(c));

    document.getElementById('trip-count').textContent = visible.length;
    const empty = document.getElementById('filter-empty');
    if (empty) empty.style.display = visible.length ? 'none' : 'block';
};

window.setSort = function (el, sort) {
    document.querySelectorAll('.filter-sort').forEach((c) => c.classList.remove('chip-active'));
    el.classList.add('chip-active');
    filterState.sort = sort;
    applyFilters();
};
window.toggleAc = function (el) { filterState.ac = !filterState.ac; el.classList.toggle('chip-active', filterState.ac); applyFilters(); };
window.onPriceInput = function (v) { filterState.maxPrice = +v; document.getElementById('price-val').textContent = v; applyFilters(); };
window.onStopsInput = function (v) { filterState.maxStops = +v; document.getElementById('stops-val').textContent = v; applyFilters(); };

window.clearFilters = function () {
    filterState.sort = 'best'; filterState.ac = false; filterState.maxPrice = Infinity; filterState.maxStops = Infinity; filterState.type = null;
    document.querySelectorAll('.filter-sort').forEach((c) => c.classList.toggle('chip-active', c.dataset.sort === 'best'));
    document.getElementById('ac-chip')?.classList.remove('chip-active');
    document.querySelectorAll('#type-chips .chip').forEach((c) => c.classList.remove('chip-active'));
    const pr = document.getElementById('price-range'), sr = document.getElementById('stops-range');
    if (pr) { pr.value = pr.max; document.getElementById('price-val').textContent = pr.max; }
    if (sr) { sr.value = sr.max; document.getElementById('stops-val').textContent = sr.max; }
    applyFilters();
};

window.openFiltersSheet = function () {
    document.getElementById('filters-backdrop')?.classList.add('open');
    document.getElementById('filters-sheet')?.classList.add('open');
    document.body.style.overflow = 'hidden';
};
window.closeFiltersSheet = function () {
    document.getElementById('filters-backdrop')?.classList.remove('open');
    document.getElementById('filters-sheet')?.classList.remove('open');
    document.body.style.overflow = '';
};

function initResultsFilters() {
    const cards = tripCards();
    if (!cards.length) return;
    cards.forEach((c, i) => (c.dataset.idx = i));

    // اضبط حدود السعر/الوقفات من الداتا
    const prices = cards.map((c) => +c.dataset.price);
    const stops = cards.map((c) => +c.dataset.stops);
    const pr = document.getElementById('price-range');
    const sr = document.getElementById('stops-range');
    if (pr) { pr.max = Math.ceil(Math.max(...prices)); pr.value = pr.max; document.getElementById('price-val').textContent = pr.max; }
    if (sr) { sr.max = Math.max(...stops); sr.value = sr.max; document.getElementById('stops-val').textContent = sr.max; }

    // شرائح نوع القطر
    const types = [...new Set(cards.map((c) => c.dataset.type).filter((t) => t && t !== 'null'))];
    const box = document.getElementById('type-chips');
    if (box) {
        if (!types.length) { document.getElementById('type-filters').style.display = 'none'; }
        types.forEach((t) => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'chip'; b.textContent = t;
            b.onclick = () => {
                const on = filterState.type === t;
                filterState.type = on ? null : t;
                box.querySelectorAll('.chip').forEach((x) => x.classList.remove('chip-active'));
                if (!on) b.classList.add('chip-active');
                applyFilters();
            };
            box.appendChild(b);
        });
    }
    applyFilters();
}

/* ============ مشاركة الرحلة ============ */
window.shareTrip = function (trip) {
    const text = `قطر ${trip.train} · ${trip.from} ← ${trip.to}\n${trip.depart} - ${trip.arrive} · من ${trip.price} جنيه\n\nعبر EgTrain`;
    const url = trip.url;
    document.getElementById('share-preview').innerHTML =
        `<div style="display:flex;align-items:center;gap:8px;font-weight:800;font-size:16px;margin-bottom:4px">${jsIcon('train', 20)} قطر ${trip.train}</div>
         <div style="font-size:15px;font-weight:600;margin-bottom:8px">${trip.from} <span style="color:var(--accent)">←</span> ${trip.to}</div>
         <div style="display:flex;gap:14px;font-size:13px;color:var(--ink-soft)"><span>${jsIcon('clock', 14)} ${trip.depart} - ${trip.arrive}</span><span>${jsIcon('tag', 14)} من ${trip.price} ج</span></div>`;
    const enc = encodeURIComponent(text + '\n' + url);
    document.getElementById('share-wa').href = 'https://wa.me/?text=' + enc;
    document.getElementById('share-tg').href = 'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(text);
    document.getElementById('share-fb').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    document.getElementById('share-copy').onclick = () => { navigator.clipboard?.writeText(text + '\n' + url); showToast('اتنسخ الرابط'); };
    const nativeBtn = document.getElementById('share-native');
    if (navigator.share) { nativeBtn.style.display = ''; nativeBtn.onclick = () => navigator.share({ title: 'EgTrain', text, url }).catch(() => {}); }
    else { nativeBtn.style.display = 'none'; }
    document.getElementById('share-backdrop').classList.add('open');
    document.getElementById('share-sheet').classList.add('open');
    document.body.style.overflow = 'hidden';
};
window.closeShare = function () {
    document.getElementById('share-backdrop')?.classList.remove('open');
    document.getElementById('share-sheet')?.classList.remove('open');
    document.body.style.overflow = '';
};

/* ============ مقارنة القطارات ============ */
let compareSet = [];

window.toggleCompare = function (btn) {
    const card = btn.closest('.trip-card');
    const i = compareSet.indexOf(card);
    if (i >= 0) {
        compareSet.splice(i, 1);
        card.classList.remove('is-comparing');
        btn.classList.remove('chip-active');
    } else if (compareSet.length >= 2) {
        showToast('اختار قطرين بس للمقارنة');
        return;
    } else {
        compareSet.push(card);
        card.classList.add('is-comparing');
        btn.classList.add('chip-active');
    }
    const bar = document.getElementById('compare-bar');
    document.getElementById('compare-count').textContent = compareSet.length;
    bar.style.display = compareSet.length >= 2 ? 'flex' : 'none';
};

window.openCompare = function () {
    if (compareSet.length < 2) return;
    const [a, b] = compareSet;
    const fmtDur = (m) => Math.floor(m / 60) + ':' + String(m % 60).padStart(2, '0');
    const rows = [
        { label: 'القطر', a: a.dataset.train, b: b.dataset.train, better: null },
        { label: 'القيام', a: a.dataset.depart, b: b.dataset.depart, better: a.dataset.depart <= b.dataset.depart ? 'a' : 'b' },
        { label: 'الوصول', a: a.dataset.arrive, b: b.dataset.arrive, better: null },
        { label: 'المدة', a: fmtDur(+a.dataset.duration), b: fmtDur(+b.dataset.duration), better: +a.dataset.duration <= +b.dataset.duration ? 'a' : 'b' },
        { label: 'السعر', a: a.dataset.price + ' ج', b: b.dataset.price + ' ج', better: +a.dataset.price <= +b.dataset.price ? 'a' : 'b' },
        { label: 'الوقفات', a: a.dataset.stops, b: b.dataset.stops, better: +a.dataset.stops <= +b.dataset.stops ? 'a' : 'b' },
        { label: 'مكيّف', a: a.dataset.ac === '1' ? 'نعم' : '—', b: b.dataset.ac === '1' ? 'نعم' : '—', better: null },
    ];
    const cell = (v, win) => `<div style="padding:11px 10px;text-align:center;font-weight:700;font-size:14px;border-radius:10px;${win ? 'background:var(--success-tint);color:var(--success)' : ''}">${v}${win ? ' ' + jsIcon('check', 13) : ''}</div>`;
    const html = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        ${rows.map((r) => `
            <div style="grid-column:1/-1;text-align:center;font-size:11px;color:var(--ink-faint);margin-top:6px">${r.label}</div>
            ${cell(r.a, r.better === 'a')}
            ${cell(r.b, r.better === 'b')}
        `).join('')}
    </div>`;
    document.getElementById('compare-content').innerHTML = html;
    document.getElementById('compare-backdrop').classList.add('open');
    document.getElementById('compare-sheet').classList.add('open');
    document.body.style.overflow = 'hidden';
};
window.closeCompare = function () {
    document.getElementById('compare-backdrop')?.classList.remove('open');
    document.getElementById('compare-sheet')?.classList.remove('open');
    document.body.style.overflow = '';
};

/* وضع البحث: قيام / أوصل قبل */
window.setSearchMode = function (mode) {
    const arrive = mode === 'arrive';
    document.getElementById('mode-depart')?.classList.toggle('seg-active', !arrive);
    document.getElementById('mode-arrive')?.classList.toggle('seg-active', arrive);
    const wrap = document.getElementById('arrive-wrap');
    const input = document.getElementById('arrive_by');
    if (wrap) wrap.style.display = arrive ? '' : 'none';
    if (input) { input.disabled = !arrive; if (arrive && !input.value) input.value = '18:00'; }
};

/* ============ محفظة الرحلات + تذكيرات ============ */
const WALLET_KEY = 'egtrain-wallet';

window.saveToWallet = function (trip) {
    let list = readStore(WALLET_KEY);
    if (list.some((t) => t.id === trip.id)) { showToast('الرحلة محفوظة عندك بالفعل'); return; }
    list.unshift(trip);
    writeStore(WALLET_KEY, list.slice(0, 50));
    showToast('اتحفظت في محفظتك');
};
window.removeFromWallet = function (id) {
    writeStore(WALLET_KEY, readStore(WALLET_KEY).filter((t) => t.id !== id));
    renderWallet();
    showToast('اتشالت من المحفظة');
};

function pad2(n) { return String(n).padStart(2, '0'); }
function to12(hm) {
    if (!hm) return '';
    const [h, m] = hm.split(':').map(Number);
    return (h % 12 || 12) + ':' + pad2(m) + ' ' + (h < 12 ? 'ص' : 'م');
}

// أضف للتقويم — .ics موثوق (نظام التشغيل بيتكفّل بالتنبيه)
window.addToCalendar = function (trip) {
    const dt = (trip.date + 'T' + (trip.depart24 || '08:00') + ':00').replace(/[-:]/g, '');
    const end = (trip.date + 'T' + (trip.arrive24 || trip.depart24 || '09:00') + ':00').replace(/[-:]/g, '');
    const ics = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//EgTrain//AR', 'BEGIN:VEVENT',
        'UID:egtrain-' + trip.id + '@egtrain',
        'DTSTART;TZID=Africa/Cairo:' + dt,
        'DTEND;TZID=Africa/Cairo:' + end,
        'SUMMARY:قطر ' + trip.train + ' — ' + trip.fromName + ' ← ' + trip.toName,
        'DESCRIPTION:رحلة قطار عبر EgTrain (استرشادي — الحجز من المصدر الرسمي).',
        'BEGIN:VALARM', 'TRIGGER:-PT45M', 'ACTION:DISPLAY', 'DESCRIPTION:رحلتك بعد 45 دقيقة', 'END:VALARM',
        'END:VEVENT', 'END:VCALENDAR',
    ].join('\r\n');
    const blob = new Blob([ics], { type: 'text/calendar' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'egtrain-' + trip.train + '.ics';
    a.click();
    showToast('اتضافت للتقويم مع تنبيه قبلها بـ ٤٥ دقيقة');
};

window.renderWallet = function () {
    const box = document.getElementById('wallet-list');
    if (!box) return;
    const list = readStore(WALLET_KEY);
    document.getElementById('wallet-empty').style.display = list.length ? 'none' : 'block';
    box.innerHTML = list.map((t) => `
        <div class="card" style="padding:16px;margin-bottom:12px">
            <div class="flex items-center" style="justify-content:space-between;gap:8px;margin-bottom:10px">
                <div style="font-weight:800;font-size:15px">${t.fromName} <span style="color:var(--accent)">←</span> ${t.toName}</div>
                <span class="badge badge-brand">${jsIcon('train',13)} ${t.train}</span>
            </div>
            <div class="flex items-center" style="gap:14px;color:var(--ink-soft);font-size:13px;margin-bottom:12px">
                <span>${jsIcon('clock',14)} ${to12(t.depart24)} - ${to12(t.arrive24)}</span>
                <span>${t.dateLabel || t.date}</span>
                ${t.price ? '<span>'+jsIcon('tag',14)+' من '+t.price+' ج</span>' : ''}
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="${t.url}" class="btn btn-accent pressable" style="padding:8px 12px;font-size:13px">${jsIcon('ticket',15)} افتح</a>
                <button type="button" class="btn btn-ghost pressable" style="padding:8px 12px;font-size:13px" onclick='addToCalendar(${JSON.stringify(t).replace(/'/g,"&#39;")})'>${jsIcon('clock',15)} أضف للتقويم</button>
                <button type="button" class="btn btn-ghost pressable" style="padding:8px 12px;font-size:13px;margin-inline-start:auto;color:var(--error)" onclick="removeFromWallet('${t.id}')">${jsIcon('trash',15)}</button>
            </div>
        </div>`).join('');
};

/* ============ رحلاتي وإنجازاتي (Wrapped) ============ */
const JOURNEYS_KEY = 'egtrain-journeys';

window.logJourney = function (trip) {
    const list = readStore(JOURNEYS_KEY);
    list.unshift(trip);
    writeStore(JOURNEYS_KEY, list.slice(0, 500));
    showToast('اتسجّلت الرحلة في إحصائياتك');
};

function computeStats() {
    const j = readStore(JOURNEYS_KEY);
    const stations = new Set();
    let km = 0, mins = 0;
    const routeCount = {}, routeName = {};
    let longest = null;
    j.forEach((t) => {
        km += +t.km || 0;
        mins += +t.durationMin || 0;
        if (t.fromName) stations.add(t.fromName);
        if (t.toName) stations.add(t.toName);
        const key = t.fromName + '→' + t.toName;
        routeCount[key] = (routeCount[key] || 0) + 1;
        routeName[key] = t.fromName + ' ← ' + t.toName;
        if (!longest || (+t.km || 0) > (+longest.km || 0)) longest = t;
    });
    let favKey = null, favMax = 0;
    for (const k in routeCount) if (routeCount[k] > favMax) { favMax = routeCount[k]; favKey = k; }
    return {
        trips: j.length, km: Math.round(km), stations: stations.size, hours: Math.round(mins / 60),
        favorite: favKey ? routeName[favKey] : null,
        longest: longest ? { name: longest.fromName + ' ← ' + longest.toName, km: Math.round(+longest.km || 0) } : null,
    };
}

const ACHIEVEMENTS = [
    { icon: 'star', title: 'أول رحلة', test: (s) => s.trips >= 1 },
    { icon: 'train', title: '٥ رحلات', test: (s) => s.trips >= 5 },
    { icon: 'train', title: 'رحّالة (١٠)', test: (s) => s.trips >= 10 },
    { icon: 'star', title: 'خبير السكة (٢٥)', test: (s) => s.trips >= 25 },
    { icon: 'tag', title: '١٠٠٠ كم', test: (s) => s.km >= 1000 },
    { icon: 'tag', title: '٥٠٠٠ كم', test: (s) => s.km >= 5000 },
    { icon: 'pin', title: '٥ محطات', test: (s) => s.stations >= 5 },
    { icon: 'pin', title: '١٥ محطة', test: (s) => s.stations >= 15 },
    { icon: 'clock', title: '٢٤ ساعة سفر', test: (s) => s.hours >= 24 },
];

let lastStats = null;
window.renderStats = function () {
    const wrap = document.getElementById('stats-wrap');
    if (!wrap) return;
    const s = computeStats();
    lastStats = s;
    document.getElementById('stats-empty').style.display = s.trips ? 'none' : 'block';
    wrap.style.display = s.trips ? 'block' : 'none';
    document.getElementById('share-stats-btn').style.display = s.trips ? 'inline-flex' : 'none';
    if (!s.trips) return;

    document.getElementById('st-trips').textContent = s.trips;
    document.getElementById('st-km').textContent = s.km;
    document.getElementById('st-stations').textContent = s.stations;
    document.getElementById('st-hours').textContent = s.hours;
    document.getElementById('st-route').textContent = s.favorite || '—';
    document.getElementById('st-longest').textContent = s.longest ? (s.longest.name + ' · ' + s.longest.km + ' كم') : '—';

    document.getElementById('achievements').innerHTML = ACHIEVEMENTS.map((a) => {
        const on = a.test(s);
        return `<div class="card" style="padding:14px 8px;text-align:center;${on ? '' : 'opacity:.4'}">
            <div style="width:38px;height:38px;border-radius:12px;display:grid;place-items:center;margin:0 auto 6px;background:${on ? 'var(--brand-tint)' : 'var(--surface-2)'};color:${on ? 'var(--brand)' : 'var(--ink-faint)'}">${jsIcon(a.icon, 20)}</div>
            <div style="font-size:11px;font-weight:600">${a.title}</div>
        </div>`;
    }).join('');
};

window.shareStats = function () {
    const s = lastStats || computeStats();
    const text = `رحلاتي على القطر: ${s.trips} رحلة · ${s.km} كم · زرت ${s.stations} محطة\n\nعبر EgTrain`;
    document.getElementById('share-preview').innerHTML =
        `<div style="font-weight:800;font-size:16px;margin-bottom:8px">رحلاتي على القطر</div>
         <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:14px;font-weight:700">
            <span>${jsIcon('train',15)} ${s.trips} رحلة</span><span>${jsIcon('tag',15)} ${s.km} كم</span><span>${jsIcon('pin',15)} ${s.stations} محطة</span>
         </div>`;
    const enc = encodeURIComponent(text + '\n' + location.origin);
    document.getElementById('share-wa').href = 'https://wa.me/?text=' + enc;
    document.getElementById('share-tg').href = 'https://t.me/share/url?url=' + encodeURIComponent(location.origin) + '&text=' + encodeURIComponent(text);
    document.getElementById('share-fb').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(location.origin);
    document.getElementById('share-copy').onclick = () => { navigator.clipboard?.writeText(text); showToast('اتنسخ'); };
    const nb = document.getElementById('share-native');
    if (navigator.share) { nb.style.display = ''; nb.onclick = () => navigator.share({ title: 'EgTrain', text }).catch(() => {}); }
    else { nb.style.display = 'none'; }
    document.getElementById('share-backdrop').classList.add('open');
    document.getElementById('share-sheet').classList.add('open');
    document.body.style.overflow = 'hidden';
};

/* ============ PWA: service worker + تثبيت ============ */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

let deferredInstall = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstall = e;
    const btn = document.getElementById('install-btn');
    if (btn) btn.style.display = 'inline-flex';
});
window.installApp = function () {
    if (!deferredInstall) return;
    deferredInstall.prompt();
    deferredInstall.userChoice.finally(() => {
        deferredInstall = null;
        const btn = document.getElementById('install-btn');
        if (btn) btn.style.display = 'none';
    });
};
window.addEventListener('appinstalled', () => { showToast('اتثبّت EgTrain على جهازك'); });

document.addEventListener('DOMContentLoaded', () => {
    renderQuickRoutes();
    renderWallet();
    renderStats();
    initTrainLottie();
    initResultsFilters();

    // احفظ آخر بحث عند الإرسال + أظهر شاشة التحميل
    document.querySelectorAll('form[data-search-form]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const fh = document.getElementById('hidden-from');
            const th = document.getElementById('hidden-to');
            const fl = document.getElementById('label-from');
            const tl = document.getElementById('label-to');
            if (fh?.value && th?.value) {
                saveRecent({ from: fh.value, to: th.value, fromName: fl?.textContent || '', toName: tl?.textContent || '' });
            }
            e.preventDefault();
            showRailLoader();
            setTimeout(() => form.submit(), RAIL_MIN_MS);
        });
    });
});
