<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\DateTimeHelper;

class CustomHelpersFacadeProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        /**  Bind DateTimeHelper Class */
        $this->app->bind('DateTimeHelper', function () {
            return new DateTimeHelper();
        });
    }
}
