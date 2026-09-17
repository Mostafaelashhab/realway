<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Services\Enr\EnrClient;
use App\Services\Enr\EnrNormalizer;
use App\Support\Ar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function home()
    {
        return view('home', [
            'stations' => $this->stations(),
            'date'     => now()->toDateString(),
        ]);
    }

    /** بحث من محطة لمحطة. */
    public function search(Request $request, EnrClient $client)
    {
        $from = (string) $request->query('from', '');
        $to   = (string) $request->query('to', '');
        $date = $this->date($request);

        $error = null;
        if ($from === '' || $to === '') {
            $error = 'اختار محطة القيام ومحطة الوصول.';
        } elseif ($from === $to) {
            $error = 'المحطتين واحدة — غيّر واحدة منهم.';
        }

        $trains = $error ? [] : $this->live($client, $from, $to, $date);

        if (! $error && ! $trains) {
            $error = 'مفيش قطارات على الخط ده في التاريخ ده.';
        }

        return view('results', [
            'title' => 'قطارات '.$this->name($from).' ← '.$this->name($to),
            'date'  => $date,
            'trains' => $trains,
            'error'  => $error,
        ]);
    }

    /** بحث برقم القطر — بنجيب خطه من الداتا المحلية وبعدين نسأل ENR عليه. */
    public function train(Request $request, EnrClient $client)
    {
        $number = trim(Ar::west((string) $request->query('number', '')));
        $date   = $this->date($request);

        $error  = null;
        $trains = [];

        if ($number === '') {
            $error = 'اكتب رقم القطر.';
        } else {
            // أطول خط معروف للقطر ده (عشان نجيب كل محطاته)
            $route = DB::table('trips')
                ->where('train_number', $number)
                ->orderByDesc('stops_count')
                ->first(['from_id', 'to_id']);

            if (! $route) {
                $error = 'القطر رقم '.Ar::num($number).' مش موجود في الداتا — جرّب البحث بالمحطات.';
            } else {
                $trains = array_values(array_filter(
                    $this->live($client, $route->from_id, $route->to_id, $date),
                    fn ($t) => $t['number'] === $number
                ));

                if (! $trains) {
                    $error = 'القطر رقم '.Ar::num($number).' مش مشغّل في التاريخ ده.';
                }
            }
        }

        return view('results', [
            'title' => $number === '' ? 'بحث برقم القطر' : 'قطر رقم '.Ar::num($number),
            'date'  => $date,
            'trains' => $trains,
            'error'  => $error,
        ]);
    }

    /**
     * نداء ENR الحي + تسمية المحطات من الداتا المحلية.
     * الكاش دقيقتين — الكراسي الفاضية بتتغيّر بسرعة.
     */
    private function live(EnrClient $client, string $from, string $to, string $date): array
    {
        if ($from === '' || $to === '' || $from === $to) {
            return [];
        }

        $raw = Cache::remember(
            "enr:$from:$to:$date",
            now()->addMinutes(2),
            fn () => $client->search($from, $to, $date)
        );

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

        usort($trains, fn ($a, $b) => ($a['depart'] ?? '') <=> ($b['depart'] ?? ''));

        return $trains;
    }

    private function date(Request $request): string
    {
        $date = (string) $request->query('date', '');

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : now()->toDateString();
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
