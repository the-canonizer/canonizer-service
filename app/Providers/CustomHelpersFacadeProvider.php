<?php

namespace App\Providers;

use App\Helpers\DateTimeHelper;
use App\Helpers\UtilHelper;
use Illuminate\Support\ServiceProvider;

class CustomHelpersFacadeProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        /**  Bind DateTimeHelper Class */
        $this->app->bind('DateTimeHelper', function () {
            return new DateTimeHelper;
        });

        /** Bind UtileHelper Class */
        $this->app->bind('UtilHelper', function () {
            return new UtilHelper;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
