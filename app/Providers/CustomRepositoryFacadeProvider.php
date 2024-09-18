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
        /**  Bind TreeRepository Class */
        $this->app->bind('TreeRepository', function () {
            return new TreeRepository(new Tree());
        });

        /**  Bind TopicRepository Class */
        $this->app->bind('TopicRepository', function () {
            return new TopicRepository(new Tree());
        });

        /**  Bind TimelineRepository Class */
        $this->app->bind('TimelineRepository', function () {
            return new TimelineRepository(new Timeline());
        });
    }
}
