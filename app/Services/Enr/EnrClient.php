<?php

namespace App\Services\Enr;

use Illuminate\Support\Facades\Http;

/**
 * عميل ENR — نداء البحث (from → to لتاريخ معيّن).
 * الـ endpoint عام (من غير auth). كل النداءات تعدي من هنا عشان نتحكم
 * في الـ timeout / retry / rate-limit من مكان واحد.
 */
class EnrClient
{
    private const BASE = 'https://obs.enr.gov.eg/api/v1/tickets/search';

    public function search(string $fromId, string $toId, string $date): array
    {
        $response = Http::timeout(30)
            ->retry(2, 1500)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get(self::BASE, [
                'from'                 => $fromId,
                'to'                   => $toId,
                'transfers'            => 'false',
                'with_reservations'    => 'true',
                'without_reservations' => 'false',
                'skip_places_information' => 'false',
                'departureDate'        => $date,
                'searchMode'           => 'WEB',
                'project'              => 'enr',
            ]);

        if (! $response->successful()) {
            return [];
        }

        return $response->json() ?: [];
    }
}
