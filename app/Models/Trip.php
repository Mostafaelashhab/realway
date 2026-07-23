<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'train_number', 'train_type', 'from_id', 'to_id', 'weekday', 'sample_date',
        'depart_at', 'arrive_at', 'duration_min', 'distance_km',
        'start_price', 'stops_count', 'harvested_at',
    ];

    protected $casts = [
        'sample_date'  => 'date',
        'depart_at'    => 'datetime',
        'arrive_at'    => 'datetime',
        'harvested_at' => 'datetime',
    ];

    public function stops(): HasMany
    {
        return $this->hasMany(TripStop::class)->orderBy('sequence');
    }

    /** ميعاد القيام بنظام ١٢ ساعة (٦:٠٠ ص). */
    public function departLabel(): ?string
    {
        return $this->depart_at ? self::time12($this->depart_at) : null;
    }

    /** ميعاد الوصول بنظام ١٢ ساعة. */
    public function arriveLabel(): ?string
    {
        return $this->arrive_at ? self::time12($this->arrive_at) : null;
    }

    public static function time12(\Carbon\Carbon $dt): string
    {
        $mer = (int) $dt->format('H') < 12 ? 'ص' : 'م';

        return $dt->format('g:i').' '.$mer;
    }
}
