<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachTypeSeat extends Model
{
    public $timestamps = false;

    protected $fillable = ['coach_type_id', 'number', 'x', 'y', 'row_index', 'is_window'];

    protected $casts = ['is_window' => 'boolean', 'x' => 'float', 'y' => 'float'];
}
