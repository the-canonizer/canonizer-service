<?php

namespace App\Facades\Repositories;

use Illuminate\Support\Facades\Facade;

class TimelineRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'TimelineRepository';
    }
}
