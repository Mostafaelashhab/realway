{{-- شيت مشاركة الرحلة --}}
<div id="share-backdrop" class="sheet-backdrop" onclick="closeShare()"></div>
<div id="share-sheet" class="sheet" role="dialog" aria-modal="true" aria-label="مشاركة الرحلة">
    <div class="sheet-handle"></div>
    <div class="sheet-head">
        <div class="sheet-title">مشاركة الرحلة</div>
        <button type="button" class="btn btn-ghost btn-icon pressable" onclick="closeShare()" aria-label="إغلاق" style="margin-inline-start:auto"><x-icon name="x" size="18px" /></button>
    </div>
    <div class="sheet-body" style="padding:0 18px 22px">
        {{-- كارت المعاينة --}}
        <div id="share-preview" style="background:linear-gradient(135deg,var(--brand-tint),var(--surface-2));border:1px solid var(--border);border-radius:16px;padding:18px;margin-bottom:16px"></div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
            <a id="share-wa" target="_blank" rel="noopener" class="share-btn">
                <span class="share-ic" style="background:#25d36620;color:#25d366"><x-icon name="chat" size="20px" /></span><span>واتساب</span>
            </a>
            <a id="share-tg" target="_blank" rel="noopener" class="share-btn">
                <span class="share-ic" style="background:#2aabee20;color:#2aabee"><x-icon name="send" size="20px" /></span><span>تيليجرام</span>
            </a>
            <a id="share-fb" target="_blank" rel="noopener" class="share-btn">
                <span class="share-ic" style="background:#1877f220;color:#1877f2"><x-icon name="facebook" size="20px" /></span><span>فيسبوك</span>
            </a>
            <button type="button" id="share-copy" class="share-btn">
                <span class="share-ic" style="background:var(--brand-tint);color:var(--brand)"><x-icon name="copy" size="19px" /></span><span>نسخ</span>
            </button>
        </div>
        <button type="button" id="share-native" class="btn btn-primary btn-block pressable" style="margin-top:14px;display:none">
            <x-icon name="route" size="16px" /> مشاركة عبر التطبيقات
        </button>
    </div>
</div>
