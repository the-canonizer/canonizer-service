<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repository\Tree\TreeRepository;
use App\Model\v1\Tree;

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
    }
}
