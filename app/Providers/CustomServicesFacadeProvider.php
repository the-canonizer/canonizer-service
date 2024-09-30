<?php

namespace App\Providers;

use App\Services\AlgorithmService;
use App\Services\CampService;
use App\Services\TimelineService;
use App\Services\TopicService;
use App\Services\TreeService;
use Illuminate\Support\ServiceProvider;

class CustomServicesFacadeProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        /**  Bind CampService Class */
        $this->app->bind('CampService', function () {
            return new CampService;
        });

        /**  Bind AlgorithmService Class */
        $this->app->bind('AlgorithmService', function () {
            return new AlgorithmService;
        });

        /**  Bind TreeService Class */
        $this->app->bind('TreeService', function () {
            return new TreeService;
        });

        /**  Bind TopicService Class */
        $this->app->bind('TopicService', function () {
            return new TopicService;
        });

        /**  Bind TimelineService Class */
        $this->app->bind('TimelineService', function () {
            return new TimelineService;
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
