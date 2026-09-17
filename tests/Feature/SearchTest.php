<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Support\DevMode;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_both_search_forms(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('بالمحطات')
            ->assertSee('برقم القطر');
    }

    public function test_home_defaults_to_the_first_date_enr_serves(): void
    {
        // ENR بيرجّع فاضي للنهاردة واللي فات، فالافتراضي لازم يكون بكرة
        $this->get('/')
            ->assertOk()
            ->assertSee('value="'.now()->addDay()->toDateString().'"', false)
            // النهاردة مسموح في المنتقي (بيوري الجدول)، بس الافتراضي بكرة عشان الكراسي تبان
            ->assertSee('min="'.now()->toDateString().'"', false);
    }

    public function test_past_dates_are_refused_before_any_enr_call(): void
    {
        Http::fake();   // لو حصل نداء التست هيفشل تحت
        $this->seedStoredTrip();

        $this->get('/search?'.http_build_query(['from' => 'القاهرة', 'to' => 'الإسكندرية', 'date' => now()->subDay()->toDateString()]))
            ->assertOk()->assertSee('عدّى');

        $this->get('/train?number=903&date='.now()->subDay()->toDateString())
            ->assertOk()->assertSee('عدّى');

        Http::assertNothingSent();
    }

    public function test_today_falls_back_to_the_weekly_schedule_without_availability(): void
    {
        Http::fake(['obs.enr.gov.eg/*' => Http::response($this->enrPayload())]);
        $this->seedStoredTrip();

        $page = $this->asDev()->get('/search?'.$this->cairoToAlex(now()->toDateString()))->assertOk();

        $page->assertSee('قطر ١٦٣')                    // القطر ظاهر
            ->assertSee('٦٥ جنيه')                     // السعر حقيقي
            ->assertSee('الجدول الأسبوعي')              // الزائر عارف إن دي مواعيد متكررة
            ->assertSee('بتبان قبل الرحلة بيوم')         // ومعرفه إمتى يشوف الكراسي
            ->assertSee('التوفّر مش متاح للنهاردة')      // مفيش أي رقم كراسي
            ->assertDontSee('<summary>', false)     // أسماء الكلاسات موجودة في الـ CSS، فبنختبر الماركب
            ->assertDontSee('class="seat"', false);

        // لازم نكون سألنا عن نفس اليوم الأسبوع الجاي، مش النهاردة
        Http::assertSent(fn ($r) => str_contains($r->url(), 'departureDate='.now()->addWeek()->toDateString()));
    }

    public function test_a_future_date_keeps_the_live_seat_numbers(): void
    {
        Http::fake(['obs.enr.gov.eg/*' => Http::response($this->enrPayload())]);
        $this->seedStoredTrip();

        $this->asDev()->get('/search?'.$this->cairoToAlex(now()->addWeek()->toDateString()))
            ->assertOk()
            ->assertSee('فاضي كرسيين')
            ->assertSee('عربية ٨')
            ->assertDontSee('الجدول الأسبوعي');
    }

    public function test_it_falls_back_to_the_harvested_timetable_when_enr_is_down(): void
    {
        Http::fake(['obs.enr.gov.eg/*' => Http::response('', 503)]);
        $this->seedStoredTrip();

        $this->asDev()->get('/search?'.$this->cairoToAlex(now()->addWeek()->toDateString()))
            ->assertOk()
            ->assertSee('قطر ٩٠٣')
            ->assertSee('القاهرة')
            ->assertSee('طنطا')                      // المحطات من trip_stops
            ->assertSee('مش راد دلوقتي')             // الزائر عارف إن دي مواعيد محفوظة
            ->assertSee('أولى مكيفة')                // الدرجات من الداتا الثابتة
            ->assertSee('التوفّر مش متاح')
            ->assertDontSee('class="seat"', false);
    }

    public function test_a_real_empty_answer_is_not_overridden_by_stored_data(): void
    {
        // ENR ردّ وقال "مفيش قطارات" — ده رد صحيح، مينفعش نغطي عليه بجدول قديم
        Http::fake(['obs.enr.gov.eg/*' => Http::response([])]);
        $this->seedStoredTrip();

        $this->get('/search?'.$this->cairoToAlex(now()->addWeek()->toDateString()))
            ->assertOk()
            ->assertSee('مفيش قطارات على الخط ده')
            ->assertDontSee('مش راد دلوقتي')
            ->assertDontSee('قطر ٩٠٣');
    }

    public function test_stations_are_searched_by_name_in_any_spelling(): void
    {
        Http::fake(['obs.enr.gov.eg/*' => Http::response($this->enrPayload())]);
        $this->seedStoredTrip();
        $date = now()->addWeek()->toDateString();

        foreach (['القاهرة', 'القاهره', ' القاهرة '] as $typed) {
            $this->get('/search?'.http_build_query(['from' => $typed, 'to' => 'الإسكندرية', 'date' => $date]))
                ->assertOk()->assertSee('قطر ١٦٣');
        }

        // اللينكات القديمة اللي فيها id لازم تفضل شغالة
        $this->get('/search?'.http_build_query(['from' => 'CAI', 'to' => 'ALX', 'date' => $date]))
            ->assertOk()->assertSee('قطر ١٦٣');
    }

    public function test_a_misspelled_station_gets_suggestions_for_the_field_that_failed(): void
    {
        Http::fake();
        $this->seedStoredTrip();

        $page = $this->get('/search?'.http_build_query(['from' => 'القاهرة', 'to' => 'طنتا']))->assertOk();

        $page->assertSee('مالقيناش محطة اسمها «طنتا»')
            ->assertSee('طنطا')
            // الاقتراح بيصلّح الحقل اللي غلط (to)، ومبيلمسش اللي صح (from)
            ->assertSee('to='.urlencode('طنطا'), false)
            ->assertSee('from='.urlencode('القاهرة'), false);

        Http::assertNothingSent();
    }

    public function test_seats_are_never_mentioned_without_the_dev_key(): void
    {
        Http::fake(['obs.enr.gov.eg/*' => Http::response($this->enrPayload())]);
        $this->seedStoredTrip();

        $this->get('/search?'.$this->cairoToAlex(now()->addWeek()->toDateString()))
            ->assertOk()
            ->assertSee('قطر ١٦٣')            // المواعيد والأسعار زي ما هي
            ->assertSee('٦٥ جنيه')
            ->assertDontSee('كرسي')            // ولا كلمة عن الكراسي
            ->assertDontSee('كراسي')
            ->assertDontSee('التوفّر')
            ->assertDontSee('عربية')
            ->assertDontSee('class="seat"', false);

        $this->get('/')->assertOk()->assertDontSee('كراسي');
    }

    public function test_a_wrong_key_neither_unlocks_nor_is_remembered(): void
    {
        config(['egtrain.dev_key' => 'right-key']);

        $this->post('/dev', ['key' => 'wrong-key'])
            ->assertRedirect(route('home').'#dev')
            ->assertSessionHas('devError')
            ->assertCookieMissing(DevMode::COOKIE);
    }

    public function test_the_right_key_unlocks_and_the_browser_remembers_it(): void
    {
        config(['egtrain.dev_key' => 'right-key']);
        Http::fake(['obs.enr.gov.eg/*' => Http::response($this->enrPayload())]);
        $this->seedStoredTrip();

        $this->post('/dev', ['key' => 'right-key'])
            ->assertRedirect(route('home'))
            ->assertCookie(DevMode::COOKIE, 'right-key');

        // نفس الكوكي بيفتح الكراسي في أي طلب بعد كده من غير ما يسأل تاني
        $this->withCookie(DevMode::COOKIE, 'right-key')
            ->get('/search?'.$this->cairoToAlex(now()->addWeek()->toDateString()))
            ->assertOk()
            ->assertSee('فاضي كرسيين')
            ->assertSee('عربية ٨');
    }

    public function test_an_unset_key_keeps_the_feature_closed_for_everyone(): void
    {
        config(['egtrain.dev_key' => null]);
        Http::fake(['obs.enr.gov.eg/*' => Http::response($this->enrPayload())]);
        $this->seedStoredTrip();

        // حتى لو حد بعت كوكي فاضية أو أي قيمة
        $this->withCookie(DevMode::COOKIE, '')
            ->get('/search?'.$this->cairoToAlex(now()->addWeek()->toDateString()))
            ->assertOk()->assertDontSee('كرسي');

        $this->get('/')->assertOk()->assertDontSee('كن مطوّر');
    }

    /** طلب بمفتاح مطوّر صالح. */
    private function asDev(): self
    {
        config(['egtrain.dev_key' => 'test-key']);

        return $this->withCookie(DevMode::COOKIE, 'test-key');
    }

    private function cairoToAlex(string $date): string
    {
        return http_build_query(['from' => 'القاهرة', 'to' => 'الإسكندرية', 'date' => $date]);
    }

    /** رحلة محفوظة لنفس يوم الأسبوع اللي هنسأل عنه. */
    private function seedStoredTrip(): void
    {
        foreach ([['CAI', 'القاهرة'], ['TNT', 'طنطا'], ['ALX', 'الإسكندرية']] as [$id, $name]) {
            \App\Models\Station::create(['id' => $id, 'code' => $id, 'name_ar' => $name, 'active' => true]);
        }

        $class = \App\Models\CoachClass::create(['id' => 'c1', 'code' => 'AC1', 'name_ar' => 'أولى مكيفة', 'label_ar' => 'أولى مكيفة']);
        $train = \App\Models\Train::create(['number' => '903', 'type' => 'PLD']);
        $train->coachClasses()->attach($class->id);

        $trip = \App\Models\Trip::create([
            'train_number' => '903', 'train_type' => 'خاص',
            'from_id' => 'CAI', 'to_id' => 'ALX',
            'weekday' => now()->addWeek()->dayOfWeek,
            'depart_at' => '2026-07-24 06:00:00', 'arrive_at' => '2026-07-24 09:30:00',
            'duration_min' => 210, 'distance_km' => 208, 'start_price' => 170,
            'stops_count' => 3, 'harvested_at' => now()->subMonths(2),
        ]);
        $trip->stops()->createMany([
            ['station_id' => 'CAI', 'sequence' => 0],
            ['station_id' => 'TNT', 'sequence' => 1],
            ['station_id' => 'ALX', 'sequence' => 2],
        ]);
    }

    /** رد ENR مصغّر بعربية فيها كرسيين فاضيين. */
    private function enrPayload(): array
    {
        return [[
            'steps' => [[
                'fromId' => 'a', 'toId' => 'b', 'duration' => 230, 'totalDistance' => 208,
                'startingPrice' => 6500,
                'fromDate' => now()->addWeek()->toDateString().'T04:00:00+03:00',
                'finishDate' => now()->addWeek()->toDateString().'T07:50:00+03:00',
                'route' => [['id' => 'a'], ['id' => 'b']],
                'train' => [
                    'name' => '163',
                    'fields' => [['key' => 'enr_train_description', 'params' => ['ar' => 'ثالثة تهوية']]],
                    'servicePoints' => [[
                        'name' => '8', 'cost' => 6500, 'params' => ['seatCount' => '2'],
                        'coachClass' => ['id' => '1210059', 'localizationMap' => ['ar' => 'ثالثة تهوية']],
                        'places' => [
                            ['number' => '12', 'params' => ['kind' => 'seat'], 'available' => true],
                            ['number' => '3', 'params' => ['kind' => 'seat'], 'available' => true],
                            ['number' => '7', 'params' => ['kind' => 'seat'], 'available' => false],
                        ],
                    ]],
                ],
            ]],
        ]];
    }

    public function test_search_without_stations_shows_a_message(): void
    {
        $this->get('/search')->assertOk()->assertSee('اكتب محطة القيام');
    }

    public function test_unknown_train_number_shows_a_message(): void
    {
        $this->get('/train?number=99999')->assertOk()->assertSee('مش موجود في الداتا');
    }

    public function test_arabic_digits_in_the_number_field_are_accepted(): void
    {
        // الرقم الهندي بيتحوّل قبل اللوكاب، فالرسالة بترجع بنفس الرقم
        $this->get('/train?number=٩٩٩٩٩')->assertOk()->assertSee('٩٩٩٩٩');
    }
}
