<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CampService;
use App\Services\AlgorithmService;
use App\Services\TopicService;
use App\Services\TreeService;

class CustomServicesFacadeProvider extends ServiceProvider
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
        /**  Bind CampService Class */
        $this->app->bind('CampService', function () {
            return new CampService();
        });

        /**  Bind AlgorithmService Class */
        $this->app->bind('AlgorithmService', function () {
            return new AlgorithmService();
        });

        /**  Bind TreeService Class */
        $this->app->bind('TreeService', function () {
            return new TreeService();
        });

         /**  Bind TopicService Class */
         $this->app->bind('TopicService', function () {
            return new TopicService();
        });
    }
}
