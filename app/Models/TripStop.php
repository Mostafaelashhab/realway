<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripStop extends Model
{
    public $timestamps = false;

    protected $fillable = ['trip_id', 'station_id', 'sequence'];
}
