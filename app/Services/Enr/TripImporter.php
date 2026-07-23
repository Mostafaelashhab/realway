<?php

namespace App\Services\Enr;

use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * يخزّن الرحلات المطبّعة (من الحصاد أو من الـ fallback الحي) في الـ DB.
 * مشترك بين enr:harvest وبحث الويب.
 */
class TripImporter
{
    /** يخزّن قائمة رحلات مطبّعة لتاريخ معيّن؛ يرجّع عدد اللي اتخزّن. */
    public function storeMany(array $normalizedTrips, string $date): int
    {
        $count = 0;
        foreach ($normalizedTrips as $t) {
            if ($this->store($t, $date)) {
                $count++;
            }
        }

        return $count;
    }

    public function store(array $t, string $date): bool
    {
        if (($t['train_number'] ?? '') === '' || ($t['from_id'] ?? '') === '' || ($t['to_id'] ?? '') === '') {
            return false;
        }

        $weekday = \Carbon\Carbon::parse($date)->dayOfWeek; // 0=الأحد .. 6=السبت

        DB::transaction(function () use ($t, $date, $weekday) {
            $trip = Trip::updateOrCreate(
                [
                    'train_number' => $t['train_number'],
                    'from_id'      => $t['from_id'],
                    'to_id'        => $t['to_id'],
                    'weekday'      => $weekday,
                ],
                [
                    'sample_date'  => $date,
                    'depart_at'    => $t['depart'],
                    'arrive_at'    => $t['arrive'],
                    'duration_min' => $t['duration_min'],
                    'distance_km'  => $t['distance_km'],
                    'start_price'  => $t['start_price'],
                    'stops_count'  => count($t['route_ids']),
                    'harvested_at' => now(),
                ]
            );

            $trip->stops()->delete();
            $stops = [];
            foreach ($t['route_ids'] as $seq => $sid) {
                if ($sid !== '') {
                    $stops[] = ['trip_id' => $trip->id, 'station_id' => $sid, 'sequence' => $seq];
                }
            }
            if ($stops) {
                DB::table('trip_stops')->insert($stops);
            }
        });

        return true;
    }
}
