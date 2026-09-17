@php use App\Support\Ar; @endphp
{{-- بحث مشترك بين الرئيسية وصفحة النتايج. بيشتغل من غير جافاسكريبت (datalist)،
     والسكربت تحت بيرقّيه لقايمة منسّقة + loader وقت الإرسال. --}}
<div class="tabs">
    <input type="radio" name="tab" id="t1" @checked($tab !== 'number')>
    <input type="radio" name="tab" id="t2" @checked($tab === 'number')>

    <div class="tabbar">
        <label for="t1">بالمحطات</label>
        <label for="t2">برقم القطر</label>
    </div>

    <form class="card panel p1" action="{{ route('search') }}" method="GET" data-loading>
        <div class="two">
            <div class="field combo">
                <label class="lbl" for="from">من</label>
                <input type="text" name="from" id="from" list="stations" autocomplete="off"
                       placeholder="اكتب اسم المحطة" value="{{ $fromText }}" required>
            </div>
            <div class="field combo">
                <label class="lbl" for="to">إلى</label>
                <input type="text" name="to" id="to" list="stations" autocomplete="off"
                       placeholder="اكتب اسم المحطة" value="{{ $toText }}" required>
            </div>
        </div>
        <div class="field">
            <label class="lbl" for="d1">التاريخ <span class="lbl-hint" data-date-hint>{{ Ar::date($date) }}</span></label>
            <input type="date" name="date" id="d1" value="{{ $date }}" min="{{ $minDate }}">
        </div>
        <button type="submit"><span class="btn-label">ابحث</span></button>
    </form>

    <form class="card panel p2" action="{{ route('train') }}" method="GET" data-loading>
        <div class="field">
            <label class="lbl" for="number">رقم القطر</label>
            <input type="text" name="number" id="number" inputmode="numeric"
                   placeholder="903" value="{{ $number }}" required>
        </div>
        <div class="field">
            <label class="lbl" for="d2">التاريخ <span class="lbl-hint" data-date-hint>{{ Ar::date($date) }}</span></label>
            <input type="date" name="date" id="d2" value="{{ $date }}" min="{{ $minDate }}">
        </div>
        <button type="submit"><span class="btn-label">ابحث</span></button>
    </form>
</div>

<datalist id="stations">
    @foreach ($stations as $name)<option value="{{ $name }}">@endforeach
</datalist>

<script>
(function () {
    'use strict';

    // نفس تطبيع Ar::fold في PHP — لازم الاتنين يتفقوا
    var AR = { 'أ':'ا','إ':'ا','آ':'ا','ٱ':'ا','ة':'ه','ى':'ي','ؤ':'و','ئ':'ي','ء':'' };
    function fold(text) {
        return text.replace(/[أإآٱةىؤئء]/g, function (c) { return AR[c]; })
            .replace(/[ـً-ْ]/g, '')
            .replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); })
            .replace(/\s+/g, ' ').trim().toLowerCase();
    }

    var list = document.getElementById('stations');
    if (!list) return;

    var names = Array.prototype.map.call(list.options, function (o) { return o.value; });
    var folded = names.map(fold);
    list.remove();   // القايمة الأصلية اتشالت — عندنا واحدة أحلى

    document.querySelectorAll('.combo input').forEach(function (input) {
        input.removeAttribute('list');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-autocomplete', 'list');

        var panel = document.createElement('div');
        panel.className = 'combo-panel';
        panel.setAttribute('role', 'listbox');
        input.parentNode.appendChild(panel);

        var matches = [], active = -1;

        function search(term) {
            var q = fold(term), starts = [], has = [];
            for (var i = 0; i < folded.length && starts.length + has.length < 60; i++) {
                if (q === '' || folded[i].indexOf(q) === 0) { starts.push(names[i]); }
                else if (folded[i].indexOf(q) > 0) { has.push(names[i]); }
            }
            return starts.concat(has).slice(0, 6);
        }

        function open(term) {
            matches = search(term);
            if (!matches.length) { return close(); }
            panel.innerHTML = matches.map(function (n, i) {
                return '<div class="combo-item" role="option" data-i="' + i + '">' + n + '</div>';
            }).join('');
            panel.classList.add('open');
            input.setAttribute('aria-expanded', 'true');
            active = -1;
        }

        function close() {
            panel.classList.remove('open');
            panel.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
            active = -1;
        }

        function highlight(next) {
            var items = panel.children;
            if (!items.length) return;
            if (active >= 0) items[active].classList.remove('active');
            active = (next + items.length) % items.length;
            items[active].classList.add('active');
            items[active].scrollIntoView({ block: 'nearest' });
        }

        function choose(i) {
            if (matches[i] === undefined) return;
            input.value = matches[i];
            close();
        }

        input.addEventListener('input', function () { open(input.value); });
        input.addEventListener('focus', function () { open(input.value); });
        input.addEventListener('blur', function () { setTimeout(close, 120); });

        input.addEventListener('keydown', function (e) {
            if (!panel.classList.contains('open')) {
                if (e.key === 'ArrowDown') { open(input.value); e.preventDefault(); }
                return;
            }
            if (e.key === 'ArrowDown') { highlight(active + 1); e.preventDefault(); }
            else if (e.key === 'ArrowUp') { highlight(active - 1); e.preventDefault(); }
            else if (e.key === 'Enter' && active >= 0) { choose(active); e.preventDefault(); }
            else if (e.key === 'Escape') { close(); }
        });

        panel.addEventListener('mousedown', function (e) {
            var item = e.target.closest('.combo-item');
            if (item) { e.preventDefault(); choose(+item.dataset.i); }
        });
    });

    // النداء على ENR بياخد ثواني — لازم الزائر يشوف إن فيه حاجة بتحصل
    document.querySelectorAll('form[data-loading]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('button[type=submit]');
            if (!button || button.disabled) return;
            button.disabled = true;
            button.classList.add('is-loading');
            button.querySelector('.btn-label').textContent = 'بندوّر على القطارات…';
            document.body.classList.add('busy');
        });
    });

    // الهينت العربي جنب "التاريخ" يمشي مع الحقل
    var DAYS = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
    var MONTHS = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    function arNum(n) { return String(n).replace(/[0-9]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'[+d]; }); }

    document.querySelectorAll('input[type=date]').forEach(function (input) {
        var hint = input.closest('.field').querySelector('[data-date-hint]');
        if (!hint) return;
        input.addEventListener('change', function () {
            var parts = input.value.split('-');
            if (parts.length !== 3) { hint.textContent = ''; return; }
            var d = new Date(+parts[0], +parts[1] - 1, +parts[2]);
            hint.textContent = DAYS[d.getDay()] + ' ' + arNum(d.getDate()) + ' ' +
                MONTHS[d.getMonth()] + ' ' + arNum(d.getFullYear());
        });
    });

    // الرجوع بزرار الباك بيرجّع الصفحة من الكاش — نرجّع الزرار طبيعي
    window.addEventListener('pageshow', function () {
        document.body.classList.remove('busy');
        document.querySelectorAll('button.is-loading').forEach(function (button) {
            button.disabled = false;
            button.classList.remove('is-loading');
            button.querySelector('.btn-label').textContent = 'ابحث';
        });
    });
})();
</script>
