<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    protected $fillable = ['parent_id', 'scope_type', 'scope_key', 'kind', 'body', 'author', 'helpful', 'reports', 'hidden'];

    protected $casts = ['hidden' => 'boolean'];

    public function scopeVisible($q)
    {
        return $q->where('hidden', false);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->visible()->orderBy('created_at');
    }

    /** المساهمات الرئيسية لنطاق معيّن (بدون الردود) + الردود محمّلة. */
    public static function forScope(string $type, string $key)
    {
        return static::visible()
            ->whereNull('parent_id')
            ->where('scope_type', $type)
            ->where('scope_key', $key)
            ->with('replies')
            ->orderByDesc('helpful')->orderByDesc('created_at')
            ->get();
    }
}
