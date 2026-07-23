<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Train extends Model
{
    protected $fillable = ['number', 'type', 'description_ar'];

    public function coachClasses(): BelongsToMany
    {
        return $this->belongsToMany(CoachClass::class, 'coach_class_train');
    }
}
