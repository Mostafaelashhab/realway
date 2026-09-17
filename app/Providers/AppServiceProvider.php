<?php

namespace App\Providers;

use App\Support\DevMode;
use Illuminate\Support\Facades\View;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // كل الـ views محتاجة تعرف: نعرض بيانات الكراسي ولا نسكت عنها خالص
        View::composer('*', fn ($view) => $view->with([
            'dev'          => DevMode::active(),
            'devAvailable' => DevMode::configured(),
        ]));

        //
    }
}
