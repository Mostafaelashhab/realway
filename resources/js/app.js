import lottie from 'lottie-web/build/player/lottie_light';

/* ============ EgTrain — تفاعلات الواجهة ============ */

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

window.filterStations = function (q) {
    const all = window.EGT_STATIONS || [];
    const nq = normalizeAr(q);
    if (!nq) {
        const recents = recentStations();
        pickerFiltered = recents.length ? recents : all.slice(0, 40);
    } else {
        pickerFiltered = all.filter((s) => normalizeAr(s.n).includes(nq) || (s.c || '').includes(q)).slice(0, 60);
    }
    pickerActive = -1;
    renderPickerList(!nq && recentStations().length > 0);
};

function renderPickerList(showingRecent) {
    const box = document.getElementById('picker-list');
    if (!pickerFiltered.length) {
        box.innerHTML = '<div class="picker-empty">مفيش محطة بالاسم ده</div>';
        return;
    }
    box.innerHTML = (showingRecent ? '<div class="field-label" style="padding:6px 14px 4px">أخيرة</div>' : '') +
        pickerFiltered.map((s, i) => `
            <button type="button" class="picker-row" data-i="${i}" onclick='selectStation(${JSON.stringify(s).replace(/'/g, "&#39;")})'>
                <span class="pin"><svg viewBox="0 0 24 24" class="icon"><path d="M12 21s-6-5.2-6-10a6 6 0 1 1 12 0c0 4.8-6 10-6 10Z"/><circle cx="12" cy="11" r="2.2"/></svg></span>
                <span class="name">${s.n}</span>
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
    showToast(i >= 0 ? 'اتشال من المفضّلة' : 'اتحفظ في المفضّلة ⭐');
    renderQuickRoutes();
};

function routeChip(r, fav) {
    const a = document.createElement('a');
    a.href = `/search?from=${r.from}&to=${r.to}`;
    a.className = 'chip';
    a.innerHTML = `${fav ? '<span style="color:var(--accent)">★</span>' : ''}<span>${r.fromName}</span><span style="opacity:.5">←</span><span>${r.toName}</span>`;
    return a;
}

window.renderQuickRoutes = function () {
    const recBox = document.getElementById('recent-routes');
    const favBox = document.getElementById('fav-routes');
    if (favBox) {
        const favs = readStore(FAVS_KEY);
        favBox.parentElement.style.display = favs.length ? '' : 'none';
        favBox.innerHTML = '';
        favs.forEach((r) => favBox.appendChild(routeChip(r, true)));
    }
    if (recBox) {
        const recs = readStore(RECENTS_KEY);
        recBox.parentElement.style.display = recs.length ? '' : 'none';
        recBox.innerHTML = '';
        recs.forEach((r) => recBox.appendChild(routeChip(r, false)));
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

/* اخفي اللودر لو رجع بزرار الـ back (bfcache) */
window.addEventListener('pageshow', hideRailLoader);

document.addEventListener('DOMContentLoaded', () => {
    renderQuickRoutes();
    initTrainLottie();

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
