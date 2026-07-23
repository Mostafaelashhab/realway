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

        [$recommendedTrain, $reasons] = $this->recommend($trips);

        return view('results', [
            'stations'         => $this->stationList(),
            'from'             => $from,
            'to'               => $to,
            'date'             => $date,
            'fromStation'      => $fromStation,
            'toStation'        => $toStation,
            'trips'            => $trips,
            'liveFetched'      => $liveFetched,
            'recommendedTrain' => $recommendedTrain,
            'recommendReasons' => $reasons,
        ]);
    }

    /**
     * الترشيح الذكي: يختار أفضل رحلة (أسرع/أرخص/أقل محطات متوازنة) ويشرح ليه.
     * يرجّع [رقم القطر المرشّح، أسباب[]].
     */
    private function recommend($trips): array
    {
        if ($trips->count() < 2) {
            return [null, []];
        }

        $minD = $trips->min('duration'); $maxD = $trips->max('duration');
        $minP = $trips->min('price'); $maxP = $trips->max('price');
        $minS = $trips->min('stops'); $maxS = $trips->max('stops');

        $norm = fn ($v, $lo, $hi) => $hi > $lo ? ($v - $lo) / ($hi - $lo) : 0;

        // درجة أقل = أفضل (الوقت أهم، بعده السعر، بعده المحطات)
        $best = $trips->sortBy(fn ($t) => 0.55 * $norm($t['duration'], $minD, $maxD)
            + 0.30 * $norm($t['price'], $minP, $maxP)
            + 0.15 * $norm($t['stops'], $minS, $maxS))->first();

        $reasons = [];
        if ($best['duration'] <= $minD) {
            $reasons[] = 'الأسرع على الخط';
        } elseif (($maxD - $best['duration']) >= 10) {
            $reasons[] = 'يوفّر '.($maxD - $best['duration']).' دقيقة عن الأبطأ';
        }
        if ($best['price'] <= $minP) {
            $reasons[] = 'الأرخص كمان';
        } else {
            $diff = (int) round($best['price'] - $minP);
            if ($diff > 0 && $diff <= 40) {
                $reasons[] = 'أغلى بـ '.$diff.' جنيه بس عن الأرخص';
            }
        }
        if ($best['stops'] <= $minS && $maxS > $minS) {
            $reasons[] = 'أقل عدد وقفات';
        }

        return [$best['trip']->train_number, array_slice($reasons, 0, 3)];
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

    /**
     * تخطيط الكراسي — ثابت للتخطيط فقط (من الداتا المحلية).
     * مفيش توفّر لحظي ولا أي نداء حي — بيوضّح شكل العربية بس.
     */
    public function seats(Request $request)
    {
        $train = (string) $request->query('train', '');
        $from  = (string) $request->query('from', '');
        $to    = (string) $request->query('to', '');
        $date  = (string) $request->query('date', now()->addDay()->toDateString());
        $selClass = (string) $request->query('coach', '');

        $fromStation = Station::find($from);
        $toStation   = Station::find($to);

        $trainModel = Train::with('coachClasses')->where('number', $train)->first();

        // درجات القطر → تخطيط عربية تمثيلي لكل درجة (من coach_type_seats الثابتة)
        $coaches = ($trainModel?->coachClasses ?? collect())->map(function (CoachClass $c) {
            $ct = CoachType::with('seats')->where('coach_class_id', $c->id)->has('seats')->first();
            if (! $ct) {
                return null;
            }
            $seats = $ct->seats->sortBy([['row_index', 'asc'], ['x', 'asc']])->values();

            return [
                'class_id'     => $c->id,
                'label'        => $c->label_ar ?: $c->name_ar,
                'class_ar'     => $c->name_ar,
                'ac'           => str_contains((string) $c->name_ar, 'مكيف'),
                'type_name'    => $ct->name_ar,
                'seats_count'  => $ct->seats_count ?: $seats->count(),
                'window_count' => $seats->where('is_window', true)->count(),
                'seats'        => $seats,
            ];
        })->filter()->values();

        $selected = $coaches->firstWhere('class_id', $selClass) ?: $coaches->first();

        return view('seats', [
            'train' => $train, 'from' => $from, 'to' => $to, 'date' => $date,
            'fromStation' => $fromStation, 'toStation' => $toStation,
            'coaches' => $coaches, 'selected' => $selected,
        ]);
    }

    /** صفحة تفاصيل القطر. */
    public function train(Request $request, string $number)
    {
        $from = (string) $request->query('from', '');
        $to   = (string) $request->query('to', '');
        $date = (string) $request->query('date', now()->addDay()->toDateString());
        $weekday = \Carbon\Carbon::parse($date)->dayOfWeek;

        $trip = Trip::where('train_number', $number)
            ->when($from && $to, fn ($q) => $q->where('from_id', $from)->where('to_id', $to))
            ->where('weekday', $weekday)->first()
            ?? Trip::where('train_number', $number)->orderByDesc('stops_count')->first();

        abort_if(! $trip, 404);

        $train = Train::with('coachClasses')->where('number', $number)->first();

        // محطات الروت بالترتيب + أسماؤها
        $stations = Station::whereIn('id', $trip->stops->pluck('station_id'))->get()->keyBy('id');
        $stops = $trip->stops->map(fn ($s) => [
            'id'    => $s->station_id,
            'name'  => optional($stations->get($s->station_id))->name_ar ?? '—',
            'code'  => optional($stations->get($s->station_id))->code,
            'seq'   => $s->sequence,
        ]);

        $classes = ($train?->coachClasses ?? collect())->map(function (CoachClass $c) {
            $coachType = CoachType::where('coach_class_id', $c->id)->has('seats')->first();

            return [
                'label'      => $c->label_ar ?: $c->name_ar,
                'name'       => $c->name_ar,
                'ac'         => str_contains((string) $c->name_ar, 'مكيف'),
                'coach_type' => $coachType?->id,
            ];
        });

        return view('train', [
            'trip'        => $trip,
            'number'      => $number,
            'train'       => $train,
            'stops'       => $stops,
            'classes'     => $classes,
            'from'        => $from,
            'to'          => $to,
            'date'        => $date,
            'fromStation' => Station::find($trip->from_id),
            'toStation'   => Station::find($trip->to_id),
        ]);
    }

    /** صفحة المحطة. */
    public function station(string $id)
    {
        $station = Station::findOrFail($id);
        $date = now()->addDay()->toDateString();

        // وجهات مشهورة من المحطة دي (حسب عدد الرحلات)
        $routeRows = Trip::where('from_id', $id)
            ->selectRaw('to_id, count(*) as c')
            ->groupBy('to_id')->orderByDesc('c')->limit(10)->get();
        $destNames = Station::whereIn('id', $routeRows->pluck('to_id'))->get()->keyBy('id');
        $popularRoutes = $routeRows->map(fn ($r) => [
            'to'   => $r->to_id,
            'name' => optional($destNames->get($r->to_id))->name_ar ?? '—',
        ])->filter(fn ($r) => $r['name'] !== '—')->values();

        // لوحة المواعيد: قطارات القيام من هنا
        $depRows = Trip::where('from_id', $id)->orderBy('depart_at')->get()
            ->unique(fn ($t) => $t->train_number.'-'.$t->to_id)->take(25);
        $depDests = Station::whereIn('id', $depRows->pluck('to_id'))->get()->keyBy('id');
        $departures = $depRows->map(fn ($t) => [
            'train'  => $t->train_number,
            'to'     => $t->to_id,
            'toName' => optional($depDests->get($t->to_id))->name_ar ?? '—',
            'depart' => Trip::time12($t->depart_at),
            'date'   => $date,
        ])->values();

        return view('station', [
            'station'       => $station,
            'popularRoutes' => $popularRoutes,
            'departures'    => $departures,
            'date'          => $date,
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

        $isAc = $classes->contains(fn ($c) => str_contains((string) $c['name'], 'مكيف'));

        return [
            'trip'     => $trip,
            'classes'  => $classes,
            // بيانات الفلترة والترشيح
            'is_ac'    => $isAc,
            'price'    => (float) $trip->start_price,
            'duration' => (int) $trip->duration_min,
            'stops'    => (int) $trip->stops_count,
            'depart'   => optional($trip->depart_at)->format('H:i') ?? '',
            'type'     => $trip->train_type,
        ];
    }

    private function stationList()
    {
        return Station::where('active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'code']);
    }
}
