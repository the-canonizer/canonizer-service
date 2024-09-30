<?php

namespace App\Providers;

use App\Models\v1\Timeline;
use App\Models\v1\Tree;
use App\Repository\Timeline\TimelineRepository;
use App\Repository\Topic\TopicRepository;
use App\Repository\Tree\TreeRepository;
use Illuminate\Support\ServiceProvider;

class CustomRepositoryFacadeProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        /**  Bind TreeRepository Class */
        $this->app->bind('TreeRepository', function () {
            return new TreeRepository(new Tree);
        });

        /**  Bind TopicRepository Class */
        $this->app->bind('TopicRepository', function () {
            return new TopicRepository(new Tree);
        });

        /**  Bind TimelineRepository Class */
        $this->app->bind('TimelineRepository', function () {
            return new TimelineRepository(new Timeline);
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
