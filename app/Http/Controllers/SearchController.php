<?php

namespace App\Http\Controllers;

use App\Models\CoachClass;
use App\Models\CoachType;
use App\Models\Station;
use App\Models\Train;
use App\Models\Trip;
use App\Services\Enr\EnrClient;
use App\Services\Enr\EnrNormalizer;
use App\Services\Enr\TripImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function home()
    {
        return view('home', [
            'stations' => $this->stationList(),
            'cairo'    => '606534187276566625',
            'alex'     => '606535384603557938',
            'date'     => now()->addDay()->toDateString(),
        ]);
    }

    public function search(Request $request, EnrClient $client, TripImporter $importer)
    {
        $from = (string) $request->query('from', '');
        $to   = (string) $request->query('to', '');
        $date = (string) $request->query('date', now()->addDay()->toDateString());

        $fromStation = Station::find($from);
        $toStation   = Station::find($to);

        // الجدول أسبوعي — نبحث حسب يوم الأسبوع (السبت زي كل سبت)
        $weekday = \Carbon\Carbon::parse($date)->dayOfWeek; // 0=الأحد .. 6=السبت
        $query = fn () => Trip::query()
            ->where('from_id', $from)
            ->where('to_id', $to)
            ->where('weekday', $weekday)
            ->orderBy('depart_at')
            ->get();

        $all = $query();

        // مش محفوظ لليوم ده من الأسبوع، أو الجدول قديم؟ → هات من ENR وخزّنه لليوم ده
        $stale = $all->isNotEmpty()
            && $all->max('harvested_at')
            && \Carbon\Carbon::parse($all->max('harvested_at'))->lt(now()->subDays(14));

        $liveFetched = false;
        if (($all->isEmpty() || $stale) && $from !== '' && $to !== '' && $from !== $to) {
            $raw = Cache::remember(
                "enr:search:$from:$to:$date",
                now()->addMinutes(10),
                fn () => $client->search($from, $to, $date)
            );
            $importer->storeMany(EnrNormalizer::searchResults($raw), $date);
            $fresh = $query();
            if ($fresh->isNotEmpty()) {
                $liveFetched = $all->isEmpty(); // مؤشر "اتجاب دلوقتي" للخط الجديد بس
                $all = $fresh;
            }
        }

        $trips = $all->unique('train_number')
            ->map(fn (Trip $t) => $this->decorateTrip($t))
            ->values();

        return view('results', [
            'stations'    => $this->stationList(),
            'from'        => $from,
            'to'          => $to,
            'date'        => $date,
            'fromStation' => $fromStation,
            'toStation'   => $toStation,
            'trips'       => $trips,
            'liveFetched' => $liveFetched,
        ]);
    }

    public function coach(CoachType $coachType)
    {
        $coachType->load('seats', 'coachClass');
        $seats = $coachType->seats->sortBy([['row_index', 'asc'], ['x', 'asc']])->values();

        return view('coach', [
            'coach'      => $coachType,
            'seats'      => $seats,
            'windowOpen' => $seats->where('is_window', true)->count(),
        ]);
    }

    /** الكراسي الحية — on-demand من ENR، مكاش دقايق، degradable. */
    public function liveSeats(Request $request, EnrClient $client)
    {
        $train = (string) $request->query('train', '');
        $from  = (string) $request->query('from', '');
        $to    = (string) $request->query('to', '');
        $date  = (string) $request->query('date', now()->addDay()->toDateString());
        $coachId = (string) $request->query('coach', '');

        $fromStation = Station::find($from);
        $toStation   = Station::find($to);

        // نداء ENR مكاش لـ 3 دقايق (نفس البحث لكل الركّاب)
        $raw = Cache::remember(
            "enr:search:$from:$to:$date",
            now()->addMinutes(3),
            fn () => $client->search($from, $to, $date)
        );

        $trips = EnrNormalizer::searchResults($raw);
        $trip = collect($trips)->firstWhere('train_number', $train);

        // degradable: لو ENR وقع أو القطر مش موجود → رجّع للتخطيط الثابت
        if (! $trip || empty($trip['coaches'])) {
            return view('live-seats', [
                'unavailable' => true,
                'train' => $train, 'from' => $from, 'to' => $to, 'date' => $date,
                'fromStation' => $fromStation, 'toStation' => $toStation,
                'coaches' => [], 'selected' => null,
            ]);
        }

        $coaches = collect($trip['coaches'])->filter(fn ($c) => ! empty($c['seats']))->values();
        $selected = $coaches->firstWhere('id', $coachId) ?: $coaches->first();

        return view('live-seats', [
            'unavailable' => false,
            'train' => $train, 'from' => $from, 'to' => $to, 'date' => $date,
            'fromStation' => $fromStation, 'toStation' => $toStation,
            'trip' => $trip,
            'coaches' => $coaches,
            'selected' => $selected,
        ]);
    }

    /** يضيف بيانات القطر ودرجاته لكل رحلة. */
    private function decorateTrip(Trip $trip): array
    {
        $train = Train::with('coachClasses')->where('number', $trip->train_number)->first();

        // نوع عربية تمثيلي لكل درجة (عشان نلينك لخريطة الكراسي)
        $classes = ($train?->coachClasses ?? collect())->map(function (CoachClass $c) {
            $coachType = CoachType::where('coach_class_id', $c->id)->has('seats')->first();

            return [
                'label'      => $c->label_ar ?: $c->name_ar,
                'name'       => $c->name_ar,
                'coach_type' => $coachType?->id,
            ];
        });

        return [
            'trip'    => $trip,
            'classes' => $classes,
        ];
    }

    private function stationList()
    {
        return Station::where('active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'code']);
    }
}
