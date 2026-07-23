<?php

namespace App\Console\Commands;

use App\Models\CoachClass;
use App\Models\CoachType;
use App\Models\CoachTypeSeat;
use App\Models\Station;
use App\Models\Train;
use App\Services\Enr\EnrNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnrImport extends Command
{
    protected $signature = 'enr:import {--path=public/enr : فولدر ملفات JSON الثابتة}';

    protected $description = 'يستورد داتا ENR الثابتة (محطات، عربيات، قطارات) من ملفات JSON للـ DB';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        $this->importStations("$path/stations.json");
        $this->importCoaches("$path/coaches.json");   // بيعمل الدرجات + أنواع العربيات + الكراسي
        $this->importTrains("$path/trainwithclasses.json");

        $this->newLine();
        $this->info('✅ خلص الاستيراد.');
        $this->table(['الجدول', 'العدد'], [
            ['المحطات', Station::count()],
            ['الدرجات', CoachClass::count()],
            ['أنواع العربيات', CoachType::count()],
            ['الكراسي', CoachTypeSeat::count()],
            ['القطارات', Train::count()],
        ]);

        return self::SUCCESS;
    }

    private function load(string $file): array
    {
        if (! is_file($file)) {
            $this->error("ملف مش موجود: $file");
            return [];
        }
        return json_decode(file_get_contents($file), true) ?: [];
    }

    private function importStations(string $file): void
    {
        $rows = collect($this->load($file))
            ->map(fn ($s) => EnrNormalizer::station($s))
            ->filter(fn ($s) => $s['id'] !== '')
            ->map(fn ($s) => $s + ['created_at' => now(), 'updated_at' => now()])
            ->values()->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            Station::upsert($chunk, ['id'], ['code', 'name_ar', 'name_en', 'has_gates', 'active', 'updated_at']);
        }
        $this->info('المحطات: '.count($rows));
    }

    private function importCoaches(string $file): void
    {
        $raw = $this->load($file);
        $classes = [];
        $types = [];
        $seats = [];

        foreach ($raw as $c) {
            // الدرجة (جوّة كل عربية)
            if (isset($c['coachClass']) && is_array($c['coachClass'])) {
                $cc = EnrNormalizer::coachClass($c['coachClass']);
                if ($cc['id'] !== '') {
                    $classes[$cc['id']] = $cc + ['created_at' => now(), 'updated_at' => now()];
                }
            }

            $normalized = EnrNormalizer::coach($c);
            $loc = EnrNormalizer::loc($c);
            $id = $normalized['id'];
            if ($id === '') {
                continue;
            }

            $types[$id] = [
                'id'             => $id,
                'reg_id'         => $c['regId'] ?? null,
                'name_ar'        => $loc['ar'],
                'name_en'        => $loc['en'],
                'coach_class_id' => $normalized['class_id'],
                'type'           => $c['type'] ?? null,
                'seats_count'    => $normalized['seats_total'],
                'no_seats'       => ($c['params']['no_seats'] ?? '') === '1',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            foreach ($normalized['seats'] as $seat) {
                $seats[] = [
                    'coach_type_id' => $id,
                    'number'        => $seat['number'],
                    'x'             => $seat['x'],
                    'y'             => $seat['y'],
                    'row_index'     => $seat['row'],
                    'is_window'     => $seat['window'],
                ];
            }
        }

        DB::transaction(function () use ($classes, $types, $seats) {
            foreach (array_chunk(array_values($classes), 200) as $chunk) {
                CoachClass::upsert($chunk, ['id'], ['code', 'name_ar', 'name_en', 'label_ar', 'seqno', 'updated_at']);
            }
            foreach (array_chunk(array_values($types), 200) as $chunk) {
                CoachType::upsert($chunk, ['id'], ['reg_id', 'name_ar', 'name_en', 'coach_class_id', 'type', 'seats_count', 'no_seats', 'updated_at']);
            }
            // الكراسي: نمسح ونعيد (delete مش truncate — truncate بيكسر الـ transaction في MySQL)
            CoachTypeSeat::query()->delete();
            foreach (array_chunk($seats, 1000) as $chunk) {
                CoachTypeSeat::insert($chunk);
            }
        });

        $this->info('الدرجات: '.count($classes).' | أنواع العربيات: '.count($types).' | الكراسي: '.count($seats));
    }

    private function importTrains(string $file): void
    {
        $raw = $this->load($file);
        $count = 0;

        DB::transaction(function () use ($raw, &$count) {
            foreach ($raw as $t) {
                $number = (string) ($t['name'] ?? '');
                if ($number === '') {
                    continue;
                }
                $train = Train::updateOrCreate(['number' => $number]);
                $classIds = array_values(array_filter((array) ($t['coachClasses'] ?? [])));
                $train->coachClasses()->sync($classIds);
                $count++;
            }
        });

        $this->info('القطارات: '.$count);
    }
}
