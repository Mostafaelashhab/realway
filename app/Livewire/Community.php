<?php

namespace App\Livewire;

use App\Models\CommunityPost;
use Livewire\Component;

class Community extends Component
{
    public string $scopeType;
    public string $scopeKey;
    public string $title = 'نصايح وأسئلة الركّاب';

    // فورم مساهمة جديدة
    public string $kind = 'tip';
    public string $body = '';
    public string $author = '';
    public string $website = '';   // honeypot

    // الرد
    public ?int $replyTo = null;
    public string $replyBody = '';
    public string $replyAuthor = '';

    public string $ok = '';

    // منع التكرار: اللي صوّت "مفيد" أو بلّغ عليه (لكل جلسة)
    public array $myHelpful = [];
    public array $myReported = [];

    public function mount(string $scopeType, string $scopeKey, string $title = 'نصايح وأسئلة الركّاب'): void
    {
        $this->scopeType = $scopeType;
        $this->scopeKey = $scopeKey;
        $this->title = $title;
        $this->myHelpful = session('egtrain_helpful', []);
        $this->myReported = session('egtrain_reported', []);
    }

    public function addPost(): void
    {
        if ($this->website !== '') {
            return;
        }
        $this->validate([
            'kind' => 'in:tip,question',
            'body' => 'required|string|min:3|max:500',
            'author' => 'nullable|string|max:40',
        ], [], ['body' => 'المساهمة']);

        CommunityPost::create([
            'scope_type' => $this->scopeType,
            'scope_key'  => $this->scopeKey,
            'kind'       => $this->kind,
            'body'       => trim($this->body),
            'author'     => trim($this->author) ?: 'راكب',
        ]);

        $this->reset(['body', 'author']);
        $this->kind = 'tip';
        $this->ok = 'شكرًا! مساهمتك اتنشرت وهتساعد ركّاب تانيين.';
    }

    public function openReply(int $id): void
    {
        $this->replyTo = $this->replyTo === $id ? null : $id;
        $this->replyBody = '';
        $this->replyAuthor = '';
    }

    public function addReply(): void
    {
        $parent = CommunityPost::visible()->find($this->replyTo);
        if (! $parent) {
            return;
        }
        $this->validate(['replyBody' => 'required|string|min:3|max:500', 'replyAuthor' => 'nullable|string|max:40'], [], ['replyBody' => 'الرد']);

        CommunityPost::create([
            'parent_id'  => $parent->id,
            'scope_type' => $parent->scope_type,
            'scope_key'  => $parent->scope_key,
            'kind'       => 'answer',
            'body'       => trim($this->replyBody),
            'author'     => trim($this->replyAuthor) ?: 'راكب',
        ]);

        $this->reset(['replyBody', 'replyAuthor', 'replyTo']);
        $this->ok = 'شكرًا! ردّك اتنشر.';
    }

    public function markHelpful(int $id): void
    {
        $post = CommunityPost::visible()->find($id);
        if (! $post) {
            return;
        }

        // تصويت واحد لكل مساهمة — الضغطة التانية تلغي التصويت
        if (in_array($id, $this->myHelpful, true)) {
            $post->decrement('helpful');
            $this->myHelpful = array_values(array_diff($this->myHelpful, [$id]));
        } else {
            $post->increment('helpful');
            $this->myHelpful[] = $id;
        }
        session(['egtrain_helpful' => $this->myHelpful]);
    }

    public function report(int $id): void
    {
        // بلاغ واحد لكل مساهمة
        if (in_array($id, $this->myReported, true)) {
            $this->ok = 'بلّغت عن دي قبل كده.';

            return;
        }
        $post = CommunityPost::visible()->find($id);
        if (! $post) {
            return;
        }
        $post->increment('reports');
        if ($post->reports >= 3) {
            $post->update(['hidden' => true]);
        }
        $this->myReported[] = $id;
        session(['egtrain_reported' => $this->myReported]);
        $this->ok = 'شكرًا على البلاغ — هنراجعه.';
    }

    public function render()
    {
        return view('livewire.community', [
            'posts' => CommunityPost::forScope($this->scopeType, $this->scopeKey),
        ]);
    }
}
