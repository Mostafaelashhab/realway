<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    /** عدد البلاغات اللي بعدها يتخفي المحتوى تلقائيًا لحد المراجعة. */
    private const REPORT_THRESHOLD = 3;

    public function store(Request $request)
    {
        if ($request->filled('website')) {   // honeypot
            return back();
        }

        $data = $request->validate([
            'parent_id'  => 'nullable|integer|exists:community_posts,id',
            'scope_type' => 'required|in:station,train,route',
            'scope_key'  => 'required|string|max:64',
            'kind'       => 'required|in:tip,question',
            'body'       => 'required|string|min:3|max:500',
            'author'     => 'nullable|string|max:40',
        ]);

        // لو ردّ على سؤال، يرث النطاق من الأصل ونوعه "answer"
        $parent = ! empty($data['parent_id']) ? CommunityPost::visible()->find($data['parent_id']) : null;

        CommunityPost::create([
            'parent_id'  => $parent?->id,
            'scope_type' => $parent?->scope_type ?? $data['scope_type'],
            'scope_key'  => $parent?->scope_key ?? $data['scope_key'],
            'kind'       => $parent ? 'answer' : $data['kind'],
            'body'       => trim($data['body']),
            'author'     => trim($data['author'] ?? '') ?: 'راكب',
        ]);

        return back()
            ->with('community_ok', $parent ? 'شكرًا! ردّك اتنشر.' : 'شكرًا! مساهمتك اتنشرت وهتساعد ركّاب تانيين.')
            ->withFragment('community');
    }

    public function helpful(CommunityPost $post)
    {
        $post->increment('helpful');

        return back()->withFragment('community');
    }

    public function report(CommunityPost $post)
    {
        $post->increment('reports');

        // إخفاء تلقائي مؤقت لحد المراجعة لو وصل للحد
        if ($post->reports >= self::REPORT_THRESHOLD) {
            $post->update(['hidden' => true]);
        }

        return back()->with('community_ok', 'شكرًا على البلاغ — هنراجعه.')->withFragment('community');
    }
}
