<?php

namespace App\Console\Commands;

use App\Models\Station;
use App\Models\Train;
use App\Models\Trip;
use App\Services\Enr\EnrClient;
use App\Services\Enr\EnrNormalizer;
use App\Services\Enr\TripImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnrHarvest extends Command
{
    protected $signature = 'enr:harvest
        {--date= : تاريخ البحث (YYYY-MM-DD)، الافتراضي بكرة}
        {--days=1 : عدد الأيام المتتالية (7 = أسبوع كامل لمعرفة أيام تشغيل كل قطر)}
        {--delay=800 : تأخير بالملي ثانية بين النداءات}
        {--from= : id محطة أصل — يحصد منها لكل المحطات (وضع الهَب)}
        {--both : في وضع الهَب، احصد الاتجاهين (من وإلى)}
        {--limit= : أقصى عدد أزواج (للتجربة الآمنة)}
        {--resume : تخطّي الأزواج اللي اتجرّبت قبل كده (نفس التاريخ)}
        {--pairs= : ملف JSON فيه [[from,to],...]}';

    protected $description = 'يحصد الروتس والمواعيد من ENR ويقيس التغطية (مع resume)';

    private array $terminals = [
        '606534187276566625' => 'القاهرة',
        '606535384603557938' => 'الإسكندرية',
        '606535392467877956' => 'أسوان',
        '606535391914229829' => 'الأقصر',
        '606535386352582751' => 'بورسعيد',
        '606535386931396672' => 'السويس',
        '606535388638478397' => 'المنصورة',
        '606535388638478418' => 'دمياط',
        '606535384603557977' => 'مرسى مطروح',
        '606535388638478407' => 'شربين',
        '606535386352582723' => 'الزقازيق',
    ];

    public function handle(EnrClient $client, TripImporter $importer): int
    {
        $baseDate = $this->option('date') ?: now()->addDay()->toDateString();
        $days = max(1, (int) ($this->option('days') ?: 1));
        $delayMs = (int) $this->option('delay');

        // نلف على عدد الأيام (عشان نعرف كل قطر بيمشي أنهي أيام)
        for ($i = 0; $i < $days; $i++) {
            $date = \Carbon\Carbon::parse($baseDate)->addDays($i)->toDateString();
            $pairs = $this->buildPairs($date);

            if (empty($pairs)) {
                $this->info("يوم $date: كله اتجرّب قبل كده — تخطّي.");

                continue;
            }

            $weekday = \Carbon\Carbon::parse($date)->translatedFormat('l');
            $this->info("يوم $date ($weekday) | أزواج: ".count($pairs));
            $bar = $this->output->createProgressBar(count($pairs));
            $bar->start();

            $tripsStored = 0;
            $emptyPairs = 0;

            foreach ($pairs as [$from, $to]) {
                $raw = $client->search($from, $to, $date);
                $trips = EnrNormalizer::searchResults($raw);

                $tripsStored += $importer->storeMany($trips, $date);
                if (empty($trips)) {
                    $emptyPairs++;
                }

                $this->logAttempt($from, $to, $date, count($trips));
                $bar->advance();
                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }

            $bar->finish();
            $this->newLine();
            $this->line("  → رحلات: $tripsStored | فاضي: $emptyPairs");
        }

        $this->newLine();
        $this->reportCoverage();

        return self::SUCCESS;
    }

    private function buildPairs(string $date): array
    {
        // 1) ملف أزواج جاهز
        if ($file = $this->option('pairs')) {
            $pairs = json_decode(file_get_contents($file), true) ?: [];
        }
        // 2) وضع الهَب: من أصل واحد → كل المحطات
        elseif ($from = $this->option('from')) {
            $targets = Station::where('active', true)->where('id', '!=', $from)->pluck('id');
            $pairs = [];
            foreach ($targets as $to) {
                $pairs[] = [$from, $to];
                if ($this->option('both')) {
                    $pairs[] = [$to, $from];
                }
            }
        }
        // 3) الافتراضي: مصفوفة المحطات الطرفية
        else {
            $ids = array_keys($this->terminals);
            $pairs = [];
            foreach ($ids as $a) {
                foreach ($ids as $b) {
                    if ($a !== $b) {
                        $pairs[] = [$a, $b];
                    }
                }
            }
        }

        // resume: شيل اللي اتجرّب قبل كده
        if ($this->option('resume')) {
            $done = DB::table('harvest_attempts')
                ->whereDate('departure_date', $date)
                ->get(['from_id', 'to_id'])
                ->map(fn ($r) => $r->from_id.'|'.$r->to_id)
                ->flip();
            $pairs = array_values(array_filter($pairs, fn ($p) => ! isset($done[$p[0].'|'.$p[1]])));
        }

        if ($limit = (int) $this->option('limit')) {
            $pairs = array_slice($pairs, 0, $limit);
        }

        return $pairs;
    }

    private function reportCoverage(): void
    {
        $covered = Trip::distinct()->pluck('train_number')->count();
        $total = Train::count();
        $pct = $total ? round($covered / $total * 100, 1) : 0;
        $missing = $total - $covered;

        $this->table(['المؤشر', 'القيمة'], [
            ['قطارات متغطّية', "$covered من $total ($pct%)"],
            ['قطارات ناقصة', $missing],
            ['أزواج اتجرّبت (كلي)', DB::table('harvest_attempts')->count()],
        ]);
    }

    private function logAttempt(string $from, string $to, string $date, int $found): void
    {
        DB::table('harvest_attempts')->updateOrInsert(
            ['from_id' => $from, 'to_id' => $to, 'departure_date' => $date],
            ['trips_found' => $found, 'attempted_at' => now()]
        );
    }
}
