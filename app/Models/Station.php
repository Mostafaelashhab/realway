<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'name_ar', 'name_en', 'has_gates', 'active'];

    protected $casts = ['has_gates' => 'boolean', 'active' => 'boolean'];
}
