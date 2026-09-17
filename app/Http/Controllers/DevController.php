<?php

namespace App\Http\Controllers;

use App\Support\DevMode;
use Illuminate\Http\Request;

class DevController extends Controller
{
    /** بيتأكد من المفتاح وبيفتكره في المتصفح. */
    public function unlock(Request $request)
    {
        $key = trim((string) $request->input('key', ''));

        if (! DevMode::matches($key)) {
            return redirect()->to(route('home').'#dev')
                ->with('devError', 'المفتاح غلط.');
        }

        // كوكي طويل الأجل (سنة) — Laravel بيشفّره، فمحدش يقراه من المتصفح
        return redirect()->route('home')
            ->withCookie(cookie(DevMode::COOKIE, $key, 60 * 24 * 365, httpOnly: true))
            ->with('devNotice', 'وضع المطوّر اتفتح.');
    }

    public function lock()
    {
        return redirect()->route('home')
            ->withCookie(cookie()->forget(DevMode::COOKIE))
            ->with('devNotice', 'وضع المطوّر اتقفل.');
    }
}
