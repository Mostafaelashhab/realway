<?php

namespace Tests\Unit;

use App\Services\Enr\EnrNormalizer;
use PHPUnit\Framework\TestCase;

class EnrNormalizerTest extends TestCase
{
    /** رد ENR مصغّر: عربيتين من نفس الدرجة. */
    private function payload(array $places, array $extraParams = []): array
    {
        $coach = fn (string $name) => [
            'name'           => $name,
            'params'         => ['seats_count' => '88'] + $extraParams,
            'availableSeats' => ['id-1', 'id-2', 'id-3'],
            'cost'           => 6500,
            'coachClass'     => ['id' => '1210059', 'localizationMap' => ['ar' => 'ثالثة تهوية']],
            'places'         => $places,
        ];

        return [[
            'steps' => [[
                'fromId' => 1, 'toId' => 2, 'duration' => 230, 'totalDistance' => 208,
                'startingPrice' => 6500, 'availableSeats' => 6,
                'fromDate' => '2026-09-18T04:00:00+03:00', 'finishDate' => '2026-09-18T07:50:00+03:00',
                'route' => [['id' => '1'], ['id' => '9'], ['id' => '2']],
                'train' => [
                    'name'   => '163',
                    'fields' => [['key' => 'enr_train_description', 'params' => ['ar' => 'ثالثة تهوية']]],
                    'servicePoints' => [$coach('8'), $coach('10')],
                ],
            ]],
        ]];
    }

    private function seat(string $number, bool $available): array
    {
        return ['number' => $number, 'params' => ['kind' => 'seat'], 'available' => $available];
    }

    public function test_free_seats_come_from_places_with_their_numbers(): void
    {
        $places = [$this->seat('12', true), $this->seat('3', true), $this->seat('7', false)];

        $t = EnrNormalizer::trains($this->payload($places))[0];

        $this->assertSame('163', $t['number']);
        $this->assertSame('ثالثة تهوية', $t['name']);
        $this->assertSame(['1', '9', '2'], $t['route_ids']);
        $this->assertSame(65.0, $t['start_price']);

        $class = $t['classes'][0];
        $this->assertSame(65.0, $class['price']);
        $this->assertSame(4, $class['seats']);                       // عربيتين × ٢ كرسي فاضي
        $this->assertSame(['3', '12'], $class['coaches'][0]['seats']); // مرتّبة رقميًا
        $this->assertSame('8', $class['coaches'][0]['coach']);
    }

    public function test_seat_count_survives_an_empty_places_array(): void
    {
        // ENR أحيانًا بيرجّع places فاضية — الأرقام بتضيع بس العدد لأ
        $t = EnrNormalizer::trains($this->payload([], ['seatCount' => '11']))[0];

        $class = $t['classes'][0];
        $this->assertSame(22, $class['seats']);   // ١١ × عربيتين
        $this->assertSame([], $class['coaches']); // مفيش أرقام نعرضها
    }

    public function test_it_falls_back_to_available_seat_ids_when_seat_count_is_missing(): void
    {
        $t = EnrNormalizer::trains($this->payload([]))[0];

        $this->assertSame(6, $t['classes'][0]['seats']);   // ٣ ids × عربيتين
    }
}
