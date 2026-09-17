<?php

namespace App\Http\Controllers;

use App\Models\CoachClass;
use App\Models\Station;
use App\Models\Train;
use App\Models\Trip;
use App\Services\Enr\EnrClient;
use App\Services\Enr\EnrNormalizer;
use App\Support\Ar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    private const IN_PAST = 'التاريخ ده عدّى — اختار النهاردة أو بعده.';

    private const ENR_DOWN = 'نظام السكة الحديد مش راد دلوقتي — جرّب تاني بعد شوية.';

    /** اتحط لو نداء ENR نفسه فشل، عشان نفرّق بين العطل و"مفيش قطارات". */
    private bool $enrDown = false;

    /** آخر مرة اتحصد فيها الجدول المحفوظ — بيتعرض للزائر في وضع الـ fallback. */
    private ?string $harvestedAt = null;

    /** @var array<string,string>|null فهرس [الاسم المطبَّع => id] */
    private ?array $foldedIndex = null;

    public function home()
    {
        return view('home', [
            'stations' => $this->stations(),
            'date'     => self::firstServedDate(),
            'minDate'  => self::firstServedDate(),
            'fromText' => '',
            'toText'   => '',
            'number'   => '',
            'tab'      => 'stations',
        ]);
    }

    /** بحث من محطة لمحطة. */
    public function search(Request $request, EnrClient $client)
    {
        $fromText = trim((string) $request->query('from', ''));
        $toText   = trim((string) $request->query('to', ''));
        $date     = $this->date($request);

        $from = $this->stationId($fromText);
        $to   = $this->stationId($toText);

        $error = null;
        $suggest = [];
        $suggestField = 'from';
        if ($fromText === '' || $toText === '') {
            $error = 'اكتب محطة القيام ومحطة الوصول.';
        } elseif ($from === null || $to === null) {
            $suggestField = $from === null ? 'from' : 'to';
            $missing      = $from === null ? $fromText : $toText;
            $error        = 'مالقيناش محطة اسمها «'.$missing.'».';
            $suggest      = $this->suggest($missing);
        } elseif ($from === $to) {
            $error = 'المحطتين واحدة — غيّر واحدة منهم.';
        } elseif ($date < now()->toDateString()) {
            $error = self::IN_PAST;
        }

        [$queryDate, $scheduleOnly] = $this->resolveDate($date);
        $trains = $error ? [] : $this->live($client, (string) $from, (string) $to, $queryDate, $scheduleOnly);

        $fallback = false;
        if (! $error && ! $trains && $this->enrDown) {
            $trains = $this->stored($date, (string) $from, (string) $to);
            $fallback = (bool) $trains;
        }

        if (! $error && ! $trains) {
            $error = $this->enrDown ? self::ENR_DOWN : 'مفيش قطارات على الخط ده في التاريخ ده.';
        }

        return view('results', [
            'title'        => $from && $to
                ? 'قطارات '.$this->name($from).' ← '.$this->name($to)
                : 'بحث',
            'date'         => $date,
            'stations'     => $this->stations(),
            'minDate'      => self::firstServedDate(),
            'fromText'     => $fromText,
            'toText'       => $toText,
            'number'       => '',
            'tab'          => 'stations',
            'suggest'      => $suggest,
            'suggestField' => $suggestField,
            'scheduleOnly' => $scheduleOnly && ! $fallback,
            'fallback'     => $fallback,
            'fallbackAt'   => $fallback ? $this->harvestedAt : null,
            'trains'       => $trains,
            'error'        => $error,
        ]);
    }

    /** بحث برقم القطر — بنجيب خطه من الداتا المحلية وبعدين نسأل ENR عليه. */
    public function train(Request $request, EnrClient $client)
    {
        $number = trim(Ar::west((string) $request->query('number', '')));
        $date   = $this->date($request);

        $error    = null;
        $trains   = [];
        $fallback = false;

        if ($number === '') {
            $error = 'اكتب رقم القطر.';
        } elseif ($date < now()->toDateString()) {
            $error = self::IN_PAST;
        } else {
            // أطول خط معروف للقطر ده (عشان نجيب كل محطاته)
            $route = DB::table('trips')
                ->where('train_number', $number)
                ->orderByDesc('stops_count')
                ->first(['from_id', 'to_id']);

            if (! $route) {
                $error = 'القطر رقم '.Ar::num($number).' مش موجود في الداتا — جرّب البحث بالمحطات.';
            } else {
                [$queryDate, $scheduleOnly] = $this->resolveDate($date);
                $trains = array_values(array_filter(
                    $this->live($client, $route->from_id, $route->to_id, $queryDate, $scheduleOnly),
                    fn ($t) => $t['number'] === $number
                ));

                if (! $trains && $this->enrDown) {
                    $trains = $this->stored($date, number: $number);
                    $fallback = (bool) $trains;
                }

                if (! $trains) {
                    $error = $this->enrDown
                        ? self::ENR_DOWN
                        : 'القطر رقم '.Ar::num($number).' مش مشغّل في التاريخ ده.';
                }
            }
        }

        return view('results', [
            'title'        => $number === '' ? 'بحث برقم القطر' : 'قطر رقم '.Ar::num($number),
            'date'         => $date,
            'stations'     => $this->stations(),
            'minDate'      => self::firstServedDate(),
            'fromText'     => '',
            'toText'       => '',
            'number'       => $number,
            'tab'          => 'number',
            'suggest'      => [],
            'suggestField' => 'from',
            'scheduleOnly' => ($scheduleOnly ?? false) && ! $fallback,
            'fallback'     => $fallback,
            'fallbackAt'   => $fallback ? $this->harvestedAt : null,
            'trains'       => $trains,
            'error'        => $error,
        ]);
    }

    /**
     * نداء ENR الحي + تسمية المحطات من الداتا المحلية.
     * الكاش دقيقتين — الكراسي الفاضية بتتغيّر بسرعة.
     */
    private function live(EnrClient $client, string $from, string $to, string $date, bool $scheduleOnly = false): array
    {
        if ($from === '' || $to === '' || $from === $to) {
            return [];
        }

        $key = "enr:$from:$to:$date";
        $raw = Cache::get($key);
        if ($raw === null) {
            $raw = $client->trySearch($from, $to, $date);
            if ($raw === null) {
                $this->enrDown = true;                   // النداء نفسه فشل — مش نفس "مفيش قطارات"
                return [];
            }
            if ($raw !== []) {
                Cache::put($key, $raw, now()->addMinutes(2));
            }
        }

        $trains = EnrNormalizer::trains($raw);
        if (! $trains) {
            return [];
        }

        $ids = collect($trains)
            ->flatMap(fn ($t) => [...$t['route_ids'], $t['from_id'], $t['to_id']])
            ->filter()->unique()->all();
        $names = Station::whereIn('id', $ids)->pluck('name_ar', 'id');

        foreach ($trains as &$t) {
            $t['stops'] = array_map(fn ($id) => $names[$id] ?? '—', $t['route_ids']);
            $t['from']  = $names[$t['from_id']] ?? '—';
            $t['to']    = $names[$t['to_id']] ?? '—';
        }
        unset($t);

        // جدول أسبوعي: المواعيد والأسعار حقيقية، إنما التوفّر بتاع يوم تاني — نشيله
        if ($scheduleOnly) {
            foreach ($trains as &$t) {
                $t['depart'] = $this->onDate($t['depart'], $date);
                $t['arrive'] = $this->onDate($t['arrive'], $date);
                foreach ($t['classes'] as &$c) {
                    $c['seats'] = null;
                    $c['coaches'] = [];
                }
                unset($c);
            }
            unset($t);
        }

        usort($trains, fn ($a, $b) => ($a['depart'] ?? '') <=> ($b['depart'] ?? ''));

        return $trains;
    }

    /**
     * خطة بديلة لما ENR ميردش: الجدول الأسبوعي المحصود بـ enr:harvest.
     * مواعيد ودرجات بس — مفيش أسعار لكل درجة ولا توفّر، عشان مش مخزّنين.
     */
    private function stored(string $date, string $from = '', string $to = '', string $number = ''): array
    {
        $rows = Trip::with('stops')
            ->where('weekday', \Carbon\Carbon::parse($date)->dayOfWeek)
            ->when($from !== '' && $to !== '', fn ($q) => $q->where('from_id', $from)->where('to_id', $to))
            ->when($number !== '', fn ($q) => $q->where('train_number', $number))
            ->orderBy('depart_at')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $this->harvestedAt = optional($rows->max('harvested_at'))->toDateString();

        $ids = $rows->flatMap(fn (Trip $t) => $t->stops->pluck('station_id'))
            ->merge($rows->pluck('from_id'))->merge($rows->pluck('to_id'))
            ->filter()->unique()->all();
        $names = Station::whereIn('id', $ids)->pluck('name_ar', 'id');
        $classes = $this->storedClasses($rows->pluck('train_number')->unique()->all());

        return $rows->map(function (Trip $t) use ($date, $names, $classes) {
            $depart = \Carbon\Carbon::parse($date)->setTimeFrom($t->depart_at);

            return [
                'number'       => $t->train_number,
                'name'         => $t->train_type,
                'from'         => $names[$t->from_id] ?? '—',
                'to'           => $names[$t->to_id] ?? '—',
                'depart'       => $depart->toIso8601String(),
                'arrive'       => $depart->copy()->addMinutes((int) $t->duration_min)->toIso8601String(),
                'duration_min' => (int) $t->duration_min,
                'distance_km'  => (int) $t->distance_km,
                'start_price'  => (float) $t->start_price,
                'classes'      => $classes[$t->train_number] ?? [],
                'stops'        => $t->stops->map(fn ($s) => $names[$s->station_id] ?? '—')->all(),
            ];
        })->values()->all();
    }

    /** درجات كل قطر من الداتا الثابتة — أسماء من غير أسعار. */
    private function storedClasses(array $numbers): array
    {
        return Train::with('coachClasses')
            ->whereIn('number', $numbers)
            ->get()
            ->mapWithKeys(fn (Train $train) => [
                $train->number => $train->coachClasses
                    ->map(fn (CoachClass $c) => [
                        'name'    => $c->label_ar ?: $c->name_ar,
                        'price'   => null,
                        'seats'   => null,
                        'coaches' => [],
                    ])->values()->all(),
            ])->all();
    }

    private function date(Request $request): string
    {
        $date = (string) $request->query('date', '');

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : self::firstServedDate();
    }

    /** ENR بيرجّع فاضي للنهاردة واللي فات — الحجز بيفتح من بكرة. */
    public static function firstServedDate(): string
    {
        return now()->addDay()->toDateString();
    }

    /**
     * ENR مش بترد على النهاردة خالص. الجدول بيتكرر أسبوعيًا بدقة (اتأكدنا: ٣٤/٣٤
     * قطر بنفس المواعيد بين جمعة والجمعة اللي بعدها)، فبنسأل عن نفس اليوم الأسبوع
     * الجاي ونعرضه كجدول استرشادي — من غير أي بيانات توفّر، لأنها بتخص يوم تاني.
     *
     * @return array{0:string,1:bool} [التاريخ اللي هنسأل عنه، هل ده جدول أسبوعي؟]
     */
    private function resolveDate(string $date): array
    {
        return $date === now()->toDateString()
            ? [now()->addWeek()->toDateString(), true]
            : [$date, false];
    }

    /** ينقل ميعاد من تاريخ المصدر للتاريخ اللي المستخدم بيسأل عنه. */
    private function onDate(?string $iso, string $sourceDate): ?string
    {
        if (! $iso) {
            return null;
        }
        $dt = \Carbon\Carbon::parse($iso);
        $shift = \Carbon\Carbon::parse($sourceDate)->diffInDays(now()->startOfDay(), false);

        return $dt->addDays($shift)->toIso8601String();
    }

    /**
     * يحوّل اللي المستخدم كتبه لـ id محطة: id جاهز (لينك قديم)، أو اسم بالظبط
     * بعد التطبيع العربي، أو مطابقة جزئية واحدة بس.
     */
    private function stationId(string $input): ?string
    {
        if ($input === '') {
            return null;
        }

        $stations = $this->stations();
        if (isset($stations[$input])) {
            return $input;
        }

        $folded = Ar::fold($input);
        if ($folded === '') {
            return null;
        }

        $byName = $this->foldedIndex();
        if (isset($byName[$folded])) {
            return $byName[$folded];
        }

        $hits = array_filter($byName, fn ($name) => str_contains($name, $folded), ARRAY_FILTER_USE_KEY);

        return count($hits) === 1 ? reset($hits) : null;
    }

    /** أقرب أسماء لمحطة مكتوبة غلط. */
    private function suggest(string $input, int $limit = 6): array
    {
        $folded = Ar::fold($input);
        if ($folded === '') {
            return [];
        }

        $names = array_values($this->stations());
        $scored = [];
        foreach ($names as $name) {
            $f = Ar::fold($name);
            $score = str_contains($f, $folded) || str_contains($folded, $f)
                ? 0
                : levenshtein($f, $folded);
            if ($score <= 4) {
                $scored[$name] = $score;
            }
        }
        asort($scored);

        return array_slice(array_keys($scored), 0, $limit);
    }

    /** [الاسم المطبَّع => id] — مبني مرة واحدة لكل ريكوست (مش static عشان الـ workers الدايمة). */
    private function foldedIndex(): array
    {
        if ($this->foldedIndex === null) {
            $this->foldedIndex = [];
            foreach ($this->stations() as $id => $name) {
                $this->foldedIndex[Ar::fold((string) $name)] = $id;
            }
        }

        return $this->foldedIndex;
    }

    private function name(string $id): string
    {
        return $this->stations()[$id] ?? '—';
    }

    /** كل المحطات كـ [id => الاسم] — بنكاش قيم عادية مش موديلات. */
    private function stations(): array
    {
        return Cache::remember('stations', now()->addHour(), fn () => Station::where('active', true)
            ->whereNotNull('name_ar')
            ->orderBy('name_ar')
            ->pluck('name_ar', 'id')
            ->all());
    }
}
