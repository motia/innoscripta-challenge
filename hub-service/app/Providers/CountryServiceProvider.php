<?php

namespace App\Providers;

use App\Country\CountryRegistry;
use Illuminate\Support\ServiceProvider;

class CountryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CountryRegistry::class, function () {
            return new CountryRegistry();
        });
    }

    public function boot(): void
    {
        //
    }
}
