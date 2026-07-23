<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachClass extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'code', 'name_ar', 'name_en', 'label_ar', 'seqno'];
}
