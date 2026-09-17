<?php

namespace App\Services\Enr;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * عميل ENR — نداء البحث (from → to لتاريخ معيّن).
 * الـ endpoint عام (من غير auth). كل النداءات تعدي من هنا عشان نتحكم
 * في الـ timeout / retry / rate-limit من مكان واحد.
 */
class EnrClient
{
    private const BASE = 'https://obs.enr.gov.eg/api/v1/tickets/search';

    /**
     * يرجّع null لو النداء نفسه فشل (شبكة/رفض/رد مش JSON).
     * ده مختلف عن [] اللي معناها "النداء نجح بس مفيش قطارات".
     */
    public function trySearch(string $fromId, string $toId, string $date): ?array
    {
        try {
            $response = Http::timeout(30)
                ->retry(2, 1500, throw: false)
                ->acceptJson()
                ->withHeaders([
                    // نفس اللي بيبعته موقع ENR نفسه
                    'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
                    'Accept-Language' => 'ar',
                    'Referer'         => 'https://obs.enr.gov.eg/o-city/obs/enr/railway/ar/booktickets',
                ])
                ->get(self::BASE, [
                    'from'                    => $fromId,
                    'to'                      => $toId,
                    'transfers'               => 'false',
                    'with_reservations'       => 'true',
                    'without_reservations'    => 'false',
                    'skip_places_information' => 'false',   // محتاجين places عشان أرقام الكراسي
                    'departureDate'           => $date,
                    'searchMode'              => 'WEB',
                    'project'                 => 'enr',
                ]);
        } catch (\Throwable $e) {
            Log::warning('ENR search failed', ['from' => $fromId, 'to' => $toId, 'date' => $date, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('ENR search rejected', ['from' => $fromId, 'to' => $toId, 'date' => $date, 'status' => $response->status()]);

            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    /** نفس النداء بس بيبلع الفشل — للحصاد اللي بيلف على آلاف الأزواج. */
    public function search(string $fromId, string $toId, string $date): array
    {
        return $this->trySearch($fromId, $toId, $date) ?? [];
    }
}
