<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoachType extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'reg_id', 'name_ar', 'name_en', 'coach_class_id',
        'type', 'seats_count', 'no_seats',
    ];

    protected $casts = ['no_seats' => 'boolean', 'seats_count' => 'integer'];

    public function coachClass(): BelongsTo
    {
        return $this->belongsTo(CoachClass::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(CoachTypeSeat::class);
    }
}
