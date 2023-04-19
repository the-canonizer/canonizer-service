<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repository\Tree\TreeRepository;
use App\Repository\Timeline\TimelineRepository;
use App\Repository\Topic\TopicRepository;
use App\Model\v1\Tree;
use App\Model\v1\Timeline;
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
