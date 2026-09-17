<?php

namespace Tests\Unit;

use App\Support\Ar;
use PHPUnit\Framework\TestCase;

class ArTest extends TestCase
{
    public function test_digits_convert_both_ways(): void
    {
        $this->assertSame('٩٠٣', Ar::num('903'));
        $this->assertSame('903', Ar::west('٩٠٣'));
    }

    public function test_time_is_twelve_hour_arabic(): void
    {
        $this->assertSame('٦:٠٠ ص', Ar::time('2026-09-18T06:00:00+03:00'));
        $this->assertSame('٩:٣٠ م', Ar::time('2026-09-18T21:30:00+03:00'));
        $this->assertSame('—', Ar::time(null));
    }

    public function test_duration_and_money(): void
    {
        $this->assertSame('٣ س ٣٠ د', Ar::duration(210));
        $this->assertSame('٤٥ د', Ar::duration(45));
        $this->assertSame('٢ س', Ar::duration(120));
        $this->assertSame('١٧٠ جنيه', Ar::money(170.0));
        $this->assertSame('٦٥.٥٠ جنيه', Ar::money(65.5));
    }

    public function test_counting_follows_arabic_plural_rules(): void
    {
        $args = ['محطة واحدة', 'محطتين', 'محطات', 'محطة'];
        $this->assertSame('محطة واحدة', Ar::count(1, ...$args));
        $this->assertSame('محطتين', Ar::count(2, ...$args));
        $this->assertSame('٩ محطات', Ar::count(9, ...$args));
        $this->assertSame('١١ محطة', Ar::count(11, ...$args));
    }

    public function test_folding_makes_arabic_spelling_variants_match(): void
    {
        $this->assertSame(Ar::fold('القاهره'), Ar::fold('القاهرة'));     // ة مقابل ه
        $this->assertSame(Ar::fold('اسوان'), Ar::fold('أسوان'));          // همزة
        $this->assertSame(Ar::fold('سيدي جابر'), Ar::fold('سيدى جابر'));  // ى مقابل ي
        $this->assertSame(Ar::fold('طنطا'), Ar::fold('  طنطا  '));        // مسافات
        $this->assertSame('الاسكندريه', Ar::fold('الإسكندريّة'));         // تشكيل
    }

    public function test_date_is_spelled_out_to_survive_rtl(): void
    {
        $this->assertSame('الجمعة ١٨ سبتمبر ٢٠٢٦', Ar::date('2026-09-18'));
    }
}
