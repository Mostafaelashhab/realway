<div id="community" class="card" style="padding:20px;margin-bottom:14px">
    <div class="flex items-center" style="gap:8px;margin-bottom:4px">
        <x-icon name="crowd" size="18px" style="color:var(--brand)" />
        <h2 style="font-size:15px;font-weight:800">{{ $title }}</h2>
    </div>
    <p style="font-size:12px;color:var(--ink-faint);margin-bottom:14px">مساهمات من ركّاب زيّك — آراء شخصية مش معلومات رسمية.</p>

    @if ($ok)
        <div class="badge badge-success" style="width:100%;padding:10px 12px;margin-bottom:12px;justify-content:flex-start">
            <x-icon name="check" size="14px" /> {{ $ok }}
        </div>
    @endif

    @forelse ($posts as $p)
        <div wire:key="post-{{ $p->id }}" style="padding:12px 0;border-bottom:1px solid var(--border)">
            <div class="flex items-center" style="gap:8px;margin-bottom:6px">
                <span class="badge {{ $p->kind === 'question' ? 'badge-accent' : 'badge-brand' }}">
                    <x-icon name="{{ $p->kind === 'question' ? 'chat' : 'info' }}" size="12px" />
                    {{ $p->kind === 'question' ? 'سؤال' : 'نصيحة' }}
                </span>
                <span style="font-size:12px;color:var(--ink-soft)">{{ $p->author }}</span>
                <span style="font-size:11px;color:var(--ink-faint);margin-inline-start:auto">{{ $p->created_at->diffForHumans() }}</span>
            </div>
            <p style="font-size:14px;line-height:1.7;margin-bottom:8px">{{ $p->body }}</p>

            <div class="flex items-center" style="gap:8px;flex-wrap:wrap">
                <button type="button" wire:click="markHelpful({{ $p->id }})" class="chip pressable {{ in_array($p->id, $myHelpful, true) ? 'chip-active' : '' }}" style="font-size:12px;padding:4px 10px">
                    <x-icon name="check" size="13px" /> مفيد @if ($p->helpful) · {{ $p->helpful }} @endif
                </button>
                <button type="button" wire:click="report({{ $p->id }})" wire:confirm="تبلّغ عن المساهمة دي؟" class="chip pressable" style="font-size:12px;padding:4px 10px;color:var(--ink-faint)">
                    <x-icon name="alert" size="13px" /> إبلاغ
                </button>
                @if ($p->kind === 'question')
                    <button type="button" wire:click="openReply({{ $p->id }})" class="chip pressable" style="font-size:12px;padding:4px 10px">
                        <x-icon name="chat" size="13px" /> رد
                    </button>
                @endif
            </div>

            {{-- الردود --}}
            @if ($p->replies->count())
                <div style="margin-top:10px;padding-inline-start:14px;border-inline-start:2px solid var(--border);display:flex;flex-direction:column;gap:10px">
                    @foreach ($p->replies as $r)
                        <div wire:key="reply-{{ $r->id }}">
                            <div class="flex items-center" style="gap:8px;margin-bottom:3px">
                                <span class="badge badge-success"><x-icon name="check" size="11px" /> رد</span>
                                <span style="font-size:12px;color:var(--ink-soft)">{{ $r->author }}</span>
                                <span style="font-size:11px;color:var(--ink-faint);margin-inline-start:auto">{{ $r->created_at->diffForHumans() }}</span>
                            </div>
                            <p style="font-size:13px;line-height:1.7;margin-bottom:4px">{{ $r->body }}</p>
                            <div class="flex items-center" style="gap:8px">
                                <button type="button" wire:click="markHelpful({{ $r->id }})" class="chip pressable {{ in_array($r->id, $myHelpful, true) ? 'chip-active' : '' }}" style="font-size:11px;padding:3px 8px"><x-icon name="check" size="12px" /> مفيد @if ($r->helpful) · {{ $r->helpful }} @endif</button>
                                <button type="button" wire:click="report({{ $r->id }})" wire:confirm="تبلّغ عن الرد ده؟" class="chip pressable" style="font-size:11px;padding:3px 8px;color:var(--ink-faint)"><x-icon name="alert" size="12px" /></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- فورم الرد (يظهر بدون reload) --}}
            @if ($replyTo === $p->id)
                <form wire:submit="addReply" style="margin-top:10px;display:flex;flex-direction:column;gap:8px">
                    <textarea wire:model="replyBody" rows="2" maxlength="500" placeholder="ردّك على السؤال..." class="input" style="resize:vertical"></textarea>
                    @error('replyBody')<span style="font-size:12px;color:var(--error)">{{ $message }}</span>@enderror
                    <div style="display:flex;gap:8px">
                        <input type="text" wire:model="replyAuthor" maxlength="40" placeholder="اسمك (اختياري)" class="input" style="flex:1">
                        <button type="submit" class="btn btn-primary pressable" wire:loading.attr="disabled" wire:target="addReply">
                            <x-icon name="send" size="16px" /> <span wire:loading.remove wire:target="addReply">رد</span><span wire:loading wire:target="addReply">...</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @empty
        <p style="color:var(--ink-soft);font-size:14px;padding:8px 0 16px">لسه مفيش مساهمات — كن أول واحد يساعد!</p>
    @endforelse

    {{-- إضافة مساهمة جديدة --}}
    <form wire:submit="addPost" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:10px">
        <input type="text" wire:model="website" style="display:none" tabindex="-1" autocomplete="off">
        <div style="display:flex;gap:8px">
            <label class="chip" style="cursor:pointer"><input type="radio" wire:model="kind" value="tip" style="accent-color:var(--brand)"> نصيحة</label>
            <label class="chip" style="cursor:pointer"><input type="radio" wire:model="kind" value="question" style="accent-color:var(--accent)"> سؤال</label>
        </div>
        <textarea wire:model="body" rows="2" maxlength="500" placeholder="اكتب نصيحتك أو سؤالك للركّاب..." class="input" style="resize:vertical"></textarea>
        @error('body')<span style="font-size:12px;color:var(--error)">{{ $message }}</span>@enderror
        <div style="display:flex;gap:8px">
            <input type="text" wire:model="author" maxlength="40" placeholder="اسمك (اختياري)" class="input" style="flex:1">
            <button type="submit" class="btn btn-primary pressable" wire:loading.attr="disabled" wire:target="addPost">
                <x-icon name="send" size="16px" /> <span wire:loading.remove wire:target="addPost">انشر</span><span wire:loading wire:target="addPost">...</span>
            </button>
        </div>
    </form>
</div>
