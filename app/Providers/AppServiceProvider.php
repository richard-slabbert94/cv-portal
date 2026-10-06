<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // PIN guessing: a handful of tries per minute, and a hard ceiling per day.
        RateLimiter::for('pin', fn (Request $request) => [
            Limit::perMinute(5)->by('pin-min:'.$request->ip()),
            Limit::perDay(30)->by('pin-day:'.$request->ip()),
        ]);

        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by('admin:'.$request->ip()),
        ]);
    }
}
