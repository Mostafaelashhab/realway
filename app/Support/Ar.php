<?php

namespace App\Support;

/** تنسيق عربي: أرقام هندية، وقت ١٢ ساعة، مدة. */
class Ar
{
    private const MAP = [
        '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
        '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
    ];

    /** أرقام إنجليزية → هندية. */
    public static function num(int|float|string|null $v): string
    {
        return strtr((string) $v, self::MAP);
    }

    /** أرقام هندية (اللي المستخدم ممكن يكتبها) → إنجليزية. */
    public static function west(string $v): string
    {
        return strtr($v, array_flip(self::MAP));
    }

    private const DAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    private const MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    /** "2026-09-18" → "الخميس ١٨ سبتمبر ٢٠٢٦" (الأرقام المفصولة بشرطات بتتقلب في RTL). */
    public static function date(string $ymd): string
    {
        $dt = \Carbon\Carbon::parse($ymd);

        return self::DAYS[$dt->dayOfWeek].' '
            .self::num($dt->day).' '
            .self::MONTHS[$dt->month - 1].' '
            .self::num($dt->year);
    }

    /** "2026-09-18T06:00:00+03:00" → "٦:٠٠ ص" */
    public static function time(?string $iso): string
    {
        if (! $iso) {
            return '—';
        }
        $dt = \Carbon\Carbon::parse($iso);

        return self::num($dt->format('g:i')).' '.((int) $dt->format('H') < 12 ? 'ص' : 'م');
    }

    /** 210 → "٣ س ٣٠ د" */
    public static function duration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return trim(($h ? self::num($h).' س ' : '').($m ? self::num($m).' د' : ''));
    }

    /** عدّ عربي صحيح: ١ محطة · ٢ محطتين · ٣-١٠ محطات · ١١+ محطة. */
    public static function count(int $n, string $one, string $two, string $few, string $many): string
    {
        return match (true) {
            $n === 1          => $one,
            $n === 2          => $two,
            $n >= 3 && $n <= 10 => self::num($n).' '.$few,
            default           => self::num($n).' '.$many,
        };
    }

    /** 170.0 → "١٧٠ جنيه" */
    public static function money(float $egp): string
    {
        $v = fmod($egp, 1.0) === 0.0 ? (string) (int) $egp : number_format($egp, 2);

        return self::num($v).' جنيه';
    }
}
