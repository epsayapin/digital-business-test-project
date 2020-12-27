<?php

namespace App\Providers;

use App\Services\AmoCRMService;
use Illuminate\Support\ServiceProvider;

class DigitalBusinessTestProjectProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->app->singleton("AmoCRMService", function(){
            return new AmoCRMService();
        });
    }
}
