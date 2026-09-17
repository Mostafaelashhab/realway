<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_search_without_stations_shows_a_message(): void
    {
        $this->get('/search')->assertOk()->assertSee('اختار محطة القيام');
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
