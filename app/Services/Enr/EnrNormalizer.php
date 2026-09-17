<?php

namespace App\Services\Enr;

/**
 * يحوّل ردود ENR الخام (المليانة حقول داخلية) لموديل نضيف يستهلكه التطبيق.
 *
 * قواعد أساسية اكتشفناها من الداتا:
 *  - الأسعار عند ENR بالقرش (×100): cost=2500 => 25.00 جنيه.
 *  - إحداثيات الكراسي "متلغبطة" (99 مقابل 100 مقابل 101) => لازم snap-to-grid.
 *  - صفوف الكراسي بتتجمّع في بنود على محور y؛ أول وآخر بند = كراسي شباك.
 *  - localizationMap فيه {ar,en} وهو أنضف من فك سلسلة localization.
 */
class EnrNormalizer
{
    /** تحويل قرش ENR لجنيه. */
    public static function toEgp(int|float|string|null $piastres): float
    {
        return round(((int) $piastres) / 100, 2);
    }

    /** استخراج {ar,en} من كائن فيه localizationMap أو localization. */
    public static function loc(array $node): array
    {
        $map = $node['localizationMap'] ?? null;
        if (is_array($map) && ($map['ar'] ?? null)) {
            return ['ar' => $map['ar'] ?? null, 'en' => $map['en'] ?? null];
        }
        $raw = $node['localization'] ?? null;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return ['ar' => $decoded['ar'] ?? null, 'en' => $decoded['en'] ?? null];
            }
        }
        return ['ar' => $node['name'] ?? null, 'en' => $node['name'] ?? null];
    }

    /** محطة واحدة. */
    public static function station(array $s): array
    {
        $loc = self::loc($s);
        return [
            'id'        => (string) ($s['id'] ?? ''),
            'code'      => (string) ($s['params']['station_code'] ?? $s['description'] ?? ''),
            'name_ar'   => $loc['ar'],
            'name_en'   => $loc['en'],
            'has_gates' => ($s['params']['has_gates'] ?? 'false') === 'true',
            'active'    => (bool) ($s['active'] ?? true),
        ];
    }

    /** درجة العربية (من كائن coachClass). */
    public static function coachClass(array $c): array
    {
        $loc = self::loc($c);
        return [
            'id'      => (string) ($c['id'] ?? ''),
            'code'    => (string) ($c['params']['code'] ?? $c['name'] ?? ''),
            'name_ar' => $c['params']['ar'] ?? $loc['ar'],   // الاسم التقني (ثالثة مكيفة)
            'name_en' => $c['params']['en'] ?? $loc['en'],
            'label_ar'=> $loc['ar'],                          // الاسم التسويقي (تحيا مصر) لو مختلف
            'seqno'   => (int) ($c['seqno'] ?? 0),
        ];
    }

    /**
     * نوع العربية + تخطيط الكراسي مع كشف الشباك.
     * بياخد كائن servicePoint (من البحث) أو كائن coach-type (من الملف الثابت).
     */
    public static function coach(array $c): array
    {
        $class = isset($c['coachClass']) && is_array($c['coachClass'])
            ? self::coachClass($c['coachClass'])
            : null;

        $seats = self::seats($c['places'] ?? []);

        return [
            'id'           => (string) ($c['id'] ?? ''),
            'number'       => (string) ($c['name'] ?? ''),
            'class_id'     => $class['id'] ?? null,
            'class_ar'     => $class['name_ar'] ?? null,
            'seats_total'  => (int) ($c['params']['seats_count'] ?? count($seats)),
            'seats_open'   => isset($c['params']['seatCount']) ? (int) $c['params']['seatCount'] : null,
            'price_egp'    => isset($c['cost']) ? self::toEgp($c['cost']) : null,
            'seats'        => $seats,
        ];
    }

    /**
     * تطبيع الكراسي: snap-to-grid + كشف كراسي الشباك.
     * كل كرسي => number, x, y, row, available, sold, window(bool)
     */
    public static function seats(array $places): array
    {
        if (empty($places)) {
            return [];
        }

        // اجمع الكراسي بإحداثياتها
        $items = [];
        foreach ($places as $p) {
            if (($p['params']['kind'] ?? 'seat') !== 'seat') {
                continue; // تجاهل عناصر مش كراسي (مناضد/حمامات لو وُجدت)
            }
            $items[] = [
                'number'    => (int) ($p['number'] ?? 0),
                'x'         => (float) ($p['topLeft']['x'] ?? 0),
                'y'         => (float) ($p['topLeft']['y'] ?? 0),
                'available' => array_key_exists('available', $p) ? (bool) $p['available'] : null,
                'sold'      => (bool) ($p['sold'] ?? false),
            ];
        }
        if (empty($items)) {
            return [];
        }

        // snap صفوف y لبنود (tolerance 25 وحدة)
        $ys = array_map(fn ($i) => $i['y'], $items);
        $rows = self::cluster($ys, 25);           // مصفوفة مراكز الصفوف مرتبة
        $minRow = 0;
        $maxRow = count($rows) - 1;

        foreach ($items as &$it) {
            $it['row'] = self::nearestIndex($rows, $it['y']);
            // كرسي شباك = أول صف أو آخر صف (بعيد عن الممر)
            $it['window'] = ($it['row'] === $minRow || $it['row'] === $maxRow);
        }
        unset($it);

        usort($items, fn ($a, $b) => [$a['row'], $a['x']] <=> [$b['row'], $b['x']]);
        return $items;
    }

    /** تطبيع رد بحث كامل لقائمة رحلات نضيفة. */
    public static function searchResults(array $response): array
    {
        $trips = [];
        foreach ($response as $option) {
            foreach (($option['steps'] ?? []) as $step) {
                $train = $step['train'] ?? [];
                $coaches = array_map(
                    fn ($sp) => self::coach($sp),
                    $train['servicePoints'] ?? []
                );

                $trips[] = [
                    'train_number'  => (string) ($train['name'] ?? ''),
                    'train_type'    => self::trainType($train),
                    'from_id'       => (string) ($step['fromId'] ?? ($step['from']['id'] ?? '')),
                    'to_id'         => (string) ($step['toId'] ?? ($step['to']['id'] ?? '')),
                    'from_ar'       => self::loc($step['from'] ?? [])['ar'] ?? null,
                    'to_ar'         => self::loc($step['to'] ?? [])['ar'] ?? null,
                    'depart'        => $step['fromDate'] ?? null,
                    'arrive'        => $step['finishDate'] ?? null,
                    'duration_min'  => (int) ($step['duration'] ?? 0),
                    'distance_km'   => (int) ($step['totalDistance'] ?? $step['distance'] ?? 0),
                    'route_ids'     => array_map(fn ($r) => (string) ($r['id'] ?? ''), $step['route'] ?? []),
                    'seats_open'    => (int) ($step['availableSeats'] ?? 0),
                    'start_price'   => self::toEgp($step['startingPrice'] ?? 0),
                    'coaches'       => $coaches,
                ];
            }
        }
        return $trips;
    }

    /**
     * تطبيع رد البحث للعرض المبسّط: لكل قطر → اسمه، مواعيده، سعر البداية،
     * درجاته بأسعارها، الكراسي الفاضية بأرقامها لكل عربية، ومحطات الروت.
     */
    public static function trains(array $response): array
    {
        $trains = [];

        foreach ($response as $option) {
            foreach (($option['steps'] ?? []) as $step) {
                $train  = $step['train'] ?? [];
                $number = (string) ($train['name'] ?? '');
                if ($number === '' || isset($trains[$number])) {
                    continue;
                }

                $trains[$number] = [
                    'number'       => $number,
                    'name'         => self::trainName($train),
                    'from_id'      => (string) ($step['fromId'] ?? ''),
                    'to_id'        => (string) ($step['toId'] ?? ''),
                    'depart'       => $step['fromDate'] ?? null,
                    'arrive'       => $step['finishDate'] ?? null,
                    'duration_min' => (int) ($step['duration'] ?? 0),
                    'distance_km'  => (int) ($step['totalDistance'] ?? 0),
                    'start_price'  => self::toEgp($step['startingPrice'] ?? 0),
                    'seats_open'   => (int) ($step['availableSeats'] ?? 0),
                    'route_ids'    => array_values(array_filter(array_map(
                        fn ($r) => (string) ($r['id'] ?? ''),
                        $step['route'] ?? []
                    ))),
                    'classes'      => self::classes($train['servicePoints'] ?? []),
                ];
            }
        }

        return array_values($trains);
    }

    /** يجمّع عربيات القطر حسب الدرجة: سعر الدرجة + الكراسي الفاضية بأرقامها. */
    private static function classes(array $servicePoints): array
    {
        $classes = [];

        foreach ($servicePoints as $sp) {
            $class = $sp['coachClass'] ?? [];
            $id    = (string) ($class['id'] ?? ($sp['name'] ?? ''));

            $free = [];
            foreach (($sp['places'] ?? []) as $p) {
                if (($p['params']['kind'] ?? 'seat') === 'seat' && ($p['available'] ?? false)) {
                    $free[] = (string) ($p['number'] ?? '');
                }
            }
            usort($free, fn ($a, $b) => (int) $a <=> (int) $b);

            $classes[$id] ??= [
                'name'    => self::loc($class)['ar'] ?? ($class['params']['ar'] ?? '—'),
                'price'   => self::toEgp($sp['cost'] ?? 0),
                'seats'   => 0,
                'coaches' => [],
            ];
            $classes[$id]['seats'] += count($free);
            if ($free) {
                $classes[$id]['coaches'][] = ['coach' => (string) ($sp['name'] ?? ''), 'seats' => $free];
            }
        }

        foreach ($classes as &$c) {
            usort($c['coaches'], fn ($a, $b) => (int) $a['coach'] <=> (int) $b['coach']);
        }
        unset($c);

        $classes = array_values($classes);
        usort($classes, fn ($a, $b) => $b['price'] <=> $a['price']);

        return $classes;
    }

    /** وصف القطر بالعربي (ثالثة تهوية / خاص...) من fields. */
    private static function trainName(array $train): ?string
    {
        foreach (($train['fields'] ?? []) as $f) {
            if (($f['key'] ?? '') === 'enr_train_description') {
                return $f['params']['ar'] ?? self::loc($f)['ar'] ?? null;
            }
        }

        return null;
    }

    private static function trainType(array $train): ?string
    {
        foreach (($train['fields'] ?? []) as $f) {
            if (($f['key'] ?? '') === 'enr_train_type') {
                return $f['stringV'] ?? $f['name'] ?? null;
            }
        }
        return null;
    }

    /** تجميع قيم متقاربة في عناقيد؛ يرجّع مراكز العناقيد مرتبة. */
    private static function cluster(array $values, float $tolerance): array
    {
        $sorted = $values;
        sort($sorted);
        $clusters = [];
        $current = [];
        foreach ($sorted as $v) {
            if (empty($current) || ($v - end($current)) <= $tolerance) {
                $current[] = $v;
            } else {
                $clusters[] = array_sum($current) / count($current);
                $current = [$v];
            }
        }
        if ($current) {
            $clusters[] = array_sum($current) / count($current);
        }
        return $clusters;
    }

    private static function nearestIndex(array $centers, float $value): int
    {
        $best = 0;
        $bestDist = INF;
        foreach ($centers as $i => $c) {
            $d = abs($c - $value);
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = $i;
            }
        }
        return $best;
    }
}
